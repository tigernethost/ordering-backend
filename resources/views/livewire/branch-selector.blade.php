<div>
    <label class="form-label">Select Branch</label>
    <select wire:change="onBranchChange($event.target.value)" class="form-select">
        @foreach($branches as $branch)
            <option value="{{ $branch->id }}" @if ($branch->id == $selectedBranch) selected @endif>
                {{ $branch->name }}
            </option>
        @endforeach
    </select>
</div>