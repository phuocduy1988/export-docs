<?php

namespace Onetech\ExportDocs\Exports\Sheets;

use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Exception;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\SheetView;

class AfterSheetApiAllSheet
{
    const BG = '666795';

    const COLOR_FONT = 'ffffff';

    const COLOR_BORDER = '000000';

    public function __construct(private readonly array $apis)
    {
    }

    /**
     * @throws Exception
     */
    public function __invoke(AfterSheet $event): void
    {
        $start = 5;
        $end = count($this->apis) + $start;
        $event->sheet->getStyle("B{$start}:G{$end}")
            ->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['argb' => self::COLOR_BORDER],
                    ],
                ],
            ]);
        $event->sheet->getStyle('B5:G5')
            ->applyFromArray([
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'color' => ['argb' => self::BG],
                ],
                'font' => [
                    'color' => ['argb' => self::COLOR_FONT],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ],
            ]);
        $event->sheet->getStyle("B{$start}:B{$end}")
            ->applyFromArray([
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_RIGHT,
                ],
            ]);

        $event->sheet->getStyle("E{$start}:E{$end}")
            ->applyFromArray([
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ],
            ]);

        $event->sheet->getStyle("G{$start}:G{$end}")
            ->applyFromArray([
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ],
            ]);

        $event->getSheet()->getSheetView()->setView(SheetView::SHEETVIEW_PAGE_BREAK_PREVIEW);
        $event->getSheet()->getPageSetup()->setFitToPage(true);
        $event->getSheet()->getPageSetup()->setFitToWidth(1);
    }
}
