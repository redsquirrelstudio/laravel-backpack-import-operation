<?php

namespace RedSquirrelStudio\LaravelBackpackImportOperation\Interfaces;

interface ImportColumnInterface
{
    /**
     * Instantiate with data from the spreadsheet column
     * @param ?string $data = null
     * @param ?array $config = []
     */
    public function __construct(?string $data = null, ?array $config = []);

    /**
     * Return the data after processing
     * @return mixed
     */
    public function output(): mixed;

    /**
     * @param string|null $key
     * @return mixed
     */
    public function getConfig(?string $key = null): mixed;
}
