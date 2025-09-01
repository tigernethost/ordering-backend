@extends(backpack_view('blank'))


@section('content')
<div class="page-wrapper">
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <!-- Page pre-title -->
                    <div class="page-pretitle">
                        Overview
                    </div>
                    <h2 class="page-title">
                        Dashboard
                    </h2>
                    
                    <livewire:branch-selector />
                </div>
                <!-- Page title actions -->
                <div class="col-auto ms-auto d-print-none">
                    <div class="btn-list">
                        <a href="#" class="btn btn-primary d-sm-none btn-icon" data-bs-toggle="modal"
                            data-bs-target="#modal-report" aria-label="Create new report">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round" class="icon">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                <path d="M12 5l0 14"></path>
                                <path d="M5 12l14 0"></path>
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="page-body">
        <div class="container-xl">
            <div class="row row-deck row-cards">

                @if(backpack_auth()->user()->hasRole('Administrator') && config('biometrics.enabled'))
                    <div wire:poll.30s class="col-sm-6 col-lg-6">
                        <livewire:device-online />
                    </div>

                    <div wire:poll.60s class="col-sm-6 col-lg-6">
                        <livewire:employee-in />
                    </div>
                @endif
                
                @if (backpack_auth()->user()->hasRole('Administrator') || backpack_auth()->user()->hasRole('Branch Manager'))

                    <div class="col-12">
                        <livewire:sales-order-metrics />
                    </div>

                    <div class="col-sm-12 col-lg-12">
                        <livewire:lalamove-orders />
                    </div>

                    <div class="col-sm-12 col-lg-12">
                        <livewire:incoming-order />
                    </div>

                    {{-- <div wire:poll.60s class="col-sm-6 col-lg-6">
                        <livewire:order-today />
                    </div> --}}

                    <div class="col-12">
                        <livewire:order-calendar />
                    </div>

                @endif

            </div>
        </div>
    </div>
</div>
@endsection

@push('before_scripts')
    @vite('resources/js/app.js')
@endpush

@push('after_scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- Add a pop notification sound -->
<audio id="notif-sound" src="https://notificationsounds.com/storage/sounds/file-sounds-1150-pristine.mp3" preload="auto" loop></audio>


<script>
    document.addEventListener('DOMContentLoaded', function () {
        const userBranchIds = @json(backpack_auth()->user()->branches->pluck('id'));
        window.Echo.channel('orders')
            .listen('.NewOrderPlaced', (data) => {
                // Check if the order's branch_id is in the user's branch list
                if (!userBranchIds.includes(data.order.branch_id)) {
                    return; // Skip notification if not from a user's branch
                }

          const audio = document.getElementById('notif-sound');

            // Play the looping sound
            audio.play().catch(err => {
                console.warn('🔇 Autoplay blocked:', err);
            });

                Swal.fire({
                    title: 'New Order Received',
                    html: `
                        <div style="font-size: 14px;">
                            <p><strong>Order ID:</strong> ${data.order.id}</p>
                            <p><strong>Branch:</strong> ${data.order.branch_name}</p>
                            <p><strong>Customer:</strong> ${data.order.customer_name}</p>
                            <p><strong>Total Amount:</strong> ₱${data.order.total_amount}</p>
                            <p><strong>Status:</strong> ${data.order.order_type == 'delivery' ? 'For Delivery' : 'For Pickup'}</p>
                        </div>
                    `,
                    icon: 'info',
                    showConfirmButton: true,
                    confirmButtonText: 'OK',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    width: '300px'
                }).then(() => {
                    location.reload();
                });
            });
    });

</script>

@endpush

