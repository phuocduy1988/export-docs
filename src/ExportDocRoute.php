<?php

namespace Onetech\ExportDocs;

use Illuminate\Support\Facades\Route;

class ExportDocRoute
{
    public static function api()
    {
        Route::group(
            ['prefix' => 'export-docs', 'controller' => '\\Onetech\\ExportDocs\Controllers\\Api\\ExportDocController'],
            function () {
                Route::post('export-database', 'exportDatabase');
                Route::post('export-api', 'exportApi');

            },
        );
    }
}
