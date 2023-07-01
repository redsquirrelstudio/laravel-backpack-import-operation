<?php

namespace RedSquirrelStudio\LaravelBackpackImportOperation\Imports;

use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Row;
use RedSquirrelStudio\LaravelBackpackImportOperation\Models\ImportLog;
use Exception;

class CrudImport implements OnEachRow, WithHeadingRow
{
    protected $import_log;

    /**
     * @param int $import_log_id
     * @throws Exception
     */
    public function __construct(int $import_log_id)
    {
        $model = config('backpack.operations.import.import_log_model') ?? ImportLog::class;
        $import_log = $model::find($import_log_id);
        if (!$import_log){
            throw new Exception(__('import-operation::import.cant_find_log'));
        }
    }

    /**
     * @param Row $row
     * @return void
     */
    public function onRow(Row $row): void
    {

    }
}
