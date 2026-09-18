export interface ComplianceNotificationListItem {
    id: number;
    requirement_id: string | null;
    notification_type: string;
    channel: string;
    priority: string;
    status: string;
    subject: string;
    due_at: string | null;
    sent_at: string | null;
    recipient: string | null;
}

export interface ComplianceNotification {
    id: number;
    user_id: number | null;
    recipient: string | null;
    requirement_id: string | null;
    regulatory_requirement: { id: number; title: string; source_version: string } | null;
    notification_type: string;
    idempotency_key: string | null;
    channel: string;
    priority: string;
    status: string;
    delivery_attempts: number;
    subject: string;
    message: string;
    failure_reason: string | null;
    due_at: string | null;
    sent_at: string | null;
    read_at: string | null;
    acknowledged_at: string | null;
}

export interface ComplianceNotificationOptions {
    types: Record<string, string>;
    channels: Record<string, string>;
    priorities: Record<string, string>;
    statuses: Record<string, string>;
    users: Array<{ id: number; label: string }>;
}
