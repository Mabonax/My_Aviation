<?php

namespace App\Domains\Uas\AeronauticalInformation\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;

abstract class ImmutableEvidence extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Historical aeronautical evidence is immutable; create a new revision.'));
        static::deleting(fn () => throw new LogicException('Historical aeronautical evidence cannot be deleted.'));
    }
}
