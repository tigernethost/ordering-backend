<?php

namespace App\Livewire;

use App\Http\Controllers\Api\LalamoveController;
use App\Models\LalamovePlaceOrder;
use Livewire\Component;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Http\Request;

class LalamoveOrders extends Component
{
    public $lalamoveOrders = [];
    public $branches = [];
    public $selectedBranch;

    protected $listeners = [
        'branchChanged' => 'onBranchChange',
        'processOrder' => 'onProcessOrder',
        'bookLalamove' => 'bookLalamoveOrder'
    ];

    public function mount()
    {
        $user = backpack_auth()->user();

        // Get branches assigned to the user
        $this->branches = $user ? $user->branches()->orderBy('name')->get() : collect([]);

        if ($this->branches->isEmpty()) {
            $this->selectedBranch = null;
            $this->lalamoveOrders = collect([]);
        } else {
            $this->selectedBranch = $this->branches->first()->id;
            $this->fetchLalamoveOrders();
        }
    }

    public function onBranchChange($branchId)
    {
        $this->selectedBranch = $branchId;
        $this->fetchLalamoveOrders();
    }

    public function fetchLalamoveOrders()
    {
        if (!$this->selectedBranch) {
            $this->lalamoveOrders = collect([]);
            return;
        }

        $this->lalamoveOrders = Order::with(['reservation'])
            ->where('branch_id', $this->selectedBranch)
            ->whereHas('reservation', function ($query) {
                $query->whereDate('reservation_date', Carbon::today());
            })
            ->latest()
            ->get();
        
    }

    public function onProcessOrder($detail)
    {
        $order = Order::findOrFail($detail['id']);
        if ($detail['processOrderStatus'] === 'processing') {
            $orderQuotation = $order->orderQuotation;
            $lalamoveController = new LalamoveController();
    
            try {
                // Check if quotation is expired
                if (now()->greaterThan(Carbon::parse($orderQuotation->expires_at))) {
                    $regenerateQuotation = $lalamoveController->regenerateQuotationId($orderQuotation->id);
    
                    if ($regenerateQuotation->getStatusCode() !== 200) {
                        $error = $regenerateQuotation->getData(true);
                        $message = $error['details']['errors'][0]['message']
                            ?? $error['error']
                            ?? 'Unknown error occurred.';
                        
                        //dd($message);
                        $this->dispatch('orderFailed',  $message);
                        \Log::error('Error in Lalamove', ['response' => $error]);                    
                        return;
                    }
    
                    $newQuotationData = $regenerateQuotation->getData(true);
    
                    // Validate data key exists
                    if (!isset($newQuotationData['data']['quotationId'])) {
                        $this->dispatch('orderFailed', ['errorMessage' => 'Missing quotationId from regenerate response.']);
                        return;
                    }
    
                    $orderQuotation->update([
                        'quotation' => $newQuotationData['data']['quotationId'],
                        'sender_stop_id' => $newQuotationData['data']['stops'][0]['stopId'] ?? null,
                        'recipient_stop_id' => $newQuotationData['data']['stops'][1]['stopId'] ?? null,
                    ]);
                }
    
                $fakeRequest = new Request([
                    'quotationId' => $orderQuotation->quotation
                ]);
    
                $placeOrder = $lalamoveController->placeOrder($fakeRequest);
    
                if ($placeOrder->getStatusCode() !== 200) {
                    $error = $placeOrder->getData(true);
                    $message = $error['details']['errors'][0]['message']
                        ?? $error['error']
                        ?? 'Unknown error occurred.';
                    
                    //dd($message);
                    $this->dispatch('orderFailed',  $message);
                    \Log::error('Error in Lalamove', ['response' => $error]);                    
                    return;
                }
    
                $data = $placeOrder->getData(true)['data'] ?? null;
    
                if (!$data || !isset($data['orderId'])) {
                    $this->dispatch('orderFailed', ['errorMessage' => 'Lalamove returned invalid data.']);
                    return;
                }
    
                LalamovePlaceOrder::create([
                    'order_id' => $order->id,
                    'lalamove_order_id' => $data['orderId'],
                    'lalamove_quotation_id' => $data['quotationId'],
                    'lalamove_driver_id' => $data['driverId'] ?? null,
                    'share_link' => $data['shareLink'] ?? null,
                    'status' => $data['status'],
                    'distance_value' => isset($data['distance']['value']) ? floatval($data['distance']['value']) : null,
                    'distance_unit' => $data['distance']['unit'] ?? null,
                    'price_base' => $data['priceBreakdown']['base'] ?? null,
                    'price_total_exclude_priority_fee' => $data['priceBreakdown']['totalExcludePriorityFee'] ?? null,
                    'price_total' => $data['priceBreakdown']['total'] ?? null,
                    'currency' => $data['priceBreakdown']['currency'] ?? null,
                    'partner' => $data['partner'] ?? null,
                    'stops' => json_encode($data['stops'] ?? []),
                    'response_payload' => json_encode($data),
                ]);
            } catch (\Throwable $e) {
                $this->dispatch('orderFailed',  $e->getMessage());
            }

            $order->update([
                'status' => $detail['processOrderStatus']
            ]);

            $order->reservation?->update([
                'status' => $detail['processOrderStatus']
            ]);
    
            $this->dispatch('orderProcessed');
    
        } else {
            $order->update([
                'status' => $detail['processOrderStatus']
            ]);

            $order->reservation?->update([
                'status' => $detail['processOrderStatus']
            ]);

            $this->dispatch('orderProcessed');
        }

        $this->fetchLalamoveOrders();
    }
    
    public function bookLalamoveOrder($detail)
    {
        $order = Order::findOrFail($detail['orderId']);
        $orderQuotation = $order->orderQuotation;
        $lalamoveController = new LalamoveController();

        try {
            // Check if quotation is expired
            if (now()->greaterThan(Carbon::parse($orderQuotation->expires_at))) {
                $regenerateQuotation = $lalamoveController->regenerateQuotationId($orderQuotation->id);

                if ($regenerateQuotation->getStatusCode() !== 200) {
                    $error = $regenerateQuotation->getData(true);
                    $message = $error['details']['errors'][0]['message']
                        ?? $error['error']
                        ?? 'Unknown error occurred.';
                    
                    //dd($message);
                    $this->dispatch('bookLalamoveFailed',  $message);
                    \Log::error('Error in Lalamove', $error);                    
                    return;
                }

                $newQuotationData = $regenerateQuotation->getData(true);

                // Validate data key exists
                if (!isset($newQuotationData['data']['quotationId'])) {
                    $this->dispatch('bookLalamoveFailed','Missing quotationId from regenerate response.');
                    return;
                }

                $orderQuotation->update([
                    'quotation' => $newQuotationData['data']['quotationId'],
                    'sender_stop_id' => $newQuotationData['data']['stops'][0]['stopId'] ?? null,
                    'recipient_stop_id' => $newQuotationData['data']['stops'][1]['stopId'] ?? null,
                ]);
            }

            $fakeRequest = new Request([
                'quotationId' => $orderQuotation->quotation
            ]);

            $placeOrder = $lalamoveController->placeOrder($fakeRequest);

            if ($placeOrder->getStatusCode() !== 200) {
                $error = $placeOrder->getData(true);
                \Log::error('Lalamove place order error', ['response' => $error]);
            
                if (is_array($error)) {
                    $message = $error['details']['errors'][0]['message']
                        ?? $error['error']
                        ?? 'Unknown error occurred.';
                } else {
                    $message = is_string($error) ? $error : 'Unknown error occurred.';
                }
            
                $this->dispatch('bookLalamoveFailed', $message);
                return;
            
            }

            // dd($placeOrder);
            $data = $placeOrder->getData(true)['data'] ?? null;

            if (!$data || !isset($data['orderId'])) {
                $this->dispatch('bookLalamoveFailed', ['errorMessage' => 'Lalamove returned invalid data.']);
                return; // Stop further execution
            }

            LalamovePlaceOrder::create([
                'order_id' => $order->id,
                'lalamove_order_id' => $data['orderId'],
                'lalamove_quotation_id' => $data['quotationId'],
                'lalamove_driver_id' => $data['driverId'] ?? null,
                'share_link' => $data['shareLink'] ?? null,
                'status' => $data['status'],
                'distance_value' => isset($data['distance']['value']) ? floatval($data['distance']['value']) : null,
                'distance_unit' => $data['distance']['unit'] ?? null,
                'price_base' => $data['priceBreakdown']['base'] ?? null,
                'price_total_exclude_priority_fee' => $data['priceBreakdown']['totalExcludePriorityFee'] ?? null,
                'price_total' => $data['priceBreakdown']['total'] ?? null,
                'currency' => $data['priceBreakdown']['currency'] ?? null,
                'partner' => $data['partner'] ?? null,
                'stops' => json_encode($data['stops'] ?? []),
                'response_payload' => json_encode($data),
            ]);
            $order->update([
                'status' => 'order_booked'
            ]);

            $order->reservation?->update([
                'status' => 'order_booked'
            ]);

            $this->dispatch('orderBooked');
        } catch (\Throwable $e) {

            $this->dispatch('bookLalamoveFailed',  $e->getMessage());
        }

        
        $this->fetchLalamoveOrders();
    }

    public function render()
    {
        return view('livewire.lalamove-orders');
    }
}
