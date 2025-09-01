<div class="card">
    <div class="card-body">
        <div class="d-flex align-items-center">
            <div class="subheader">Device Online</div>

        </div>
        <div class="d-flex align-items-baseline">
            <div class="h1 mb-3 me-2">{{ $onlineCount }}</div>
            <div class="me-auto">
                <span class="text-green d-inline-flex align-items-center lh-1">
                    0% <!-- Download SVG icon from http://tabler-icons.io/i/minus -->
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                        class="icon ms-1">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                        <path d="M5 12l14 0"></path>
                    </svg>
                </span>
            </div>
        </div>
        <div class="mt-3">
            <h6 class="subheader">Branches Online</h6>
            @if(count($branchesOnline) > 0)
                <div id="branchesCarousel" class="carousel slide" data-bs-ride="carousel">
                    <div class="carousel-inner">
                        @foreach ($branchesOnline as $index => $branch)
                            <div class="carousel-item {{ $index === 0 ? 'active' : '' }}">
                                <div class="text-center">
                                    <h4>{{ $branch }}</h4>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <button class="carousel-control-prev" type="button" data-bs-target="#branchesCarousel"
                        data-bs-slide="prev">
                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Previous</span>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#branchesCarousel"
                        data-bs-slide="next">
                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Next</span>
                    </button>
                </div>
            @else
                <div class="text-muted">No branches online.</div>
            @endif
        </div>
    </div>
</div>