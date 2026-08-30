<?php

namespace App\Livewire\Setup;

use App\Models\Demand;
use Livewire\Component;
use Livewire\WithPagination;

class SyncLogs extends Component
{
    use WithPagination;

    public function render()
    {
        $demands = Demand::with('contact')
            ->latest()
            ->paginate(10);

        return view('livewire.setup.sync-logs', ['demands' => $demands]);
    }
}
