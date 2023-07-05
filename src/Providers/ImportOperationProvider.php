<?php

namespace RedSquirrelStudio\LaravelBackpackImportOperation\Providers;

use Illuminate\Support\ServiceProvider;

class ImportOperationProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     * @return void
     */
    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__.'/../../resources/lang', 'import-operation');

        //Load Views
        if (is_dir(resource_path('views/vendor/backpack/import-operation'))) {
            $this->loadViewsFrom(resource_path('views/vendor/backpack/import-operation'), 'import-operation');
        }
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'import-operation');

        //Publish migrations
        $this->publishes([
            __DIR__ . '/../../database/migrations/' => database_path('migrations'),
        ], 'laravel-backpack-import-operation-migrations');

        //Publish config
        $this->publishes([
            __DIR__ . '/../../config/' => config_path('backpack/operations'),
        ], 'laravel-backpack-import-operation-config');

        //Publish views
        $this->publishes([
            __DIR__ . '/../../resources/views/' => resource_path('views/vendor/backpack/import-operation'),
        ], 'laravel-backpack-import-operation-views');

        // Publishing is only necessary when using the CLI.
        if ($this->app->runningInConsole()) {
            $this->bootForConsole();
        }
    }

    /**
     * Console-specific booting.
     * @return void
     */
    protected function bootForConsole(): void
    {
        // Publishing Views
        $this->publishes([
            __DIR__.'/../../resources/views' => base_path('resources/views/vendor/backpack'),
        ], 'import-operation.views');

        // Publishing Translations
        $this->publishes([
            __DIR__.'/../../resources/lang' => resource_path('lang/vendor/backpack'),
        ], 'import-operation.lang');
    }

}
