{{-- resources/views/admin/fields/permission_column.blade.php --}}
@php
    use Illuminate\Support\Str;

    // Fetch grouped permissions
    $permissions = \Spatie\Permission\Models\Permission::all()->groupBy('group');
    $selectedPermissions = old('permissions', isset($entry) ? $entry->permissions->pluck('id')->toArray() : []);
@endphp

<div class="form-group">
    {{-- Display the label --}}
    @if (!empty($field['label']))
        <label>{!! $field['label'] !!}</label>
    @endif

    {{-- Permissions grouped by their categories --}}
    @foreach ($permissions as $group => $groupPermissions)
        <h4>
            {{ ucfirst($group) }}
            <button type="button" class="btn btn-sm btn-link select-all-group" data-group="{{ Str::slug($group) }}">
                Select All
            </button>
        </h4>
        <div class="row" data-group="{{ Str::slug($group) }}">
            @foreach ($groupPermissions as $permission)
                <div class="col-md-3"> {{-- Adjust column size as needed --}}
                    <div class="form-check">
                        <input
                            type="checkbox"
                            name="permissions[{{ $permission->id }}]"
                            value="{{ $permission->id }}"
                            class="form-check-input group-permission"
                            id="permission_{{ $permission->id }}"
                            {{ in_array($permission->id, $selectedPermissions) ? 'checked' : '' }}
                            data-group="{{ Str::slug($group) }}"
                        >
                        <label class="form-check-label" for="permission_{{ $permission->id }}">
                            {{ $permission->name }}
                        </label>
                    </div>
                    <br>
                </div>
            @endforeach
        </div>
    @endforeach
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Handle "Select All" button click for each group
        document.querySelectorAll('.select-all-group').forEach(button => {
            button.addEventListener('click', function () {
                const group = this.dataset.group;
                const checkboxes = document.querySelectorAll(`.group-permission[data-group="${group}"]`);
                const allChecked = Array.from(checkboxes).every(checkbox => checkbox.checked);
                
                checkboxes.forEach(checkbox => {
                    checkbox.checked = !allChecked; // Toggle based on current state
                });
            });
        });
    });
</script>
