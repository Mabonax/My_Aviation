export interface PilotProfile {
    id: number;
    display_name: string;
    user_id: number | null;
    employee_number: string | null;
    first_name: string;
    last_name: string;
    preferred_name: string | null;
    email: string | null;
    phone: string | null;
    nationality: string | null;
    date_of_birth: string | null;
    sacaa_certificate_number: string | null;
    rpc_category: string;
    ratings: string[];
    medical_status: string;
    radiotelephony_qualification: string;
    language_proficiency: string | null;
    training_history: Record<string, unknown>[];
    examiner_records: Record<string, unknown>[];
    operator_affiliations: Record<string, unknown>[];
    supporting_document_references: Record<string, unknown>[];
    profile_status: string;
    regulatory_source: string;
    regulatory_source_version: string;
    regulatory_effective_date: string | null;
    regulatory_applicability: string;
    responsible_role: string;
    notes: string | null;
    created_at: string | null;
    updated_at: string | null;
}

export interface PilotOptions {
    rpcCategories: Record<string, string>;
    medicalStatuses: Record<string, string>;
    radiotelephonyQualifications: Record<string, string>;
    profileStatuses: Record<string, string>;
}
