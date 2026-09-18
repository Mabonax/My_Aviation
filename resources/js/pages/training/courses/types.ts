export interface TrainingCourseOptions {
    classifications: Record<string, string>;
    statuses: Record<string, string>;
    regulatory_requirements: Array<{
        id: number;
        requirement_id: string;
        title: string;
        regulation_part: string;
        clause_reference: string | null;
    }>;
}

export interface TrainingCourseListItem {
    id: number;
    code: string;
    title: string;
    classification: string;
    status: string;
    modules_count: number;
    assessments_count: number;
    competencies_count: number;
    records_count: number;
    compliance_links_count: number;
}

export interface TrainingCourse {
    id: number;
    code: string;
    title: string;
    classification: string;
    status: string;
    summary: string | null;
    authority_approval_reference: string | null;
    modules: Array<{ id: number; title: string; sequence: number; lessons: Array<{ id: number; title: string; sequence: number; lesson_type: string; resources: Array<{ id: number; title: string; resource_type: string; reference: string | null }> }> }>;
    assessments: Array<{ id: number; title: string; assessment_type: string; pass_mark: number | null }>;
    competencies: Array<{ id: number; title: string; standard: string | null }>;
    competency_records: Array<{ id: number; participant_name: string; competency_title: string; record_status: string }>;
    compliance_links: Array<{
        id: number;
        source_type: string;
        requirement_reference: string;
        title: string;
        responsible_role: string;
        applicability: string;
        evidence_required: string;
        validity_period: string | null;
        retention_period: string | null;
        due_date: string | null;
        link_status: string;
        competency_title: string | null;
        competency_record: string | null;
    }>;
    regulatory_source: string;
    regulatory_source_version: string;
    regulatory_effective_date: string | null;
}
