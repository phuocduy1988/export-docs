<?php

namespace Onetech\ExportDocs\Exports\Sheets;

use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class AfterSheetApiAllSheet
{
    const BG = '666795';

    const COLOR_FONT = 'ffffff';

    const COLOR_BORDER = '000000';

    private array $api;

    public function __construct($databases)
    {
        $this->api = $databases;
    }

    public function __invoke(AfterSheet $event)
    {
        $start = 1;
        $end = 0;
        foreach ($this->api as $api) {
            $end += count($api) + 1;
            $event->sheet->styleCells("A{$start}", [
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'color' => ['argb' => self::BG],
                ],
                'font' => [
                    'color' => ['argb' => self::COLOR_FONT],
                ],
            ]);
            $event->sheet->styleCells("A{$start}:A{$end}", [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['argb' => self::COLOR_BORDER],
                    ],
                ],
            ]);
            $end += 1;
            $start = $end + 1;
        }
    }
}
