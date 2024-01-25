<?php

namespace RedSquirrelStudio\LaravelBackpackImportOperation\Imports;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Events\AfterImport;
use Maatwebsite\Excel\Events\BeforeImport;
use Maatwebsite\Excel\Row;
use RedSquirrelStudio\LaravelBackpackImportOperation\Columns\TextColumn;
use RedSquirrelStudio\LaravelBackpackImportOperation\Events\ImportCompleteEvent;
use RedSquirrelStudio\LaravelBackpackImportOperation\Events\ImportRowProcessedEvent;
use RedSquirrelStudio\LaravelBackpackImportOperation\Events\ImportRowSkippedEvent;
use RedSquirrelStudio\LaravelBackpackImportOperation\Events\ImportStartedEvent;
use RedSquirrelStudio\LaravelBackpackImportOperation\Interfaces\WithCrudSupport;
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
        $this->rules = $validator ? (new $validator)->rules() : null;
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
        if ($this->rules) {
            $mapped_rules = [];
            foreach ($this->rules as $key => $rule) {
                $matching_heading = $this->getMatchedHeading($key);
                if ($matching_heading) {
                    $mapped_rules[$matching_heading] = $rule;
                }
            }

            if (count($mapped_rules) > 0 && Validator::make($row, $mapped_rules)->fails()) {
                ImportRowSkippedEvent::dispatch($this->import_log, $row);
                return;
            }
        }

        //Loop through row headings
        foreach ($row as $heading => $value) {
            $data = null;
            //Get the config that matches the current column heading
            $matched_config = $this->getMatchedConfig($heading);
            $handler_classes = $this->getColumnHandlerClasses($matched_config);

            if ($matched_config && count($handler_classes) === count($matched_config)) {
                foreach ($handler_classes as $index => $handler_class) {
                    //Instantiate handler class, process data from column
                    $handler = new $handler_class($value, $matched_config[$index], $this->import_log->model);
                    $data = $handler->output();

                    //Assign the data to the model field specified in config
                    $model_field = $matched_config[$index]['name'];
                    $entry->{$model_field} = $data;
                }
            }
        }
        //Save the entry
        $entry->save();
        ImportRowProcessedEvent::dispatch($this->import_log, $entry, $row);
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

        if (!is_null($primary_key)){
            $primary_column_header = $this->getPrimaryKeyColumnHeader();
            $primary_key_value = $row[$primary_column_header] ?? null;
            if ($primary_key_value) {
                $entry = $model::where($primary_key, $primary_key_value)->first();
            }

            if (!$entry) {
                $entry = new $model([$primary_key => $primary_key_value]);
            }
            return $entry;
        }


        return new $model;
    }

    /**
     * @return string|null
     */
    protected function getPrimaryKeyColumnHeader(): ?string
    {
        $primary_key = $this->import_log->model_primary_key;

        $primary_column_header = null;
        foreach ($this->import_log->config as $column_header => $column_configs) {
            if (collect($column_configs)->where('name', $primary_key)->count() > 0) {
                $primary_column_header = $column_header;
            }
        }
        return $primary_column_header;
    }

    /**
     * @param array $row
     * @return array
     */
    //Only handle columns that have been mapped
    protected function filterRow(array $row): array
    {
        return collect($row)->filter(function ($column, $heading) {
            return in_array($heading, array_keys($this->import_log->config));
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
        $matching = collect($config)->filter(function ($items) use ($config_name) {
            return !is_null(collect($items)->where('name', $config_name)->first());
        })->keys()->first();
        if ($matching) {
            return $matching;
        }
        return null;
    }

    /**
     * @param array|null $matched_config
     * @return array{string}
     */
    protected function getColumnHandlerClasses(?array $matched_config = null): array
    {
        $columns_types = [];
        if ($matched_config) {
            foreach ($matched_config as $matched_config_column) {
                if (!isset($matched_config_column['type'])) {
                    $column_types[] = TextColumn::class;
                }
                if (in_array($matched_config_column['type'], array_keys(config('backpack.operations.import.column_aliases')))) {
                    $aliases = config('backpack.operations.import.column_aliases');
                    $column_types[] = $aliases[$matched_config_column['type']];
                } else {
                    $column_types[] = $matched_config_column['type'];
                }
            }
        }

        return $column_types;
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
            BeforeImport::class => function (BeforeImport $event) {
                $importer = $event->getConcernable();
                $log = $importer->getImportLog();
                ImportStartedEvent::dispatch($log);
            },
            AfterImport::class => function (AfterImport $event) {
                $importer = $event->getConcernable();
                $log = $importer->getImportLog();
                $log->completed_at = Carbon::now();
                $log->save();

                if ($log->delete_file_after_import) {
                    Storage::disk($log->disk)->delete($log->file_path);
                }

                ImportCompleteEvent::dispatch($log);
            },
        ];
    }
}
