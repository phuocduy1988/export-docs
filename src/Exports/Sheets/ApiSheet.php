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
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ApiSheet implements WithTitle, FromView, WithStyles, ShouldAutoSize, WithColumnWidths, WithEvents
{
    const BG = '666795';

    const COLOR_FONT = 'ffffff';

    const COLOR_BORDER = '000000';

    private string $name;

    private array $api;

    public function __construct(string $name, array $sheet)
    {
        $this->name = $name;
        $this->api = $sheet;
    }

    public function columnWidths(): array
    {
        return [
            'F' => 100,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => new AfterSheetApiSheet($this->api()),
        ];
    }

    /**
     * @return string
     */
    public function title(): string
    {
        return $this->name;
    }

    public function styles(Worksheet $sheet)
    {
        $endRows = count($this->api) + 1;
        $sheet->setShowGridlines(false);
        $sheet
            ->getStyle("F1:F{$endRows}")
            ->getAlignment()
            ->setWrapText(true);
    }

    public function view(): View
    {
        return view('exports.apis.index', [
            'apis' => $this->api(),
        ]);
    }

    public function api()
    {
        return $this->api;
    }
}
