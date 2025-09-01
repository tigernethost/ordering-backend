<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\AttendanceLog;
use MehediJaman\LaravelZkteco\LaravelZkteco;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Exception;

class FetchZKTecoAttendance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attendance:fetch {--ip=} {--port=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch attendance data from ZKTeco device';

    /**
     * Execute the console command.
     */

     public function handle()
     {
         if (file_exists(base_path('.env'))) {
             \Dotenv\Dotenv::createImmutable(base_path())->load();
         }
 
         try {
             $ip = $this->option('ip');
             $port = $this->option('port');
             $zk = new LaravelZkteco($ip, $port);

             if (!$zk->connect()) {
                 throw new Exception("Failed to connect to the biometric device at IP: " . env('ZK_DEVICE_IP'));
             }
 
             $this->info("Connected to device at IP: " . env('ZK_DEVICE_IP'));
             Log::channel('attendance')->info("Connected to device to fetch attendance logs.");
 
             $attendanceLogs = $zk->getAttendance();
 
             if (empty($attendanceLogs)) {
                 throw new Exception("No attendance data retrieved from the device.");
             }
 
             foreach ($attendanceLogs as $log) {
                 try {
                     if (empty($log['id']) || empty($log['timestamp']) || !isset($log['type'])) {
                         $this->warn("Skipping log with missing ID, timestamp, or type.");
                         Log::channel('attendance')->warning("Skipping log with missing data.");
                         continue;
                     }
 
                     // Find the employee by their device user ID
                     $employee = Employee::where('employee_id', $log['id'])->with('branches')->first();
 
                     if (!$employee) {
                         $this->warn("No matching employee found for user ID: {$log['id']}");
                         Log::channel('attendance')->warning("No matching employee found for user ID: {$log['id']}");
                         continue;
                     }
 
                     // Retrieve the branch ID from the employee's associated branches
                     $branch = $employee->branches->first(); // Get the first branch or set logic for selecting a specific branch
                     
                     if (!$branch) {
                         $this->warn("Employee {$employee->name} (ID: {$employee->id}) is not associated with any branch. Skipping.");
                         continue;
                     }
                     
                     $branchId = $branch->id;
 
                     // Parse the timestamp
                     $timestamp = Carbon::parse($log['timestamp']);
 
                     // Check for duplicate log entries
                     $existingLog = AttendanceLog::where('employee_id', $employee->id)
                         ->where('branch_id', $branchId)
                         ->where('type', $log['type'])
                         ->where('timestamp', $timestamp)
                         ->first();
 
                     if ($existingLog) {
                         $this->info("Duplicate entry found for {$employee->name} (ID: {$employee->id}) at {$timestamp} with type {$log['type']} in branch {$branchId}. Skipping.");
                         continue;
                     }
 
                     // Create the attendance log with branch_id
                     AttendanceLog::create([
                         'employee_id' => $employee->id,
                         'branch_id' => $branchId,
                         'timestamp' => $timestamp,
                         'type' => $log['type'],
                         'device_id' => env('ZK_DEVICE_IP'),
                     ]);
 
                     $typeDescription = match ($log['type']) {
                         0 => "time-in",
                         1 => "time-out",
                         4 => "overtime time-in",
                         5 => "overtime time-out",
                         default => "unknown type",
                     };
 
                     $this->info("Logged {$typeDescription} for {$employee->name} (ID: {$employee->id}) at {$timestamp} in branch {$branchId}.");
                     Log::channel('attendance')->info("Logged {$typeDescription} for {$employee->name} (ID: {$employee->id}) at {$timestamp} in branch {$branchId}.");
 
                 } catch (Exception $e) {
                     $errorMessage = "Error processing log for user ID: {$log['id']} - " . $e->getMessage();
                     $this->error($errorMessage);
                     Log::channel('attendance')->error($errorMessage);
                 }
             }
 
             $zk->disconnect();
             $this->info("Attendance data fetched and saved successfully.");
             Log::channel('attendance')->info("Disconnected from device after fetching attendance logs.");
 
         } catch (Exception $e) {
             $errorMessage = "An error occurred during attendance fetch: " . $e->getMessage();
             $this->error($errorMessage);
             Log::channel('attendance')->error($errorMessage);
         }
 
         return 0;
     }

    // public function handle()
    // {

    //     if (file_exists(base_path('.env'))) {
    //         \Dotenv\Dotenv::createImmutable(base_path())->load();
    //     }

        

    //     try {

    //         $ip = $this->option('ip') ?? env('ZK_DEVICE_IP');
    //         $port = $this->option('port') ?? env('ZK_DEVICE_PORT');

    //         $zk = new LaravelZkteco($ip, $port);
    //         // Initialize and connect to the device
    //         // $zk = new LaravelZkteco(env('ZK_DEVICE_IP'), env('ZK_DEVICE_PORT'));

    //         if (!$zk->connect()) {
    //             throw new Exception("Failed to connect to the biometric device at IP: " . env('ZK_DEVICE_IP'));
    //         }

    //         $this->info("Connected to device at IP: " . env('ZK_DEVICE_IP'));
    //         Log::channel('attendance')->info("Connected to device at IP: " . env('ZK_DEVICE_IP') . " to fetch attendance logs.");

    //         // Retrieve attendance logs from the device
    //         $attendanceLogs = $zk->getAttendance();

    //         if (empty($attendanceLogs)) {
    //             throw new Exception("No attendance data retrieved from the device.");
    //         }

    //         foreach ($attendanceLogs as $log) {
    //             try {
    //                 // Check if log has required fields
    //                 if (empty($log['id']) || empty($log['timestamp'])) {
    //                     $this->warn("Skipping log with missing ID or timestamp.");
    //                     Log::channel('attendance')->warning("Skipping log with missing ID or timestamp.");
    //                     continue;
    //                 }

    //                 // dd($log);

    //                 // Find the employee based on the device user ID
    //                 $employee = Employee::where('employee_id', $log['id'])->first();

    //                 if (!$employee) {
    //                     $warningMessage = "No matching employee found for user ID: {$log['id']}";
    //                     $this->warn($warningMessage);
    //                     Log::channel('attendance')->warning($warningMessage);
    //                     continue;
    //                 }

    //                 // Parse the timestamp
    //                 $timestamp = Carbon::parse($log['timestamp']);
    //                 $currentDate = $timestamp->toDateString(); // Only the date part, e.g., "2024-11-13"

    //                 // Check if there's already a record for the current date with both time_in and time_out set
    //                 $existingLog = AttendanceLog::where('employee_id', $employee->id)
    //                     ->whereDate('time_in', $currentDate)
    //                     ->whereNotNull('time_in')
    //                     ->whereNotNull('time_out')
    //                     ->first();

    //                 if ($existingLog) {
    //                     $message = "Attendance for {$employee->name} (ID: {$employee->id}) already exists for today with both time in and time out. Skipping entry.";
    //                     $this->info($message);
    //                     Log::channel('attendance')->info($message);
    //                     continue; // Skip to the next log if today's record already exists
    //                 }

    //                 // If no existing log with time_out, check for open time_in
    //                 $openLog = AttendanceLog::where('employee_id', $employee->id)
    //                     ->whereDate('time_in', $currentDate)
    //                     ->whereNull('time_out')
    //                     ->first();

    //                 if ($openLog) {
    //                     // Update existing open log with time_out
    //                     $openLog->update(['time_out' => $timestamp]);
    //                     $message = "Updated time-out for {$employee->name} (ID: {$employee->id}) at {$timestamp}.";
    //                     $this->info($message);
    //                     Log::channel('attendance')->info($message);
    //                 } else {
    //                     // Create a new record with time_in if no record exists for today
    //                     AttendanceLog::create([
    //                         'employee_id' => $employee->id,
    //                         'time_in' => $timestamp,
    //                         'device_id' => env('ZK_DEVICE_IP'),
    //                     ]);

    //                     $message = "Logged time-in for {$employee->name} (ID: {$employee->id}) at {$timestamp}.";
    //                     $this->info($message);
    //                     Log::channel('attendance')->info($message);
    //                 }

    //             } catch (Exception $e) {
    //                 $errorMessage = "Error processing log for user ID: {$log['id']} - " . $e->getMessage();
    //                 $this->error($errorMessage);
    //                 Log::channel('attendance')->error($errorMessage);
    //             }
    //         }

    //         $zk->disconnect();
    //         $this->info("Attendance data fetched and saved successfully.");
    //         Log::channel('attendance')->info("Disconnected from device after fetching attendance logs.");

    //     } catch (Exception $e) {
    //         // Log and display general errors that occur during the command
    //         $errorMessage = "An error occurred during attendance fetch: " . $e->getMessage();
    //         $this->error($errorMessage);
    //         Log::channel('attendance')->error($errorMessage);
    //     }

    //     return 0;
    // }


}
