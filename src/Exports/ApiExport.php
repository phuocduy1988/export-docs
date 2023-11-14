<?php

namespace Onetech\ExportDocs\Exports;

use Faker\Generator;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Illuminate\Foundation\Bus\PendingDispatch;
use Onetech\ExportDocs\Exports\Sheets\ApiSheet;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Onetech\ExportDocs\Exports\Sheets\ApiDefinitionSheet;
use Onetech\ExportDocs\Utils\Request;

class ApiExport implements WithMultipleSheets
{
    use Exportable;

    const FILE_EXTENSION = "xlsx";
    const API_FOLDER = "export/api";

    private string $filename;

    private $bar;

    public Generator $faker;

    private Collection $apiData;

    public string $requestToken = "";

    private $definitionSheet;

    public function __construct(array $apis, array $envs, $bar)
    {
        info("Initializing....");
        $this->bar = $bar;
        $this->replacementEnvVariables($apis, $envs);
    }

    public function sheets(): array
    {
        $this->initializeFaker();
        $this->requestGetToken();

        info("Create sheets");
        $fileSheets[] = $this->getDefinitionSheet();
        foreach ($this->apiData as $index => $apiDatum) {
            $headers = data_get($apiDatum, "request.header");
            foreach ($headers as $headerIdx => $value) {
                if(data_get($value, "key") == "Authorization") {
                    data_set($apiDatum, "request.header.$headerIdx.value", "Bearer $this->requestToken");
                }
            }
            $apiSheet = new ApiSheet($index, $apiDatum, $this, $this->faker);
            $this->bar->advance();
            $fileSheets[] = $apiSheet;
        }
        return $fileSheets;
    }

    public function initializeFaker(): void
    {
        $this->faker = \Faker\Factory::create();
    }

    public function replacementEnvVariables(array $apis, array $envs): void
    {
        $this->apiData =  collect($apis)->map(fn($item) => $this->replacementEnvironmentVariable($item, $envs));
    }

    private function replacementEnvironmentVariable($replacements, $envs): array|string|null
    {
        if(is_string($replacements)) {
            foreach ($envs as $value) {
                $key = data_get($value, 'key');
                $value = data_get($value, 'value');
                if(Str::contains($value, 'token')) {
                    $replacements = str_replace("{{", "", $value);
                    $replacements = str_replace("}}", "", $replacements);
                    $replacements = Str::upper(Str::snake($replacements));
                } else {
                    $replacements =  str_replace("{{{$key}}}", $value, $replacements);
                }
            }
            return $replacements;
        }
        if(is_array($replacements)) {
            $newReplacements = [];
            foreach ($replacements as $key => $value) {
                $newReplacements[$key] = $this->replacementEnvironmentVariable($value, $envs);
            }

            return $newReplacements;
        }

        return $replacements;
    }

    public function getDefinitionSheet(): ApiDefinitionSheet
    {
        if(!$this->definitionSheet) {
            $this->definitionSheet = new ApiDefinitionSheet($this->getSavePath(), $this->apiData->all());
        }

        return $this->definitionSheet;
    }

    private function requestGetToken(): void
    {
        foreach ($this->apiData as $apiDatum) {
            $urlRaw = data_get($apiDatum, "request.url.raw");

            if(Str::contains($urlRaw, "login")) {
                $params = Request::getParams($apiDatum);
                $headers = Request::getHeaders($apiDatum);

                $headers['Accept'] = 'application/json';
                if(isset($headers['Authorization'])) {
                    unset($headers['Authorization']);
                }

                $response = Request::post($urlRaw, $params, $headers);

                $jsonResponse = json_decode($response->body());

                $tokenFields = ['access_token', 'token'];
                foreach ($tokenFields as $tokenField) {
                    $token = data_get($jsonResponse, "data.$tokenField");
                    if($token) {
                        $this->requestToken = $token;
                        break;
                    }
                }
                break;
            }
        }
    }

    public function setFileName(string $filename)
    {
        $this->filename = $filename;
    }

    public function save(string $disk = 'local'): PendingDispatch|bool
    {
        if(empty($this->filename)) {
            $this->filename = $this->getDefaultFileName() . "." . self::FILE_EXTENSION;
        }

        return $this->store($this->getSavePath(), $disk);
    }

    public function getSavePath(): string
    {
        $filename = preg_replace("/\s/", "", $this->filename);
        if(!Str::endsWith($filename, self::FILE_EXTENSION)) {
            $filename = $filename . "." . self::FILE_EXTENSION;
        }

        return self::API_FOLDER . DIRECTORY_SEPARATOR . $filename;
    }

    public function getFileName(): string
    {
        return $this->filename;
    }

    private function getDefaultFileName(): string
    {
        return "Api";
    }
}
