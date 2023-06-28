<?php

namespace RedSquirrelStudio\LaravelBackpackImportOperation\Columns;

use RedSquirrelStudio\LaravelBackpackImportOperation\Interfaces\ImportColumnInterface;
class ImportColumn implements ImportColumnInterface
{
    protected string $data;
    protected array $config;

    /**
     * Instantiate with data from the spreadsheet column
     * @param string $data
     * @param array $config
     */
    public function __construct(string $data, array $config)
    {
        $this->data = $data;
        $this->config = $config;
    }

    /**
     * Return the data after processing
     * @return mixed
     */
    public function output(): mixed
    {
        return $this->data;
    }
}
