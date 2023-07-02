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
}
