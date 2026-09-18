export interface RegulatoryFeeListItem {
    id: number;
    regulation_part: string;
    transaction_code: string;
    description: string;
    amount: string | null;
    currency: string;
    effective_from: string | null;
    effective_to: string | null;
    source: string;
    source_version: string;
    status: string;
    verified_at: string | null;
    superseding_versions_count: number;
}

export interface RegulatoryFeeSummary {
    id: number;
    transaction_code: string;
    description: string;
    amount: string | null;
    currency: string;
    source_version: string;
    status: string;
    effective_from: string | null;
}

export interface RegulatoryFee {
    id: number;
    previous_fee_id: number | null;
    previous_fee: RegulatoryFeeSummary | null;
    superseding_fees: RegulatoryFeeSummary[];
    regulation_part: string;
    transaction_code: string;
    description: string;
    amount: string | null;
    currency: string;
    effective_from: string | null;
    effective_to: string | null;
    source: string;
    source_version: string;
    status: string;
    verified_at: string | null;
}
