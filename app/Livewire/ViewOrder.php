<?php

namespace App\Livewire;

use App\Http\Controllers\Api\LalamoveController;
use App\Models\Order;
use Livewire\Component;

use function PHPUnit\Framework\isNull;

class ViewOrder extends Component
{
    public $order = null;
    public $isReservation = false;
    public $orderItems = [];
    public $driverLat = null;
    public $driverLng = null;


    public function mount($id)
    {
        $this->order = Order::findOrFail($id);
        $this->isReservation = $this->order->reservation?->reservation_date > now();
        $this->orderItems = $this->order->orderItems;

        // dd($this->order->order_type === 'delivery', $this->order->orderQuotation);
    
       
    }

    public function getDriverCoordinates()
    {
        $lalamoveOrder = $this->order->lalamovePlacedOrder;
    
        if ($lalamoveOrder && $lalamoveOrder->lalamove_driver_id) {
            $lalamoveController = new LalamoveController();
            $response = $lalamoveController->getDriverDetails(
                $lalamoveOrder->lalamove_order_id,
                $lalamoveOrder->lalamove_driver_id
            );
    
            if ($response->getStatusCode() === 200) {
                $data = $response->getData(true);
                $location = $data['data']['location'] ?? null;
    
                if ($location && isset($location['lat'], $location['lng'])) {
                    $this->driverLat = floatval($location['lat']);
                    $this->driverLng = floatval($location['lng']);
                }
            }
        }
    }
    

    public function render()
    {
        return view('livewire.view-order')
        ->extends(backpack_view('blank')) 
        ->section('content'); ;
    }
}
