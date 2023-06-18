<?php

namespace Onetech\ExportDocs\Exports\Sheets;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DatabaseERSheet implements WithTitle, FromView, WithStyles
{
    public function __construct()
    {

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
        return view('docs::exports.databases.er');
    }
}
