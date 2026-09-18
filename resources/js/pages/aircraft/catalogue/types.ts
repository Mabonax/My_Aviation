export interface AircraftModel {
    id: number;
    manufacturer: { id: number; name: string; slug: string };
    model: string;
    family: string | null;
    aircraft_type: string;
    primary_use: string | null;
    status: string | null;
    weight_kg: string | null;
    mtow_kg: string | null;
    max_payload_kg: string | null;
    max_flight_time_min: number | null;
    max_speed_m_s: string | null;
    max_range_km: string | null;
    service_ceiling_m: number | null;
    max_wind_m_s: string | null;
    ip_rating: string | null;
    operating_temp_c: string | null;
    dimensions: string | null;
    wingspan_mm: number | null;
    gnss: string | null;
    camera_payload_summary: string | null;
    remote_id: string | null;
    source_url: string | null;
    image_source_url: string | null;
    image_license_status: string | null;
    notes: string | null;
    verified_at: string | null;
    media_status: string | null;
    source_priority: string | null;
    catalogue_status: string;
    package_status: string;
    battery_package: Array<Record<string, unknown>>;
    component_package: Array<Record<string, unknown>>;
    maintenance_package: Array<Record<string, unknown>>;
    package_counts: {
        batteries: number;
        components: number;
        maintenance_baselines: number;
    };
}

export interface AircraftCataloguePage {
    data: AircraftModel[];
    links: Array<{ url: string | null; label: string; active: boolean }>;
    meta?: Record<string, unknown>;
}

export interface CatalogueFilters {
    manufacturers: Array<{ id: number; label: string }>;
    aircraft_types: string[];
    statuses: Record<string, string>;
    catalogue_statuses: Record<string, string>;
}
