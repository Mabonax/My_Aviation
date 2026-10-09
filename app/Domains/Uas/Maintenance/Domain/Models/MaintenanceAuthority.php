<?php

namespace App\Domains\Uas\Maintenance\Domain\Models;

use Illuminate\Database\Eloquent\Model;

class MaintenanceAuthority extends Model
{
    protected $table = 'uas_maintenance_authorities';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['valid_until' => 'date', 'revoked_at' => 'datetime', 'can_return_to_service' => 'boolean'];
    }
}
