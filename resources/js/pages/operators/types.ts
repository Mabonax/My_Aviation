export interface OperatorProfile {
    id: number;
    legal_entity: string;
    trading_name: string | null;
    registration_number: string | null;
    uasoc_number: string | null;
    certificate_issue_date: string | null;
    certificate_expiry_date: string | null;
    status: string;
    accountable_manager: string;
    responsible_person_flight_operations: string;
    responsible_person_aircraft: string;
    safety_manager: string | null;
    security_coordinator: string | null;
    operating_bases: string[];
    approved_aircraft: number[];
    approved_pilots: number[];
    operations_specifications: string[];
    evidence_references: string[];
    evidence: {
        count: number;
        documents: Array<{
            id: number;
            document_uid: string;
            title: string;
            category: string;
            status: string;
            version: number;
            expires_at: string | null;
            evidence_role: string;
            requirement_id: string | null;
            notes: string | null;
            attached_at: string | null;
        }>;
    };
    regulatory_source: string;
    regulatory_source_version: string;
    regulatory_effective_date: string | null;
    regulatory_applicability: string;
    responsible_role: string;
    created_at: string | null;
}

export interface OperatorOptions {
    statuses: Record<string, string>;
    aircraft: Array<{ id: number; label: string }>;
    pilots: Array<{ id: number; label: string }>;
}

export interface OperatorMembershipOptions {
    roles: Record<string, string>;
    statuses: Record<string, string>;
    users: Array<{ id: number; label: string }>;
}

export interface OperatorMembershipReport {
    summary: {
        members_total: number;
        members_active: number;
        pilots_active: number;
        aircraft_active: number;
        missions_total: number;
    };
    memberships: Array<{
        id: number;
        user: { id: number; name: string; email: string } | null;
        membership_role: string;
        status: string;
        joined_at: string | null;
        activated_at: string | null;
        left_at: string | null;
    }>;
    pilots: Array<{ id: number; label: string; assignment_role: string; status: string }>;
    aircraft: Array<{ id: number; label: string; assignment_role: string; status: string }>;
    missions: Array<{ id: number; mission_number: string; purpose: string; lifecycle_state: string }>;
}
export interface CertificateCaseOptions {
    types: Record<string, string>;
    statuses: Record<string, string>;
}

export interface OperatorCertificateCase {
    id: number;
    operator: { id: number; legal_entity: string };
    case_number: string;
    case_type: string;
    status: string;
    deadline_at: string | null;
    evidence_requirements: string[];
    outstanding_documents: string[];
    fleet_scope: string[];
    personnel_scope: string[];
    ops_spec_scope: string[];
    operations_manual_revision: string | null;
    fees: string[];
    submission_status: string;
    authority_correspondence: string[];
    outcome: string | null;
    submitted_at: string | null;
    decided_at: string | null;
    opened_by: string | null;
    regulatory_source: string;
    regulatory_source_version: string;
    regulatory_effective_date: string | null;
}

export interface OperatorCertificateCaseReport {
    summary: {
        total: number;
        open: number;
        ready: number;
        submitted: number;
    };
    cases: Array<{
        id: number;
        case_number: string;
        case_type: string;
        status: string;
        deadline_at: string | null;
        outstanding_documents_count: number;
        submission_status: string;
        outcome: string | null;
        opened_by: string | null;
    }>;
}

export interface ApplicationRenewalPack {
    requirement_id: string;
    case: OperatorCertificateCase;
    cover_sheet: {
        title: string;
        operator: string;
        case_number: string;
        deadline_at: string | null;
        regulatory_source: string;
        regulatory_source_version: string;
    };
    required_forms: Array<{
        id: number;
        form_code: string;
        form_title: string;
        revision: string;
        required_transaction: string;
        source_reference: string;
    }>;
    applicable_fees: Array<{
        id: number;
        transaction_code: string;
        description: string;
        amount: string | null;
        currency: string;
        source_version: string;
    }>;
    checklist: Array<{
        label: string;
        category: string;
        complete: boolean;
    }>;
    evidence_index: Array<{
        category: string;
        items: string[];
    }>;
    readiness: {
        complete: number;
        total: number;
        score: number;
    };
}
export interface ManualRevisionOptions {
    approval_statuses: Record<string, string>;
    superseded_revisions: Array<{ id: number; label: string }>;
}

export interface OperationsManualRevision {
    id: number;
    operator: { id: number; legal_entity: string };
    manual_name: string;
    revision_code: string;
    effective_date: string | null;
    approval_status: string;
    authority_approval_reference: string | null;
    sections: string[];
    change_summary: string | null;
    evidence_references: string[];
    superseded_revision_id: number | null;
    superseded_revision: { id: number; label: string } | null;
    regulatory_source: string;
    regulatory_source_version: string;
    regulatory_effective_date: string | null;
}

export interface OperatorManualRevisionReport {
    summary: {
        total: number;
        approved: number;
        draft: number;
        superseded: number;
    };
    revisions: Array<{
        id: number;
        manual_name: string;
        revision_code: string;
        effective_date: string | null;
        approval_status: string;
        authority_approval_reference: string | null;
        sections_count: number;
        superseded_revision: string | null;
    }>;
}
export interface ManualDistributionOptions {
    channels: Record<string, string>;
    statuses: Record<string, string>;
}

export interface ManualDistributionReport {
    summary: {
        total: number;
        required: number;
        distributed: number;
        waived: number;
        acknowledgement_pending: number;
        acknowledged: number;
    };
    distributions: Array<{
        id: number;
        recipient_name: string;
        recipient_role: string;
        recipient_email: string | null;
        distribution_channel: string;
        distribution_status: string;
        acknowledgement_status: string;
        required_by: string | null;
        distributed_at: string | null;
        acknowledged_at: string | null;
        acknowledgement_statement: string | null;
        acknowledgement_notes: string | null;
        evidence_references: string[];
        notes: string | null;
    }>;
}
export interface ManualDistributionAcknowledgement {
    id: number;
    recipient_name: string;
    recipient_role: string;
    recipient_email: string | null;
    acknowledgement_status: string;
    acknowledged_at: string | null;
    acknowledgement_statement: string | null;
    acknowledgement_notes: string | null;
}
export interface ManualTrainingOptions {
    types: Record<string, string>;
    statuses: Record<string, string>;
}

export interface ManualTrainingReport {
    summary: {
        total: number;
        required: number;
        assigned: number;
        completed: number;
        waived: number;
    };
    requirements: Array<{
        id: number;
        title: string;
        requirement_type: string;
        training_status: string;
        affected_roles: string[];
        due_date: string | null;
        competency_standard: string | null;
        trigger_reason: string | null;
        evidence_references: string[];
        notes: string | null;
    }>;
}
