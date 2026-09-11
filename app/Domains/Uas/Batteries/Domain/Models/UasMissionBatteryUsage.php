<?php

namespace App\Domains\Uas\Batteries\Domain\Models;

use App\Domains\Uas\Missions\Domain\Models\UasMission;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UasMissionBatteryUsage extends Model
{
    protected $fillable = [
        'uas_mission_id', 'uas_battery_id', 'recorded_by', 'cycles_added', 'state_of_charge_start', 'state_of_charge_end', 'used_at', 'notes',
    ];

    protected function casts(): array
    {
        return ['used_at' => 'datetime'];
    }

    public function mission(): BelongsTo
    {
        return $this->belongsTo(UasMission::class, 'uas_mission_id');
    }

    public function battery(): BelongsTo
    {
        return $this->belongsTo(UasBattery::class, 'uas_battery_id');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}