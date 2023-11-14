<?php

namespace Onetech\ExportDocs\Utils;

use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\Response as HttpResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class Request
{
    public static function getParams($sheetData): array
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

    public static function getHeaders($sheetData): array
    {
        $auths = data_get($sheetData, 'request.auth', []);
        $headers = [];

        foreach ($auths as $key => $values) {
            if ($key != 'type') {
                foreach ($values as $value) {
                    $headers[] = $value;
                }
            }
        }

        $headers = array_merge(data_get($sheetData, 'request.header', []), $headers);

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

    private static function getFormDataParams($params): array
    {
        return is_array($params) ? $params : [];
    }
    private static function getRawParams($params): array
    {
        return arrayCastRecursive(json_decode($params)) ?? [];
    }
    private static function getGraphqlParams($params): array
    {
        return [];
    }

    public static function post(string $url, $formParams, $headers): PromiseInterface|HttpResponse
    {
        return Http::accept('application/json')
            ->timeout(5)
            ->withHeaders($headers)
            ->post($url, $formParams);
    }

    public static function get(string $url, $formParams, $headers): PromiseInterface|HttpResponse
    {
        return Http::accept('application/json')
            ->timeout(5)
            ->withHeaders($headers)
            ->get($url, $formParams);
    }
}
