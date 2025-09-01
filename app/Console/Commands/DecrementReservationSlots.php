<?php

namespace App\Console\Commands;

use App\Models\OrderReservation;
use Carbon\Carbon;
use Illuminate\Console\Command;

class DecrementReservationSlots extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'product:decrement-reservation-slots';

    /**
     * The console command description.
     */
    protected $description = 'Decrement slots for reservations based on branch categories and intervals';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $currentDateTime = Carbon::now();
        $this->info("Decrementing slots for reservations at: " . $currentDateTime);
        //\Log::info("DecrementReservationSlots: Starting slot decrement at {$currentDateTime}");

        // Fetch reservations for today
        $reservations = OrderReservation::with(['order.orderItems.product', 'branch.categories', 'orderInterval'])
            ->whereDate('reservation_date', '=', $currentDateTime->toDateString())
            ->where('is_cancelled', false)
            ->where('status', 'paid')
            ->get();

        foreach ($reservations as $reservation) {
            $branch = $reservation->branch;
            $orderTime = $reservation->orderInterval;

            // Match the current time to the interval
            $startTime = Carbon::today()->setTimeFromTimeString($orderTime->start_time);
            $endTime = Carbon::today()->setTimeFromTimeString($orderTime->end_time);

            // Handle overnight intervals
            if ($endTime->lessThan($startTime)) {
                $endTime->addDay();
            }

            if ($currentDateTime->between($startTime, $endTime)) {
                $this->info("Processing reservation ID {$reservation->id} for interval ID {$orderTime->id}.");
                //\Log::info("Processing reservation ID {$reservation->id} for branch '{$branch->name}' and interval ID {$orderTime->id}");

                foreach ($reservation->order->orderItems as $orderItem) {
                    $product = $orderItem->product;
                    $category = $product->category;

                    if ($category) {
                        $categorySlot = $branch->categories->firstWhere('id', $category->id);

                        if ($categorySlot && $categorySlot->pivot->slots !== null) { // Category has restricted slots
                            $currentSlots = $categorySlot->pivot->slots;
                            $newSlots = max($currentSlots - $orderItem->quantity, 0); // Prevent negative slots
                            $categorySlot->pivot->slots = $newSlots;
                            $categorySlot->pivot->save();

                            $this->info("Decremented slots for category '{$category->name}' in branch '{$branch->name}' for reservation ID {$reservation->id}.");
                           \Log::info("Decremented slots for category '{$category->name}' in branch '{$branch->name}' by {$orderItem->quantity}. Remaining slots: {$newSlots}");
                        } else {
                            $this->info("Category '{$category->name}' in branch '{$branch->name}' has unlimited slots or no slot restriction.");
                            //\Log::info("Category '{$category->name}' in branch '{$branch->name}' has unlimited slots or no slot restriction. Skipping.");
                        }
                    } else {
                        $this->warn("Order item with product ID {$product->id} does not have a category. Skipping.");
                        //\Log::warning("Order item with product ID {$product->id} does not have a category. Skipping.");
                    }
                }
            } else {
                $this->warn("Reservation ID {$reservation->id} does not fall within the current time interval.");
                \Log::warning("Reservation ID {$reservation->id} does not fall within the current time interval.");
            }
        }

        $this->info("Slot decrement completed.");
        \Log::info("DecrementReservationSlots: Slot decrement completed at {$currentDateTime}");
    }
}
