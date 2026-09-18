import { PilotOptions, PilotProfile } from '@/pages/pilots/types';

export interface MyCertificateSummary {
    id: number;
    certificate_number: string;
    status: string;
    expiry_date: string | null;
    days_until_expiry: number | null;
}

export interface MyMissionSummary {
    id: number;
    mission_number: string;
    purpose: string;
    location: string;
    planned_start_at: string | null;
    lifecycle_state: string | null;
    release_gate_state: string | null;
}

export interface MyPilotWorkspace {
    pilot: PilotProfile;
    certificates: MyCertificateSummary[];
    compliance: {
        profile_status: string;
        rpc_category: string;
        medical_status: string;
        radiotelephony_qualification: string;
        certificate_count: number;
        expiring_certificates_30_days: number;
    };
    logbook: {
        pilot_id: number;
        entry_count: number;
        total_hours: number;
        from: string | null;
        to: string | null;
    };
    missions: MyMissionSummary[];
    documents: {
        count: number;
        architecture_gap: string;
    };
}

export type { PilotOptions, PilotProfile };
