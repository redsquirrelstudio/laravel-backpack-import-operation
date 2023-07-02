<?php

namespace RedSquirrelStudio\LaravelBackpackImportOperation;

use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\HeadingRowImport;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use RedSquirrelStudio\LaravelBackpackImportOperation\Columns\NumberColumn;
use RedSquirrelStudio\LaravelBackpackImportOperation\Columns\TextColumn;
use RedSquirrelStudio\LaravelBackpackImportOperation\Imports\CrudImport;
use RedSquirrelStudio\LaravelBackpackImportOperation\Models\ImportLog;
use RedSquirrelStudio\LaravelBackpackImportOperation\Requests\ImportFileRequest;
use Exception;

trait ImportOperation
{
    /**
     * Define which routes are needed for this operation.
     *
     * @param string $segment Name of the current entity (singular). Used as first URL segment.
     * @param string $routeName Prefix of the route name.
     * @param string $controller Name of the current CrudController.
     * @return void
     */
    protected function setupImportRoutes(string $segment, string $routeName, string $controller): void
    {
        Route::get($segment . '/import', [
            'as' => $routeName . '.import.selectFile',
            'uses' => $controller . '@selectFile',
            'operation' => 'import',
        ]);

        Route::post($segment . '/import', [
            'as' => $routeName . '.import.handleFile',
            'uses' => $controller . '@handleFile',
            'operation' => 'import',
        ]);

        Route::get($segment . '/import/{id}/map', [
            'as' => $routeName . '.import.mapFields',
            'uses' => $controller . '@mapFields',
            'operation' => 'import',
        ]);

        Route::post($segment . '/import/{id}/map', [
            'as' => $routeName . '.import.handleMapping',
            'uses' => $controller . '@handleMapping',
            'operation' => 'import',
        ]);

        Route::get($segment . '/import/{id}/confirm', [
            'as' => $routeName . '.import.confirmImport',
            'uses' => $controller . '@confirmImport',
            'operation' => 'import',
        ]);

        Route::post($segment . '/import/{id}/confirm', [
            'as' => $routeName . '.import.handleImport',
            'uses' => $controller . '@handleImport',
            'operation' => 'import',
        ]);
    }

    /**
     * Add the default settings, buttons, etc that this operation needs.
     * @return void
     */
    protected function setupImportDefaults(): void
    {
        CRUD::allowAccess('import');
        CRUD::enableGroupedErrors();
        CRUD::operation('import', function () {
            CRUD::loadDefaultOperationSettingsFromConfig();
        });
        CRUD::operation('list', function () {
            CRUD::addButton('top', 'import', 'view', 'import-operation::buttons.import_button');
        });
    }

    /**
     * @return void
     */
    protected function setupImportFileUpload(): void
    {
        CRUD::setValidation(ImportFileRequest::class);

        CRUD::addField([
            'name' => 'file',
            'label' => __('import-operation::import.select_a_file'),
            'type' => 'upload',
            'hint' => __('import-operation::import.accepted_types'),
        ]);
    }

    /**
     * @return View
     * Return initial view for import file upload
     */
    public function selectFile(): View
    {
        $this->crud->hasAccessOrFail('import');

        $this->data['crud'] = $this->crud;
        $this->data['title'] = CRUD::getTitle() ?? __('import-operation::import.import') . ' ' . $this->crud->entity_name_plural;

        $this->setupImportFileUpload();

        return view('import-operation::select-file', $this->data);
    }

    /**
     * @return RedirectResponse
     * @throws Exception
     * Handle saving the import file and redirect to field mapper
     */
    public function handleFile(): RedirectResponse
    {
        $this->crud->hasAccessOrFail('import');
        $this->setupImportFileUpload();

        $request = $this->crud->validateRequest();

        $disk = config('backpack.operations.import.disk') ?? 'local';
        $path = config('backpack.operations.import.path') ?? 'imports';

        try {
            $file_path = $request->file('file')->store($path, $disk);
        } catch (\Exception $e) {
            \Alert::add('error',
                __('import-operation::import.file_upload_problem') . (config('app.env') === 'development') ? $e->getMessage() : ''
            )->flash();
            return redirect()->back();
        }

        $log_model = $this->getImportLogModel();

        $log = $log_model::create([
            'user_id' => backpack_user()->id,
            'file_path' => $file_path,
            'disk' => $disk,
            'model' => get_class($this->crud->model),
            'model_primary_key' => $this->getImportPrimaryKey(),
        ]);

        return redirect($this->crud->route . '/import/' . $log->id . '/map');
    }

    /**
     * @param HeadingRowImport $headingImport
     * @param int $id
     * @return View|RedirectResponse
     * Return view for mapping fields to import columns
     */
    public function mapFields(HeadingRowImport $headingImport, int $id): View|RedirectResponse
    {
        //Find the import log
        $log = $this->getCurrentImportLog($id);

        if (!$this->validateImport($log)) {
            return redirect($this->crud->route . '/import');
        }

        //Get base level of array if import returns multiple nested arrays for headers
        $column_headers = Excel::toArray($headingImport, $log->file_path, $log->disk);
        do {
            $column_headers = $column_headers[0];
        } while (isset($column_headers[0]) && is_array($column_headers[0]));

        return view('import-operation::map-fields', [
            'crud' => $this->crud,
            'title' => CRUD::getTitle() ?? __('import-operation::import.import') . ' ' . $this->crud->entity_name_plural,
            'column_headers' => $column_headers,
            'import' => $log,
            'primary_key' => $log->model_primary_key,
        ]);
    }


    /**
     * @param Request $request
     * @param int $id
     * @return RedirectResponse
     * Save mapping configuration to import log and redirect to confirmation screen
     */
    public function handleMapping(Request $request, int $id): RedirectResponse
    {
        $this->crud->hasAccessOrFail('import');

        $log = $this->getCurrentImportLog($id);

        $config = [];
        foreach ($this->crud->columns() as $column) {
            $chosen_field = $request->get($column['name'] . '__heading');
            if ($chosen_field) {
                $config[$chosen_field] = collect($column)->filter(
                    fn($value, $key) => in_array($key, ['name', 'label', 'type', 'primary_key', 'options', 'separator', 'multiple']
                    ))->toArray();
            }
        }

        if (count($config) === 0) {
            return redirect($this->crud->route . '/import/' . $id . '/map')->withErrors([
                'import' => __('import-operation::import.please_map_at_least_one'),
            ]);
        }
        if (!isset($config[$log->model_primary_key])) {
            return redirect($this->crud->route . '/import/' . $id . '/map')->withErrors([
                'import' => __('import-operation::import.please_map_the_primary_key'),
            ]);
        }

        $log->config = $config;
        $log->save();

        return redirect($this->crud->route . '/import/' . $id . '/confirm');
    }

    /**
     * @param int $id
     * @return View|RedirectResponse
     * Show the user their configured import and ask to confirm
     */
    public function confirmImport(int $id): View|RedirectResponse
    {
        $this->crud->hasAccessOrFail('import');

        $log = $this->getCurrentImportLog($id);

        if (!$this->validateImport($log, true)) {
            return redirect($this->crud->route . '/import/' . $id . '/map');
        }

        return view('import-operation::confirm-import', [
            'crud' => $this->crud,
            'title' => CRUD::getTitle() ?? __('import-operation::import.import') . ' ' . $this->crud->entity_name_plural,
            'import' => $log,
        ]);
    }

    public function handleImport(int $id): RedirectResponse
    {
        $this->crud->hasAccessOrFail('import');

        $log = $this->getCurrentImportLog($id);

        if (!$this->validateImport($log, true)) {
            return redirect($this->crud->route . '/import/' . $id . '/map');
        }

        $log->started_at = Carbon::now();
        $log->save();

        if ($this->crud->getOperationSetting('queue_import', 'import') ?? false) {

            \Alert::add('success', __('import-operation::import.your_import_has_been_queued'))->flash();
        } else {
            Excel::import(new CrudImport($log->id), $log->file_path, $log->disk);
            \Alert::add('success', __('import-operation::import.your_import_has_been_processed'))->flash();
        }
        return redirect($this->crud->route);
    }

    /**
     * @param int $id
     * @return ImportLog
     */
    protected function getCurrentImportLog(int $id): ImportLog
    {
        $log_model = $this->getImportLogModel();
        $log = $log_model::find($id);
        if (!$log) {
            abort(404);
        }
        return $log;
    }

    /**
     * @return Model|string
     */
    protected function getImportLogModel(): Model|string
    {
        return config('backpack.operations.import.import_log_model') ?? ImportLog::class;
    }

    /**
     * @return string
     * @throws Exception
     * Get the model's primary key based on the import config or model setup
     */
    protected function getImportPrimaryKey(): string
    {
        //First look for a column with primary_key => true
        $primary_key_column = collect($this->crud->columns())->where('primary_key', true)->first();
        if ($primary_key_column) {
            $primary_key = $primary_key_column['name'];
        } else {
            //Get the current CRUD models' primary key as a fallback if the user has not defined a column as primary key
            $model = (new $this->crud->model);
            $primary_key = $model->getKeyName();

            //Check if a column is defined in import setup
            $primary_key_column = collect($this->crud->columns())->where('name', $primary_key)->first();
            if (!$primary_key_column) {
                //If a column hasn't been set with the model's primary key, choose the first text/number column as a primary key
                $first_column = collect($this->crud->columns())->whereIn('type', [
                    'text', 'number', TextColumn::class, NumberColumn::class,
                ])->first();
                if ($first_column) {
                    $primary_key = $first_column['name'];
                } else {
                    throw new Exception(__('import-operation::import.primary_key_not_found', [
                        'model' => get_class($this->crud->model)
                    ]));
                }
            }
        }
        return $primary_key;
    }

    /**
     * @param Model $log
     * @param bool $include_config
     * @return bool
     */
    protected function validateImport(Model $log, bool $include_config = false): bool
    {
        $rules = [
            'file_path' => 'required',
            'model_primary_key' => 'required',
            'model' => 'required',
        ];
        if ($include_config) {
            $rules['config'] = 'required|min:1';
        }
        $import_validator = Validator::make($log->toArray(), $rules);
        return $import_validator->passes();
    }
}
