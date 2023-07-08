<?php

namespace RedSquirrelStudio\LaravelBackpackImportOperation\Console\Commands;

use Backpack\Generators\Services\BackpackCommand;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Str;

class ImportColumnBackpackCommand extends BackpackCommand
{
    use \Backpack\CRUD\app\Console\Commands\Traits\PrettyCommandOutput;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'backpack:import-column';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backpack:import-column {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a Backpack CRUD import column handler';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'ImportColumn';

    /**
     * Execute the console command.
     *
     * @return int
     *
     * @throws FileNotFoundException
     */
    public function handle(): int
    {
        $name = $this->getNameInput();
        $nameTitle = $this->buildCamelName($name);
        $qualifiedClassName = $this->qualifyClass($nameTitle);
        $path = $this->getPath($qualifiedClassName);
        $relativePath = Str::of($path)->after(base_path())->trim('\\/');

        $this->progressBlock("Creating Import Column <fg=blue>$relativePath</>");

        // Next, We will check to see if the class already exists. If it does, we don't want
        // to create the class and overwrite the user's code. So, we will bail out so the
        // code is untouched. Otherwise, we will continue generating this class' files.
        if ((!$this->hasOption('force') || !$this->option('force')) && $this->alreadyExists($this->getNameInput())) {
            $this->closeProgressBlock('Already existed', 'yellow');

            return self::FAILURE;
        }

        // Next, we will generate the path to the location where this class' file should get
        // written. Then, we will build the class and make the proper replacements on the
        // stub files so that it gets the correctly formatted namespace and class name.
        $this->makeDirectory($path);

        $this->files->put($path, $this->sortImports($this->buildClass($nameTitle)));

        $this->closeProgressBlock();
        return self::SUCCESS;
    }

    /**
     * Get the destination class path.
     *
     * @param string $name
     * @return string
     */
    protected function getPath($name): string
    {
        $name = str_replace($this->laravel->getNamespace(), '', $name);

        return $this->laravel['path'] . '/' . str_replace('\\', '/', $name).'.php';
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub(): string
    {
        return __DIR__ . '/../stubs/import-column.stub';
    }

    /**
     * Get the default namespace for the class.
     *
     * @param string $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace . '\Imports\Columns';
    }

    /**
     * Replace the table name for the given stub.
     *
     * @param string $stub
     * @param string $name
     * @return self
     */
    protected function replaceNameStrings(&$stub, $name): self
    {
        $nameTitle = Str::afterLast($name, '\\');
        $stub = str_replace('DummyColumn', $this->buildClassName($nameTitle), $stub);

        return $this;
    }

    /**
     * Build the class with the given name.
     *
     * @param string $name
     * @return string
     * @throws FileNotFoundException
     */
    protected function buildClass($name): string
    {
        $stub = $this->files->get($this->getStub());

        $this->replaceNamespace($stub, $this->qualifyClass($name))
            ->replaceNameStrings($stub, $this->buildCamelName($name));

        return $stub;
    }
}
