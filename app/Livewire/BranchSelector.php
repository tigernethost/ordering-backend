<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Branch; // Replace with your Branch model

class BranchSelector extends Component
{
    public $branches = [];
    public $selectedBranch = null;

    public function mount()
    {
        // Fetch all branches
        $user = backpack_auth()->user(); // Get the currently authenticated user

        if ($user) {
            // Fetch branches assigned to the user
            $this->branches = $user->branches()->orderBy('name')->get();
        } else {
            $this->branches = collect([]); // Empty collection if no user is authenticated
        }

        // Set the default selected branch (e.g., the first branch)
        $this->selectedBranch = $this->branches->first()->id ?? null;


        // Emit the selected branch on mount
        $this->dispatch('branchChanged', $this->selectedBranch);
    }

    public function onBranchChange($value)
    {
        $this->dispatch('branchChanged', $value);
    }

    public function render()
    {
        return view('livewire.branch-selector');
    }
}
