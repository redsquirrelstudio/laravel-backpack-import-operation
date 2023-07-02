<?php

namespace RedSquirrelStudio\LaravelBackpackImportOperation\Imports;

use Illuminate\Contracts\Queue\ShouldQueue;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class QueuedCrudImport extends CrudImport implements ShouldQueue, WithChunkReading
{
    public function chunkSize(): int
    {
        return config('backpack.operations.import.chunk_size') ?? 100;
    }
}
