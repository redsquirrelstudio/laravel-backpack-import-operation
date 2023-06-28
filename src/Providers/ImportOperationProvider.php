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

        // load views
        // - from 'resources/views/vendor/backpack/import-operation' if they're there
        // - otherwise fall back to package views
        if (is_dir(resource_path('views/vendor/backpack/import-operation'))) {
            $this->loadViewsFrom(resource_path('views/vendor/backpack/import-operation'), 'import-operation');
        }
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'import-operation');

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
