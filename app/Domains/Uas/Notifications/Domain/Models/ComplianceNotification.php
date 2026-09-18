<?php

namespace App\Domains\Uas\Notifications\Domain\Models;

use App\Domains\Uas\Regulations\Domain\Models\RegulatoryRequirement;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ComplianceNotification extends Model
{
    protected $fillable = ['user_id', 'notifiable_record_type', 'notifiable_record_id', 'requirement_id', 'notification_type', 'idempotency_key', 'channel', 'priority', 'status', 'delivery_attempts', 'subject', 'message', 'failure_reason', 'due_at', 'sent_at', 'read_at', 'acknowledged_at'];

    protected function casts(): array
    {
        return [
            'delivery_attempts' => 'integer',
            'due_at' => 'datetime',
            'sent_at' => 'datetime',
            'read_at' => 'datetime',
            'acknowledged_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function notifiableRecord(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'notifiable_record_type', 'notifiable_record_id');
    }

    public function regulatoryRequirement(): BelongsTo
    {
        return $this->belongsTo(RegulatoryRequirement::class, 'requirement_id', 'requirement_id');
    }
}
