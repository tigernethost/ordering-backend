@extends(backpack_view('blank'))

@section('header')
    <section class="container-fluid">
        <h2>Import Products</h2>
    </section>
@endsection

@section('content')
    <div class="row">
        <div class="col-md-8">
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="card">
                <div class="card-body">
                    <form method="POST" action="{{ route('product.import') }}" enctype="multipart/form-data">
                        @csrf

                        <div class="mb-3">
                            <label for="file" class="form-label">Excel File (.xlsx, .xls, .csv)</label>
                            <input type="file" class="form-control" id="file" name="file" required accept=".xlsx,.xls,.csv" />
                            <div class="form-text">Download the template for correct columns: <a href="{{ route('product.template') }}">Product Import Template</a></div>
                        </div>

                        <button type="submit" class="btn btn-primary">Import</button>
                        <a href="{{ backpack_url('product') }}" class="btn btn-secondary">Back to Products</a>
                    </form>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-body">
                    <h5>Notes</h5>
                    <ul>
                        <li>Columns supported: name, description, price, stock, sku, barcode, qr_code, category_slug, category_name, weight_in_grams, length_cm, width_cm, height_cm, is_active.</li>
                        <li>If both category_slug and category_name are provided, slug takes priority.</li>
                        <li>When SKU exists, the row updates the matching product. Without SKU, a new product is created.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endsection
