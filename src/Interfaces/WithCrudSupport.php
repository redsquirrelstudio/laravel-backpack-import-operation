<?php

namespace RedSquirrelStudio\LaravelBackpackImportOperation\Interfaces;

interface WithCrudSupport
{
    /**
     * @param int $import_log_id
     * @param string|null $validator
     */
    public function __construct(int $import_log_id, ?string $validator = null);
}
