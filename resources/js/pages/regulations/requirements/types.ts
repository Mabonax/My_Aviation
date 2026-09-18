export interface RegulatoryRequirementListItem {
    id: number;
    requirement_id: string;
    regulation_part: string;
    clause_reference: string | null;
    title: string;
    status: string;
    source_version: string;
    effective_date: string | null;
    superseded_date: string | null;
    training_links_count: number;
    superseding_versions_count: number;
}

export interface RegulatoryRequirementSummary {
    id: number;
    requirement_id: string;
    title: string;
    source_version: string;
    status: string;
    effective_date: string | null;
}

export interface RegulatoryRequirement {
    id: number;
    previous_requirement_id: number | null;
    previous_requirement: RegulatoryRequirementSummary | null;
    superseding_requirements: RegulatoryRequirementSummary[];
    requirement_id: string;
    regulation_part: string;
    clause_reference: string | null;
    title: string;
    requirement_text: string;
    responsible_party: string;
    applicability: string;
    system_control: string;
    evidence_required: string | null;
    frequency: string | null;
    validity_period: string | null;
    retention_period: string | null;
    effective_date: string | null;
    superseded_date: string | null;
    official_source: string;
    source_version: string;
    status: string;
    training_links: Array<{ id: number; course: string | null; requirement_reference: string; link_status: string }>;
}
