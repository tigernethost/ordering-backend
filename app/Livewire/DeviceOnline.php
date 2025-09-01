<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Device;
use App\Models\Branch;

class DeviceOnline extends Component
{
    public $onlineCount = 0;
    public $branchesOnline = [];

    protected $listeners = ['deviceStatusUpdated' => 'fetchOnlineData'];

    public function mount()
    {
        $this->fetchOnlineData();
    }

    public function fetchOnlineData()
    {
        // Count the number of online devices
        $this->onlineCount = Device::where('status', 'online')->count();

        // Get branches with at least one online device
        $this->branchesOnline = Branch::whereHas('devices', function ($query) {
            $query->where('status', 'online');
        })->pluck('name')->toArray(); // Adjust the 'name' field to match your Branch model
    }


    public function render()
    {
        return view('livewire.device-online');
    }
}
