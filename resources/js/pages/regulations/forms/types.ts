export interface RegulatoryFormListItem {
    id: number;
    form_code: string;
    form_title: string;
    regulatory_area: string;
    revision: string;
    effective_date: string | null;
    superseded_date: string | null;
    required_transaction: string;
    status: string;
    verified_at: string | null;
    superseding_versions_count: number;
}

export interface RegulatoryFormSummary {
    id: number;
    form_code: string;
    form_title: string;
    revision: string;
    status: string;
    effective_date: string | null;
}

export interface RegulatoryForm {
    id: number;
    previous_form_id: number | null;
    previous_form: RegulatoryFormSummary | null;
    superseding_forms: RegulatoryFormSummary[];
    form_code: string;
    form_title: string;
    regulatory_area: string;
    revision: string;
    effective_date: string | null;
    superseded_date: string | null;
    source_reference: string;
    source_url: string | null;
    required_transaction: string;
    status: string;
    verified_at: string | null;
}
