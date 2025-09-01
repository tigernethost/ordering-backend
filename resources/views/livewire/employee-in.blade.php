<div class="card">
    <div class="card-body">
        <div class="d-flex align-items-center">
            <div class="subheader">Employees Checked In Today</div>
        </div>
        <div class="d-flex align-items-baseline">
            <div class="h1 mb-3 me-2">{{ $checkedInCount }}</div>
        </div>
        <div class="mt-3">
            <h6 class="subheader">Employees</h6>
            @if(count($employeesCheckedIn) > 0)
                <div id="employeesCarousel" class="carousel slide" data-bs-ride="carousel">
                    <div class="carousel-inner">
                        @foreach ($employeesCheckedIn as $index => $employee)
                            <div class="carousel-item {{ $index === 0 ? 'active' : '' }}">
                                <div class="text-center">
                                    <h5>{{ $employee }}</h5>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <button class="carousel-control-prev" type="button" data-bs-target="#employeesCarousel"
                        data-bs-slide="prev">
                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Previous</span>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#employeesCarousel"
                        data-bs-slide="next">
                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Next</span>
                    </button>
                </div>
            @else
                <div class="text-muted">No employees have checked in today.</div>
            @endif
        </div>
    </div>
</div>