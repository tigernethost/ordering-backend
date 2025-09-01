<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use ZK\ZKTeco;

class DeviceSyncService
{
    protected $devices;

    public function __construct()
    {
        // Fetch all active devices from the database
        $this->devices = \App\Models\Device::where('is_active', true)->get()->toArray();
    }

    /**
     * Perform handshake with a device to verify connectivity and readiness.
     *
     * @param string $ip
     * @param int $port
     * @return bool
     */
    public function handshake(string $ip, int $port): bool
    {
        $url = "http://$ip:$port/iclock/cdata?SN=EXPECTED_SERIAL_NUMBER&options=all&pushver=2.4.1";

        $response = Http::get($url);

        if ($response->ok() && strpos($response->body(), 'GET OPTION FROM:') !== false) {
            return true;
        }

        \Log::error("Handshake failed for device $ip:$port");
        return false;
    }

    /**
     * Sync a new user to all active devices.
     *
     * @param array $userData
     * @return void
     */
    public function syncNewUser(array $userData): void
    {
        foreach ($this->devices as $device) {
            try {
                // Perform handshake before sending user data
                if (!$this->handshake($device['ip'], $device['port'])) {
                    continue;
                }

                // Push user data to the device
                $zk = new ZKTeco($device['ip'], $device['port']);
                $zk->connect();

                $zk->setUser(
                    $userData['uid'],
                    $userData['userid'],
                    $userData['name'],
                    $userData['password'],
                    $userData['role'],
                    $userData['card']
                );

                $zk->disconnect();
            } catch (\Exception $e) {
                \Log::error("Failed to sync user to device {$device['ip']}: {$e->getMessage()}");
            }
        }
    }
}
