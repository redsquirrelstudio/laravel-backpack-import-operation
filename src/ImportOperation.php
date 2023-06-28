<?php

namespace RedSquirrelStudio\LaravelBackpackImportOperation;

use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Illuminate\Support\Facades\Route;

trait ImportOperation
{
    /**
     * Define which routes are needed for this operation.
     *
     * @param string $segment    Name of the current entity (singular). Used as first URL segment.
     * @param string $routeName  Prefix of the route name.
     * @param string $controller Name of the current CrudController.
     * @return void
     */
    protected function setupImportRoutes(string $segment, string $routeName, string $controller): void
    {
        Route::get($segment.'/import', [
            'as'        => $routeName.'.import',
            'uses'      => $controller.'@import',
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
        CRUD::operation('import', function () {
            CRUD::loadDefaultOperationSettingsFromConfig();
        });
        CRUD::operation('list', function () {
             CRUD::addButton('top', 'import', 'view', 'import-operation::buttons.import_button');
        });
    }

    public function import()
    {
        CRUD::hasAccessOrFail('import');

        $this->data['crud'] = $this->crud;
        $this->data['title'] = CRUD::getTitle() ?? __('import-operation::import.import').' '.$this->crud->entity_name;

        return view('import-operation::import', $this->data);
    }
}
