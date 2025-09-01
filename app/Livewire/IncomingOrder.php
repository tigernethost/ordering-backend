<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\OrderReservation;

class IncomingOrder extends Component
{
    use WithPagination;

    public $branches = [];
    public $selectedBranch;

    protected $paginationTheme = 'bootstrap'; // Or 'tailwind' depending on your UI
    protected $listeners = [
        'branchChanged' => 'onBranchChange',
    ];

    public function mount()
    {
        $user = backpack_auth()->user();
        $this->branches = $user ? $user->branches()->orderBy('name')->get() : collect([]);

        if ($this->branches->isNotEmpty()) {
            $this->selectedBranch = $this->branches->first()->id;
        }
    }

    public function updatingSelectedBranch()
    {
        $this->resetPage();
    }

    public function updatedSelectedBranch($value)
    {
        $this->dispatch('branchChanged', $value);
    }

    public function onBranchChange($branchId)
    {
        $this->selectedBranch = $branchId;
        $this->resetPage();
    }

    public function updateOrderStatus($orderId, $newStatus)
    {
        $order = OrderReservation::find($orderId);

        if ($order && $order->branch_id == $this->selectedBranch) {
            $order->update(['status' => $newStatus]);
            $this->dispatch('orderStatusUpdated');
            session()->flash('message', 'Order status has been updated to ' . ucfirst($newStatus) . '.');
        }
    }

    public function render()
    {
        $incomingOrders = [];

        if ($this->selectedBranch) {
            $incomingOrders = OrderReservation::with('order')
                ->where('branch_id', $this->selectedBranch)
                ->whereIn('status', ['pending', 'preparing', 'ready', 'completed'])
                ->orderByRaw("
                    CASE
                        WHEN status = 'pending' THEN 1
                        WHEN status = 'preparing' THEN 2
                        WHEN status = 'ready' THEN 3
                        WHEN status = 'completed' THEN 3
                    END ASC
                ")
                ->whereDate('reservation_date', '>', now())
                ->orderBy('reservation_date', 'asc')
                ->paginate(5);
        }

        return view('livewire.incoming-order', [
            'incomingOrders' => $incomingOrders,
        ]);
    }
}
