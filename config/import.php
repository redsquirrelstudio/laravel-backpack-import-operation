<?php

use RedSquirrelStudio\LaravelBackpackImportOperation\Columns;

/**
 * Configurations for ImportOperation.
 */

return [
    //Filesystem disk to store uploaded import files
    'disk' => env('FILESYSTEM_DISK', 'local'),

    //Path to store uploaded import files
    'path' => env('BACKPACK_IMPORT_FILE_PATH', 'imports'),

    // Aliases for import column types to be used in operation setup
    'columns' => [
        'text' => Columns\TextColumn::class,
        'number' => Columns\NumberColumn::class,
    ]
];
