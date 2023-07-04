<?php

namespace Onetech\ExportDocs\Controllers\Api;

use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Onetech\ExportDocs\Controllers\BaseController;
use Onetech\ExportDocs\Exports\ApiExport;
use Onetech\ExportDocs\Exports\DatabaseExport;

class ExportDocController extends BaseController
{
    /**
     * Step constructor.
     */
    public function __construct()
    {
    }
}
