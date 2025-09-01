<div>
    <div class="card">
        <div class="card-body">
            <div class="mb-3">
                <input
                    id="search"
                    type="text"
                    class="form-control"
                    placeholder="Type to search"
                    wire:model.live.debounce.300ms="query"
                >
            </div>
        
            <ul class="list-group">
                @if ($query)
                    @forelse ($employees as $employee)
                        <li
                            class="list-group-item d-flex justify-content-between align-items-center"
                            wire:click="selectEmployee({{ $employee->id }})"
                            style="cursor: pointer; font-size: 1rem; color: #212529;"
                        >
                            {{ $employee->name }}
                            <span class="badge bg-primary">Select</span>
                        </li>
                    @empty
                        <li class="list-group-item text-muted">No employees found.</li>
                    @endforelse
                @endif
            </ul>

            @if ($selectedEmployee)
            <div class="col-md-6 col-lg-12">
                <div class="card">
                    <div class="card-body p-4 text-center">
                        <span class="avatar avatar-xl mb-3 rounded">
                            {{ strtoupper(substr($selectedEmployee->name, 0, 1)) }}
                        </span>
                        <h3 class="m-0 mb-5">
                            <a href="#">{{ $selectedEmployee->name }}</a>
                        </h3>

                        <!-- Date Range Input -->
                        <!-- Date Range Input -->
                        <div class="d-flex justify-content-center gap-2">
                            <div class="mb-3">
                                <label for="startDate" class="form-label">Start Date:</label>
                                <input 
                                    type="date" 
                                    id="startDate" 
                                    class="form-control" 
                                    wire:model.lazy="startDate"
                                >
                            </div>

                            <div class="mb-3">
                                <label for="endDate" class="form-label">End Date:</label>
                                <input 
                                    type="date" 
                                    id="endDate" 
                                    class="form-control" 
                                    wire:model.lazy="endDate"
                                >
                            </div>
                        </div>
                    
                        <!-- Attendance Logs Table -->
                        <div class="card mt-3 test-y">
                            <div class="card-body p-0">
                                <div id="table-default" class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                                    <table class="table table-striped">
                                        <thead class="thead-dark">
                                            <tr>
                                                <th>Time </th>
                                                <th>Type</th>
                                                <th>Branch</th>
                                                <th>Device</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($attendanceLogs as $log)
                                                <tr>
                                                    <td>{{ \Carbon\Carbon::parse($log->time_in)->format('F d. Y g:i A') }}</td>
                                                    <td>
                                                        @switch($log->type)
                                                            @case(0)
                                                                Check In
                                                                @break
                                                            @case(1)
                                                                Check Out
                                                                @break
                                                            @case(4)
                                                                OT In
                                                                @break
                                                            @case(5)
                                                                OT Out
                                                                @break
                                                            @default
                                                                Unknown
                                                        @endswitch
                                                    </td>
                                                    <td>{{ $log->branch->name }}</td>
                                                    <td>{{ $log->device->device_name }}</td>
                                                    <td>
                                                        <a href="#" 
                                                           class="openEditModal" wire:click.prevent="openEditModal({{ $log->id }})">
                                                            <i class="la la-edit"></i> <span>Edit</span>
                                                        </a>                                                    
                                                    </td>                                                    
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="5" class="text-center">No attendance logs found for the selected range.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        Livewire.on('openEditAttendanceModal', function () {
            Swal.fire({
                title: 'Edit Attendance Type',
                html: `
                    <div style="display: flex; flex-direction: column; gap: 10px; text-align: left;">
                        <label for="attendanceTypeSelect">Select Type:</label>
                        <select id="attendanceTypeSelect" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 5px;">
                            <option value="0">Check In</option>
                            <option value="1">Check Out</option>
                            <option value="4">OT In</option>
                            <option value="5">OT Out</option>
                        </select>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: 'Update Type',
                preConfirm: () => {
                    return new Promise((resolve) => {
                        let selectedType = document.getElementById('attendanceTypeSelect').value;

                        if (!selectedType) {
                            Swal.showValidationMessage('Please select a type.');
                            resolve(false); // Prevents closing the modal
                            return;
                        }

                        // Show processing loader
                        Swal.fire({
                            title: 'Processing...',
                            text: 'Please wait while the attendance type is being updated.',
                            icon: 'info',
                            allowOutsideClick: false,
                            showConfirmButton: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });

                        // Listen for Livewire event completion
                        Livewire.on('logTypeUpdated', () => {
                            resolve(true); // Resolves the promise when event is received
                        });

                        // Dispatch the Livewire event with the correct object structure
                        Livewire.dispatch('updateLogType', {
                            type: selectedType
                        });
                    });
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Success!',
                        text: 'Attendance type has been updated.',
                        icon: 'success',
                        showConfirmButton: false,
                        timer: 2000
                    });
                }
            });
        });

        Livewire.on('closeEditAttendanceModal', function () {
            Swal.close();
        });
    });

</script>