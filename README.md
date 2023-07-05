# Import Operation for Backpack for Laravel

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Total Downloads][ico-downloads]][link-downloads]

Adds a configurable interface that allows your admin users to:

- Upload a spreadsheet file
- Map the file's columns to your CRUD model's fields
- Import their data

and allows you as the developer to:

- Customise each CRUD's import behaviour using the Backpack API you know and love
- Choose between queued or instant imports
- Completely customise the operation's behaviour

![Screenshot of the operation's mapping screen](https://raw.githubusercontent.com/redsquirrelstudio/laravel-backpack-import-operation/dev/assets/screenshot.jpg?raw=true)

Powering the imports in the background is [```maatwebsite/excel```][link-laravel-excel]
and if you wish, you can define your own import class for the operation to use.

***However,***
The real power lies in being able to configure an import lightning fast using
the same syntax as you would to define your list views.

## Table of Contents

1. [Installation](#installation)
2. [Usage](#usage)
3. [Column Types](#column-types)
    1. [Text](#text)
    2. [Number](#number)
    3. [Boolean](#boolean)
    4. [Date](#date)
    5. [Array](#array)
4. [Adding Your Own Columns](#adding-your-own-columns)
5. [Queued Imports](#queued-imports)
6. [Configuration](#configuration)
    1. [File Uploads](#file-uploads)
    2. [Queues](#queues)
    3. [Changing the Import log Model](#import-log)
    4. [Customising Views](#views)
7. [Credits](#credits)
8. [License](#license)

## Installation

**Step 1.**

Require the package with composer:

```bash
composer require redsquirrelstudio/laravel-backpack-import-operation
```

This will also install [```maatwebsite/excel```][link-laravel-excel] if it's not already in your project.

**Step 2. (Optional)**

The service provider at: ```RedSquirrelStudio\LaravelBackpackImportOperation\Providers\ImportOperationProvider```
will be auto-discovered and registered by default. However, if you're like me, you can add it to
your ```config/app.php```

```php
    'providers' => ServiceProvider::defaultProviders()->merge([
        /*
         * Package Service Providers...
         */
        //Some other package's service providers...
        RedSquirrelStudio\LaravelBackpackImportOperation\Providers\ImportOperationProvider::class,
    ])->toArray(),
```

**Step 3.**

Publish the config file:

```bash
php artisan vendor:publish --tag=laravel-backpack-import-operation-config
```

This will create a new file at ```config/backpack/operations/import.php``` allowing you
to customise things such as the disk and path uploaded files should be stored at.

**Step 4.**

Publish and run the migration:

```bash
php artisan vendor:publish --tag=laravel-backpack-import-operation-migrations
```

*Then*

```bash
php artisan migrate
```

## Usage

In your CRUD Controller where you need the import operation.

*Wait for it...*

**Add the import operation:**

```php
class ExampleCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
    use \RedSquirrelStudio\LaravelBackpackImportOperation\ImportOperation;
    //...
```

But wait! There's more!

### Configuring the Import

Configuring a CRUD import is very similar to configuring the list
or show view, here's an example:

```php
    //Probably some more CRUD config...
    
    protected function setupImportOperation()
    {
        CRUD::addColumn([
           'name' => 'id',
           'label' => 'ID',
           'type' => 'number',
        ]);

        CRUD::addColumn([
           'name' => 'name',
           'label' => 'Name',
           'type' => 'text',
        ]);  
    }
    
    //Fetch functions or something...  
```

Within the ```setupImportOperation()``` function you can add CRUD columns
with, in most cases, a name, label, and type. Each column corresponds to a model field.

The type of column is important as it specifies how the data from the spreadsheet
will be processed before it is saved against the model.

### <span style="color: red">=======IMPORTANT=======</span>

The columns you specify here correspond to the model fields **NOT**
the spreadsheet columns. The user has the option to assign any spreadsheet column
to any import column within the interface.

### <span style="color: red">=======================</span>

## Column Types

### Text

The simplest type of column, just takes the text from the spreadsheet
and saves it to the model field.

**Example**

```php
CRUD::addColumn([
   'name' => 'name',
   'label' => 'Name',
   'type' => 'text',
]);  
```

### Number

This column will check whether the number is numeric,
if it is, it will be saved against the model otherwise ```null```
will be used. (This field also supports decimal values).

**Example**

```php
CRUD::addColumn([
   'name' => 'age',
   'label' => 'Age',
   'type' => 'number',
]);  
```

### Boolean

By default, this column will evaluate whether the column contains:
'true', '1', or 'y' (case-insensitive). But you can specify what should be counted
as true or false using the ```'options'``` key.

**Example**

```php
CRUD::addColumn([
   'name' => 'active',
   'label' => 'Active Customer',
   'type' => 'boolean',
   'options' => [
        false => 'No',
        true => 'Yes',
    ]  
]);  
```

### Date

This column will take the input and try to convert it to a datetime,
if successful, the datetime will be return as a [```Carbon\Carbon``` ][link-carbon]
instance, otherwise it will return null.

**Example**

```php
CRUD::addColumn([
   'name' => 'date_of_birth',
   'label' => 'Birthday',
   'type' => 'date',
]);  
```

### Array

This column is ideal for only importing one or more of a number
of options.
**Example**

```php
CRUD::addColumn([
   'name' => 'type',
   'label' => 'Customer Type',
   'type' => 'array',
   'options' => [
        'retail' => 'Retail',
        'trade' => 'Trade',
        'other' => 'Other',
    ]
]);  
```

In this example, the import will only save data from the column
if it is 'Retail', 'Trade', or 'Other'. In these instances,
values 'retail', 'trade', and 'other' will be saved respectively.

**Multiple Values**

The array column also supports multiple values.

**Example**

```php
CRUD::addColumn([
   'name' => 'type',
   'label' => 'Customer Type',
   'type' => 'array',
   'multiple' => true,
   'options' => [
        'retail' => 'Retail',
        'trade' => 'Trade',
        'other' => 'Other',
    ]
]);  
```

In this example, the user could import the following data:

```
Retail,Trade,Other
```

and the column would save the following array against the model's type field:

```php
[
    'retail',
    'trade',
    'other'
]
```

For this to work, make sure to cast the model's field as an array
within the ```$casts``` array, as shown below:

```php
  protected $casts = [
        'types' => 'array',
  ];
```

## Adding Your Own Columns

The import operation offers the option to create your own
handlers. This only takes two steps.

**Step 1.**

Extend the base import column class and add the ```output()``` function.

```php
<?php

namespace App\ImportColumns;

use RedSquirrelStudio\LaravelBackpackImportOperation\Columns\ImportColumn;

class ExampleColumn extends ImportColumn
{
    public function output(): mixed
    {
        return $this->data;
    }
}
```

```$this->data``` is the input from the spreadsheet column.
Process the data how you need to and return it from the function.
Voila!

**Step 2.**

Add your new class to the file at ```config/backpack/operations/import.php``` under
the ```'column_aliases'``` array:

```php
    //...
    // Aliases for import column types to be used in operation setup
    'column_aliases' => [
        'array' => Columns\ArrayColumn::class,
        'boolean' => Columns\BooleanColumn::class,
        'date' => Columns\DateColumn::class,
        'number' => Columns\NumberColumn::class,
        'text' => Columns\TextColumn::class,
        'example' => App\ImportColumns\ExampleColumn::class
    ]
```

***Boom***

Your column is ready for action

```php
CRUD::addColumn([
   'name' => 'name',
   'label' => 'Name',
   'type' => 'example',
]);  
```

Note: you can skip adding a column aliases and specify the column
class directly against the type key:

```php
CRUD::addColumn([
   'name' => 'name',
   'label' => 'Name',
   'type' => App\ImportColumns\ExampleColumn::class,
]);  
```

## Queued Imports

In most situations, it is going to be better for the user
if your imports are processed in the background rather than making them
wait for the import to happen on a button press.

Therefore, you have the option to queue your imports by adding this one line
of code to the ```setupImportOperation()``` function:

```php
    //...
    protected function setupImportOperation()
    {
        CRUD::setOperationSetting('queueImport', true);
    //...
```

Of course, for this to work, you will need to set up a queue for
your application to dispatch jobs to, to do that, [follow Laravel's
official docs][link-laravel-queue-docs].

## Configuration

### File Uploads

By default, spreadsheets will be stored in your default disk
at the path /imports. but this can be altered either by changing the following
env variables

```dotenv
FILESYSTEM_DISK="s3"
BACKPACK_IMPORT_FILE_PATH="/2023/application-name/imports"
```

Or by directly changing the options within ```config/backpack/operations/import.php```

```php
    //...
    //Filesystem disk to store uploaded import files
    'disk' => "s3",
    
    //Path to store uploaded import files
    'path' => "/2023/application-name/imports",
    //...
```

### Queues

You can also change the queue that queued imports are dispatched to
and the number of rows processed per chunk by changing the following env variables:

```dotenv
QUEUE_CONNECTION="import-queue"
BACKPACK_IMPORT_CHUNK_SIZE=300
```

or changing the value directly within ```config/backpack/operations/import.php```

```php
    //...
    //Queue to dispatch import jobs to
    'queue' => 'import-queue',

    //Chunk size for reading import files
    'chunk_size' => 300,
    //...
```

### Import Log

In very rare cases, you may wish to also change the model
that is used to log imports, I can't think of a reason why, but I'm
sure someone will come up with one.

If you do, make sure to update the migration, and specify your own
model at ```config/backpack/operations/import.php```

```php
//...
return [
    'import_log_model' => ImportLog::class,
    //...
```

### Views

You can update the operation views if required. To do this run
```bash
php artisan vendor:publish --tag=laravel-backpack-import-operation-views
```
to publish the operation blade files to ```resources/views/vendor/backpack/import-operation```
The files stored in this directory take priority over the package's default views.

### Credits
- [Lewis Raggett][link-me] :: Package Creator
- [Cristian Tabacitu][link-backpack] :: Backpack for Laravel Creator
- [Spartner][link-laravel-excel] :: Laravel Excel Creator

### License

MIT. Please see the [license file](license.md) for more information.

[ico-version]: https://img.shields.io/packagist/v/redsquirrelstudio/laravel-backpack-import-operation?style=flat-square
[ico-license]: https://img.shields.io/badge/license-dual-blue?style=flat-square
[link-packagist]: https://packagist.org/packages/redsquirrelstudio/laravel-backpack-import-operation
[ico-downloads]: https://img.shields.io/packagist/dt/redsquirrelstudio/laravel-backpack-import-operation.svg?style=flat-square
[link-downloads]: https://packagist.org/packages/backpack/revise-operation
[link-laravel-excel]: https://laravel-excel.com
[link-carbon]: https://carbon.nesbot.com/docs
[link-laravel-queue-docs]: https://laravel.com/docs/queues
[link-backpack]: https://github.com/backpack
[link-me]: https://github.com/redsquirrelstudio
