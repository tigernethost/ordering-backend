
<style type="text/css">
	* {
		box-sizing: border-box;
	}

	body {
		margin: 0;
		font-family: Arial, Helvetica, sans-serif;

	}

	.bg-primary {
		background-color: #2d3a4c !important;
	}

	.text-decoration-underline {
		text-decoration: underline;
	}

	.text-primary {
		color: #fca70b !important;
	}

	.text-brown {
		color: #532f17 !important;
	}

	.text-dark {
		color: #343a40 !important;
	}

	.text-white {
		color: #fff !important;
	}

	.text-center {
		text-align: center;
	}

	.text-left {
		text-align: left;
	}

	.text-right {
		text-align: right;
	}

	.d-block {
		display: block;
	}

	.m-0 {
		margin: 0;
		margin-bottom: 4px;
	}

	.m-l-1 {
		margin-left: 10pxl
	}

	.m-r-1 {
		margin-right: 10pxl
	}

	.wrapper {
		height: 100%; 
		min-width: 600px;
		width: auto; 
		background: #c3ced1; 
		padding: 60px;
		overflow-y: auto;
	}

	.second-wrapper {
		height: auto; 
		width: 80%; 
		background: #FFF; 
		margin: auto;
		position: relative;
	}
	
	.header {
		color: #FFF;
	}

	.table {
		width: 100%;
		/*border: 1px solid #ccc;*/
		padding: 10px;
	}

	.table td {
		font-size: 13px;
		vertical-align: top;
	}

	.items {
		background: #f4f6f7;
		margin-top: 30px;
	}
	
	.items table td {
		font-size: 14px;
		padding-top: 3px;
		padding-bottom: 3px;
	}

	.body {
		background: #FFF;
	}

	.footer {
		/*position: absolute;*/
		background: #FFF;
		padding: 20px;
		width: 100%;
	}

	.m-t-0 {
		margin-top: 0 !important;
	}

	.m-b-0 {
		margin-bottom: 0 !important;
	}

	pre {
  		overflow: hidden; 
  		white-space: break-spaces; 
  		word-break: break-word;
		padding-left: 50px;
		padding-right: 50px;
  	}
</style>

<div class="wrapper" style="height: 100%; min-width: 600px; width: auto; background: #c3ced1; padding: 60px; overflow-y: auto;">
	
	<div class="second-wrapper" style="height: auto; width: 80%; background: #FFF; margin: auto; position: relative;">
		
		<!-- <div class="header" style="padding: 20px; background: #156dcc"> -->
		<div class="header" style="padding: 20px; color: #FFF; text-align: center !important;">
			<img src="{{ url($imageUrl) }}" alt="Business Logo" width="100" style="display: block; margin: auto;">
			<h3 style="text-align: center !important; color: #532f17 !important; margin-bottom: 0 !important;">
				Makimura Ramen
			</h3>
			<p style="text-align: center !important; color: #532f17 !important; margin-bottom: 0 !important; font-size: 12px;">
				Makimura Address Here
			</p>
		</div>

		<div class="body" style="padding: 20px 20px 40px 20px; background: #FFF; margin: 0; font-family: Arial, Helvetica, sans-serif;">

			<!-- TRANSACTION MESSAGE (STATUS) -->
			<div class="items" style="padding: 20px; background: #f4f6f7; margin-top: 30px;">
				<table class="table" style=" width: 100% !important; padding: 10px; !important;">
					<tbody>
						<tr>
							<td class="text-center text-brown" style="text-align: center !important; color: #532f17 !important; 
								font-size: 14px !important;
								padding-top: 3px !important;
								padding-bottom: 3px !important;">
									<h1>{{ $multisysPayment->response_message ?? 'Payment Process Complete' }}</h1>
							</td>
						</tr>
					</tbody>
				</table>
			</div>

			<div class="items" style="padding: 20px; background: #f4f6f7; margin-top: 30px;">
				<div class="container-fluid text-center" style="text-align: center !important;">
					<table class="table" style="width: 100% !important; padding: 10px; !important;" width="100%">
						<tbody>
							<!-- DATE -->
							<tr>
								<td class="text-left text-brown" style="text-align: left !important; color: #532f17 !important; 
									font-size: 14px !important;
									padding-top: 3px !important;
									padding-bottom: 3px !important;">
										<b>Transaction Date :</b>
								</td>
								<td class="text-right text-brown"  style="text-align: right !important; color: #532f17 !important;
									font-size: 14px !important;
									padding-top: 3px !important;
									padding-bottom: 3px !important;">
									<b>{{ \Carbon\Carbon::parse($multisysPayment->transaction_date)->format('F d, Y')}}</b>
								</td>
							</tr>
							<!-- TIME -->
							<tr>
								<td class="text-left text-brown"  style="text-align: left !important; color: #532f17 !important;
									font-size: 14px !important;
									padding-top: 3px !important;
									padding-bottom: 3px !important;">
										<b>Transaction Time :</b>
								</td>
								<td style="text-align: right !important; color: #532f17 !important;">
									<b>{{ \Carbon\Carbon::parse($multisysPayment->transaction_date)->format('h:i A') }}</b>
								</td>
							</tr>

						</tbody>
					</table>
				</div>
			</div>

			<div class="items" style="padding: 20px; background: #f4f6f7; margin-top: 30px;">
				<div class="container-fluid text-center" style="text-align: center !important;">

					<img src="{{ asset($paymentMethod->logo) }}" alt="Payment Logo" style="max-height: 125px !important;">
					<hr>

					<table class="table" style="width: 100% !important; padding: 10px; !important;" width="100%">
						<tbody>
							<!-- TXNID -->
							<tr>
								<td class="text-left text-brown" style="text-align: left !important; color: #532f17 !important;
									font-size: 14px !important;
									padding-top: 3px !important;
									padding-bottom: 3px !important;">
										<b>TXNID :</b>
								</td>
								<td class="text-right text-brown" style="text-align: right !important; color: #532f17 !important;
									font-size: 14px !important;
									padding-top: 3px !important;
									padding-bottom: 3px !important;">
										<b> {{ $multisysPayment->txnid }} </b>
								</td>
							</tr>
							<!-- REFERENCE NUMBER -->
							<tr>
								<td class="text-left text-brown" style="text-align: left !important; color: #532f17 !important;
									font-size: 14px !important;
									padding-top: 3px !important;
									padding-bottom: 3px !important;">
										<b>Reference Number :</b>
								</td>
								<td class="text-right text-brown" style="text-align: right !important; color: #532f17 !important;
									font-size: 14px !important;
									padding-top: 3px !important;
									padding-bottom: 3px !important;">
										<b> {{ $multisysPayment->refno }} </b>
								</td>
							</tr>
							<!-- AMOUNT -->
							<tr>
								<td class="text-left text-brown" style="text-align: left !important; color: #532f17 !important;
									font-size: 14px !important;
									padding-top: 3px !important;
									padding-bottom: 3px !important;">
									<b>Amount :</b>
								</td>
								<td class="text-right text-brown" style="text-align: right !important; color: #532f17 !important;
									font-size: 14px !important;
									padding-top: 3px !important;
									padding-bottom: 3px !important;">
									<b> PHP {{ $multisysPayment->amount }} </b>
								</td>
							</tr>
						</tbody>
					</table>
				</div>

			</div>

            <!-- Order Information -->
            <div class="items" style="padding: 20px; background: #f4f6f7; margin-top: 30px;">
                <h4 style="color: #532f17;">Order Details</h4>
                <table class="table" style="width: 100% !important; padding: 10px; !important;">
                    <tbody>
                        <!-- Order ID -->
                        <tr>
                            <td class="text-left text-brown" style="text-align: left !important; color: #532f17 !important;">
                                <b>Order ID:</b>
                            </td>
                            <td class="text-right text-brown" style="text-align: right !important; color: #532f17 !important;">
                                {{ $multisysPayment->order->order_id }}
                            </td>
                        </tr>
                        <!-- Order Total -->
                        <tr>
                            <td class="text-left text-brown" style="text-align: left !important; color: #532f17 !important;">
                                <b>Order Total:</b>
                            </td>
                            <td class="text-right text-brown" style="text-align: right !important; color: #532f17 !important;">
                                {{ $multisysPayment->order->total_amount }}
                            </td>
                        </tr>
                        <!-- Order Date -->
                        <tr>
                            <td class="text-left text-brown" style="text-align: left !important; color: #532f17 !important;">
                                <b>Order Date:</b>
                            </td>
                            <td class="text-right text-brown" style="text-align: right !important; color: #532f17 !important;">
                                {{ \Carbon\Carbon::parse($multisysPayment->order->created_at)->format('F d, Y') }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Order Items -->
            <div class="items" style="padding: 20px; background: #f4f6f7; margin-top: 30px;">
                <h4 style="color: #532f17;">Order Items</h4>
                <table class="table" style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr>
                            <th style="text-align: left; color: #532f17; padding: 10px;">Product</th>
                            <th style="text-align: right; color: #532f17; padding: 10px;">Quantity</th>
                            <th style="text-align: right; color: #532f17; padding: 10px;">Price</th>
                            <th style="text-align: right; color: #532f17; padding: 10px;">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($multisysPayment->order->orderItems as $item)
                            <tr>
                                <td style="text-align: left; color: #532f17; padding: 10px;">{{ $item->product_name }}</td>
                                <td style="text-align: right; color: #532f17; padding: 10px;">{{ $item->quantity }}</td>
                                <td style="text-align: right; color: #532f17; padding: 10px;">PHP {{ number_format($item->price, 2) }}</td>
                                <td style="text-align: right; color: #532f17; padding: 10px;">PHP {{ number_format($item->quantity * $item->price, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Order Tracking Link -->
            <div class="items" style="padding: 20px; background: #f4f6f7; margin-top: 30px; text-align: center;">
                <p style="color: #532f17; font-size: 16px;">To track your order, please click the link below:</p>
                <a href="{{ $trackOrderUrl }}" 
                style="color: #fff; background-color: #fca70b; padding: 10px 20px; text-decoration: none; font-weight: bold; border-radius: 5px;">
                    Track Your Order
                </a>
            </div>

		</div>

		<div class="footer bg-warning text-center" style="background: #FFF; padding: 20px; text-align: center; background-color: #2d3a4c !important;">
			<img src="{{ url($imageUrl) }}" alt="Logo" width="50" style="display: block; margin: auto;">
            {{-- <h5 class="text-center text-primary m-0 text" style="margin: 0; margin-bottom: 4px; text-align: center; color: #fca70b !important;">
                {{ env('APP_NAME') ?? 'Project One' }}
            </h5> --}}
		</div>

	</div>

</div>