<?php

namespace App\Domains\Uas\Maintenance\Domain\Models;

use Illuminate\Database\Eloquent\Model;

class MaintenanceTask extends Model
{
    protected $table = 'uas_maintenance_tasks';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['due_at' => 'date', 'due_hours' => 'decimal:2', 'interval_hours' => 'decimal:2',
            'interval_days' => 'integer', 'interval_cycles' => 'integer',
            'completed_at' => 'datetime', 'completion_evidence' => 'array'];
    }
}
