<?php

namespace Onetech\ExportDocs\Exports\Sheets;

use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class AfterSheetDatabaseSheet
{
    const BG = '666795';

    const COLOR_FONT = 'ffffff';

    const COLOR_BORDER = '000000';

    private array $databases;

    public function __construct($databases)
    {
        $this->databases = $databases;
    }

    public function __invoke(AfterSheet $event)
    {
        $cellDb = 6;
        $event->sheet->getStyle('A1:A4')->applyFromArray(
            [
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'color' => ['argb' => self::BG],
                ],
                'font' => [
                    'color' => ['argb' => self::COLOR_FONT],
                ],
            ]
        );

        $event->sheet->getStyle('A1:J4')->applyFromArray(
            [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['argb' => self::COLOR_BORDER],
                    ],
                ],
            ]
        );

        $event->sheet->getStyle("A$cellDb:J$cellDb")->applyFromArray(
            [
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
            ]
        );

        $endBorder = count($this->databases) + $cellDb;
        $cellDbCenter = $cellDb + 1;

        $event->sheet->getStyle("D$cellDbCenter:D{$endBorder}")->applyFromArray(
            [
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ],
            ]
        );

        $event->sheet->getStyle("E$cellDbCenter:E{$endBorder}")->applyFromArray(
            [
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ],
            ]
        );

        $event->sheet->getStyle("F$cellDbCenter:F{$endBorder}")->applyFromArray(
            [
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ],
            ]
        );

        $event->sheet->getStyle("A$cellDb:J{$endBorder}")->applyFromArray(
            [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['argb' => self::COLOR_BORDER],
                    ],
                ],
            ]
        );

        $cellIndex = $endBorder + 6;
        $event->sheet->getStyle("A$cellIndex")->applyFromArray(
            [
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
            ]
        );

        $cellUnique = $cellIndex + 7;
        $event->sheet->getStyle("A$cellUnique")->applyFromArray(
            [
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
            ]
        );

        $cellForeignKey = $cellUnique + 7;
        $event->sheet->getStyle("A$cellForeignKey")->applyFromArray(
            [
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
            ]
        );

        // end table
        $endTable = $cellForeignKey + 1;
        $event->sheet->getStyle("A$endTable:D$endTable")->applyFromArray(
            [
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'color' => ['argb' => 'C39DF8'],
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['argb' => self::COLOR_BORDER],
                    ],
                ],
            ]
        );
    }
}
