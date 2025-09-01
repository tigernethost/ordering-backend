<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class FetchAttendanceLogJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $ip;
    protected $port;

    /**
     * Create a new job instance.
     */
    public function __construct($ip, $port)
    {
        $this->ip = $ip;
        $this->port = $port;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Call the attendance:fetch command with the provided IP and port
            Artisan::call('attendance:fetch', [
                '--ip' => $this->ip,
                '--port' => $this->port,
            ]);

            Log::channel('attendance')->info("Attendance fetched successfully for device at {$this->ip}:{$this->port}");
        } catch (\Exception $e) {
            Log::channel('attendance')->error("Failed to fetch attendance for device at {$this->ip}:{$this->port} - " . $e->getMessage());
        }
    }
}
