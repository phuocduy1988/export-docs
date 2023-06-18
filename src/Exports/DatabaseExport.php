<?php

namespace Onetech\ExportDocs\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Onetech\ExportDocs\Exports\Sheets\DatabaseDefinitionSheet;
use Onetech\ExportDocs\Exports\Sheets\DatabaseERSheet;
use Onetech\ExportDocs\Exports\Sheets\DatabaseSheet;

class DatabaseExport implements WithMultipleSheets
{
    use Exportable;

    protected $sheets;

    public function __construct(array $sheets)
    {
        $this->sheets = $sheets;
    }

    /**
     * @return array
     */
    public function sheets(): array
    {
        $sheets = [];
        $sheets[] = new DatabaseDefinitionSheet();
        $sheets[] = new DatabaseERSheet();
        foreach ($this->sheets as $table => $sheet) {
            $sheets[] = new DatabaseSheet($table, $sheet);
        }

        return $sheets;
    }
}
