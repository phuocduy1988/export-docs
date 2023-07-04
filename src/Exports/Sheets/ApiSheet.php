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
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Symfony\Component\HttpFoundation\Response;

class ApiSheet implements WithTitle, FromView, ShouldAutoSize, WithColumnWidths, WithEvents, WithStyles
{
    const BG = '666795';

    private null|string $id;

    private null|string $name;

    private null|string $screenID;

    private null|string $path;

    private null|string $method;

    private null|string $url;

    private array $headers = [];

    private array $inputs = [];

    private array $outputs = [];

    private array $responses = [];

    private array $errorResponses = [];

    private array $successResponses = [];

    public function __construct(
        private readonly ApiDefinitionSheet $definitionSheet,
        private readonly int $sheetIndex,
        private readonly array $sheetData
    ) {
        $this->id = formatSheetId($this->sheetIndex + 1);
        $this->name = data_get($this->sheetData, 'name');
        $this->method = data_get($this->sheetData, 'method');
        $this->screenID = data_get($this->sheetData, 'screen');
        $this->path = data_get($this->sheetData, 'path');
        $this->url = data_get($this->sheetData, 'url');

        $this->headers = data_get($this->sheetData, 'header', []);
        $this->inputs = data_get($this->sheetData, 'inputs', []);
        $this->outputs = data_get($this->sheetData, 'outputs', []);
        $this->responses = data_get($this->sheetData, 'responses', []);
        $this->parseResponse();
    }

    public function columnWidths(): array
    {
        return [
            'A' => 3,
            'B' => 12,
            'C' => 10,
            'D' => 50,
            'E' => 10,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => new AfterSheetApiSheet($this->getViewData()),
        ];
    }

    /**
     * @return string
     */
    public function title(): string
    {
        return formatSheetName($this->sheetData['name']);
    }

    public function view(): View
    {
        return view('docs::exports.apis.api', $this->getViewData());
    }

    public function getViewData(): array
    {
        return [
            'apis' => $this->sheetData,
            'id' => $this->id,
            'name' => $this->name,
            'screenID' => $this->screenID,
            'method' => $this->method,
            'path' => $this->path,
            'url' => $this->url,
            'headers' => $this->headers,
            'inputs' => $this->inputs,
            'outputs' => $this->outputs,
            'responses' => $this->responses,
            'successResponses' => $this->successResponses,
            'errorResponses' => $this->errorResponses,
            'refName' => $this->definitionSheet->title(),
            'refIdx' => $this->sheetIndex + APIDefinitionSheetEnum::START_ROW->value,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('C1:C4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
    }

    private function parseResponse()
    {
        foreach ($this->sheetData['responses'] as $response) {
            if ($response['code'] == Response::HTTP_OK) {
                $this->successResponses = $response;
            } elseif (
                in_array($response['code'], [
                    Response::HTTP_NOT_FOUND,
                    Response::HTTP_UNAUTHORIZED,
                    Response::HTTP_FORBIDDEN,
                    Response::HTTP_UNPROCESSABLE_ENTITY,
                ])
            ) {
                $this->errorResponses = $response;
            }
        }
    }
}
