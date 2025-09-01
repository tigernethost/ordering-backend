<?php

namespace App\Livewire;

use App\Models\OrderReservation;
use Livewire\Component;
use App\Models\Order;
use Carbon\Carbon;

class SalesOrderMetrics extends Component
{
    public $selectedBranch = null;
    public $salesToday = 0;
    public $ordersToday = 0;
    public $ordersCompleted = 0;

    protected $listeners = [
        'orderStatusUpdated' => 'fetchMetrics', // Listen for status updates
        'branchChanged' => 'onBranchChange', // Listen for branch changes
    ];

    public function mount()
    {
        $user = backpack_auth()->user();

        // Get the user's branches and set the default branch
        $branches = $user->branches()->orderBy('name')->get();

        if ($branches->isNotEmpty()) {
            $this->selectedBranch = $branches->first()->id; // Set the default branch
        }

        $this->fetchMetrics();
    }

    public function fetchMetrics()
    {
        // Calculate Sales Today for the selected branch
        $this->salesToday = Order::where('branch_id', $this->selectedBranch)
            ->whereDate('created_at', Carbon::today())
            ->sum('total_amount'); // Assuming `total_amount` is the sales column

        // Count Orders Today for the selected branch
        $this->ordersToday = Order::where('branch_id', $this->selectedBranch)
            ->whereDate('created_at', Carbon::today())
            ->count();

        // Count Orders Completed for the selected branch
        $this->ordersCompleted = OrderReservation::where('branch_id', $this->selectedBranch)
            ->where('status', 'completed')
            ->whereDate('updated_at', Carbon::today())
            ->count();
    }

    public function onBranchChange($branchId)
    {
        $this->selectedBranch = $branchId; // Update the selected branch
        $this->fetchMetrics(); // Refresh metrics for the new branch
    }

    public function render()
    {
        return view('livewire.sales-order-metrics');
    }
}
