<?php

namespace App\Domains\Uas\AeronauticalInformation\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class AeronauticalInformationItem extends Model
{
    protected $table = 'uas_aeronautical_information_items';

    protected $guarded = [];

    protected static function booted(): void
    {
        static::updating(function (self $item) {
            if (array_diff(array_keys($item->getDirty()), ['superseded_at', 'updated_at']) !== []) {
                throw new LogicException('Create a new interpretation revision instead of overwriting source interpretation.');
            }
        });
        static::deleting(fn () => throw new LogicException('Aeronautical history cannot be deleted.'));
    }

    protected function casts(): array
    {
        return ['usable_for_release' => 'boolean', 'permanent' => 'boolean', 'geometry_json' => 'array', 'interpretation' => 'array', 'issued_at' => 'immutable_datetime', 'effective_from' => 'immutable_datetime', 'effective_until' => 'immutable_datetime', 'received_at' => 'immutable_datetime', 'superseded_at' => 'immutable_datetime'];
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(AeronauticalSourceRecord::class, 'source_record_id');
    }
}
