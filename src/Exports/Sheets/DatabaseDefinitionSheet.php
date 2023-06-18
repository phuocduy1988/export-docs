<?php

namespace Onetech\ExportDocs\Exports\Sheets;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DatabaseDefinitionSheet implements WithTitle, FromView, WithStyles
{
    public function __construct()
    {

    }

    public function styles(Worksheet $sheet)
    {
        $cells = 'A1:AE50';
        $sheet->mergeCells($cells);
        $sheet->getStyle($cells)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle($cells)->getAlignment()->setHorizontal(Alignment::VERTICAL_CENTER);
        $sheet->setShowGridlines(false);
        $sheet->getStyle($cells)->applyFromArray([
            'font' => [
                'size' => 48,
            ],
        ]);
    }

    /**
     * @return string
     */
    public function title(): string
    {
        return '表紙';
    }

    public function view(): View
    {
        return view('docs::exports.databases.definition');
    }
}
