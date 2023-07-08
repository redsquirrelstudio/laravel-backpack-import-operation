<?php

namespace RedSquirrelStudio\LaravelBackpackImportOperation\Exceptions;

use Exception;
use Throwable;
class PrimaryKeyNotFoundException extends Exception
{
    public function __construct(string $class = '', string $message = "", int $code = 0, ?Throwable $previous = null)
    {
        $message = __('import-operation::import.primary_key_not_found', [
            'model' => $class
        ]);
        parent::__construct($message, $code, $previous);
    }
}
