@if ($crud->hasAccess('import'))
    <a href="{{ url($crud->route.'/import') }}" class="btn btn-secondary">
    <span class="ladda-label">
        <i class="las la-file-upload"></i>
        @lang('import-operation::import.import') {{ $crud->entity_name_plural }}
    </span>
    </a>
@endif
