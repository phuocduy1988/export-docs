<?php

namespace Onetech\ExportDocs\Enums;

enum ExportDocEnum: string
{
    case HEADER = 'header';
    case FORM_DATA = 'multipart/form-data';
    case FILE = 'file';
    case NUMBER = 'number';
    case STRING = 'string';
    case BOOLEAN = 'boolean';
    case EMAIL = 'email';
    case PHONE = 'phone';
    case APP_PATH = 'app/';
    case EXPORT_PATH = 'export/';
    case DATABASE_PATH = 'database/';
    case API_PATH = 'api/';
}
