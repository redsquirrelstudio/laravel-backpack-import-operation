@extends(backpack_view('blank'))

@php
    $defaultBreadcrumbs = [
      trans('backpack::crud.admin') => url(config('backpack.base.route_prefix'), 'dashboard'),
      $crud->entity_name_plural => url($crud->route),
      trans('import-operation::import.import') => url($crud->route.'/import'),
      trans('import-operation::import.map_fields') => url($crud->route.'/import/'.$import->id,'/map'),
      trans('import-operation::import.confirm_import') => url($crud->route.'/import/'.$import->id,'/confirm'),
    ];

    // if breadcrumbs aren't defined in the CrudController, use the default breadcrumbs
    $breadcrumbs = $breadcrumbs ?? $defaultBreadcrumbs;
@endphp

@section('header')
    <section class="container-fluid">
        <h2>
            <span class="text-capitalize">
                {!! $crud->getHeading() ?? $crud->entity_name_plural !!}
            </span>
            <small>
                {!! $crud->getSubheading() ?? trans('import-operation::import.import').' '.$crud->entity_name_plural !!}
                .
            </small>

            @if ($crud->hasAccess('list'))
                <small>
                    <a href="{{ url($crud->route) }}" class="d-print-none font-sm">
                        <i class="la la-angle-double-{{ config('backpack.base.html_direction') == 'rtl' ? 'right' : 'left' }}"></i>
                        {{ trans('backpack::crud.back_to_all') }}
                        <span>
                            {{ $crud->entity_name_plural }}
                        </span>
                    </a>
                </small>
            @endif
        </h2>
    </section>
@endsection

@section('content')

    <div class="row">
        <div class="col-md-10">
            {{-- Default box --}}

            @include('crud::inc.grouped_errors')

            <form method="post"
                  action="{{ url($crud->route.'/import/'.$import->id.'/confirm') }}"
                  enctype="multipart/form-data"
            >
                {!! csrf_field() !!}
                {{-- load the view from the application if it exists, otherwise load the one in the package --}}
                <div class="card">
                    <div class="card-body row">
                        <div class="col-md-12">
                            <h5>
                                @lang('import-operation::import.confirm_your_import')
                            </h5>

                            <table
                                class="table  nowrap rounded card-table table-vcenter card-table shadow-xs border-xs">
                                <thead>
                                <tr>
                                    <th>
                                        @lang('import-operation::import.import_data_from')
                                    </th>
                                    <th colspan="2">
                                        @lang('import-operation::import.into_field')
                                    </th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($import->config as $heading => $columns)
                                    @foreach($columns as $column)
                                        <tr>
                                            @if($loop->index === 0)
                                                <td class="border-right" rowspan="{{ count($columns) }}">
                                                    {{ ucfirst(str_replace('_', ' ', $heading)) }}
                                                </td>
                                            @endif
                                            <td>
                                                {{ $column['label'] }}
                                            </td>
                                            <td>
                                                @if(in_array($column['type'], array_keys(config('backpack.operations.import.column_aliases'))))
                                                    @php
                                                        $aliases = config('backpack.operations.import.column_aliases');
                                                        $class = $aliases[$column['type']];
                                                    @endphp

                                                    {{ (new $class(''))->getName() }}
                                                @else
                                                    {{ (new $column['type'](''))->getName() }}
                                                @endif
                                                @if($column['name'] === $import->model_primary_key)
                                                    <small class="text-primary">
                                                        [@lang('import-operation::import.primary_key')]
                                                    </small>
                                                @endif
                                            </td>
                                        </tr>
                                        @endforeach
                                    @endforeach
                                </tbody>
                            </table>
                            <div class="mt-2">
                                @if($import->file_url)
                                    <a title="@lang('import-operation::import.click_here_to_download_file')"
                                       href="{{ $import->file_url }}" download>
                                        <span class="ladda-label">
                                            <i class="las la-download"></i>
                                             @lang('import-operation::import.click_here_to_download_file')
                                        </span>
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                {{-- This makes sure that all field assets are loaded. --}}
                <div class="d-none" id="parentLoadedAssets">{{ json_encode(Basset::loaded()) }}</div>
                <div class="d-flex">
                    <button title="@lang('import-operation::import.confirm_selection')"
                            class="btn btn-success mr-2">
                        <span class="ladda-label">
                            <i class="las la-file-upload"></i>
                            @lang('import-operation::import.confirm_import')
                        </span>
                    </button>
                    <a title="@lang('import-operation::import.remap_import')" class="btn btn-secondary"
                       href="{{ url($crud->route.'/import/'.$import->id.'/map') }}">
                        <span class="ladda-label">
                            <i class="las la-times-circle"></i>
                            @lang('import-operation::import.remap_import')
                        </span>
                    </a>
                </div>

            </form>
        </div>
    </div>

@endsection

