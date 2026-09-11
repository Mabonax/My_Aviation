export interface GeoPointValue {
    latitude: number | string;
    longitude: number | string;
    label?: string | null;
}

export interface MissionOption {
    id: number;
    label: string;
}

export interface MissionOptions {
    aircraft: MissionOption[];
    pilots: MissionOption[];
    lifecycle_states: Record<string, string>;
    operation_categories: Record<string, string>;
    visibility_modes: Record<string, string>;
    day_night_modes: Record<string, string>;
}

export interface MissionProfile {
    id: number;
    mission_number: string;
    purpose: string;
    client_project: string | null;
    location: string;
    location_search_query: string | null;
    latitude: string | null;
    longitude: string | null;
    takeoff_point: GeoPointValue | null;
    landing_point: GeoPointValue | null;
    mission_polygon: GeoPointValue[];
    flight_route: GeoPointValue[];
    flight_radius_m: number | null;
    operation_category: string;
    aircraft: { id: number; registration: string; model: string } | null;
    pilot: { id: number; display_name: string } | null;
    planned_start_at: string | null;
    planned_end_at: string | null;
    maximum_altitude_ft: number | null;
    planned_distance_km: string | null;
    operation_visibility: string;
    day_night: string;
    weather: string | null;
    airspace_assessment: string | null;
    emergency_arrangements: string | null;
    lifecycle_state: string;
    release_gate_state: string;
    release_gate_results: {
        state?: string;
        checks?: Array<{ label: string; result: string; basis: string; message: string }>;
        evaluated_at?: string;
    };
    regulatory_source: string;
    regulatory_source_version: string;
    regulatory_effective_date: string | null;
    regulatory_applicability: string;
    responsible_role: string;
}

export interface SpatialRuleReview {
    state: string;
    summary: string;
    evaluated_at: string;
    source_boundary: string;
    matches: Array<{
        zone_type: string;
        zone_name: string;
        identifier: string | null;
        result: string;
        basis: string;
        label: string;
        message: string;
        operational_notes: string | null;
        source: {
            name: string;
            publisher: string;
            source_url: string;
            source_version: string;
            authoritative: boolean;
        };
    }>;
}
export interface MissionChecklistReport {
    template: {
        id: number;
        type: string;
        name: string;
        version: string;
        effective_date: string | null;
        items: Array<{ key: string; label: string; required: boolean; sequence: number }>;
        regulatory_source: string;
        regulatory_source_version: string;
        regulatory_effective_date: string | null;
    } | null;
    latest: {
        id: number;
        type: string;
        checklist_version: string;
        performed_at: string | null;
        performed_by: string | null;
        results: Record<string, { result: string; notes: string | null }>;
        exceptions: string | null;
        state: string;
    } | null;
}
export interface MissionCrewReport {
    summary: {
        total: number;
        briefed: number;
        accepted: number;
        competency_verified: number;
        attention_required: number;
    };
    members: Array<{
        id: number;
        crew_role: string;
        display_name: string;
        email: string | null;
        phone: string | null;
        briefing_status: string;
        competency_status: string;
        acceptance_status: string;
        emergency_contact_name: string | null;
        emergency_contact_phone: string | null;
        notes: string | null;
        linked_pilot: { id: number; display_name: string } | null;
        linked_user: { id: number; name: string } | null;
        assigned_by: string | null;
        regulatory_source: string;
        regulatory_source_version: string;
        regulatory_effective_date: string | null;
    }>;
}
export interface MissionTrackReport {
    summary: {
        total: number;
        total_points: number;
        total_distance_km: number;
        max_altitude_ft: number | null;
        anomaly_count: number;
    };
    tracks: Array<{
        id: number;
        source_type: string;
        track_reference: string | null;
        started_at: string | null;
        ended_at: string | null;
        point_count: number;
        total_distance_km: string | null;
        max_altitude_ft: number | null;
        anomalies: string[];
        notes: string | null;
        captured_by: string | null;
        regulatory_source: string;
        regulatory_source_version: string;
        regulatory_effective_date: string | null;
    }>;
}
export interface MissionBatteryReport {
    summary: {
        total_batteries: number;
        cycles_added: number;
        attention_required: number;
    };
    usages: Array<{
        id: number;
        battery_uid: string;
        serial_number: string;
        cycles_added: number;
        state_of_charge_start: number | null;
        state_of_charge_end: number | null;
        used_at: string | null;
        notes: string | null;
        recorded_by: string | null;
        battery_health_status: string;
        battery_cycle_count: number;
    }>;
    available_batteries: Array<{
        id: number;
        label: string;
        health_status: string;
        cycle_count: number;
        maximum_cycles: number | null;
    }>;
}
export interface MissionDefectReport {
    summary: {
        total: number;
        open: number;
        serviceability_impacts: number;
        grounding: number;
    };
    defects: Array<{
        id: number;
        defect_number: string;
        source: string;
        severity: string;
        status: string;
        serviceability_impact: string;
        title: string;
        description: string;
        immediate_action: string | null;
        reported_at: string | null;
        aircraft_registration: string | null;
        reported_by: string | null;
        regulatory_source: string;
        regulatory_source_version: string;
        regulatory_effective_date: string | null;
    }>;
}