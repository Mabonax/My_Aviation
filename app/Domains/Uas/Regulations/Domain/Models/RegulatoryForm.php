<?php

namespace App\Domains\Uas\Regulations\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RegulatoryForm extends Model
{
    protected $fillable = ['previous_form_id', 'form_code', 'form_title', 'regulatory_area', 'revision', 'effective_date', 'superseded_date', 'source_reference', 'source_url', 'required_transaction', 'status', 'verified_at'];

    protected function casts(): array
    {
        return [
            'effective_date' => 'date',
            'superseded_date' => 'date',
            'verified_at' => 'datetime',
        ];
    }

    public function previousForm(): BelongsTo
    {
        return $this->belongsTo(self::class, 'previous_form_id');
    }

    public function supersedingForms(): HasMany
    {
        return $this->hasMany(self::class, 'previous_form_id');
    }
}
