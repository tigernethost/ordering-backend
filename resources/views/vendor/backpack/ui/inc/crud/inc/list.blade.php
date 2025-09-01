<script>
    function downloadPdfReport() {
        // Get the current URL and extract the filter parameters
        let url = new URL(window.location.href);
        let params = new URLSearchParams(url.search);

        // Construct the PDF download URL with the current filter parameters
        let downloadUrl = "{{ url('admin/attendance-log/download-pdf') }}?" + params.toString();

        // Redirect to the download URL
        window.open(downloadUrl, '_blank');
    }
</script>
