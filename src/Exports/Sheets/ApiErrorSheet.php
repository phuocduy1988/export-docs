<?php

namespace Onetech\ExportDocs\Exports\Sheets;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Symfony\Component\HttpFoundation\Response;

class ApiErrorSheet implements WithTitle, FromView, WithStyles, WithColumnWidths, ShouldAutoSize
{
    const BACKGROUND_COLOR = '666795';

    const COLOR_FONT = 'ffffff';

    const COLOR_BORDER = '000000';

    public function __construct(
        private readonly string $refFileName,
        private readonly array $sheetData,
    )
    {
    }

    public function view(): View
    {
        return view('docs::exports.apis.error', [
            'data' => $this->getApiErrors(),
            'refFile' => $this->refFileName,
        ]);
    }

    public function getApiErrors(): array
    {
        $errorResponses = [];
        foreach ($this->sheetData as $apiResponses) {
            foreach ($apiResponses as $apiResponse) {
                if ($apiResponse['code'] >= Response::HTTP_INTERNAL_SERVER_ERROR) {
                    $errorResponses[] = $apiResponse;
                }
            }
        }

        return $errorResponses;
    }

    public function columnWidths(): array
    {
        return [
            'B' => 40,
            'D' => 35,
            'E' => 60,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheetRows = count($this->sheetData) + 1;
        $sheet->getStyle("E2:E{$sheetRows}")->getAlignment()->setWrapText(true);
        $sheet->getStyle('A1:E1')->applyFromArray($this->getHeaderFormat());
    }

    public function title(): string
    {
        return 'Report Errors';
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
                'color' => ['argb' => self::BACKGROUND_COLOR],
            ],
            'font' => [
                'color' => ['argb' => self::COLOR_FONT],
            ],
        ];
    }
}
