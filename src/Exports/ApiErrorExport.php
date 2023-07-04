<?php

namespace Onetech\ExportDocs\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Onetech\ExportDocs\Exports\Sheets\ApiErrorSheet;

class ApiErrorExport implements WithMultipleSheets
{
    use Exportable;

    public function __construct(
        private readonly string $refFilename,
        private readonly array $routes,
        private readonly array $sheetData,
    )
    {
    }

    public function sheets(): array
    {
        return [
            new ApiErrorSheet($this->refFilename, $this->sheetData),
        ];
    }

    /**
     * @return array
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }
}
