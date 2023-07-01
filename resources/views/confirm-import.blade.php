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
                            @include('import-operation::inc.mapper-headings')
                            <div class="border p-1" style="height: 50vh; overflow-y: auto; overflow-x:hidden">
                                @foreach($import->config as $heading => $column)
                                    <div class="row mb-2">
                                        <div class="col-md-6">
                                            <div class="card" style="height: 100%;">
                                                <div
                                                    class="card-body py-0 d-flex flex-column justify-content-center px-3">
                                                    <p class="font-xl m-0">
                                                        {{ ucfirst(str_replace('_', ' ', $heading)) }}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="card" style="height: 100%;">
                                                <div
                                                    class="card-body py-0 d-flex flex-column align-items-center justify-content-center py-1 px-3">
                                                    <i class="las la-arrow-right d-none d-md-block font-5xl text-muted"></i>
                                                    <i class="las la-arrow-down d-md-none font-5xl text-muted"></i>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div
                                                class="card @if($column['name'] === $import->model_primary_key) border-primary @endif"
                                                style="height: 100%;">
                                                <div
                                                    class="card-body py-0 d-flex flex-column justify-content-center px-3">
                                                    <p class="text-uppercase text-muted m-0 font-xs">
                                                        {{ $crud->entity_name }}
                                                    </p>
                                                    <h4 class="m-0 font-xl">
                                                        {{ $column['label'] }}
                                                    </h4>
                                                    <strong class="text-muted">
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
                                                    </strong>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
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
                <div class="d-none" id="parentLoadedAssets">{{ json_encode(Assets::loaded()) }}</div>

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

