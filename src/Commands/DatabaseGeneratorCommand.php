<?php

namespace Onetech\ExportDocs\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Onetech\ExportDocs\Enums\ExportDocEnum;
use Onetech\ExportDocs\Exports\DatabaseExport;

class DatabaseGeneratorCommand extends Command
{
    protected $signature = 'docs:database {--filename=}';

    public function handle()
    {
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

        foreach ($tables as $table) {
            $bar->advance();
            $tableName = $table->{'Tables_in_' . $database};
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
