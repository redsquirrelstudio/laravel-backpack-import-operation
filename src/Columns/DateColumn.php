<?php

namespace RedSquirrelStudio\LaravelBackpackImportOperation\Columns;

use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class DateColumn extends ImportColumn
{
    /**
     * Return the data after processing
     * @return ?Carbon
     */
    public function output(): ?Carbon
    {
        $date = null;
        if ($this->data){
            try {
                if (is_numeric($this->data)) {
                    $date = Carbon::parse(Date::excelToDateTimeObject($this->data));
                } else {
                    $date = Carbon::parse(str_replace(['/', '_', ' '], ['-', '-', '-'], $this->data));
                }
            } catch (\Exception $e) {
            }
        }


        return $date;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return __('import-operation::import.date');
    }
}
