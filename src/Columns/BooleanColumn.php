<?php

namespace RedSquirrelStudio\LaravelBackpackImportOperation\Columns;

class BooleanColumn extends ImportColumn
{
    /**
     * Return the data after processing
     * @return bool
     */
    public function output(): bool
    {
        $options = $this->getConfig('options');
        collect($options)->map(function($option, $key){
            if (!is_bool($key)){
                throw new \Exception(
                    'The key: '.$key.' is invalid for a boolean import column option. Please use true/false 1/0 etc.'
                );
            }
        });

        if ($options){
            foreach($options as $value => $option) {
                if ($option === $this->data) {
                    return $value;
                }
            }
        }

        return $this->data === '1';
    }
}
