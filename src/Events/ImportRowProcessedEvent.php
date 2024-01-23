<?php

namespace RedSquirrelStudio\LaravelBackpackImportOperation\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RedSquirrelStudio\LaravelBackpackImportOperation\Models\ImportLog;

class ImportRowProcessedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public ImportLog $import_log;
    public mixed $entry;
    public array $row_data;

    /**
     * Create a new event instance.
     */
    public function __construct(ImportLog $import_log, mixed $entry, array $row_data)
    {
        $this->import_log = $import_log;
        $this->entry = $entry;
        $this->row_data = $row_data;
    }
}
