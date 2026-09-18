<?php

namespace App\Domains\Uas\Access\Domain\Models;

use Illuminate\Database\Eloquent\Model;

class WorkosMobileSession extends Model
{
    protected $guarded = [];
    protected $hidden = ['sealed_session', 'session_id'];

    protected function casts(): array
    {
        return ['sealed_session' => 'encrypted'];
    }
}
