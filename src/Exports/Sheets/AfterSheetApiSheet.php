<?php

namespace Onetech\ExportDocs\Exports\Sheets;

use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Exception;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\SheetView;

class AfterSheetApiSheet
{
    const MIN_CELL_WIDTH = 10;

    const BG = '666795';

    const COLOR_FONT = 'ffffff';

    const COLOR_BORDER = '000000';

    const GAP_ROW = 2;

    public function __construct(private readonly array $sheetData)
    {
    }

    /**
     * @throws Exception
     */
    public function __invoke(AfterSheet $event)
    {
        // set format name
        $event->sheet->getStyle('B1:B3')->applyFromArray($this->getHeaderFormat());
        $event->sheet->getStyle('C1:E3')->applyFromArray($this->getRowFormat());
        // set format uri
        $event->sheet->getStyle('B6:E6')->applyFromArray($this->getHeaderFormat());
        $event->sheet->getStyle('B7:E7')->applyFromArray($this->getRowFormat());

        // set format header
        $event->sheet->getStyle('B9:E9')->applyFromArray($this->getHeaderFormat());
        $event->sheet->getStyle('B10:E10')->getFont()->setBold(true);

        $inputs = data_get($this->sheetData, 'inputs', []);
        $outputs = data_get($this->sheetData, 'outputs', []);
        $successResponse = data_get($this->sheetData, 'successResponses');
        $errorResponse = data_get($this->sheetData, 'errorResponses');
        $headers = data_get($this->sheetData, 'headers', []);
        $responses = [$successResponse, $errorResponse];

        $startHeader = 9;
        $startHeaderRow = $startHeader + 1;
        $endHeaderRow = count($headers) + $startHeaderRow;

        $event->sheet->getStyle("B{$startHeader}:E{$startHeader}")->applyFromArray($this->getHeaderFormat());
        $event->sheet->getStyle("B" . $startHeader + 1 . ":E" . $startHeader + 1)->getFont()->setBold(true);
        $event->sheet->getStyle("B{$startHeaderRow}:E{$endHeaderRow}")->applyFromArray($this->getRowFormat());

        // set format input
        $startInputHeader = $endHeaderRow + self::GAP_ROW;
        $startInputRow = $startInputHeader + 1;
        $endInputRow = count($inputs) + $startInputRow;

        $event->sheet->getStyle("B{$startInputHeader}:E{$startInputHeader}")->applyFromArray($this->getHeaderFormat());
        $event->sheet->getStyle("B" . $startInputHeader + 1 . ":E" . $startInputHeader + 1)->getFont()->setBold(true);
        $event->sheet->getStyle("B{$startInputRow}:E{$endInputRow}")->applyFromArray($this->getRowFormat());

        // set format output
        $startOutputHeader = $endInputRow + self::GAP_ROW;
        $startOutputRow = $startOutputHeader + 1;
        $endOutputRow = count($outputs) + $startOutputRow;

        $event->sheet->getStyle("B{$startOutputHeader}:E{$startOutputHeader}")->applyFromArray($this->getHeaderFormat());
        $event->sheet->getStyle("B" . $startOutputHeader + 1 . ":E" . $startOutputHeader + 1)->getFont()->setBold(true);
        $event->sheet->getStyle("B{$startOutputRow}:E{$endOutputRow}")->applyFromArray($this->getRowFormat());

        // set format response
        $startResponseHeader = $endOutputRow + self::GAP_ROW;
        $startResponseRow = $startResponseHeader + 1;
        $endResponseRow = count($responses) + $startResponseRow - 1;

        $event->sheet->getStyle("B{$startResponseHeader}:E{$startResponseHeader}")->applyFromArray($this->getHeaderFormat());
        $event->sheet->getStyle("B{$startResponseRow}:E{$endResponseRow}")->applyFromArray($this->getRowFormat());
        $event->sheet->getStyle("D{$startResponseRow}:E{$endResponseRow}")->getAlignment()->setWrapText(true);

        for ($row = $startResponseRow; $row <= $endResponseRow; $row++) {
            $responseIdx = $row - $startResponseRow;
            $responseTextInIndex = data_get($responses, "$responseIdx.text");
            $responseDataInIndex = data_get($responses, "$responseIdx.data");
            $responseDataLength = 0;
            $responseDataInIndex = json_encode($responseDataInIndex);
            $responseLength = strlen($responseDataInIndex) / 60;
            if ($responseLength > $responseDataLength) {
                $responseDataLength = $responseLength;
            }

            $height = 100;
            if ($responseDataLength > $height) {
                $height = $responseDataLength;
            }
            // set data height column
            $event->sheet->getDelegate()
                ->getRowDimension($row)
                ->setRowHeight($height);

            // set status code with
            if (strlen($responseTextInIndex) > 0 && strlen($responseTextInIndex) > self::MIN_CELL_WIDTH) {
                $event->sheet->getDelegate()
                    ->getColumnDimension('B')
                    ->setWidth(strlen($responseTextInIndex) + 5);

            }

            $event->getSheet()->getSheetView()->setView(SheetView::SHEETVIEW_PAGE_BREAK_PREVIEW);
            $event->getSheet()->getPageSetup()->setFitToPage(true);
            $event->getSheet()->getPageSetup()->setFitToWidth(1);
        }
    }

    public function getRowFormat(): array
    {
        return [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => self::COLOR_BORDER],
                ],
                'font' => [
                    'color' => ['argb' => self::COLOR_BORDER],
                ],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ];
    }

    private function getHeaderFormat(): array
    {
        return [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => self::COLOR_BORDER],
                ],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'color' => ['argb' => self::BG],
            ],
            'font' => [
                'color' => ['argb' => self::COLOR_FONT],
            ],
        ];
    }
}
