export interface GisProjectListItem {
    id: number;
    project_code: string;
    name: string;
    project_type: string;
    client_or_stakeholder: string | null;
    area_name: string;
    lifecycle_state: string;
    responsible_role: string;
    created_by: string | null;
}

export interface GisProject extends GisProjectListItem {
    location_search_query: string | null;
    centroid_latitude: string | null;
    centroid_longitude: string | null;
    area_boundary: Array<{ latitude: number; longitude: number }> | null;
    source_reference: string;
    source_version: string | null;
    data_governance_notes: string | null;
    evidence_required: string;
    created_at: string | null;
    updated_at: string | null;
    updated_by: string | null;
    project_missions: GisProjectMissionAssignment[];
}

export interface GisProjectOptions {
    project_types: Record<string, string>;
    states: Record<string, string>;
}

export interface GisProjectMissionOption {
    id: number;
    mission_number: string;
    purpose: string;
    location: string;
    lifecycle_state: string;
    release_gate_state: string;
}

export interface GisProjectMissionAssignment {
    id: number;
    mapping_objective: string;
    capture_plan: string;
    expected_outputs: string[];
    field_verification_required: string;
    evidence_notes: string | null;
    status: string;
    assigned_by: string | null;
    mission: GisProjectMissionOption | null;
    datasets: GisDataset[];
}

export interface GisDataset {
    id: number;
    dataset_code: string;
    title: string;
    dataset_type: string;
    storage_uri: string;
    processing_status: string;
    quality_status: string;
    captured_at: string | null;
    processed_at: string | null;
    layers: GisSpatialLayer[];
}

export interface GisSpatialLayer {
    id: number;
    layer_name: string;
    layer_type: string;
    geometry_type: string;
    status: string;
    features: GisFeature[];
}

export interface GisFeature {
    id: number;
    feature_code: string;
    name: string;
    feature_type: string;
    verification_status: string;
    opportunities_findings: GisOpportunityFinding[];
}

export interface GisOpportunityFinding {
    id: number;
    record_type: string;
    category: string;
    title: string;
    significance: string;
    priority: string;
    status: string;
}

export interface GisProjectMissionOptions {
    outputs: Record<string, string>;
    field_verification: Record<string, string>;
    statuses: Record<string, string>;
    missions: GisProjectMissionOption[];
}

export interface GisDatasetOptions {
    dataset_types: Record<string, string>;
    processing_statuses: Record<string, string>;
    quality_statuses: Record<string, string>;
    layer_types: Record<string, string>;
    geometry_types: Record<string, string>;
    layer_statuses: Record<string, string>;
}

export interface GisFeatureOptions {
    feature_types: Record<string, string>;
    verification_statuses: Record<string, string>;
    record_types: Record<string, string>;
    categories: Record<string, string>;
    significance: Record<string, string>;
    priorities: Record<string, string>;
    statuses: Record<string, string>;
}
