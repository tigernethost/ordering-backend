<?php

namespace App\Http\Controllers\Api;

use App\Console\Commands\CleanDailyAttendance;
use App\Http\Controllers\Controller;
use App\Models\AttendanceLog;
use App\Models\Branch;
use App\Models\CleanAttendanceLog;
use App\Models\Department;
use App\Models\Device;
use App\Models\Employee;
use App\Models\EmployeeBiometric;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class HRISController extends Controller
{
    public function getUser(Request $request)
    {
		// Use Laravel's default Auth system
		$user = Auth::user();
	
		if ($user) {
			\Log::info('Authenticated User:', ['user' => $user->toArray()]);
			return $user;
		} else {
			\Log::error('No Authenticated User Found');
			return response()->json(['error' => 'Unauthenticated.'], 401);
		}
    }


	public function getAllEmployees()
	{
		$employees = Employee::all();

		return response()->json($employees);
	}
	
	public function getAllBranches()
	{
		$branches = Branch::all();

		return response()->json($branches);
	}

	public function getAllDepartments()
	{
		$departments = Department::all();

		return response()->json($departments);
	}
	
	public function getBranchName($branchId)
	{
		return Branch::where('id', $branchId)->value('name') ?? 'Unknown Branch';
	}
	
	public function getEmployee($employeeId)
	{
		return Employee::where('id', $employeeId)->first();
	}
	
	public function getDailyAttendance()
	{
		//$today = Carbon::today();
		$today = Carbon::parse('2025-02-14');

		$attendance = AttendanceLog::whereDate('time_in', $today)
			->with('employee')
			->orderBy('time_in', 'asc')
			->get()
			->groupBy('employee_id');


		$dailyAttendance = [];
		
		foreach ($attendance as $employeeId => $logs) {
			//return response()->json($logs->pluck('type'));
			$checkIn = $logs->firstWhere('type', '0');
			$checkOut = $logs->firstWhere('type', '1');
			$breakOut = $logs->where('type', '1')->skip(1)->first();
			$breakIn = $logs->where('type', '0')->skip(1)->first();
			$otIn = $logs->firstWhere('type', '4');
			$otOut = $logs->firstWhere('type', '5');

			$dailyAttendance[] = [
				'employee_id' => optional($logs->first()->employee)->employee_id,
				'branch' => optional($logs->first()->branch)->name,
				'department' => optional(optional($logs->first()->employee)->department)->name,
				'employee_name' => optional($logs->first()->employee)->name,
				'time_in' => $checkIn ? Carbon::parse($checkIn->time_in)->setTimezone('Asia/Manila')->format('Y-m-d H:i:s') : null,
				'break_out' => $breakOut ? Carbon::parse($breakOut->time_in)->setTimezone('Asia/Manila')->format('Y-m-d H:i:s') : null,
				'break_in' => $breakIn ? Carbon::parse($breakIn->time_in)->setTimezone('Asia/Manila')->format('Y-m-d H:i:s') : null,
				'time_out' => $checkOut ? Carbon::parse($checkOut->time_in)->setTimezone('Asia/Manila')->format('Y-m-d H:i:s') : null,
				'ot_in' => $otIn ? Carbon::parse($otIn->time_in)->setTimezone('Asia/Manila')->format('Y-m-d H:i:s') : null,
				'ot_out' => $otOut ? Carbon::parse($otOut->time_in)->setTimezone('Asia/Manila')->format('Y-m-d H:i:s') : null
			];
		}

		return response()->json($dailyAttendance);
	}

	public function getAttendanceBaseonPeriod(Request $request)
	{
		$startDate = $request->query('start_date');
		$endDate = $request->query('end_date');
	
		// Validate the date inputs
		if (!$startDate || !$endDate) {
			return response()->json(['error' => 'Start date and end date are required'], 400);
		}
	
		// Convert dates to Carbon instances
		$startDate = Carbon::parse($startDate)->startOfDay();
		$endDate = Carbon::parse($endDate)->endOfDay();
	
		// Retrieve attendance logs with employee details
		$attendanceLogs = AttendanceLog::whereBetween('time_in', [$startDate, $endDate])
			->orderBy('time_in', 'asc')
			->get()
			->groupBy('employee_id');
	
		// Format response and reset array keys
		$formattedAttendanceLogs = $attendanceLogs->map(function ($logs) {
			$employee = $logs->first()->employee; // Get employee details from the first log
	
			return [
				'employee_id' => $employee->employee_id,
				'employee_name' => $employee->name ?? 'Unknown',
				'branch' => $employee->branch->name ?? 'Unknown',
				'department' => $employee->department->name ?? 'Unknown',
				'logs' => $logs->groupBy(function ($log) {
					return Carbon::parse($log->time_in)->timezone('Asia/Manila')->format('Y-m-d'); // Group by correct date
				})->map(function ($logsByDate, $date) {
					return [
						'date' => $date,
						'entries' => $logsByDate->map(function ($log) {
							return [
								'time_in' => Carbon::parse($log->time_in)->timezone('Asia/Manila')->format('Y-m-d H:i:s'),
								'type' => $this->getTypeLabel($log->type), // Convert type to readable text
							];
						}),
					];
				})->values(), // Reset array keys
			];
		})->values(); // Reset array keys to remove employee ID as key
	
		return response()->json($formattedAttendanceLogs);
	}
	
	private function getTypeLabel($type)
	{
		$types = [
			0 => 'Check In',
			1 => 'Check Out',
			4 => 'OT In',
			5 => 'OT Out'
		];
	
		return $types[$type] ?? 'Unknown';
	}
	
	/**
	 * ATTENDANCE DISPUTES
	*/

	public function getEmployeeAttendanceWeek($employee_id)
	{
		$today = Carbon::now(); // Use the current date
		// $today = Carbon::parse('2025-02-15');
		$startOfWeek = $today->copy()->startOfWeek(Carbon::SUNDAY); // Start week on Sunday (optional)
		$endOfWeek = $today->copy()->endOfWeek(Carbon::SATURDAY); // End the week on Saturday (optional)

		$employee = Employee::where('employee_id', $employee_id)->first();
		// Fetch logs for the current week, filtered by employee
		// $attendances = AttendanceLog::where('employee_id', $employee->id)
		//                             ->whereBetween('time_in', [$startOfWeek, $endOfWeek])
		//                             ->orderBy('time_in', 'asc')
		//                             ->get();

		// return response()->json($attendances);
		$attendances = AttendanceLog::with(['branch', 'device'])
		->where('employee_id', $employee->id)
		->whereBetween('time_in', [$startOfWeek, $endOfWeek])
		->orderBy('time_in', 'asc')
		->get();


		$typeMapping = [
			'0' => 'Check In',
			'1' => 'Check Out',
			'4' => 'OT In',
			'5' => 'OT Out',
		];
		// Format the response to include branch and device names	
		$formattedAttendances = $attendances->map(function ($attendance) use ($typeMapping) {
			return [
				'id' => $attendance->id,
				'employee_id' => $attendance->employee_id,
				'branch' => $attendance->branch->name ?? 'N/A', // Get branch name or N/A if not available
				'branch_id' => $attendance->branch_id, 
				'device' => $attendance->device->device_name ?? 'N/A', // Get device name or N/A if not available
				'pin' => $attendance->pin,
				// 'time_in' => $attendance->time_in,
				'time_in' => Carbon::parse($attendance['time_in'])
				->setTimezone('Asia/Manila')
				->format('M d, Y h:i A'),
				'type' => $typeMapping[$attendance->type] ?? 'Unknown',
				'type_id' =>$attendance->type,
				'created_at' => $attendance->created_at,
				'updated_at' => $attendance->updated_at,
				'status' => $attendance->status,
				'device_sn' => $attendance->device_sn,
				'work_code' => $attendance->work_code,
			];
		});

		return response()->json($formattedAttendances);
	}

	public function getEmployeeAttendanceRange($employee_id,Request $request)
	{
		// Get start_date and end_date from query parameters
		$start_date = $request->query('start_date');
		$end_date = $request->query('end_date');
	
		// Parse the start and end dates to Carbon instances for proper date handling
		$start = Carbon::parse($start_date);
		$end = Carbon::parse($end_date);
		// Parse the start and end dates to Carbon instances for proper date handling
		$start = Carbon::parse($start_date);
		$end = Carbon::parse($end_date);

		$employee = Employee::where('employee_id', $employee_id)->first();
		// Fetch logs between the start and end date, filtered by employee
		// $attendances = AttendanceLog::where('employee_id', $employee->id)
		//                             ->whereBetween('time_in', [$start, $end])
		//                             ->orderBy('time_in', 'asc')
		//                             ->get();

		// return response()->json($attendances);

		$attendances = AttendanceLog::with(['branch', 'device'])
		->where('employee_id', $employee->id)
		->whereBetween('time_in', [$start, $end])
		->orderBy('time_in', 'asc')
		->get();

		$typeMapping = [
			'0' => 'Check In',
			'1' => 'Check Out',
			'4' => 'OT In',
			'5' => 'OT Out',
		];

		// Format the response to include branch and device names
		$formattedAttendances = $attendances->map(function ($attendance) use ($typeMapping) {
		return [
			'id' => $attendance->id,
			'employee_id' => $attendance->employee_id,
			'branch' => $attendance->branch->name ?? 'N/A', // Get branch name or N/A if not available
			'branch_id' => $attendance->branch_id, 
			'device' => $attendance->device->device_name ?? 'N/A', // Get device name or N/A if not available
			'time_in' => Carbon::parse($attendance['time_in'])
			->setTimezone('Asia/Manila')
			->format('M d, Y h:i A'),
			'pin' => $attendance->pin,
			'type' => $typeMapping[$attendance->type] ?? 'Unknown',
			'type_id' =>$attendance->type,
			'created_at' => $attendance->created_at,
			'updated_at' => $attendance->updated_at,
			'status' => $attendance->status,
			'device_sn' => $attendance->device_sn,
			'work_code' => $attendance->work_code,
		];
		});

		return response()->json($formattedAttendances);
	}

	public function getBranches(){
		$branches = Branch::all();
		return response($branches);
	}

	public function disputeApprove(Request $request)
	{
		$branch = Branch::where('name', $request->branch_name)->first();

        // Create a new instance of the model and save data to the database
		
		if($request->type === "new"){
			
			$employee = Employee::where('employee_id', $request->employee_id)->first();
			if($employee){
				$attendance = new AttendanceLog();
				$attendance->employee_id = $employee->id;
				$attendance->device_id = 1;
				$attendance->type = $request->time_in_type;
				$attendance->branch_id =  $branch->id;
				$attendance->pin = 1;
				$attendance->time_in = $request->new_time_in;
				$attendance->device_sn = "CNYG233560597";
				$attendance->work_code = 1;
				$attendance->save();

				$cleanDailyAttendance = CleanAttendanceLog::where('employee_id', $employee->id)->where('date', $request->date)->first();
				$logType = $request->log_type;

				\Log::info("Clean Daily Attendance", ['cleanDailyAttendance' => $cleanDailyAttendance]);

				if ($cleanDailyAttendance) {
					$logType = $request->log_type; // Make sure this is passed
					$newTime = $request->new_time_in;
				
					if ($logType === 'time_in') {
						$cleanDailyAttendance->update(['check_in' => $newTime]);
					} elseif ($logType === 'break_out') {
						$cleanDailyAttendance->update(['break_out' => $newTime]);
					} elseif ($logType === 'break_in') {
						$cleanDailyAttendance->update(['break_in' => $newTime]);
					} else {
						$cleanDailyAttendance->update(['check_out' => $newTime]);
					}
				
					$cleanDailyAttendance->refresh();
				
					$missingParts = [];
				
					if (!$cleanDailyAttendance->check_in) {
						$missingParts[] = 'check_in';
					}
					if (!$cleanDailyAttendance->break_out) {
						$missingParts[] = 'break_out';
					}
					if (!$cleanDailyAttendance->break_in) {
						$missingParts[] = 'break_in';
					}
					if (!$cleanDailyAttendance->check_out) {
						$missingParts[] = 'check_out';
					}
				
					$cleanDailyAttendance->update([
						'status' => empty($missingParts) ? 'complete' : 'incomplete',
						'missing_parts' => json_encode($missingParts),
					]);

					\Log::info("Clean Daily Attendance", ['cleanDailyAttendance' => $cleanDailyAttendance]);
				}
				
				

				if($attendance){
					return response($attendance, 201);
				}

				$response = [
					'message' => "Failed to Create Attendnce",
				];
				return response($response, 403);
			}
			
			$response = [
				'message' => "Employee is missing",
			];
			return response($response, 403);
		}

		
		if ($request->type === "change") {
			$employee = Employee::where('employee_id', $request->employee_id)->firstOrFail();
		
			$cleanDailyAttendance = CleanAttendanceLog::where('employee_id', $employee->id)
				->where('date', $request->date)
				->first();

			\Log::info("clean attendance", ['clean' => $cleanDailyAttendance]);
		
			if (!$cleanDailyAttendance) {
				return response(['message' => "Clean daily attendance not found."], 404);
			}
		
			$logType = $request->log_type;
			$newTime = $request->new_time_in;
		
			$updateFields = [
				'time_in' => 'check_in',
				'break_out' => 'break_out',
				'break_in' => 'break_in',
				'time_out' => 'check_out',
			];
		
			if (isset($updateFields[$logType])) {
				$cleanDailyAttendance->update([
					$updateFields[$logType] => $newTime
				]);
			}
		
			$cleanDailyAttendance->refresh();
		
			$missingParts = collect(['check_in', 'break_out', 'break_in', 'check_out'])
				->filter(fn($part) => !$cleanDailyAttendance->$part)
				->values()
				->all();
		
			$cleanDailyAttendance->update([
				'status' => empty($missingParts) ? 'complete' : 'incomplete',
				'missing_parts' => json_encode($missingParts),
			]);
		
			return response(['message' => "Clean daily attendance updated."], 200);
		}
		


		$response = [
			'message' => "Something went wrong",
		];
		return response($response, 403);

		
	}


	/* BIOMETRICS */

	public function getEmployeeBiometrics()
	{
		$biometrics = EmployeeBiometric::all()->map(function ($item) {
			return [
				'id' => $item->id,
				'employee_id' => $item->employee->employee_id,
				'pin' => $item->pin,
				'biometric_type' => $item->biometric_type,
				'fid' => $item->fid,
				'size' => $item->size,
				'valid' => $item->valid,
				'biometric_data' => base64_encode($item->biometric_data), // encode here
				'created_at' => $item->created_at,
				'updated_at' => $item->updated_at,
			];
		});
	
		return response()->json($biometrics);
	}
	

	public function getAttendanceLogs(Request $request)
	{
		$perPage = $request->input('per_page', 500);
		
		$paginated = AttendanceLog::with('employee')
			->whereNotNull('employee_id')
			->paginate($perPage);
	
		$data = $paginated->getCollection()->map(function ($item) {
			return [
				'id' => $item->id,
				'employee_id' => optional($item->employee)->employee_id,
				'device_id' => $item->device_id,
				'type' => $item->type,
				'branch_id' => $item->branch_id,
				'pin' => $item->pin,
				'branch_name' => optional($item->branch)->name,
				'time_in' => $item->time_in,
				'status' => $item->status,
				'work_code' => $item->work_code,
				'device_sn' => $item->device_sn,
				'created_at' => $item->created_at,
				'updated_at' => $item->updated_at,
			];
		});
	
		return response()->json([
			'current_page' => $paginated->currentPage(),
			'last_page' => $paginated->lastPage(),
			'per_page' => $paginated->perPage(),
			'total' => $paginated->total(),
			'data' => $data,
		]);
	}

	public function getDevices()
	{
		$devices = Device::all()->map(function ($item) {

			return [
				'id' => $item->id,
				'branch_id' => $item->branch_id,
				'ip_address' => $item->ip_address,
				'branch_name' => optional($item->branch)->name,
				'port' => $item->port,
				'device_name' => $item->device_name,
				'main_device' => $item->main_device,
				'status' => $item->status,
				'device_sn' => $item->device_sn,
				'created_at' => $item->created_at,
				'updated_at' => $item->updated_at,
			];
		});



		return response()->json($devices);
	}
	
	
}