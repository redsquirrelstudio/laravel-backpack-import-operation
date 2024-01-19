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
            if ($multiple) {
                $values = [];
                $data_split = explode($separator, $this->data);
                foreach ($data_split as $data_value) {
                    if (is_array($options)) {
                        foreach ($options as $value => $option) {
                            if ($option === $data_value) {
                                $values[] = $value;
                            }
                        }
                    } else if ($options === 'any') {
                        $values[] = $data_value;
                    }
                }
                return $values;
            } else {
                if (is_array($options)) {
                    foreach ($options as $value => $option) {
                        if ($option === $this->data) {
                            return $value;
                        }
                    }
                }
            }
        }

        return null;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return __('import-operation::import.array');
    }
}
