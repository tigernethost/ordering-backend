<button class="btn btn-primary" id="download-pdf">
    <i class="la la-file-pdf"></i> Export to PDF
</button>

<script>
    document.getElementById('download-pdf').addEventListener('click', function () {
        // Capture the current query string (including filters)
        let params = new URLSearchParams(window.location.search);

        // Construct the PDF download URL
        let downloadUrl = "{{ url($crud->route . '/download-pdf') }}?" + params.toString();

        // Open the PDF download in a new tab
        window.open(downloadUrl, '_blank');
    });
</script>

