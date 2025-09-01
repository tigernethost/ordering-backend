{{-- resources/views/admin/fields/roles_permissions_column.blade.php --}}
@php
    // Fetch roles and permissions
    $roles = \Spatie\Permission\Models\Role::all();
    $permissions = \Spatie\Permission\Models\Permission::all()->groupBy('group');

    $userRoles = old('roles', isset($entry) ? $entry->roles->pluck('id')->toArray() : []);
    $userPermissions = old('permissions', isset($entry) ? $entry->permissions->pluck('id')->toArray() : []);

    // Permissions inherited via roles
    $permissionsViaRoles = isset($entry) ? $entry->getPermissionsViaRoles()->pluck('id')->toArray() : [];
@endphp

<div class="form-group">
    {{-- Display the label --}}
    @if (!empty($field['label']))
        <label>{!! $field['label'] !!}</label>
    @endif

    {{-- Roles --}}
    <h4>Roles</h4>
    <div class="row">
        @foreach ($roles as $role)
            <div class="col-md-3">
                <div class="form-check">
                    <input
                        type="checkbox"
                        name="roles[{{ $role->id }}]"
                        value="{{ $role->id }}"
                        class="form-check-input role-checkbox"
                        id="role_{{ $role->id }}"
                        {{ in_array($role->id, $userRoles) ? 'checked' : '' }}
                        data-role-permissions="{{ json_encode($role->permissions->pluck('id')->toArray()) }}"
                    >
                    <label class="form-check-label" for="role_{{ $role->id }}">
                        {{ $role->name }}
                    </label>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Permissions --}}
    <h4>Permissions</h4>
    @foreach ($permissions as $group => $groupPermissions)
        <h5>{{ ucfirst($group) }}</h5>
        <div class="row">
            @foreach ($groupPermissions as $permission)
                <div class="col-md-3">
                    <div class="form-check">
                        <input
                            type="checkbox"
                            name="permissions[{{ $permission->id }}]"
                            value="{{ $permission->id }}"
                            class="form-check-input permission-checkbox"
                            id="permission_{{ $permission->id }}"
                            {{ in_array($permission->id, $userPermissions) ? 'checked' : '' }}
                            {{ in_array($permission->id, $permissionsViaRoles) ? 'data-inherited="true"' : '' }}
                        >
                        <label class="form-check-label {{ in_array($permission->id, $permissionsViaRoles) ? 'inherited-permission' : '' }}" for="permission_{{ $permission->id }}">
                            {{ $permission->name }}
                        </label>
                    </div>
                </div>
            @endforeach
        </div>
    @endforeach
</div>

<style>
    .inherited-permission {
        color: #6c757d; /* Gray text for inherited permissions */
    }
    .form-check-input[data-inherited="true"] {
        accent-color: #007bff; /* Blue color for inherited permissions */
        cursor: not-allowed; /* Prevent interaction */
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Handle role checkbox changes
        document.querySelectorAll('.role-checkbox').forEach(roleCheckbox => {
            roleCheckbox.addEventListener('change', function () {
                const rolePermissions = JSON.parse(this.dataset.rolePermissions || '[]');
                rolePermissions.forEach(permissionId => {
                    const permissionCheckbox = document.querySelector(`#permission_${permissionId}`);
                    if (permissionCheckbox) {
                        if (this.checked) {
                            permissionCheckbox.checked = true;
                            permissionCheckbox.setAttribute('data-inherited', 'true');
                        } else {
                            // Only uncheck if it wasn't manually checked
                            if (!permissionCheckbox.dataset.manual) {
                                permissionCheckbox.checked = false;
                                permissionCheckbox.removeAttribute('data-inherited');
                            }
                        }
                    }
                });
            });
        });

        // Handle manual permission changes
        document.querySelectorAll('.permission-checkbox').forEach(permissionCheckbox => {
            permissionCheckbox.addEventListener('change', function () {
                if (this.checked) {
                    this.dataset.manual = true; // Mark as manually checked
                    this.removeAttribute('data-inherited'); // Remove inherited marker
                } else {
                    delete this.dataset.manual; // Remove manual marker
                }
            });
        });
    });
</script>
