export interface ExternalIntegrationListItem {
    id: number;
    name: string;
    authority: string;
    classification: string;
    regulatory_area: string;
    supported_process: string;
    api_assumption_blocked: boolean;
    status: string;
}

export interface ExternalIntegration extends ExternalIntegrationListItem {
    authoritative_url: string | null;
    evidence_required: string;
    workflow_notes: string;
    verified_at: string | null;
    created_at: string | null;
    updated_at: string | null;
}

export interface ExternalIntegrationOptions {
    classifications: Record<string, string>;
    statuses: Record<string, string>;
}
