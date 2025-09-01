<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class OrderToday extends Component
{
    public $orders = [];
    public $branches = [];
    public $selectedBranch;

    protected $listeners = [
        'branchChanged' => 'onBranchChange', // Listen for the branchChanged event
    ];

    public function mount()
    {
        $user = backpack_auth()->user();

        // Get the user's branches
        $this->branches = $user->branches()->orderBy('name')->get();

        if ($this->branches->isEmpty()) {
            // No branches assigned to this user
            $this->orders = collect([]);
            $this->selectedBranch = null;
        } else {
            // Default to the first branch
            $this->selectedBranch = $this->branches->first()->id;
            $this->fetchOrders();
        }
    }

    public function onBranchChange($branchId)
    {
        // Update the selected branch
        $this->selectedBranch = $branchId;

        // Fetch orders for the selected branch
        $this->fetchOrders();
    }

    public function fetchOrders()
    {
        // Only fetch if we have a selected branch
        if ($this->selectedBranch) {
            $this->orders = Order::where('branch_id', $this->selectedBranch)
                ->whereDate('created_at', Carbon::today())
                ->get();
        } else {
            $this->orders = collect([]);
        }
    }

    public function render()
    {
        return view('livewire.order-today');
    }
}
