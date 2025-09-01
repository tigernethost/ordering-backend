<div class="page-body">
    <div class="container-xl">
        <div class="col-lg-12 mb-5">
            <div class="card">
              <div class="card-body">
                <h3 class="card-title">Order Location</h3>
                <div class="ratio ratio-21x9">
                    {{-- {{ $order->order_type === 'delivery' && $order->orderQuotation }} --}}
                    @if ($order->order_type === 'delivery' && $order->orderQuotation)
                        @if ($order->lalamovePlacedOrder && $order->lalamovePlacedOrder)
                            <div id="map" style="width: 100%; height: 100%;"></div>
                        @endif

                        
                    @else
                        <div>
                            No Available Order Location
                        </div>
                    @endif

                    
                </div>                
              </div>
            </div>
        </div>
        
        <div class="col-md-6 col-lg-12">
            <div class="card">
                <div class="card-body p-4 text-center">
                    <h3 class="m-0 mb-1"><a href="#"> Order ID </a></h3>
                    <div class="text-secondary mb-2">{{ $order->order_id }}</div>


                    <div class="accordion mt-3" id="accordion-example">
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="heading-1">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-1" aria-expanded="false">
                                    Order Information
                                </button>
                            </h2>
                            <div id="collapse-1" class="accordion-collapse collapse" data-bs-parent="#accordion-example" style="">
                                <div class="accordion-body pt-0">
                                    <table class="table table-striped table-bordered">
                                        <tbody>
                                            <tr>
                                                <th scope="row">Order Type</th>
                                                <th scope="row">Amount</th>
                                                <th scope="row">Delivery Fee</th>
                                                <th scope="row">Status</th>
                                                <th scope="row">Payment Method</th>
                                                <th scope="row">Order Created</th>
                                                {{ $isReservation }}
                                                @if ($isReservation)
                                                    <th scope="row">Reserved At</th>
                                                @endif
                                            </tr>
                                            <tr>
                                                <td>{{ ucfirst($order->order_type) }}</td>
                                                <td>{{ $order->total_amount }}</td>
                                                <td>{{ $order->delivery_fee }}</td>
                                                <td>{{ ucfirst($order->status) }}</td>
                                                <td>{{ $order->multisysPayment ? $order->multisysPayment->payment_channel : 'NA' }}</td>

                                                <td>{{ $order->created_at }}</td>
                                                @if ($isReservation)
                                                    <td>{{ $order->reservation->reservation_date }}</td>
                                                @endif
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="accordion mt-3" id="accordion-example">
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="heading-1">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-2" aria-expanded="false">
                                    Order Items
                                </button>
                            </h2>
                            <div id="collapse-2" class="accordion-collapse collapse" data-bs-parent="#accordion-example" style="">
                                <div class="accordion-body pt-0">
                                    <table class="table table-striped table-bordered">
                                        <tbody>
                                            <tr>
                                                <th scope="row">Image</th>
                                                <th scope="row">Product</th>
                                                <th scope="row">Quantity</th>
                                                <th scope="row">Price</th>
                                            </tr>
                                            @foreach ($orderItems as $item)
                                                <tr>
                                                    <td><img src="{{ $item->product?->image_thumbnail }}" width="70px" height="70px" alt="product"></td>
                                                    <td style="text-align: center; vertical-align: middle;">{{ $item->product?->name }}</td>
                                                    <td style="text-align: center; vertical-align: middle;">{{ $item->quantity }}</td>
                                                    <td style="text-align: center; vertical-align: middle;">{{ $item->price }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="accordion mt-3" id="accordion-example">
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="heading-1">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-3" aria-expanded="false">
                                    Customer Information
                                </button>
                            </h2>
                            <div id="collapse-3" class="accordion-collapse collapse" data-bs-parent="#accordion-example" style="">
                                <div class="accordion-body pt-0">
                                    <table class="table table-striped table-bordered">
                                        <tbody>
                                            <tr>
                                                <th scope="row">Name</th>
                                                <th scope="row">Email</th>
                                                <th scope="row">Phone</th>
                                                <th scope="row">Address Line</th>
                                                <th scope="row">City</th>
                                                <th scope="row">Province</th>
                                                <th scope="row">Region</th>
                                            </tr>
                                            <tr>
                                                <td>{{ $order->customer?->first_name . ' ' . $order->customer?->first_name }}</td>
                                                <td>{{ $order->customer?->email }}</td>
                                                <td>{{ $order->customer?->phone }}</td>
                                                <td>{{ $order->customer?->address }}</td>
                                                <td>{{ $order->customer?->city }}</td>
                                                <td>{{ $order->customer?->province }}</td>
                                                <td>{{ $order->customer?->region }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

@push('after_scripts')
<script src="{{ env('MAP_JS_SRC') }}" async></script>

<script>
  var marker;

  const customerLat = {{ $order->latitude ?? 15.04426482138687 }};
  const customerLng = {{ $order->longitude ?? 120.68958740315281 }};

  const driverLat = @json($driverLat ?? null);
  const driverLng = @json($driverLng ?? null);

  function initMap() {
    const customerLocation = { lat: customerLat, lng: customerLng };

    const map = new google.maps.Map(document.getElementById("map"), {
      zoom: 15,
      center: customerLocation,
      mapId: '8f49a54f3d37eefa',
    });

    new google.maps.Marker({
      map,
      position: customerLocation,
      title: "Customer Location",
      icon: "http://maps.google.com/mapfiles/ms/icons/blue-dot.png"
    });

    if (driverLat && driverLng) {
      const driverLocation = { lat: parseFloat(driverLat), lng: parseFloat(driverLng) };

      new google.maps.Marker({
        map,
        position: driverLocation,
        title: "Driver Location",
        icon: "http://maps.google.com/mapfiles/ms/icons/green-dot.png"
      });

      const bounds = new google.maps.LatLngBounds();
      bounds.extend(customerLocation);
      bounds.extend(driverLocation);
      map.fitBounds(bounds);
    }
  }
</script>

@endpush
