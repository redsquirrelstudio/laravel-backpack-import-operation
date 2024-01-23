<?php

namespace RedSquirrelStudio\LaravelBackpackImportOperation\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RedSquirrelStudio\LaravelBackpackImportOperation\Models\ImportLog;

class ImportRowSkippedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public ImportLog $import_log;
    public array $row_data;

    /**
     * Create a new event instance.
     */
    public function __construct(ImportLog $import_log, array $row_data)
    {
        $this->import_log = $import_log;
        $this->row_data = $row_data;
    }
}
