<?php

namespace Onetech\ExportDocs\Exports\Sheets;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\WithDrawings;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

class DatabaseERSheet implements WithTitle, FromView, WithStyles, WithDrawings
{
    private $imagePath;
    public function __construct()
    {
        $this->imagePath = storage_path('app/export/database/db-diagram.png');
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->setShowGridlines(false);
    }

    /**
     * @return string
     */
    public function title(): string
    {
        return 'ER図';
    }

    public function view(): View
    {
        return view('docs::exports.databases.er', [
            'imagePath' => $this->imagePath
        ]);
    }

    public function drawings()
    {
        $drawing = new Drawing();
        $drawing->setName('Image');
        $drawing->setDescription('Image');
        $drawing->setPath($this->imagePath);
        $drawing->setCoordinates('A1');

        return [$drawing];
    }
}
