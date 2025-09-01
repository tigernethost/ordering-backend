@if ($crud->hasAccess('update', $entry))
    <a href="javascript:void(0)" 
        class="btn btn-sm btn-link change-status" 
        data-id="{{ $entry->id }}" 
        data-type="{{ $entry->order_type }}" 
        data-route="{{ $crud->route }}"
        onclick="showUpdateModal(this, '{{ url($crud->route.'/change-status') }}')">
        <i class="la la-refresh"></i> <span>Change Status</span>
    </a>
@endif

@push('after_scripts') @if (request()->ajax()) @endpush @endif
@bassetBlock('backpack/crud/buttons/update-status-button-'.app()->getLocale().'.js')
<script>
    if (typeof showUpdateModal !== 'function') {
        function showUpdateModal(element, url) {
            const orderType = element.getAttribute('data-type');
            const allOptions = {
                processing: 'Order is being processed',
                for_pickup: 'Ready For Pickup',
                for_delivery: 'For Delivery',
                complete: 'Order Complete'
            };

            const filteredOptions = {};
            if (orderType === 'pickup') {
                Object.keys(allOptions).forEach(key => {
                    if (key !== 'for_delivery') {
                        filteredOptions[key] = allOptions[key];
                    }
                });
            } else if (orderType === 'delivery') {
                Object.keys(allOptions).forEach(key => {
                    if (key !== 'for_pickup') {
                        filteredOptions[key] = allOptions[key];
                    }
                });
            }

            if (Object.keys(filteredOptions).length === 0) {
                swal({
                    title: "No available status options",
                    text: "Please check the order type and try again.",
                    icon: "info",
                    buttons: {
                        confirm: {
                            text: "OK",
                            visible: true,
                            className: "bg-primary",
                            closeModal: true
                        }
                    }
                });
                return;
            }

            const select = document.createElement('select');
            select.classList.add('swal-select');
            Object.keys(filteredOptions).forEach(key => {
                const option = document.createElement('option');
                option.value = key;
                option.textContent = filteredOptions[key];
                select.appendChild(option);
            });

            swal({
                title: "Change Status",
                content: select,
                buttons: {
                    cancel: {
                        text: "Cancel",
                        value: null,
                        visible: true,
                        className: "bg-secondary",
                        closeModal: true
                    },
                    confirm: {
                        text: "Update",
                        value: true,
                        visible: true,
                        className: "bg-primary"
                    }
                }
            }).then(value => {
                if (value) {
                    const selectedStatus = select.value;
                    if (!selectedStatus) {
                        swal({
                            title: "Error",
                            text: "You need to select a status.",
                            icon: "error"
                        });
                        return;
                    }
                    const entryId = element.getAttribute('data-id');
                    submitForm(url, entryId, selectedStatus);
                }
            });
        }

        function submitForm(action, entryId, status) {
            const form = document.createElement('form');
            form.action = action;
            form.method = 'POST';
            form.style.display = 'none';

            const csrfInput = document.createElement('input');
            csrfInput.name = '_token';
            csrfInput.value = '{{ csrf_token() }}';
            csrfInput.type = 'hidden';
            form.appendChild(csrfInput);

            const idInput = document.createElement('input');
            idInput.name = 'id';
            idInput.value = entryId;
            idInput.type = 'hidden';
            form.appendChild(idInput);

            const statusInput = document.createElement('input');
            statusInput.name = 'status';
            statusInput.value = status;
            statusInput.type = 'hidden';
            form.appendChild(statusInput);

            document.body.appendChild(form);
            form.submit();
        }
    }
</script>
@endBassetBlock
@if (!request()->ajax()) @endpush @endif
