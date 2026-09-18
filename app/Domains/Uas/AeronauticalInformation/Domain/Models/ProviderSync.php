<?php

namespace App\Domains\Uas\AeronauticalInformation\Domain\Models;

use Illuminate\Database\Eloquent\Model;

class ProviderSync extends Model
{
    protected $table = 'uas_aeronautical_provider_syncs';

    protected $guarded = [];

    protected $hidden = ['sync_metadata', 'approval_fingerprint'];

    public $timestamps = false;

    protected function casts(): array
    {
        return ['started_at' => 'immutable_datetime', 'completed_at' => 'immutable_datetime', 'dataset_timestamp' => 'immutable_datetime', 'sync_metadata' => 'encrypted:array', 'coverage' => 'array', 'usable_for_release' => 'boolean'];
    }
}
