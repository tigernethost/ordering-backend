<div class="row row-cards">
    <!-- Sales Today -->
    <div class="col-sm-6 col-lg-4">
        <div class="card">
            <div class="card-body d-flex align-items-center">
                <div class="icon icon-shape bg-purple text-white me-3">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div>
                    <h3 class="mb-1">₱{{ number_format($salesToday, 2) }}</h3>
                    <div class="text-muted">Sales Today</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Orders Today -->
    <div class="col-sm-6 col-lg-4">
        <div class="card">
            <div class="card-body d-flex align-items-center">
                <div class="icon icon-shape bg-green text-white me-3">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div>
                    <h3 class="mb-1">{{ $ordersToday }}</h3>
                    <div class="text-muted">Orders Today</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Orders Completed -->
    <div class="col-sm-6 col-lg-4">
        <div class="card">
            <div class="card-body d-flex align-items-center">
                <div class="icon icon-shape bg-blue text-white me-3">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div>
                    <h3 class="mb-1">{{ $ordersCompleted }}</h3>
                    <div class="text-muted">Orders Completed</div>
                </div>
            </div>
        </div>
    </div>
</div>