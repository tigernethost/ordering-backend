<div>
    <div id="calendar"></div>

    <!-- @push('styles')
        <link href="https://cdn.jsdelivr.net/npm/@fullcalendar/core/main.min.css" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/@fullcalendar/daygrid/main.min.css" rel="stylesheet">
    @endpush -->

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/@fullcalendar/core/main.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/@fullcalendar/daygrid/main.min.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var calendarEl = document.getElementById('calendar');

                var calendar = new FullCalendar.Calendar(calendarEl, {
                    plugins: ['dayGrid'],
                    initialView: 'dayGridMonth',
                    events: @json($events), // Pass events from Livewire
                });

                calendar.render();
            });

            // Listen to Livewire event updates and refresh the calendar
            Livewire.on('refreshCalendar', (events) => {
                calendar.removeAllEvents();
                calendar.addEventSource(events);
            });
        </script>
    @endpush
</div>