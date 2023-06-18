<?php

namespace Onetech\ExportDocs\Exports\Sheets;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ApiAllSheet implements WithTitle, FromView, WithStyles, WithEvents, ShouldAutoSize
{
    protected array $apis;

    public function __construct(array $apis)
    {
        $this->apis = $apis;
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->setShowGridlines(false);
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
        return 'All API';
    }

    public function view(): View
    {
        return view('exports.apis.all', [
            'apis' => $this->apis,
        ]);
    }
}
