@extends(backpack_view('blank'))

@push('before_styles')
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
@endpush

@section('content')
    <div id="app">
        <sales-report/>
    </div>
@endsection

@push('after_scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @vite('resources/js/app.js')
@endpush