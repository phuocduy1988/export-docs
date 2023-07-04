<?php

namespace Onetech\ExportDocs\Exports;

use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Foundation\Bus\PendingDispatch;
use Illuminate\Http\Client\Response as HttpResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Onetech\ExportDocs\Exports\Sheets\ApiDefinitionSheet;
use Onetech\ExportDocs\Exports\Sheets\ApiSheet;
use Symfony\Component\HttpFoundation\Response;

class ApiExport implements WithMultipleSheets
{
    use Exportable;

    const FILE_EXTENSION = 'xlsx';

    const API_FOLDER = 'export/api';

    const ERROR_FOLDER = 'Errors';

    private array $errorCodeReports = [
        Response::HTTP_METHOD_NOT_ALLOWED,
        Response::HTTP_SERVICE_UNAVAILABLE,
    ];

    private string $filename;

    protected Collection $apis;

    private Collection $envs;

    private \Faker\Generator $faker;

    private array $responses;

    private array $requestSheetApi;

    private int $requestSheetIndex;

    private array $apiErrors = [];

    private array $sheets = [];

    public function __construct(array $sheets, array $envs)
    {
        info('Initializing....');
        $this->faker = \Faker\Factory::create();
        $this->envs = Collection::make($envs);
        $this->apis = Collection::make($sheets);
        $this->sheets = $this->getSheetData();
    }

    public function sheets(): array
    {
        $apiList = new ApiDefinitionSheet($this->getSavePath(), $this->sheets);
        $fileSheets[] = $apiList;

        foreach ($this->sheets as $index => $sheet) {
            $fileSheets[] = new ApiSheet($apiList, $index, $sheet);
        }

        // export errors
        $this->createReportExcelApiErrors();

        return $fileSheets;
    }

    private function getSheetData()
    {
        $this->errorCodeReports = $this->getMergeStatusCode();

        return $this->apis->map(function ($api, $index) {
            $this->requestSheetApi = $api;
            $this->requestSheetIndex = $index;

            $headers = $this->getHeaders($api);
            $requestHeaders = [];

            foreach ($headers as $values) {
                foreach ($values as $key => $value) {
                    if ($key == 'token') {
                        $requestHeaders['Authorization'] = "Bearer $value";
                        $requestHeaders['Content-Type'] = 'application/json';
                        $requestHeaders['Accept'] = 'application/json;charset=UTF-8';
                        $requestHeaders['Charset'] = 'utf-8';
                    }
                }
            }

            $name = data_get($api, 'name');
            $body = data_get($api, 'request.body');
            $method = data_get($api, 'request.method');
            $url = $this->getUrl(data_get($api, 'request.url.raw'));
            $path = $this->getUrlPath(data_get($api, 'request.url.raw'));
            $params = $this->getParams(data_get($api, 'request.body'));
            $responses = $this->getRequestResponse(data_get($api, 'request'), $requestHeaders);
            $updatedAt = dateNow('Y年m月d日');

            $outputs = [];
            if (is_array($responses)) {
                foreach ($responses as $values) {
                    if (isset($values['data']) && is_array($values['data'])) {
                        foreach ($values['data'] as $key => $value) {
                            $outputs[] = [
                                'key' => $key,
                                'type' => Str::upper(gettype($value)),
                            ];
                        }
                    }
                }
            }

            $inputs = [];
            if ($params && is_array($params)) {
                foreach ($params as $key => $value) {
                    if (is_numeric($key)) {
                        foreach ($value as $k => $v) {
                            $inputs[$k] = [
                                'key' => $k,
                                'type' => Str::upper(gettype($v)),
                            ];
                        }
                    } else {
                        $inputs[] = [
                            'key' => $key,
                            'type' => Str::upper(gettype($value)),
                        ];
                    }
                }
            }

            return [
                'name' => $name,
                'url' => $url,
                'method' => $method,
                'path' => $path,
                'header' => $headers ?? [],
                'authorize' => $requestHeaders,
                'body' => $body ?? [],
                'params' => $params ?? [],
                'inputs' => $inputs,
                'responses' => $responses ?? [],
                'outputs' => $outputs ?? [],
                'updated_at' => $updatedAt,
            ];
        })->all();
    }

    private function getUrl(string $url): string
    {
        $newApi = $url;
        foreach ($this->envs as $value) {
            $key = data_get($value, 'key');
            $value = data_get($value, 'value');
            $newApi = str_replace("{{{$key}}}", $value, $newApi);
        }

        return $newApi;
    }

    public function getUrlPath($url): array|string
    {
        $newUrl = $url;
        foreach ($this->envs as $value) {
            $key = data_get($value, 'key');
            $newUrl = str_replace("{{{$key}}}", '', $newUrl);
        }

        return $newUrl;
    }

    private function getHeaders($api): array
    {
        $auths = data_get($api, 'request.auth', []);
        $headers = [];

        foreach ($auths as $key => $values) {
            if ($key != 'type') {
                foreach ($values as $value) {
                    $headers[] = $value;
                }
            }
        }

        $headers = array_merge(data_get($api, 'request.header', []), $headers);

        foreach ($headers as &$header) {
            if (Str::startsWith($header['value'], '{{')) {
                $header['data'] = $header['value'];
                $header['value'] = str_replace('{{', '', $header['value']);
                $header['value'] = str_replace('}}', '', $header['value']);
                $header['value'] = str_replace('-', '_', $header['value']);
                $header['value'] = Str::upper($header['value']);
                $header['token'] = $this->getUrl($header['data']);
            }

            $header['type'] = Str::upper($header['type']);
        }

        return $headers;
    }

    public function getParams($body)
    {
        $mode = data_get($body, 'mode');
        $params = data_get($body, $mode);

        return match ($mode) {
            'raw' => arrayCastRecursive(json_decode($params)),
            'formdata' => $params,
            default => [],
        };
    }

    private function getRequestResponse(array $request, $requestHeader)
    {
        $url = $this->getUrl(data_get($request, 'url.raw'));
        $method = data_get($request, 'method');
        $mode = data_get($request, 'body.mode');
        $params = data_get($request, "body.$mode");

        return match ($mode) {
            'raw' => $this->makeRequestWithRawParams($url, $method, $params, $requestHeader),
            'formdata' => $this->makeRequestWithFormDataParams($url, $method, $params, $requestHeader),
            default => [],
        };
    }

    private function makeRequestWithRawParams(string $url, string $method, mixed $params, $headers)
    {
        $formParams = arrayCastRecursive(json_decode($params));

        return $this->makeFormRequest($url, $method, $formParams, $headers);
    }

    private function makeRequestWithFormDataParams(string $url, string $method, mixed $params, $headers)
    {
        $formParams = [];
        if (is_array($params)) {
            foreach ($params as $param) {
                $formParams[data_get($param, 'key')] = data_get($param, 'value');
            }
        }

        return $this->makeFormRequest($url, $method, $formParams, $headers);
    }

    public function makeFormRequest(string $url, string $method, mixed $formParams, $headers)
    {
        //        if (Str::contains($url, 'login')) {
        //            return;
        //        }

        if (Str::contains($url, 'logout')) {
            return;
        }

        $url = $this->formatUrlRequest($url);

        try {
            $errorParams = $this->makeErrorRequest($formParams);

            $responseSuccess = match (Str::upper($method)) {
                'POST' => $this->makeHttpPostRequest($url, $formParams, $headers),
                'GET' => $this->makeHttpGetRequest($url, $formParams, $headers),
            };

            $responseError = match (Str::upper($method)) {
                'POST' => $this->makeHttpPostRequest($url, $errorParams, $headers),
                'GET' => $this->makeHttpGetRequest($url, $errorParams, $headers),
            };

            if ($responseSuccess->status() == Response::HTTP_OK) {
                $requestResponse[] = [
                    'url' => $url,
                    'code' => $responseSuccess->status(),
                    'index' => $this->requestSheetIndex,
                    'id' => data_get($this->requestSheetApi, 'id'),
                    'name' => data_get($this->requestSheetApi, 'name'),
                    'text' => Str::upper(Str::snake(data_get(Response::$statusTexts, $responseSuccess->status()))),
                    'data' => json_decode($responseSuccess->body(), true) ?? [],
                ];
            } else {
                $requestResponse[] = [
                    'url' => $url,
                    'code' => Response::HTTP_OK,
                    'index' => $this->requestSheetIndex,
                    'id' => data_get($this->requestSheetApi, 'id'),
                    'name' => data_get($this->requestSheetApi, 'name'),
                    'text' => Str::upper(Str::snake(data_get(Response::$statusTexts, Response::HTTP_OK))),
                    'data' => [],
                ];
            }

            $requestResponse[] = [
                'url' => $url,
                'code' => $responseError->status(),
                'index' => $this->requestSheetIndex,
                'id' => data_get($this->requestSheetApi, 'id'),
                'name' => data_get($this->requestSheetApi, 'name'),
                'text' => Str::upper(Str::snake(data_get(Response::$statusTexts, $responseError->status()))),
                'data' => json_decode($responseError->body(), true) ?? [],
            ];

            // create api
            if (in_array($responseSuccess->status(), $this->errorCodeReports)) {
                $this->apiErrors[$this->requestSheetIndex] = $requestResponse;
            }

            return $requestResponse;
        } catch (\Exception $e) {
            write_log_exception($e);

            return [];
        }
    }

    private function makeSuccessRequest(mixed $formParams): array
    {
        $successParams = [];
        $this->getSuccessRecursiveFormParams($formParams, $successParams);

        return $successParams;
    }

    private function makeErrorRequest(mixed $formParams): array
    {
        $errorParams = [];
        $this->getErrorRecursiveFormParams($formParams, $errorParams);

        return $errorParams;
    }

    public function getSuccessRecursiveFormParams(&$params, &$requestData): void
    {
        if (is_array($params)) {
            foreach ($params as $key => $value) {
                $valueType = gettype($value);
                if ($valueType == 'string') {
                    $requestData[$key] = $this->faker->text(10);
                } elseif ($valueType == 'boolean') {
                    $requestData[$key] = (bool) $this->faker->randomElement([0, 1]);
                } elseif ($valueType == 'integer') {
                    $requestData[$key] = $this->faker->numberBetween(1, 1000);
                } elseif ($valueType == 'double') {
                    $requestData[$key] = $this->faker->randomFloat(10);
                } elseif ($valueType == 'array') {
                    $this->getSuccessRecursiveFormParams($value, $requestData[$key]);
                }
            }
        }
    }

    public function getErrorRecursiveFormParams(&$params, &$requestData): void
    {
        if (is_array($params)) {
            foreach ($params as $key => $value) {
                $valueType = gettype($value);
                if ($valueType == 'array') {
                    $this->getErrorRecursiveFormParams($value, $requestData[$key]);
                } else {
                    $requestData[$key] = null;
                }
            }
        }
    }

    private function makeHttpPostRequest(string $url, $formParams, $headers): PromiseInterface|HttpResponse
    {
        info($url);
        info($formParams);

        return Http::accept('application/json')
            ->timeout(5)
            ->withHeaders($headers)
            ->withOptions([
                'decode_content' => false,
            ])
            ->post($url, $formParams);
    }

    private function makeHttpGetRequest(string $url, $formParams, $headers): PromiseInterface|HttpResponse
    {
        return Http::accept('application/json')
            ->timeout(5)
            ->withHeaders($headers)
            ->get($url, $formParams);
    }

    private function formatUrlRequest(string $url): array|string|null
    {
        $url = preg_replace("/(\{[^{}]*token[^{}]*\})/i", $this->faker->text(10), $url);

        return preg_replace("/(\{[^{}]*id[^{}]*\})/i", $this->faker->numberBetween(1, 100), $url);
    }

    private function createReportExcelApiErrors()
    {
        if (count($this->apiErrors) > 0) {
            info('Create error report');
            $response = new ApiErrorExport($this->getSavePath(), $this->sheets, $this->apiErrors);
            $savePath = self::ERROR_FOLDER . DIRECTORY_SEPARATOR . $this->getApiErrorFileName();
            $response->store($savePath, 'local');
        }
    }

    private function getMergeStatusCode(): array
    {
        $statusCodes = array_keys(Response::$statusTexts);
        $serverErrorCodes = array_filter($statusCodes, fn($item) => $item >= Response::HTTP_INTERNAL_SERVER_ERROR);

        return array_merge($this->errorCodeReports, $serverErrorCodes);
    }

    private function getApiErrorFileName(): string
    {
        return $this->filename . '_APIErrorReport.xlsx';
    }

    public function setFileName(string $filename)
    {
        $this->filename = $filename;
    }

    public function save(string $disk = 'local'): PendingDispatch|bool
    {
        if (empty($this->filename)) {
            $this->filename = $this->getDefaultFileName() . '.' . self::FILE_EXTENSION;
        }

        return $this->store($this->getSavePath(), $disk);
    }

    public function getSavePath(): string
    {
        $filename = preg_replace("/\s/", '', $this->filename);
        if (!Str::endsWith($filename, self::FILE_EXTENSION)) {
            $filename = $filename . '.' . self::FILE_EXTENSION;
        }

        return self::API_FOLDER . DIRECTORY_SEPARATOR . $filename;
    }

    public function getFileName(): string
    {
        return $this->filename;
    }

    private function getDefaultFileName(): string
    {
        return 'Api';
    }
}
