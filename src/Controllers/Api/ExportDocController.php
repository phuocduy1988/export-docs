<?php

namespace Onetech\ExportDocs\Controllers\Api;

use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Onetech\ExportDocs\Controllers\BaseController;
use Onetech\ExportDocs\Exports\DatabaseExport;

class ExportDocController extends BaseController
{
    /**
     * Pattern constructor.
     */
    public function __construct()
    {
    }

    public function exportDatabase(Request $request)
    {
        try {
            set_time_limit(0);
            $sheets = [];
            $db = DB::connection();
            $tables = $db->select('SHOW TABLES');
            $database = config('database.connections.mysql.database');
            $ignoreTables = [
                'failed_jobs',
                'generators',
                'migrations',
                'model_has_permissions',
                'jobs',
                'job_batches',
            ];
            foreach ($tables as $table) {
                $tableName = $table->{'Tables_in_' . $database};
                if (in_array($tableName, $ignoreTables)) {
                    continue;
                }
                $describeTables = $db->select("SHOW FULL COLUMNS FROM $tableName");
                foreach ($describeTables as $column) {
                    $field = $column->Field;
                    $type = $column->Type;
                    $null = $column->Null;
                    $key = $column->Key;
                    $default = $column->Default;
                    $extra = $column->Extra;
                    $comment = $column->Comment;
                    $sheets[$tableName][] = [
                        'field' => $field,
                        'type' => $type,
                        'null' => $null,
                        'key' => $key,
                        'default' => $default,
                        'extra' => $extra,
                        'comment' => $comment,
                    ];
                }
            }

            $fileName = $database . dateNow('YmdHi') . '.xlsx';
            $res = (new DatabaseExport($sheets))->store($fileName, 'local');
            if (!$res) {
                $this->jsonErrorMessage(trans('errors.unexpected_error'));
            }

            return $this->jsonData([
                'filename' => $fileName,
            ]);
        } catch (\Exception $e) {
            $this->writeLogException($e);

            return $this->jsonError($e);
        }
    }

    public function exportApi(Request $request)
    {
        try {
            set_time_limit(0);
            $url = $request->get('url');
            $cacheKey = 'GENERATE_API'. md5($url);
            if (Cache::has($cacheKey)) {
                $dataPostman = Cache::get($cacheKey);
            } else {
                $dataPostman = file_get_contents($url);
                Cache::set($cacheKey, $dataPostman, 111111);
            }
            $jsonPostman = json_decode($dataPostman, true);
            if (!isset($jsonPostman['collection']) || !isset($jsonPostman['collection']['item'])) {
                return $this->jsonError(trans('errors.unexpected_error'));
            }
            $this->fetchRequestApis($requestApi, $jsonPostman['collection']['item']);
            dd($requestApi);

            // return (new ApiExport($sheets))->store($request->get('file_name', 'Api') . '.xlsx', 'local');
            return $this->jsonMessage(trans('messages.success'));
        } catch (\Exception $e) {
            $this->writeLogException($e);

            return $this->jsonError($e);
        }
    }

    public function fetchRequestApis(&$requestApi, $item) {
        foreach ($item as $value) {
            if (isset($value['item'])) {
                $this->fetchRequestApis($requestApi, $value['item']);
            } else {
                $requestApi[] = $value;
            }

        }
    }
}
