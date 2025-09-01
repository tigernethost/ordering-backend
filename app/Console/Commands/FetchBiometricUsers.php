<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Employee;
use App\Models\EmployeeBiometric;
use MehediJaman\LaravelZkteco\LaravelZkteco;
use Illuminate\Support\Facades\Log;
use Exception;

class FetchBiometricUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'biometric:fetch-users';
    


    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch user data from biometric device and associate with employees';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (file_exists(base_path('.env'))) {
            \Dotenv\Dotenv::createImmutable(base_path())->load();
        }

        try {
            // Initialize and connect to the device
            $zk = new LaravelZkteco(env('ZK_DEVICE_IP'), env('ZK_DEVICE_PORT'));

            if (!$zk->connect()) {
                throw new Exception("Failed to connect to the biometric device at IP: " . env('ZK_DEVICE_IP'));
            }

            $this->info("Connected to device at IP: " . env('ZK_DEVICE_IP'));
            Log::channel('attendance')->info("Connected to device at IP: " . env('ZK_DEVICE_IP') . " to fetch user data and fingerprints.");

            // Fetch employees who need biometric data updates
            $employees = Employee::all();
            foreach ($employees as $employee) {
                try {
                    // Fetch user data based on employee_id
                    $userDataArray = $zk->getUser($employee->employee_id);
                    
                    if (empty($userDataArray)) {
                        $warningMessage = "No user data found for employee ID: {$employee->employee_id}";
                        $this->warn($warningMessage);
                        Log::channel('attendance')->warning($warningMessage);
                        continue;
                    }

                    // Extract the user data from the array
                    $userData = $userDataArray[$employee->employee_id] ?? null;

                    if (!$userData || !isset($userData['uid'])) {
                        $warningMessage = "No UID found for user with employee ID: {$employee->employee_id}";
                        $this->warn($warningMessage);
                        Log::channel('attendance')->warning($warningMessage);
                        continue;
                    }

                    // Use the UID to fetch fingerprint data
                    $uid = $userData['uid'];
                    $fingerprintData = $zk->getFingerprint($uid);

                    dd($fingerprintData);

                    if (empty($fingerprintData)) {
                        $warningMessage = "No fingerprint data found for UID: {$uid}";
                        $this->warn($warningMessage);
                        Log::channel('attendance')->warning($warningMessage);
                        continue;
                    }

                    // Store or update biometric data for this employee
                    EmployeeBiometric::updateOrCreate(
                        [
                            'employee_id' => $employee->id,
                            'biometric_type' => 'fingerprint',
                        ],
                        [
                            'biometric_data' => json_encode([
                                'user_data' => $userData,
                                'fingerprint_data' => $fingerprintData,
                            ]),
                        ]
                    );

                    $message = "Stored biometric and fingerprint data for {$employee->name} (ID: {$employee->id}).";
                    $this->info($message);
                    Log::channel('attendance')->info($message);

                } catch (Exception $e) {
                    $errorMessage = "Error processing user ID: {$employee->employee_id} - " . $e->getMessage();
                    $this->error($errorMessage);
                    Log::channel('attendance')->error($errorMessage);
                }
            }

            $zk->disconnect();
            $this->info("User data and fingerprints fetched and stored successfully.");
            Log::channel('attendance')->info("Disconnected from device after fetching user data and fingerprints.");

        } catch (Exception $e) {
            // Log and display general errors that occur during the command
            $errorMessage = "An error occurred during biometric fetch: " . $e->getMessage();
            $this->error($errorMessage);
            Log::channel('attendance')->error($errorMessage);
        }

        return 0;
        
    }
}
