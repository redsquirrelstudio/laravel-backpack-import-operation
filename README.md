# Import Operation for Backpack for Laravel

[![Latest Version on Packagist][ico-version]][link-packagist]

[![Total Downloads][ico-downloads]][link-downloads]

Adds a configurable interface that allows your admin users to:

- Upload a spreadsheet file.
- Map the file's columns to your CRUD model's fields.
- Import their data.

and allows you as the developer to:

- Customise each CRUD's import behaviour using the Backpack API you know and love.
- Choose between queued or instant imports.
- Completely customise the operation's behaviour.

[!["Buy Me A Coffee"](https://www.buymeacoffee.com/assets/img/custom_images/orange_img.png)](https://www.buymeacoffee.com/redsquirrelstudio)

If you're looking for a great team of developers to handle some Backpack/Laravel development for you, drop us a line at [Sprechen][link-sprechen]

**Also need full exports for your CRUD? Check out [redsquirrelstudio/laravel-backpack-export-operation](https://github.com/redsquirrelstudio/laravel-backpack-export-operation)**

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
4. [Primary Keys](#primary-keys)
5. [Imports Without Primary Keys](#imports-without-primary-keys)
6. [Validation](#validation)
7. [Adding an Example File](#adding-an-example-file)
8. [Adding Your Own Columns](#adding-your-own-columns)
9. [Custom Import Classes](#custom-import-classes)
10. [Disabling User Mapping](#disabling-user-mapping)
11. [Delete Spreadsheet on Completion](#delete-spreadsheet-on-completion)
12. [Queued Imports](#queued-imports)
13. [Configuration](#configuration)
    1. [File Uploads](#file-uploads)
    2. [Queues](#queues)
    3. [Changing the Import log Model](#import-log)
    4. [Customising Translations](#translations)
    5. [Customising Views](#views)
14. [Events](#events)
15. [Restricting Access](#restricting-access)
16. [Credits](#credits)
17. [License](#license)

## Installation

**Environment Requirements**
- PHP extension php_zip
- PHP extension php_xml
- PHP extension php_gd2
- PHP extension php_iconv 
- PHP extension php_simplexml
- PHP extension php_xmlreader
- PHP extension php_zlib

**Step 1.**

Require the package with composer:

```bash
composer require redsquirrelstudio/laravel-backpack-import-operation
```

This will also install [```maatwebsite/excel```][link-laravel-excel] if it's not already in your project.

**Step 2. (Optional)**

The service provider at: ```RedSquirrelStudio\LaravelBackpackImportOperation\Providers\ImportOperationProvider```
will be auto-discovered and registered by default. Although, if you're like me, you can add it to
your ```config/app.php```.

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

### <span style="color: red">=========IMPORTANT==========</span>

The columns you specify here correspond to the model fields **NOT**
the spreadsheet columns. The user has the option to assign any spreadsheet column
to any import column within the interface.

### <span style="color: red">=============================</span>

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

In the case where you would like the user to be able to specify a comma seperated list of any values. You can
add the following to the CRUD column config.

```php
CRUD::addColumn([
   'name' => 'type',
   'label' => 'Customer Type',
   'type' => 'array',
   'multiple' => true,
   'options' => 'any'
]);  
```
With this configuration, the user could put whatever they like.
For example, if they imported dog,cat,rat - It would be saved to the model as:
```php
[
    'dog',
    'cat',
    'rat'
]
```

```'options' => 'any'``` 

cannot be used without

```'multiple' => true ```

as it does not make sense for this column type. In this case, just use a text column.

## Primary Keys

The import operation needs to know your model's primary key
so that it knows whether to create or update with the row's data.
By default, the operation will try to find a column you have added that
has the model's primary key as the name.

For example, if your model's primary key is id,
the operation would use this column as the primary key:

```php
CRUD::addColumn([
   'name' => 'id',
   'type' => 'number',
]);
```

You'll be able to see on the mapping screen which column
has been identified as the primary key.

If your primary key cannot be found, the operation instead will
look for the first text or number column you have added.

You can also set a column as the primary key by adding the following
config to the column:
```php
CRUD::addColumn([
   'name' => 'id',
   'type' => 'number',
   'primary_key' => true,
]);
```

## Imports Without Primary Keys

You can disable the requirement for a primary key, however, it will mean that your import
can only create new models and won't be able to update existing data. This can be useful in cases
where you don't have a defined primary key and are relying on the model's auto-incrementing ID. This setting
can also help where you want to be able to specify a unique column that the user shouldn't be able to create multiples of or change existing data for.

TLDR: Imports with this setting enabled cannot update existing data, only import new data.

Add the following line to your ```setupImportOperation``` function:
```php
    protected function setupImportOperation()
    {
        $this->withoutPrimaryKey();
        //Some column config...
```

## Validation

Validating your imports works similarly to how you would validate a
create or update method, call the following function within the ```setupImportOperation```
function:

```php
    protected function setupImportOperation()
    {
        CRUD::setValidation(CustomerRequest::class);
        //Some column config...
```

The form request should validate what is required for your model, **Not** the spreadsheet
columns, again because the column headers shouldn't matter as the user can map them.

## Adding an Example File

You can also add a link for your user to download an example spreadsheet with data that you
would expect them to upload. To set this use the following function within the ```setupImportOperation```
function:

```php
    protected function setupImportOperation()
    {
        $this->setExampleFileUrl('https://example.com/link-to-your-download/file.csv');
        //Some column config...
```

Doing this will provide them with a link like this when uploading their file:

![Screenshot of the operation's example download](https://raw.githubusercontent.com/redsquirrelstudio/laravel-backpack-import-operation/dev/assets/example-download.jpg?raw=true)

## Adding Your Own Columns

The import operation offers the option to create your own
handlers. This only takes two steps.

**Step 1.**

I've included an artisan command to generate a custom column skeleton:

```bash
php artisan backpack:import-column ExampleColumn
```

This will generate a blank import column for you at ```app\Imports\Columns```.

```php
<?php

namespace App\Imports\Columns;

use RedSquirrelStudio\LaravelBackpackImportOperation\Columns\ImportColumn;

class ExampleColumn extends ImportColumn
{
    public function output(): mixed
    {
        return $this->data;
    }
}
```

When building your custom column you have access to ```$this->data``` which is the
input from the spreadsheet column. You can also access the configuration for the import
column using ```$this->getConfig()``` and the model that you are importing using ```$this->getModel()```.

Process the data how you need to and return it from the ```output()``` function.

**Voila!**

By default, the column type name will take the first part of the class name
for example, if you had ExampleColumn, the label would be
'Example'. You can customise this by returning a string from
the ```getName()``` function in your column.

![Screenshot of a column type label](https://raw.githubusercontent.com/redsquirrelstudio/laravel-backpack-import-operation/dev/assets/column-type-label.jpg?raw=true)

**Step 2.**

Add your new class to the file at ```config/backpack/operations/import.php``` under
the ```'column_aliases'``` array. The key should be what you specify as the column type in
```setupImportOperation```

```php
    //...
    // Aliases for import column types to be used in operation setup
    'column_aliases' => [
        'array' => Columns\ArrayColumn::class,
        'boolean' => Columns\BooleanColumn::class,
        'date' => Columns\DateColumn::class,
        'number' => Columns\NumberColumn::class,
        'text' => Columns\TextColumn::class,
        'column_type' => App\Imports\Columns\ExampleColumn::class
    ]
```

***Boom***

Your column is ready for action.

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

## Custom Import Classes

If you don't want to use the column mapping interface, you also have the option
to specify your own import class. To do this, create your import class using:

```
php artisan make:import <YourImportName>
```

You can then follow the [```maatwebsite/excel``` documentation][link-laravel-excel]
to build an import with finer control if required.

This package provides an interface ```WithCrudSupport``` for your custom import classes that allows your IDE to
grab the method stubs providing you with the import log ID and validation class.

```php
use RedSquirrelStudio\LaravelBackpackImportOperation\Interfaces\WithCrudSupport;

class CustomImport implements OnEachRow, WithCrudSupport
{
    public function __construct(int $import_log_id, ?string $validator = null)
    {

    }

    public function onRow(Row $row)
    {
        $row = $row->toArray();
        //Your import handling
```

This may make life easier, add it or don't, I'm not your boss!

Once you've created your beautiful import class, set it using
this function within the ```setupImportOperation``` function:

```php
    protected function setupImportOperation()
    {
        $this->setImportHandler(CustomImport::class);
        //Some column config...
```

## Disabling User Mapping 

Sometimes, you may not want the user to map their columns, or just need a fast import.
In these cases, you can disable the user mapping step.

When user mapping is disabled, the import handler will match the spreadsheet headings to your CRUD column config.

For example:
A spreadsheet column called "name" or "Name" would be matched with this config:

```php
CRUD::addColumn([
    'name' => 'name',
    'label' => 'Name',
    'type' => 'text',
]);
```

To enable this behaviour, add this one line
of code to the ```setupImportOperation()``` function:

```php
    //...
    protected function setupImportOperation()
    {
        $this->disableUserMapping();
    //...
```

## Delete Spreadsheet on Completion
By default, the uploaded spreadsheet will remain in storage after an import is completed.
This is useful for debugging/logging purposes but may not suit your requirements.
If you would like your file to be deleted after an import is complete, add this one line
of code to the ```setupImportOperation()``` function:
```php
  //...
    protected function setupImportOperation()
    {
        $this->deleteFileAfterImport();
    //...
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
        $this->queueImport();
    //...
```

Of course, for this to work, you will need to set up a queue for
your application to dispatch jobs to, to do that, [follow Laravel's
official docs][link-laravel-queue-docs].

## Configuration

### File Uploads

By default, spreadsheets will be stored in your default disk
at the path /imports. but this can be altered either by changing the following
env variables:

```dotenv
FILESYSTEM_DISK="s3"
BACKPACK_IMPORT_FILE_PATH="/2023/application-name/imports"
```

Or by directly changing the options within ```config/backpack/operations/import.php```.

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

or changing the value directly within ```config/backpack/operations/import.php```.

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
model at ```config/backpack/operations/import.php```.

```php
//...
return [
    'import_log_model' => ImportLog::class,
    //...
```

### Translations

You can update the operation translations if required. To do this run:

```bash
php artisan vendor:publish --tag=laravel-backpack-import-operation-translations
```

this will publish the operation lang files to ```resources/lang/vendor/backpack/import-operation```
The files stored in this directory take priority over the package's default lang files.

### Views

You can update the operation views if required. To do this run:

```bash
php artisan vendor:publish --tag=laravel-backpack-import-operation-views
```

this will publish the operation blade files to ```resources/views/vendor/backpack/import-operation```
The files stored in this directory take priority over the package's default views.

## Events
This package dispatches events at different points during the import process.
This way you can track when import rows fail, succeeds, and when an import starts and ends.

### Import Started Event
This event is fired when an import begins processing.
##### Class:
```php
RedSquirrelStudio\LaravelBackpackImportOperation\Events\ImportStartedEvent::class
```
##### Payload:
```php
[
    //The Import being processed 
   'import_log' => RedSquirrelStudio\LaravelBackpackImportOperation\Models\ImportLog::class 
]
```

### Import Completed Event
This event is fired when an import has been completed.
##### Event Class:
```php
RedSquirrelStudio\LaravelBackpackImportOperation\Events\ImportCompleteEvent::class
```
##### Payload:
```php
[
    //The Completed Import
   'import_log' => RedSquirrelStudio\LaravelBackpackImportOperation\Models\ImportLog::class 
]
```

### Import Row Processed Event
Each time a row is successfully processed, this event is fired.
##### Event Class:
```php
RedSquirrelStudio\LaravelBackpackImportOperation\Events\ImportRowProcessedEvent::class
```
##### Payload:
```php
[
    //The Import being processed 
   'import_log' => RedSquirrelStudio\LaravelBackpackImportOperation\Models\ImportLog::class,
   //The data from the spreadsheet row
   'row_data' => array(),
   //The created/update model from the row
   'entry' => \Illuminate\Database\Eloquent\Model::class 
]
```

### Import Row Skipped Event
When a row fails validation and is skipped, this event is fired.
##### Event Class:
```php
RedSquirrelStudio\LaravelBackpackImportOperation\Events\ImportRowSkippedEvent::class
```
##### Payload:
```php
[
    //The Import being processed 
   'import_log' => RedSquirrelStudio\LaravelBackpackImportOperation\Models\ImportLog::class,
   //The data from the spreadsheet row
   'row_data' => array(),
]
```

## Restricting Access
Like most operations in Backpack, you can restrict user access using the following line of code in your CRUD Controller's setup function:
```php
    public function setup()
    {
        //...
        CRUD::denyAccess('import');
        //...
    }
```


## Credits

- [Lewis Raggett][link-me] and [The Team at Sprechen][link-sprechen]  :: Package Creator
- [Cristian Tabacitu][link-backpack] :: Backpack for Laravel Creator
- [Spartner][link-laravel-excel] :: Laravel Excel Creator

## License

MIT. Please see the [license file](license.md) for more information.

[ico-version]: https://img.shields.io/packagist/v/redsquirrelstudio/laravel-backpack-import-operation?style=flat-square

[ico-license]: https://img.shields.io/badge/license-dual-blue?style=flat-square

[link-packagist]: https://packagist.org/packages/redsquirrelstudio/laravel-backpack-import-operation

[ico-downloads]: https://img.shields.io/packagist/dt/redsquirrelstudio/laravel-backpack-import-operation.svg?style=flat-square

[link-downloads]: https://packagist.org/packages/redsquirrelstudio/laravel-backpack-import-operation

[link-laravel-excel]: https://laravel-excel.com

[link-carbon]: https://carbon.nesbot.com/docs

[link-laravel-queue-docs]: https://laravel.com/docs/queues

[link-backpack]: https://github.com/Laravel-Backpack

[link-me]: https://github.com/redsquirrelstudio

[link-sprechen]: https://sprechen.co.uk
