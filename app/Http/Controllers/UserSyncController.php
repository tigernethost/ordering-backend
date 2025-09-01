<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\DeviceSyncService;

class UserSyncController extends Controller
{
    protected $deviceSyncService;

    public function __construct(DeviceSyncService $deviceSyncService)
    {
        $this->deviceSyncService = $deviceSyncService;
    }

    /**
     * Register a new user on the main device and sync to all devices.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function registerAndSync(Request $request)
    {
        $request->validate([
            'userid' => 'required|string',
            'name' => 'required|string',
            'password' => 'nullable|string',
            'role' => 'required|integer',
            'card' => 'nullable|string',
        ]);

        $userData = [
            'uid' => rand(1, 10000), // Auto-generate a unique ID
            'userid' => $request->input('userid'),
            'name' => $request->input('name'),
            'password' => $request->input('password'),
            'role' => $request->input('role'),
            'card' => $request->input('card'),
        ];

        // Sync the user to all devices
        $this->deviceSyncService->syncNewUser($userData);

        return response()->json(['message' => 'User registered and synced successfully.'], 200);
    }
}
