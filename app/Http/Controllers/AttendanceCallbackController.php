<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\AttendanceLog;
use App\Models\Branch;
use App\Models\Device;
use App\Models\Employee;
use App\Models\EmployeeBiometric;
use Carbon\Carbon;
use Throwable;
use Illuminate\Support\Facades\DB;

class AttendanceCallbackController extends Controller
{
    public function handshake(Request $request)
    {
        $deviceSerialNumber = $request->input('SN');

        try {
            // Check if SN parameter exists
            if (!$deviceSerialNumber) {
                throw new \Exception('Missing SN parameter in the handshake request.');
            }

            // Retrieve device information
            $device = Device::where('device_sn', $deviceSerialNumber)->first();

            if (!$device) {
                throw new \Exception("Device with serial number $deviceSerialNumber not found in the database.");
            }

            // Update the device status to "online"
            $device->status = 'online';
            $device->save();

            Log::info("Device $deviceSerialNumber handshake successful. Status updated to online.");

            // Handshake response
            $response = "GET OPTION FROM: {$deviceSerialNumber}\r\n" .
                "Stamp=9999\r\n" .
                "OpStamp=" . time() . "\r\n" .
                "ErrorDelay=360\r\n" .
                "Delay=120\r\n" .
                "ResLogDay=1825\r\n" .
                "ResLogDelCount=1000\r\n" .
                "ResLogCount=50000\r\n" .
                "TransTimes=00:00;14:05\r\n" .
                "TransInterval=1\r\n" .
                "TransFlag=1111010010\r\n" .
                "Realtime=1\r\n" .
                "Encrypt=0";
            // ."CLEARLOG";

            return response($response, 200)
                ->header('Content-Type', 'text/plain')
                ->header('Connection', 'close');

        } catch (\Throwable $e) {
            // Log the error
            Log::error('Handshake error:', [
                'error_message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all(),
            ]);

            // Return an error response
            return response("Handshake failed: " . $e->getMessage(), 500)
                ->header('Content-Type', 'text/plain')
                ->header('Connection', 'close');
        }
    }

    public function receiveRecords(Request $request)
    {

        $content = [
            'url' => json_encode($request->all()),
            'data' => $request->getContent(),
        ];

        $deviceSerialNumber = $request->input('SN');
        $table = $request->input('table');
        $device = Device::where('device_sn', $deviceSerialNumber)->first();

        if (!$device) {
            Log::error("Device with serial number $deviceSerialNumber not found");
            return response("Device not found", 404);
        }

        $branchId = $device->branch_id;
        $deviceId = $device->id;
        $totalProcessed = 0;

        Log::info($request->toArray());
        if ($table == 'ATTLOG') {
            Log::channel('slack')->warning('Entering AttLog');
            return $this->processAttLog($request, $device);
        }

        if ($table == 'OPERLOG') {
            Log::channel('slack')->warning('Entering OpLog');
            return $this->processOperLog($request, $device);
        }

        if ($table === 'options') {
            return $this->handleOptionsTable($device, $request);
        }

        if ($table === 'BIODATA') {
            return $this->handleOptionsTable($device, $request);
        }
    }

    private function processAttLog(Request $request, $device)
    {
        $branchId = $device->branch_id;
        $deviceId = $device->id;
        $totalProcessed = 0;
        $deviceSerialNumber = $request->input('SN');

        try {
            $records = preg_split('/\\r\\n|\\r|,|\\n/', $request->getContent());

            foreach ($records as $record) {
                if (empty($record)) {
                    continue;
                }

                $data = explode("\t", $record);
                if (count($data) < 2) {
                    continue;
                }

                $pin = $data[0];
                $timestamp = $data[1];
                $status1 = $this->validateAndFormatInteger($data[2] ?? null);
                $status2 = $this->validateAndFormatInteger($data[3] ?? null);
                $status3 = $this->validateAndFormatInteger($data[4] ?? null);
                $status4 = $this->validateAndFormatInteger($data[5] ?? null);
                $status5 = $this->validateAndFormatInteger($data[6] ?? null);

                $employee = Employee::where('pin', $pin)->first();
                if (!$employee) {
                    Log::error("Employee with PIN $pin not found");
                    continue;
                }

                try {
                    $timeIn = Carbon::createFromFormat('Y-m-d H:i:s', $timestamp);
                } catch (\Exception $e) {
                    Log::error("Invalid timestamp format: $timestamp");
                    continue;
                }

                AttendanceLog::create([
                    'employee_id' => $employee->id,
                    'device_id' => $deviceId,
                    'branch_id' => $branchId,
                    'type' => $status1,
                    'pin' => $pin,
                    'time_in' => $timeIn,
                    'status' => $status1,
                    'device_sn' => $deviceSerialNumber,
                    'work_code' => $status2,  // Adjust as per your table structure
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $totalProcessed++;
                Log::channel('slack')->info('Processed attendance record', [
                    'employee_id' => $employee->id,
                    'device_id' => $deviceId,
                    'branch_id' => $branchId,
                    'pin' => $pin,
                    'time_in' => $timestamp,
                    'status' => $status1,
                    'device_sn' => $deviceSerialNumber,
                    'work_code' => $status2,
                ]);
            }

            return "OK: " . $totalProcessed;
        } catch (Throwable $e) {
            report($e);
            Log::error("Error processing attendance data", ['error' => $e->getMessage()]);
            return "ERROR: " . $totalProcessed . "\n";
        }

    }

    private function processOperLog(Request $request, $device)
    {

        $content = $request->getContent();
        $records = preg_split('/\\r\\n|\\r|,|\\n/', $content); // Split log records
        $totalProcessed = 0;

        foreach ($records as $record) {
            if (empty($record)) {
                continue; // Skip empty records
            }



            if (str_starts_with($record, 'OPLOG')) {
                // Parse the OPLOG record
                $data = explode("\t", $record);

                if (count($data) >= 3) {
                    $operation = $data[0]; // Operation type
                    $pin = $data[1]; // User PIN (if applicable)
                    $timestamp = $data[2]; // Timestamp of operation

                    if ($operation === 'OPLOG 70') {
                        // Handle specific logic for OPLOG 30 (e.g., user creation)
                        Log::info("User creation detected", [
                            'operation' => $operation,
                            'pin' => $pin,
                            'timestamp' => $timestamp,
                        ]);

                    } elseif ($operation === 'OPLOG 4') {
                        // Handle specific logic for OPLOG 4
                        Log::info("Device management log detected", [
                            'operation' => $operation,
                            'pin' => $pin,
                            'timestamp' => $timestamp,
                            'data' => $data
                        ]);
                    } elseif ($operation === 'OPLOG 30') {
                        // Handle specific logic for OPLOG 4
                        Log::info("Device management log detected", [
                            'operation' => $operation,
                            'pin' => $pin,
                            'timestamp' => $timestamp,
                            'data' => $data
                        ]);
                    } elseif ($operation === 'OPLOG 103') {
                        // Handle deletion of user in device
                        Log::info("User deletion log detected", [
                            'operation' => $operation,
                            'pin' => $pin,
                            'timestamp' => $timestamp,
                            'data' => $data
                        ]);
                        $pin = $data[3];
                        try {
                            // Find the employee by PIN
                            $employee = Employee::where('pin', $pin)->first();

                            if ($employee) {
                                // Delete the employee and associated biometric data
                                $employee->biometrics()->delete(); // Assuming Employee has a 'biometrics' relationship
                                $employee->delete();

                                Log::info("User and biometric data successfully deleted", [
                                    'pin' => $pin,
                                    'employee_id' => $employee->id,
                                ]);
                            } else {
                                Log::warning("No employee found for deletion with PIN", ['pin' => $pin]);
                            }
                        } catch (Throwable $e) {
                            Log::error("Error while deleting user and biometric data", [
                                'pin' => $pin,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    } else {
                        // Log unknown OPLOG types
                        Log::warning("Unhandled OPLOG type", [
                            'operation' => $operation,
                            'pin' => $pin,
                            'timestamp' => $timestamp,
                            'data' => $data
                        ]);
                    }
                } else {
                    Log::warning("Incomplete OPLOG data", ['record' => $record]);
                }
            } else {
                // Check the type of log (e.g., OPLOG or FP PIN)
                if (str_starts_with($record, 'FP PIN')) {
                    Log::warning("ENTERING FP PIN");

                    // Parse FP PIN records
                    // parse_str(str_replace(' ', '&', $record), $data);
                    preg_match('/FP PIN=(\d+)\s+FID=(\d+)\s+Size=(\d+)\s+Valid=(\d+)\s+TMP=(.+)/', $record, $matches);

                    Log::info("DATA OF FINGERPRINT", [
                        'data' => $matches
                    ]);

                    $pin = $matches[1] ?? null; // User PIN
                    $fid = $matches[2] ?? null; // Fingerprint ID
                    $fsize = $matches[3] ?? null; // Template size
                    $fvalid = $matches[4] ?? null; // Validity of template
                    $fdata = $matches[5] ?? null; // Template data

                    if ($pin && $fdata) {
                        $employee = Employee::withTrashed()->firstOrNew(['pin' => $pin]);

                        if ($employee) {
                            if ($employee->trashed()) {
                                $employee->restore(); // Restore the soft-deleted record
                            }

                            $employee->fill([
                                'name' => 'New User from Device',
                                'position' => 'Not Set',
                                'employee_id' => 'EMP-' . $pin, // Example employee ID logic
                            ]);

                            $employee->save();
                        }

                        $employee = Employee::updateOrCreate(
                            ['pin' => $pin],
                            [
                                'name' => 'New User from Device', // Default values for new employees
                                'position' => 'Not Set',
                                'employee_id' => 'EMP-' . $pin,
                                'pin' => $pin,
                                'department' => 1, // Default department ID
                                'branch_id' => 1, // Default branch ID
                            ]
                        );

                        Log::info("Employee has been saved!");

                        try {
                            // Decode the biometric template
                            $decodedData = base64_decode($fdata);

                            // Save or update the biometric data
                            EmployeeBiometric::updateOrCreate(
                                [
                                    'employee_id' => $employee->id,
                                    'fid' => $fid, // Ensure unique fingerprint ID
                                ],
                                [
                                    'pin' => $pin,
                                    'biometric_type' => 'fingerprint',
                                    'size' => $fsize,
                                    'valid' => $fvalid,
                                    'biometric_data' => $decodedData,
                                ]
                            );

                            Log::info("Fingerprint data saved for PIN $pin", [
                                'employee_id' => $employee->id,
                                'fid' => $fid,
                                'size' => $fsize,
                                'valid' => $fvalid,
                            ]);
                            $totalProcessed++;
                        } catch (Throwable $e) {
                            Log::error("Failed to save fingerprint data", [
                                'pin' => $pin,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }
                }
            }
        }

        return "OK: $totalProcessed";

    }

    private function validateAndFormatInteger($value)
    {
        return isset($value) && $value !== '' ? (int) $value : null;
    }

    public function getRequest(Request $request)
    {
        $serialNumber = $request->input('SN');

        if (!$serialNumber) {
            Log::error('Invalid request: Missing SN parameter.');
            return response('Invalid request: Missing SN parameter.', 400);
        }

        $device = Device::where('device_sn', $serialNumber)->first();

        if (!$device) {
            Log::error("Device with serial number {$serialNumber} not found.");
            return response('OK', 200);
        }

        Log::info("Device {$serialNumber} activity logged.");

        // Skip if already synced today
        $lastSync = DB::table('device_logs')
            ->where('device_id', $device->id)
            ->value('last_handshake');

        if ($lastSync && Carbon::parse($lastSync)->diffInMinutes(now()) < 10) {
            Log::info("Device {$serialNumber} has already synced today.");
            return response('OK', 200)->header('Content-Type', 'text/plain');
        }

        // Main devices do not sync
        if ($device->main_device) {
            Log::info("Device {$serialNumber} is marked as main. Skipping.");
            return response('OK', 200);
        }

        // Get assigned employees
        $assignedEmployees = Employee::with('biometrics', 'branches', 'roles')
            ->whereHas('branches', fn($q) => $q->where('branches.id', $device->branch_id))
            ->get();

        // Get previously synced employee IDs
        $syncedEmployeeIds = DB::table('device_synced_employees')
            ->where('device_id', $device->id)
            ->pluck('employee_id')
            ->toArray();

        $currentEmployeeIds = $assignedEmployees->pluck('id')->toArray();

        // Determine which employees need to be deleted
        $toDelete = array_diff($syncedEmployeeIds, $currentEmployeeIds);

        $cmdId = 1;
        $commands = [];

        // Deletion commands
        foreach ($toDelete as $employeeId) {
            $employee = Employee::find($employeeId);
            if ($employee) {
                $commands[] = "C:{$cmdId}:DATA DELETE USERINFO PIN={$employee->pin}";
                DB::table('device_synced_employees')
                    ->where('device_id', $device->id)
                    ->where('employee_id', $employee->id)
                    ->delete();
                $cmdId++;
            }
        }

        // Sync/update commands
        foreach ($assignedEmployees as $employee) {
            // Check role for privilege
            // $isAdmin = $employee->roles->contains('name', 'Administrator');
            $pri = $employee->hasRole('Administrator') ? 14 : 0;

            $commands[] = "C:{$cmdId}:DATA UPDATE USERINFO PIN={$employee->pin}\tName={$employee->name}\tPri={$pri}\tCard=\tGrp=1\tTZ=0000000000000000\tVerify=0";
            $cmdId++;

            foreach ($employee->biometrics as $biometric) {
                $commands[] = "C:{$cmdId}:DATA UPDATE FINGERTMP PIN={$employee->pin}\tFID={$biometric->fid}\tSize={$biometric->size}\tValid={$biometric->valid}\tTMP=" . base64_encode($biometric->biometric_data);
                $cmdId++;
            }

            // Track as synced
            DB::table('device_synced_employees')->updateOrInsert(
                ['device_id' => $device->id, 'employee_id' => $employee->id],
                ['updated_at' => now(), 'created_at' => now()]
            );
        }

        // Log sync
        DB::table('device_logs')->updateOrInsert(
            ['device_id' => $device->id, 'serial_number' => $serialNumber],
            ['status' => 'online', 'last_handshake' => now(), 'updated_at' => now()]
        );

        Log::info("Synced employees to device {$serialNumber}", ['cmd_count' => count($commands)]);

        return response(implode("\n", $commands), 200)->header('Content-Type', 'text/plain');
    }


    //WORKING BUT NOT REAL TIME SYNCING
    // public function getRequest(Request $request)
    // {
    //     $serialNumber = $request->input('SN');

    //     if (!$serialNumber) {
    //         Log::error('Invalid request: Missing SN parameter.');
    //         return response('Invalid request: Missing SN parameter.', 400);
    //     }

    //     $device = Device::where('device_sn', $serialNumber)->first();

    //     if (!$device) {
    //         Log::error("Device with serial number {$serialNumber} not found.");
    //         return response('OK', 200);
    //     }

    //     Log::info("Device {$serialNumber} activity logged.");

    //     $lastSync = \DB::table('device_logs')
    //         ->where('device_id', $device->id)
    //         ->value('last_handshake');

    //     if ($lastSync && Carbon::parse($lastSync)->isToday()) {
    //         Log::info("Device {$serialNumber} has already synced today. Skipping sync.");
    //         return response('OK', 200)->header('Content-Type', 'text/plain');
    //     }

    //     if ($device->main_device) {
    //         Log::info("Device {$serialNumber} is the main device. No updates performed.");
    //         return response('OK', 200);
    //     }

    //     $employees = Employee::with('biometrics', 'branches', 'roles')
    //         ->whereHas('branches', function ($query) use ($device) {
    //             $query->where('branches.id', $device->branch_id);
    //         })
    //         ->get();

    //     if ($employees->isEmpty()) {
    //         Log::info("No employees assigned to branch ID {$device->branch_id} for device {$serialNumber}.");
    //         return response('OK', 200);
    //     }

    //     $cmdId = 1;

    //     $clearCommand = "C:{$cmdId}:DATA DELETE USERINFO PIN=All";
    //     $cmdId++;
    //     $clearCommand .= "\nC:{$cmdId}:DATA DELETE FINGERTMP PIN=All";
    //     $cmdId++;

    //     $userCommands = $employees->map(function ($employee) use (&$cmdId) {
    //         $isAdmin = $employee->roles->contains('name', 'Administrator') ? 6 : 0;
    //         $userInfo = "C:{$cmdId}:DATA UPDATE USERINFO PIN={$employee->pin}\tName={$employee->name}\tPri={$isAdmin}\tCard=\tGrp=1\tTZ=0000000000000000\tVerify=0";
    //         $cmdId++;

    //         $fingerprints = $employee->biometrics->map(function ($biometric) use (&$cmdId, $employee) {
    //             $cmd = "C:{$cmdId}:DATA UPDATE FINGERTMP PIN={$employee->pin}\tFID={$biometric->fid}\tSize={$biometric->size}\tValid={$biometric->valid}\tTMP=" . base64_encode($biometric->biometric_data);
    //             $cmdId++;
    //             return $cmd;
    //         })->implode("\n");

    //         return $userInfo . "\n" . $fingerprints;
    //     })->implode("\n");

    //     $commands = $clearCommand . "\n" . $userCommands;

    //     Log::info("Commands sent to device SN {$serialNumber}:\n{$commands}");

    //     \DB::table('device_logs')->updateOrInsert(
    //         ['device_id' => $device->id, 'serial_number' => $serialNumber],
    //         [
    //             'status' => 'online',
    //             'last_handshake' => now(),
    //             'updated_at' => now(),
    //         ]
    //     );

    //     return response($commands, 200)->header('Content-Type', 'text/plain');
    // }

    // public function getRequest(Request $request)
    // {

    //     $serialNumber = $request->input('SN');

    //     // Validate that the Serial Number is provided
    //     if (!$serialNumber) {
    //         Log::error('Invalid request: Missing SN parameter.');
    //         return response('Invalid request: Missing SN parameter.', 400);
    //     }



    //     // Fetch the device by serial number
    //     $device = Device::where('device_sn', $serialNumber)->first();



    //     if (!$device) {
    //         Log::error("Device with serial number {$serialNumber} not found.");
    //         return response('OK', 200);
    //     }



    //     Log::info("Device {$serialNumber} activity logged.");

    //     // Check if the device has already synced for the day
    //     $lastSync = \DB::table('device_logs')
    //         ->where('device_id', $device->id)
    //         ->value('last_handshake');

    //     if ($lastSync && Carbon::parse($lastSync)->isToday()) {
    //         Log::info("Device {$serialNumber} has already synced today. Skipping sync.");
    //         return response('OK', 200)->header('Content-Type', 'text/plain');
    //     }

    //     // Check if the device is the main device
    //     if ($device->main_device) {
    //         Log::info("Device {$serialNumber} is the main device. No updates performed.");
    //         return response('OK', 200);
    //     }

    //     // Fetch users assigned to the branch of this device
    //     $employees = Employee::with('biometrics', 'branches')
    //         ->whereHas('branches', function ($query) use ($device) {
    //             $query->where('branches.id', $device->branch_id); // Explicitly reference 'branches.id'
    //         })
    //         ->get();

    //     if ($employees->isEmpty()) {
    //         Log::info("No employees assigned to branch ID {$device->branch_id} for device {$serialNumber}.");
    //         return response('OK', 200);
    //     }

    //     // Initialize command ID (can be incrementing or unique)
    //     $cmdId = 1;

    //     // Prepare commands for all employees
    //     $commands = $employees->map(function ($employee) use (&$cmdId) {
    //         // Prepare USERINFO command
    //         $userInfoCommand = "C:{$cmdId}:DATA UPDATE USERINFO PIN={$employee->pin}\tName={$employee->name}\tPri=0\tCard=\tGrp=1\tTZ=0000000000000000\tVerify=0";
    //         $cmdId++;

    //         // Prepare FINGERTMP commands for each biometric record
    //         $fingerprintCommands = $employee->biometrics->map(function ($biometric) use (&$cmdId, $employee) {
    //             return "C:{$cmdId}:DATA UPDATE FINGERTMP PIN={$employee->pin}\tFID={$biometric->fid}\tSize={$biometric->size}\tValid={$biometric->valid}\tTMP=" . base64_encode($biometric->biometric_data);
    //         })->implode("\n");

    //         $cmdId += $employee->biometrics->count();

    //         // Combine USERINFO and FINGERTMP commands
    //         return $userInfoCommand . "\n" . $fingerprintCommands;
    //     })->implode("\n");

    //     // Log the commands for debugging
    //     Log::info("Commands sent to device SN {$serialNumber}: \n{$commands}");
    //     $logEntry = \DB::table('device_logs')->updateOrInsert(
    //         ['device_id' => $device->id, 'serial_number' => $serialNumber],
    //         [
    //             'status' => 'online',
    //             'last_handshake' => now(),
    //             'updated_at' => now(),
    //         ]
    //     );
    //     return response($commands, 200)->header('Content-Type', 'text/plain');
    // }

    public function devicecmd(Request $request)
    {
        return 'OK';
    }

    private function handleOptionsTable($device, Request $request)
    {
        Log::info("Processing 'options' table for device {$device->device_sn}");
        $deviceSerialNumber = $request->input("$device->device_sn");
        // Example of logging device options
        $options = $request->all();
        Log::info("Received options from device {$device->device_sn}", ['options' => $options]);

        $response = "OK";

        return response($response, 200)->header('Content-Type', 'text/plain');
    }
}
