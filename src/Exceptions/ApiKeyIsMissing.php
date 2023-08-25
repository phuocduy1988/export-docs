<?php

declare(strict_types=1);

namespace Onetech\ExportDocs\Exceptions;

use InvalidArgumentException;

final class ApiKeyIsMissing extends InvalidArgumentException
{
    /**
     * Create a new exception instance.
     */
    public static function create(): self
    {
        return new self(
            'The OpenAI API Key is missing. Please publish the [export-docs.php] configuration file and set the [api_key].'
        );
    }
}
