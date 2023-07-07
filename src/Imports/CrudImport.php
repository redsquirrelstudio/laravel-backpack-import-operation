<?php

namespace RedSquirrelStudio\LaravelBackpackImportOperation\Imports;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Events\AfterImport;
use Maatwebsite\Excel\Row;
use RedSquirrelStudio\LaravelBackpackImportOperation\Columns\TextColumn;
use RedSquirrelStudio\LaravelBackpackImportOperation\Interfaces\CrudImportInterface;
use RedSquirrelStudio\LaravelBackpackImportOperation\Models\ImportLog;
use Exception;

class CrudImport implements WithCrudSupport, OnEachRow, WithHeadingRow, WithEvents
{
    protected $import_log;
    protected ?array $rules;

    /**
     * @param int $import_log_id
     * @param string|null $validator
     * @throws Exception
     */
    public function __construct(int $import_log_id, ?string $validator = null)
    {
        //Find the import log
        $model = config('backpack.operations.import.import_log_model') ?? ImportLog::class;
        $import_log = $model::find($import_log_id);
        if (!$import_log) {
            throw new Exception(__('import-operation::import.cant_find_log'));
        }
        $this->import_log = $import_log;
        $this->rules =  $validator ? (new $validator)->rules() : null;
    }

    /**
     * @param Row $row
     * @return void
     */
    public function onRow(Row $row): void
    {
        $row = $row->toArray();

        //Get the current model entry based on the primary key field
        $entry = $this->getEntry($row);
        //Filter the spreadsheet row down to mapped columns, exclude the primary key
        $row = $this->filterRow($row);

        //If validation is set, we need to map the file columns to our model fields
        if ($this->rules){
            $mapped_rules = [];
            foreach($this->rules as $key => $rule){
                $matching_heading = $this->getMatchedHeading($key);
                if ($matching_heading){
                    $mapped_rules[$matching_heading] = $rule;
                }
            }

            if (count($mapped_rules) > 0 && Validator::make($row, $mapped_rules)->fails()){
                return;
            }
        }

        //Loop through row headings
        $update_data = [];
        foreach ($row as $heading => $value) {
            $data = null;
            //Get the config that matches the current column heading
            $matched_config = $this->getMatchedConfig($heading);
            $handler_class = $this->getColumnHandlerClass($matched_config);
            if ($matched_config && $handler_class) {
                //Instantiate handler class, process data from column
                $handler = new $handler_class($value, $matched_config);
                $data = $handler->output();

                //Assign the data to the model field specified in config
                $model_field = $matched_config['name'];
                $update_data[$model_field] = $data;
            }
        }
        //Save the entry
        $entry->save();
    }

    /**
     * @param array $row
     * @return Model
     * Get the current model entry based on the primary key field
     */
    protected function getEntry(array $row): Model
    {
        $entry = null;
        $model = $this->import_log->model;
        $primary_key = $this->import_log->model_primary_key;

        $primary_column_header = $this->getPrimaryKeyColumnHeader();
        $primary_key_value = $row[$primary_column_header] ?? null;
        if ($primary_key_value) {
            $entry = $model::where($primary_key, $primary_key_value)->first();
        }
        if (!$entry) {
            $entry = new $model();
        }
        return $entry;
    }

    /**
     * @return string|null
     */
    protected function getPrimaryKeyColumnHeader(): ?string
    {
        $primary_key = $this->import_log->model_primary_key;

        $primary_column_header = null;
        foreach ($this->import_log->config as $column_header => $column_config) {
            if ($column_config['name'] === $primary_key) {
                $primary_column_header = $column_header;
            }
        }
        return $primary_column_header;
    }

    /**
     * @param array $row
     * @return array
     */
    //Only handle columns that have been mapped, exclude the primary key
    protected function filterRow(array $row): array
    {
        return collect($row)->filter(function ($column, $heading) {
            $primary_column_header = $this->getPrimaryKeyColumnHeader();
            return $heading !== $primary_column_header && in_array($heading, array_keys($this->import_log->config));
        })->toArray();
    }

    /**
     * @param string $heading
     * @return array|null
     */
    protected function getMatchedConfig(string $heading): ?array
    {
        $config = $this->import_log->config;
        return $config[$heading] ?? null;
    }

    protected function getMatchedHeading(string $config_name): ?string
    {
        $config = $this->import_log->config;
        $matching = collect($config)->filter(function($item) use($config_name){
            return isset($item['name']) && $item['name'] === $config_name;
        })->keys()->first();
        if ($matching){
            return $matching;
        }
        return null;
    }

    /**
     * @param array|null $matched_config
     * @return string|null
     */
    protected function getColumnHandlerClass(?array $matched_config = null): ?string
    {
        if ($matched_config) {
            if (!isset($matched_config['type'])) {
                return TextColumn::class;
            }
            if (in_array($matched_config['type'], array_keys(config('backpack.operations.import.column_aliases')))) {
                $aliases = config('backpack.operations.import.column_aliases');
                return $aliases[$matched_config['type']];
            } else {
                return $matched_config['type'];
            }
        }
        return TextColumn::class;
    }


    /**
     * @return Model
     */
    protected function getImportLog(): Model
    {
        return $this->import_log;
    }

    /**
     * @return array
     */
    public function registerEvents(): array
    {
        return [
            AfterImport::class => function (AfterImport $event) {
                $importer = $event->getConcernable();
                $log = $importer->getImportLog();
                $log->completed_at = Carbon::now();
                $log->save();
            },
        ];
    }
}
