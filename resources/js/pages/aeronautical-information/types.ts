export interface SourceHealth {
    provider: string;
    health_status?: string;
    reason?: string;
    dataset_mode?: string;
    required: boolean;
    status: string;
    usable_for_release: boolean;
    operational_authority: boolean;
    coverage_complete: boolean;
    dataset_timestamp: string | null;
    last_successful_sync_at: string | null;
    valid_until: string | null;
    error: string | null;
}
export interface InformationItem {
    id: number;
    uuid: string;
    type: string;
    title: string;
    summary: string | null;
    provider: string;
    source_identifier: string;
    source_revision: string;
    source_classification: string;
    usable_for_release: boolean;
    status: string;
    effective_from: string | null;
    effective_until: string | null;
    issued_at: string | null;
    received_at: string;
    permanent: boolean;
    checksum: string;
    superseded_at: string | null;
    supersedes_id: number | null;
    source: { source_url: string | null; raw_message: string | null; raw_payload: unknown };
    interpretation: Record<string, unknown>;
    geometry: {
        type: string | null;
        latitude: string | number | null;
        longitude: string | number | null;
        radius_nm: string | number | null;
        geojson: { type?: string; coordinates?: unknown; points?: unknown } | null;
    };
    severity?: string;
    release_effect?: string;
    reason?: string;
}
export interface BriefingContext {
    altitude_reference?: string | null;
    minimum_altitude_ft?: number | null;
    fir_codes?: string[];
    aerodrome_codes?: string[];
}
export interface BriefingPayload {
    mission: { id: number; mission_number: string; location: string; lifecycle_state: string; aeronautical_context: BriefingContext | null };
    briefing: null | {
        id: number;
        revision: number;
        status: string;
        generated_at: string;
        valid_until: string;
        source_dataset_hash: string;
        assessment_version: string;
        acknowledged: boolean;
        acknowledgement_required: boolean;
        acknowledgements: { user_id: number; acknowledged_at: string }[];
        items: InformationItem[];
        providers: SourceHealth[];
        blockers: string[];
        warnings: string[];
        empty_data_message: string | null;
        summary: { items: number; blockers: number; warnings: number; advisories: number };
        mission: {
            location: string;
            latitude: string | null;
            longitude: string | null;
            flight_radius_m: number | null;
            mission_polygon: { latitude: number; longitude: number }[];
            flight_route: { latitude: number; longitude: number }[];
            aeronautical_context: BriefingContext | null;
        };
    };
    compliance: {
        status: string;
        freshness: string;
        current: boolean;
        briefing_id: number | null;
        blockers: number;
        warnings: number;
        acknowledged: boolean;
        acknowledgement_required: boolean;
        reasons: string[];
        providers: SourceHealth[];
    };
    revisions: { id: number; revision: number; generated_at: string; overall_status: string }[];
    permissions: { generate: boolean; acknowledge: boolean };
}
