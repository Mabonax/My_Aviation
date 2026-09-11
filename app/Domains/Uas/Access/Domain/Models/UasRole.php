<?php

namespace App\Domains\Uas\Access\Domain\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class UasRole extends Model
{
    protected $fillable = ['name', 'label', 'permissions'];

    protected function casts(): array
    {
        return ['permissions' => 'array'];
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'uas_role_user')->withTimestamps();
    }
}
