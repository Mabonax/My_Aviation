<?php

namespace App\Domains\Uas\Aircraft\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UasManufacturer extends Model
{
    protected $table = 'uas_manufacturers';

    protected $fillable = [
        'name',
        'slug',
        'website_url',
        'status',
    ];

    public function aircraftModels(): HasMany
    {
        return $this->hasMany(UasAircraftModel::class, 'manufacturer_id');
    }
}
