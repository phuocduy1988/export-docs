<?php

namespace Onetech\ExportDocs\Exports\Sheets;

use Faker\Generator;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use Matrix\Exception;
use Onetech\ExportDocs\Enums\APIDefinitionSheetEnum;
use Onetech\ExportDocs\Exports\ApiExport;
use Onetech\ExportDocs\Utils\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiSheet implements WithTitle, FromView, ShouldAutoSize, WithColumnWidths, WithEvents
{
    private array $ignoreUrls = [
        'logout',
        'change-password',
    ];

    private array $viewData;

    public function __construct(
        private readonly int                $sheetIndex,
        private readonly array              $sheetData,
        private readonly ApiExport          $apiExport,
        private readonly Generator          $faker,
    ) {
        $this->viewData = $this->getViewData();

        if(!$this->apiExport->faker) {
            $this->apiExport->initializeFaker();
        }
    }

    public function title(): string
    {
        return formatSheetName($this->sheetData['name']);
    }

    public function view(): View
    {
        return view('docs::exports.apis.api', $this->viewData);
    }

    public function getViewData(): array
    {
        $successResponse = $this->getSuccessResponse();
        $errorResponse = $this->getErrorResponse();

        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'screenId' => $this->getScreenId(),
            'method' => $this->getMethod(),
            'path' => $this->getPath(),
            'url' => $this->getUrl(),
            'headers' => $this->getHeaders(),
            'inputs' => $this->getInputs(),
            'outputs' => $this->getOutputs(data_get($successResponse, 'data')),
            'successResponses' => $successResponse,
            'errorResponses' => $errorResponse,
            'refName' => $this->apiExport->getDefinitionSheet()->title(),
            'refIdx' => $this->sheetIndex + APIDefinitionSheetEnum::START_ROW->value,
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 3,
            'B' => 12,
            'C' => 10,
            'D' => 50,
            'E' => 10,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => new AfterSheetApiSheet($this->viewData),
        ];
    }

    private function getId(): string
    {
        return formatSheetId($this->sheetData['name']);
    }
    private function getName(): string
    {
        return formatSheetName($this->sheetData['name']);
    }
    private function getScreenId(): string
    {
        $refName = $this->apiExport->getDefinitionSheet()->title();
        $refIdx = $this->sheetIndex + APIDefinitionSheetEnum::START_ROW->value;

        return "@INDEX('{{ $refName }}'!F:F, {{ $refIdx }})";
    }
    private function getMethod(): string
    {
        return data_get($this->sheetData, 'request.method');
    }
    private function getPath(): string
    {
        $host = data_get($this->sheetData, 'request.url.host.0');
        $rawUrl = data_get($this->sheetData, 'request.url.raw');
        $path = str_replace($host, '', $rawUrl);
        if(!$path) {
            if(!$path && Str::contains($host, 'graphql')) {
                $path = data_get($this->sheetData,'request.body.graphql.query');
                preg_match('/^(.*?)\{/', $path, $matches);
                $path = count($matches) ? rtrim($matches[1]) : '';
                $path = 'GraphQL: '. $path;
            }
            return $path;
        }
        return formatPath($path);
    }

    private function getUrl(): string|null
    {
        $rawUrl = $this->getRawUrl();
        return empty($this->getPath()) ? getUrlPath($rawUrl) : $this->getPath();
    }

    public function getRawUrl(): string
    {
        $url = data_get($this->sheetData, 'request.url.raw');
        return preg_replace('/(https?:\/\/[^\s\/]+)\/+/', '$1/', $url);
    }

    public function getHeaders(): array
    {
        $auths = data_get($this->sheetData, 'request.auth', []);
        $headers = [];

        foreach ($auths as $key => $values) {
            if ($key != 'type') {
                foreach ($values as $value) {
                    $headers[] = $value;
                }
            }
        }

        $headers = array_merge(data_get($this->sheetData, 'request.header', []), $headers);

        foreach ($headers as &$header) {
            if (Str::startsWith($header['value'], '{{')) {
                $header['data'] = $header['value'];
                $header['value'] = str_replace('{{', '', $header['value']);
                $header['value'] = str_replace('}}', '', $header['value']);
                $header['value'] = str_replace('-', '_', $header['value']);
                $header['value'] = Str::upper($header['value']);
            }

            $header['type'] = Str::upper($header['type']);
        }

        return $headers;
    }

    public function getInputs(): array
    {
        $requiredFields = $this->getRequireFields();
        $this->getFormInputParams($params);
        $inputs = [];
        if($params) {
            foreach ($params as $param) {
                $key = data_get($param, 'key');
                $value = data_get($param, 'type');
                $inputs[] = [
                    'key' => $key,
                    'value' => $value,
                    'type' => Str::upper(($value)),
                    'required' => in_array($key, $requiredFields)
                ];
            }
        }

        return $inputs;
    }

    public function getFormParams(): array
    {
        $formParams = $this->getParams($this->sheetData);
        $params = [];
        foreach ($formParams as $key => $value) {
            $params[] = [
                'key' => $key,
                'value' => $value,
                'description' => null,
                'type' => Str::upper(gettype($value)),
            ];
        }

        return $params;
    }

    public function getFormInputParams(&$params = [], $formParamChild = null, $keyParent = '')
    {
        $formParams = $formParamChild ?? $this->getParams($this->sheetData);
        foreach ($formParams as $key => $value) {
            $key = $keyParent ? $keyParent. '.'. $key : $key;
            $params[] = [
                'key' => $key,
                'value' => $value,
                'description' => null,
                'type' => Str::upper(gettype($value)),
            ];
            if(is_array($value)) {
                $this->getFormInputParams($params, $value, $key);
            }
        }
    }

    public function getOutputs($successResponse): array
    {
        if(isset($successResponse['data']['current_page']) && isset($successResponse['success'])) {
            return $this->getPaginationOutput(data_get($successResponse, 'data.data.0'));
        } else if(isset($successResponse['success']) && isset($successResponse['data']) && is_array($successResponse['data'])) {
            return $this->getArrayOutput(data_get($successResponse, 'data'));
        } else if(isset($successResponse['success']) && isset($successResponse['data'])) {
            return $this->getSuccessOutputs(data_get($successResponse, 'data'));
        } else if (isset($successResponse['success']) && $successResponse['message']) {
            return $this->getSuccessErrorOutput($successResponse);
        } else {
            return $this->getSuccessOutputs($successResponse);
        }
    }

    public function getPaginationOutput($paginationData): array
    {
        return $this->getSuccessOutputs($paginationData);
    }

    public function getArrayOutput($arrayData): array
    {
        return $this->getSuccessOutputs(data_get($arrayData, 0));
    }
    public function getSuccessOutputs($successResponse): array
    {
        if(is_array($successResponse)) {
            $successResponseData = $successResponse;
            $outputs = $this->getOutputKeys($successResponseData);

            $dataOutputs = [];
            foreach ($outputs as $output) {
                $value = data_get($successResponseData, $output);
                if(!empty($value)) {
                    $dataOutputs[] = [
                        'key' => $output,
                        'value' => $value,
                        'type' => Str::upper(gettype($value))
                    ];
                }
            }
            return $dataOutputs;
        } else {
            return [
                [
                    'key' => 'success',
                    'value' => true,
                    'type' => Str::upper(gettype(true))
                ],
                [
                    'key' => 'data',
                    'value' => $successResponse,
                    'type' => Str::upper(gettype($successResponse))
                ]
            ];
        }
    }

    public function getSuccessErrorOutput($successData): array
    {
        return [
            [
                'key' => 'success',
                'value' => data_get($successData, 'success'),
                'type' => Str::upper(gettype(data_get($successData, 'success')))
            ],
            [
                'key' => 'message',
                'value' => data_get($successData, 'message'),
                'type' => Str::upper(gettype(data_get($successData, 'message')))
            ],
        ];
    }

    public function getOutputKeys($inputData, $parentKey = "", $deep = 1): array
    {
        $keys = array_keys($inputData);
        foreach ($inputData as $parentKey => $values) {
            if (is_array($values)) {
                array_pop($keys);
                $nestedKeys = $this->getOutputKeys($values, $parentKey, $deep + 1);
                if($deep > 3 && is_numeric($parentKey)) {
                    break;
                }
                foreach ($nestedKeys as $index => $key) {
                    $nestedKeys[$index] = $parentKey . "." . $key;
                }
                $keys = array_merge($keys, $nestedKeys);
            }
        }

        return $keys;
    }

    public function getSuccessResponse(): array
    {
        $response = $this->tryGetSuccessResponse();
        $statusOk = Response::HTTP_OK;

        if(isset($response['code'])
            && $response['code'] == Response::HTTP_OK
            && $response['code'] < Response::HTTP_MULTIPLE_CHOICES)
        {
            return $response;
        } else {
            return [
                'code' => $statusOk,
                'text' => Response::$statusTexts[$statusOk],
                'data' => null,
            ];
        }
    }

    public function getErrorResponse(): array
    {
        $response = $this->tryGetErrorResponse();

        if(isset($response['code'])
            && $response['code'] >= Response::HTTP_MULTIPLE_CHOICES
            && $response['code'] <= Response::HTTP_INTERNAL_SERVER_ERROR
        ) {
            return $response;
        } else {
            return [
                'code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'text' => Response::$statusTexts[Response::HTTP_UNPROCESSABLE_ENTITY],
                'data' => null,
            ];
        }
    }

    public function tryGetSuccessResponse(): array
    {
        $params = collect($this->getFormParams())
            ->mapWithKeys(fn($item) => [
                data_get($item, 'key') => data_get($item, 'value'),
            ])->all();


        $url = $this->getValidRawUrl();
        $headers =  $this->getValidRequestHeaders();
        $queryString = parse_url($url, PHP_URL_QUERY);
        parse_str($queryString, $queryParams);

        $responses = $this->makeFormRequest($url, $this->getMethod(), array_merge($params, $queryParams), $headers);

        if(isset($responses['code']) && $responses['code'] >= Response::HTTP_OK && $responses['code'] < Response::HTTP_MULTIPLE_CHOICES) {
            return $responses;
        } else {
            return [];
        }
    }

    public function tryGetErrorResponse(): array
    {
        $formParams = $this->getFormParams();
        $params = collect($formParams)
            ->mapWithKeys(fn($item) => [
                data_get($item, 'key') => data_get($item, 'value'),
            ])
            ->all();

        $paramKeys = array_keys($params);

        $postParams[] = $this->createErrorParams($params);
        foreach ($paramKeys as $paramKey) {
            $items = $params;
            foreach ($params as $key => $value) {
                if($paramKey == $key && !empty($key)) {
                    $items[$key] = null;
                }
            }

            $postParams[] = $items;
        }

        $headers = $this->getValidRequestHeaders();
        foreach ($postParams as $postParam) {
            $response = $this->makeFormRequest($this->getValidRawUrl(), $this->getMethod(), $postParam, $headers);
            if(isset($response['code'])
                && $response['code'] >= Response::HTTP_MULTIPLE_CHOICES
                && $response['code'] < Response::HTTP_INTERNAL_SERVER_ERROR)
            {
                return $response;
            }
        }

        return [];
    }

    public function getRequireFields(): array
    {
        $tries = 3;
        $formParams = $this->getFormParams();
        $params = collect($formParams)
            ->mapWithKeys(fn($item) => [
                data_get($item, 'key') => data_get($item, 'value'),
            ])
            ->all();

        $params = $this->createErrorParams($params);
        $headers = $this->getValidRequestHeaders();

        $response = [];
        while($tries--) {
            $response = $this->makeFormRequest($this->getValidRawUrl(), $this->getMethod(), $params, $headers);
            if($response['code'] == Response::HTTP_UNPROCESSABLE_ENTITY) {
                break;
            }
        }

        $fields = data_get($response, 'data.errors', []);
        return array_unique(array_keys($fields));
    }

    public function getValidRawUrl(): string
    {
        $url = $this->getRawUrl();
        $url = preg_replace("/(\{[^{}]*token[^{}]*\})/i", md5($this->faker->regexify("[A-Za-z0-9]{20}")), $url);
        return preg_replace("/(\{[^{}]*id[^{}]*\})/i", $this->faker->numberBetween(1, 100), $url);
    }

    public function createErrorParams($params): array
    {
        $newParams = [];
        if(is_array($params)) {
            foreach ($params as $key => $value) {
                $newParams[$key] = null;
            }
        }
        return $newParams;
    }

    public function getParams($sheetData): array
    {
        $mode = data_get($sheetData, "request.body.mode");
        $params = data_get($sheetData, "request.body.$mode");

        return match ($mode) {
            'formdata' => self::getFormDataParams($params),
            'raw' => self::getRawParams($params),
            'graphql' => self::getGraphqlParams($params),
            default => [],
        };
    }

    private function getFormDataParams($params): array
    {
        $output = [];
        foreach ($params as $item) {
            $output[$item['key']] = $item['value'] ?? ($item['type'] ?? '');
        }
        return $output;
    }
    private function getRawParams($params): array
    {
        return arrayCastRecursive(json_decode($params)) ?? [];
    }
    private function getGraphqlParams($params): array
    {
        $str = $params['query'];
        preg_match('/\\(([^)]+)\\)/', $str, $matches);
        if(!count($matches)) {
            return [];
        }
        $str = $matches[1];
        // Thêm nháy kép vào các phần tử
        $str = preg_replace('/(\w+):/', '"$1":', $str);
        $str = "{{$str}}";
        return json_decode($str, true);
    }

    public function makeFormRequest(string $url, string $method, mixed $formParams, $headers): array
    {
        foreach ($this->ignoreUrls as $containsUrl) {
            if(Str::contains($url, $containsUrl)) {
                return [
                    'code'      => 200,
                    'text'      => 'OK',
                    'data'      => 'OK',
                    'params'    => [],
                ];
            }
        }

        try {
            $response =  match ($method) {
                'POST' => Request::post($url, $formParams, $headers),
                'GET' => Request::get($url, $formParams, $headers),
            };

            return [
                'code' => $response->status(),
                'text' => Str::upper(Str::snake(Response::$statusTexts[$response->status()])),
                'data' => json_decode($response->body(), true),
                'params' => $formParams,
            ];
        } catch (\Exception $e) {
            return [
                'code'      => null,
                'text'      => null,
                'data'      => null,
                'params'    => null,
            ];
        }
    }

    public function getValidRequestHeaders(): array
    {
        $validHeaders = [];
        $headers = data_get($this->sheetData, "request.header");

        foreach ($headers as $value) {
            $key = data_get($value, 'key');
            $validHeaders[$key] = data_get($value, 'value');
        }

        return array_merge($validHeaders, [
            'Content-Type' => "application/json"
        ]);
    }
}
