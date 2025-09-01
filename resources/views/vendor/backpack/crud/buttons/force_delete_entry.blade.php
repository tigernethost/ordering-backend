@if(method_exists($entry, 'trashed') && $entry->trashed())
    <form method="POST" action="{{ url($crud->route.'/'.$entry->getKey().'/force-delete') }}" style="display:inline" onsubmit="return confirm('Permanently delete this entry? This cannot be undone.');">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-sm btn-link text-danger" data-button-type="force-delete">
            <i class="la la-trash"></i> Force Delete
        </button>
    </form>
@endif
