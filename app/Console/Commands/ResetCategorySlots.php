<?php

namespace App\Console\Commands;

use App\Models\Branch;
use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Models\BranchOrderIntervals;

class ResetCategorySlots extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'category:reset-slots';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset category slots for branches after an interval is completed, based on default_slots';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info("Starting to reset category slots...");
        //\Log::info("ResetCategorySlots command started at " . Carbon::now());

        $currentTime = Carbon::now();

        try {
            // Fetch all branch order intervals
            $branchOrderIntervals = BranchOrderIntervals::with(['branches.categories'])->get();

            foreach ($branchOrderIntervals as $interval) {
                $startTime = Carbon::today()->setTimeFromTimeString($interval->start_time);
                $endTime = Carbon::today()->setTimeFromTimeString($interval->end_time);

                // Handle overnight intervals
                if ($endTime->lessThan($startTime)) {
                    $endTime->addDay();
                }

                // Check if the current time is after the interval's end time
                if ($currentTime->greaterThan($endTime)) {
                    $this->info("Resetting slots for interval ID {$interval->id} (ended at {$endTime}).");
                    //\Log::info("Resetting slots for interval ID {$interval->id}. Interval ended at {$endTime}.");

                    foreach ($interval->branches as $branch) {
                        foreach ($branch->categories as $category) {
                            if ($category->pivot->default_slots !== null) { // Only reset if default_slots is specified
                                $defaultSlots = $category->pivot->default_slots;
                                $category->pivot->slots = $defaultSlots;
                                $category->pivot->save();

                                $this->info("Reset slots for category '{$category->name}' in branch '{$branch->name}' to {$defaultSlots}.");
                                //\Log::info("Reset slots for category '{$category->name}' in branch '{$branch->name}' to {$defaultSlots}.");
                            } else {
                                $this->info("Category '{$category->name}' in branch '{$branch->name}' has unlimited slots.");
                                //\Log::info("Category '{$category->name}' in branch '{$branch->name}' has unlimited slots.");
                            }
                        }
                    }
                }
            }

            $this->info("Category slots reset successfully.");
            \Log::info("ResetCategorySlots command completed successfully at " . Carbon::now());

        } catch (\Exception $e) {
            $this->error("An error occurred while resetting category slots: " . $e->getMessage());
            \Log::error("Error in ResetCategorySlots command: " . $e->getMessage());
        }

        return Command::SUCCESS;
    }
}
