<?php

namespace Onetech\ExportDocs\Exports\Sheets;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use Onetech\ExportDocs\Enums\APIDefinitionSheetEnum;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ApiDefinitionSheet implements WithTitle, FromView, WithStyles, WithEvents, ShouldAutoSize, WithColumnWidths
{
    protected array $apis;

    private string $filename;

    public function __construct(private readonly string $filePath, array $apis)
    {
        $this->apis = $apis;
        $splitFileName = explode('/', $this->filePath);
        $this->filename = data_get($splitFileName, 1);
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->setShowGridlines(false);
    }

    public function columnWidths(): array
    {
        return [
            'A' => 3,
            'B' => 6,
            'C' => 12,
            'D' => 60,
            'E' => 12,
            'G' => 30,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => new AfterSheetApiAllSheet($this->apis),
        ];
    }

    /**
     * @return string
     */
    public function title(): string
    {
        return 'API List';
    }

    public function view(): View
    {
        return view('docs::exports.apis.all', [
            'apis' => $this->apis,
            'filename' => $this->filename,
            'start_idx' => APIDefinitionSheetEnum::START_ROW->value - 1,
        ]);
    }
}
