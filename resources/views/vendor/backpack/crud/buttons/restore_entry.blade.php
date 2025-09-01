@if(method_exists($entry, 'trashed') && $entry->trashed())
    <form method="POST" action="{{ url($crud->route.'/'.$entry->getKey().'/restore') }}" style="display:inline">
        @csrf
        <button type="submit" class="btn btn-sm btn-link" data-button-type="restore">
            <i class="la la-undo"></i> Restore
        </button>
    </form>
@endif
