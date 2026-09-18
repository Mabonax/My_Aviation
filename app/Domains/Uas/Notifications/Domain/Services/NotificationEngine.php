<?php

namespace App\Domains\Uas\Notifications\Domain\Services;

class NotificationEngine
{
    public const CHANNELS = [
        'in_application' => 'In Application',
        'email' => 'Email',
        'mobile_push' => 'Mobile Push',
        'sms' => 'SMS',
        'whatsapp' => 'WhatsApp',
    ];

    public const PRIORITIES = [
        'low' => 'Low',
        'normal' => 'Normal',
        'high' => 'High',
        'critical' => 'Critical',
    ];

    public const STATUSES = [
        'pending' => 'Pending',
        'sent' => 'Sent',
        'read' => 'Read',
        'acknowledged' => 'Acknowledged',
        'failed' => 'Failed',
        'cancelled' => 'Cancelled',
    ];

    public const TYPES = [
        'rpc_expiry' => 'RPC expiry',
        'revalidation_window' => 'Revalidation window',
        'medical_expiry' => 'Medical expiry',
        'security_check' => 'Security check',
        'training_competency_expiry' => 'Training or competency expiry',
        'aircraft_registration' => 'Aircraft registration',
        'uasla_expiry' => 'UASLA expiry',
        'uasoc_expiry' => 'UASOC expiry',
        'insurance' => 'Insurance',
        'maintenance' => 'Maintenance',
        'operations_manual_acknowledgement' => 'Operations Manual acknowledgement',
        'corrective_action' => 'Corrective action',
        'application_deadline' => 'Application deadline',
        'regulatory_change' => 'Regulatory change',
    ];
}
