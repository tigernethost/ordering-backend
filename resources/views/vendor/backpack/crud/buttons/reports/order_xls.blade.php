<button class="btn btn-primary" id="download-xls">
    <i class="la la-file-excel"></i> Export to XLS
</button>

<script>
    document.getElementById('download-xls').addEventListener('click', function () {
        // Capture the current query string (including filters)
        let params = new URLSearchParams(window.location.search);

        // Construct the PDF download URL
        let downloadUrl = "{{ url($crud->route . '/export-xls') }}?" + params.toString();

        // Open the PDF download in a new tab
        window.open(downloadUrl, '_blank');
    });
</script>