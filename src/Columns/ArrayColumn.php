<?php

namespace RedSquirrelStudio\LaravelBackpackImportOperation\Columns;

class ArrayColumn extends ImportColumn
{
    /**
     * Return the data after processing
     * @return mixed
     */
    public function output(): mixed
    {
        $multiple = $this->getConfig('multiple');
        $separator = $this->getConfig('separator') ?? ',';
        $options = $this->getConfig('options');

        if ($options) {
            if ($multiple){
                $values = [];
                $data_split = explode($separator, $this->data);
                foreach($data_split as $data_value){
                    foreach ($options as $value => $option) {
                        if ($option === $data_value) {
                            $values[] = $value;
                        }
                    }
                }
                return $values;
            }
            else{
                foreach ($options as $value => $option) {
                    if ($option === $this->data) {
                        return $value;
                    }
                }
            }
        }

        return null;
    }
}
