<?php

namespace RedSquirrelStudio\LaravelBackpackImportOperation\Columns;

class TextColumn extends ImportColumn
{
    /**
     * @return ?string
     */
    public function output(): ?string
    {
        return $this->data;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return __('import-operation::import.text');
    }
}
