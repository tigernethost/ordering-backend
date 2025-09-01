<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\OrderReservation;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class OrderCalendar extends Component
{
    public $events = [];

    public function mount()
    {
        $this->fetchOrderCounts();
    }

    protected function fetchOrderCounts()
    {
        $user = Auth::user();

        if (!$user) {
            $this->events = [];
            return;
        }

        // Get all branches the user has access to
        $branchIds = $user->branches()->pluck('branches.id');

        // Fetch order counts grouped by reservation_date
        $orders = OrderReservation::whereIn('branch_id', $branchIds)
            ->selectRaw('DATE(reservation_date) as date, COUNT(*) as total')
            ->groupBy('date')
            ->get();

        // Format the data for FullCalendar
        $this->events = $orders->map(function ($order) {
            return [
                'title' => $order->total . ' Orders',
                'start' => $order->date,
            ];
        })->toArray();
    }

    public function render()
    {
        return view('livewire.order-calendar');
    }
}
