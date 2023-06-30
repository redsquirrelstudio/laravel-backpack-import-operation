<?php

use RedSquirrelStudio\LaravelBackpackImportOperation\Columns;
use RedSquirrelStudio\LaravelBackpackImportOperation\Models\ImportLog;

/**
 * Configurations for ImportOperation.
 */

return [
    'import_log_model' => ImportLog::class,

    //Filesystem disk to store uploaded import files
    'disk' => env('FILESYSTEM_DISK', 'local'),

    //Path to store uploaded import files
    'path' => env('BACKPACK_IMPORT_FILE_PATH', 'imports'),

    // Aliases for import column types to be used in operation setup
    'column_aliases' => [
        'array' => Columns\ArrayColumn::class,
        'boolean' => Columns\BooleanColumn::class,
        'date' => Columns\DateColumn::class,
        'number' => Columns\NumberColumn::class,
        'text' => Columns\TextColumn::class,
    ]
];
