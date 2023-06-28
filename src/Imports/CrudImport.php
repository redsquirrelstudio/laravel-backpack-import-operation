<?php

namespace RedSquirrelStudio\LaravelBackpackImportOperation\Imports;

use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Row;

class CrudImport implements OnEachRow, WithHeadingRow
{
    /**
     * @param Row $row
     * @return void
     */
    public function onRow(Row $row): void
    {

    }
}
