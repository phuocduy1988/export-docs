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
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DatabaseSheet implements WithTitle, FromView, WithStyles, ShouldAutoSize, WithColumnWidths, WithEvents
{
    const BG = '666795';

    const COLOR_FONT = 'ffffff';

    const COLOR_BORDER = '000000';

    private string $title;

    private array $databases;

    public function __construct(string $title, array $sheet)
    {
        $this->title = $title;
        $this->databases = $sheet;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 18,
            'B' => 15,
            'D' => 8,
            'F' => 8,
            'G' => 18,
            'H' => 20,
            'I' => 18,
            'J' => 35,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => new AfterSheetDatabaseSheet($this->getDatabase()),
        ];
    }

    /**
     * @return string
     */
    public function title(): string
    {
        return $this->title;
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->mergeCells('B1:J1');
        $sheet->mergeCells('B2:J2');
        $sheet->mergeCells('B3:J3');
        $sheet->mergeCells('B4:J4');
        $sheet->getRowDimension(3)->setRowHeight(60);
        $sheet->getRowDimension(4)->setRowHeight(60);
        $sheet->getStyle('A3')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle('A4')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->setShowGridlines(false);
    }

    public function view(): View
    {
        return view('docs::exports.databases.database', [
            'databases' => $this->getDatabase(),
            'title' => $this->title,
        ]);
    }

    public function getDatabase()
    {
        return $this->databases;
    }
}
