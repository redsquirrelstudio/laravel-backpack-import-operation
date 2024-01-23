<?php

namespace RedSquirrelStudio\LaravelBackpackImportOperation\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RedSquirrelStudio\LaravelBackpackImportOperation\Models\ImportLog;

class ImportCompleteEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public ImportLog $import_log;

    /**
     * Create a new event instance.
     */
    public function __construct(ImportLog $import_log)
    {
        $this->import_log = $import_log;
    }
}
