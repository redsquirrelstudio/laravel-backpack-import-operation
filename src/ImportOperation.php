<?php

namespace RedSquirrelStudio\LaravelBackpackImportOperation;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Route;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\HeadingRowImport;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use RedSquirrelStudio\LaravelBackpackImportOperation\Models\ImportLog;
use RedSquirrelStudio\LaravelBackpackImportOperation\Requests\ImportFileRequest;

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

        Route::get($segment . '/import/{id}/map/', [
            'as' => $routeName . '.import.mapFields',
            'uses' => $controller . '@mapFields',
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
        $this->data['title'] = CRUD::getTitle() ?? __('import-operation::import.import') . ' ' . $this->crud->entity_name;

        $this->setupImportFileUpload();

        return view('import-operation::select-file', $this->data);
    }

    /**
     * @return RedirectResponse
     * Handle saving the import file and redirect to field mapper
     */
    public function handleFile(): RedirectResponse
    {
        $this->crud->hasAccessOrFail('import');
        $this->setupImportFileUpload();

        $request = $this->crud->validateRequest();

        $disk = config('backpack.operations.import.disk') ?? 'local';
        $path = config('backpack.operations.import.path') ?? 'imports';

        try{
            $file_path = $request->file('file')->store($path, $disk);
        }
        catch (\Exception $e){
            \Alert::add('error',
                __('import-operation::import.file_upload_problem').(config('app.env') === 'development') ? $e->getMessage() : ''
            )->flash();
            return redirect()->back();
        }

        $log_model = config('backpack.operations.import.import_log_model') ?? ImportLog::class;
        $log = $log_model::create([
            'user_id' => backpack_user()->id,
            'file_path' => $file_path,
            'disk' => $disk,
            'model' => $this->crud->model,
        ]);

        return redirect($this->crud->route.'/import/'.$log->id.'/map');
    }

    /**
     * @param HeadingRowImport $headingImport
     * @param int $id
     * @return View
     * Return view for mapping fields to import columns
     */
    public function mapFields(HeadingRowImport $headingImport, int $id): View
    {
        //Find the import log
        $log_model = config('backpack.operations.import.import_log_model') ?? ImportLog::class;
        $log = $log_model::find($id);
        if (!$log){
            abort(404);
        }

        //Get base level of array if import returns multiple nested arrays for headers
        $column_headers = Excel::toArray($headingImport, $log->file_path, $log->disk);
        do{
            $column_headers = $column_headers[0];
        } while(isset($column_headers[0]) && is_array($column_headers[0]));

        return view('import-operation::map-fields', [
            'crud' => $this->crud,
            'title' => CRUD::getTitle() ?? __('import-operation::import.import') . ' ' . $this->crud->entity_name,
            'column_headers' => $column_headers,
            'import_id' => $log->id,
        ]);
    }
}
