<?php

namespace App\Console\Commands;

use App\Models\Branch;
use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Models\BranchOrderIntervals;

class ResetProductSlots extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'product:reset-slots';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset product slots in productables table based on default_slots and interval time';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info("Starting to reset product slots...");

        $currentTime = Carbon::now();

        // Fetch all branch order intervals that match the current time
        $branchOrderIntervals = BranchOrderIntervals::with(['branches', 'products'])->get();

        foreach ($branchOrderIntervals as $interval) { 
            $startTime = Carbon::today()->setTimeFromTimeString($interval->start_time);
            $endTime = Carbon::today()->setTimeFromTimeString($interval->end_time);

            // Handle overnight intervals
            if ($endTime->lessThan($startTime)) {
                $endTime->addDay();
            }

            // Check if the current time falls within the interval
            if ($currentTime->between($startTime, $endTime)) { // Check the order time
                $this->info("Resetting slots for interval ID {$interval->id}.");

                foreach ($interval->branches as $branch) {
                    foreach ($interval->products as $product) {
                        // Fetch the pivot record for the productables table
                        $productable = $product->branches()
                            ->wherePivot('productable_id', $branch->id) // Match the branch ID
                            ->wherePivot('productable_type', Branch::class) // Match the branch type
                            ->first();
                
                        if ($productable) {
                            // Reset the slots to the default_slots value
                            $defaultSlots = $productable->pivot->default_slots;
                            $productable->pivot->slots = $defaultSlots;
                            $productable->pivot->save();
                
                            $this->info("Reset slots for product {$product->id} in branch {$branch->id} to {$defaultSlots}");
                        } else {
                            $this->warn("No productable record found for product {$product->id} in branch {$branch->id}");
                        }
                    }
                }
                
            }
        }

        $this->info("Product slots reset successfully.");
        return Command::SUCCESS;
    }
}
