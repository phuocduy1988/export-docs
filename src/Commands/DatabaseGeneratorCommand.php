<?php

namespace Onetech\ExportDocs\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Onetech\ExportDocs\Enums\ExportDocEnum;
use Onetech\ExportDocs\Exports\DatabaseExport;
use Onetech\ExportDocs\Services\OpenAIService;

class DatabaseGeneratorCommand extends Command
{
    protected $signature = 'docs:database {--filename=}';

    public function handle()
    {
        $this->info(moduleDateNow());

        $sheets = [];
        $db = DB::connection();
        $tables = $db->select('SHOW TABLES');
        $database = config('database.connections.mysql.database');

        //Remove ignored tables
        $tableNotIgnore = [];
        foreach ($tables as $table) {
            $tableName = $table->{'Tables_in_' . $database};
            if (!in_array($tableName, config('export-docs.database_ignore'))) {
                $tableNotIgnore[] = $table;
            }
        }

        $tables = $tableNotIgnore;

        $bar = $this->output->createProgressBar(count($tables));

        moduleLogInfo('START TRANSLATION');
        moduleLogInfo(moduleDateNow());
        $translateColumns = [];
        $translatedColumns = [];
        foreach ($tables as $index => $table) {
            $bar->advance();
            $tableName = $table->{'Tables_in_' . $database};
            $describeTables = $db->select("SHOW FULL COLUMNS FROM $tableName");
            $dataColumns = [];
            foreach ($describeTables as $column) {
                $field = $column->Field;
                $field = str_replace("\x08", "", $field);
                $columnName = Str::ucfirst(Str::replace('_', ' ', $field));
                $dataColumns[] = $columnName;
                $type = $column->Type;
                $null = $column->Null;
                $key = $column->Key;
                $default = $column->Default;
                $extra = $column->Extra;
                $comment = $column->Comment;
                $sheets[$tableName][] = [
                    'field_name' => '',
                    'field' => $field,
                    'type' => $type,
                    'null' => $null,
                    'key' => $key,
                    'default' => $default,
                    'extra' => $extra,
                    'comment' => $comment,
                ];
            }
            $translateColumns[$index][] = implode(',', $dataColumns);

            //Update fields translations
            if (Str::length(json_encode($translateColumns)) > 700) {
                $this->info(moduleDateNow());
                $res = OpenAIService::translateString(json_encode($translateColumns));
                $translateColumns = [];
                $res = json_decode($res, true);
                if (!$res) {
                    $this->info('Data translation error');
                    continue;
                }
                $translatedColumns = array_merge($translatedColumns, $res);
                $this->info(moduleDateNow());
            }
        }

        if (count($translateColumns) > 0) {
            $this->info('LAST TRANSLATION');
            $res = OpenAIService::translateString(json_encode($translateColumns));
            $res = json_decode($res, true);
            if (!$res) {
                $this->info('Data translation error');
            }
            $translatedColumns = array_merge($translatedColumns, $res);
            $this->info(moduleDateNow());
        }

        foreach ($sheets as $key => $sheet) {
            $index = array_search($key, array_keys($sheets));
            $columns = $translatedColumns[$index];
            if (count($columns) === 1) {
                $columns = explode(',', $columns[0]);
            }
            foreach ($columns as $key2 => $column) {
                $sheets[$key][$key2]['field_name'] = $column;
            }
        }

        moduleLogInfo('END TRANSLATION');
        moduleLogInfo(moduleDateNow());

        //Generate diagram
        Artisan::call('docs:diagram');

        $fileName = $this->getFileName($database);
        $exporter = new DatabaseExport($sheets);
        $exporter->store($fileName, 'local');
        $this->info(PHP_EOL);
        $this->info('Wrote docs:database to:::' . storage_path('app/' . $fileName));
    }

    private function getFileName($database): bool|array|string
    {
        $fileName = $this->option('filename');

        if ($fileName) {
            if (!Str::contains($fileName, '.xlsx')) {
                $fileName = $fileName . '.xlsx';
            }

            return ExportDocEnum::EXPORT_PATH->value . ExportDocEnum::DATABASE_PATH->value . $fileName;
        } else {
            return ExportDocEnum::EXPORT_PATH->value . ExportDocEnum::DATABASE_PATH->value . $database . moduleDateNow('YmdHi') . '.xlsx';
        }
    }
}
