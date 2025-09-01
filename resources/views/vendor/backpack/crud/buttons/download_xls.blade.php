<a class="btn btn-primary" id="download-xls" href="{{ url($crud->route.'/download-xls') }}" target="_blank">
    <i class="la la-file-excel"></i> Export to XLS
</a>

<script>
    document.getElementById('download-xls').addEventListener('click', function () {
        // Capture the current query string (including filters)
        let params = new URLSearchParams(window.location.search);

        // Construct the PDF download URL
        let downloadUrl = "{{ url($crud->route . '/download-xls') }}?" + params.toString();

        // Open the PDF download in a new tab
        window.open(downloadUrl, '_blank');
    });
</script>