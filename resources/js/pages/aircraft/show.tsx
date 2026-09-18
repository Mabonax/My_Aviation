import { Button } from '@/components/ui/button';
import { PageHeader } from '@/components/uas/page-header';
import { StatusBadge } from '@/components/uas/status-badge';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { AlertCircle, CheckCircle2, CircleAlert } from 'lucide-react';
import { AircraftModel } from './catalogue/types';

interface ReadinessCheck {
    code: string;
    label: string;
    status: 'green' | 'amber' | 'red';
    summary: string;
    evidence: Record<string, unknown>;
}

interface AircraftReadiness {
    status: 'green' | 'amber' | 'red';
    label: string;
    as_of: string;
    checks: ReadinessCheck[];
    blocking_reasons: string[];
    review_reasons: string[];
}

interface AircraftPackageInstantiation {
    state: string;
    instantiated_at: string | null;
    results: Record<string, unknown>;
    battery_count: number;
    component_count: number;
    maintenance_baseline_count: number;
    batteries: Array<{
        id: number;
        battery_uid: string;
        package_item_key: string | null;
        model: string;
        health_status: string;
        retirement_status: string;
    }>;
    components: Array<{
        id: number;
        component_uid: string;
        package_item_key: string | null;
        component_type: string;
        name: string;
        status: string;
        life_limit_hours: string | number | null;
        life_limit_cycles: number | null;
    }>;
}

interface EvidenceSummary {
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
}

interface Aircraft {
    id: number;
    catalogue_model: AircraftModel | null;
    registration: string;
    manufacturer: string;
    model: string;
    serial_number: string;
    internal_asset_number: string | null;
    owner: string | null;
    operator: string | null;
    supplier: string | null;
    firmware_version: string | null;
    flight_controller_serial: string | null;
    remote_id_serial: string | null;
    acquisition_date: string | null;
    operational_status: string;
    onboarding_status: string;
    base_location: string | null;
    operator_names: string[];
    readiness: AircraftReadiness;
    evidence: EvidenceSummary;
    package_instantiation: AircraftPackageInstantiation;
}

export default function Show({ aircraft }: { aircraft: Aircraft }) {
    const breadcrumbs: BreadcrumbItem[] = [{ title: 'Aircraft', href: '/aircraft' }, { title: aircraft.registration, href: `/aircraft/${aircraft.id}` }];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={aircraft.registration} />
            <div className="flex flex-1 flex-col gap-6 p-4 sm:p-6">
                <PageHeader title={aircraft.registration} description={`${aircraft.manufacturer} ${aircraft.model}`} actions={aircraft.catalogue_model && <Button asChild><Link href={`/aircraft-catalogue/${aircraft.catalogue_model.id}`}>Catalogue</Link></Button>} />
                <div className="grid gap-4 xl:grid-cols-2">
                    <Panel title="Readiness Summary">
                        <div className="flex items-start justify-between gap-3 rounded-md border p-3">
                            <div>
                                <div className="text-sm font-semibold">{aircraft.readiness.label}</div>
                                <div className="mt-1 text-xs text-muted-foreground">As of {aircraft.readiness.as_of}</div>
                            </div>
                            <ReadinessPill status={aircraft.readiness.status} />
                        </div>
                        <div className="space-y-2">
                            {aircraft.readiness.checks.map((check) => (
                                <div key={check.code} className="flex gap-3 rounded-md border p-3">
                                    <ReadinessIcon status={check.status} />
                                    <div>
                                        <div className="text-sm font-medium">{check.label}</div>
                                        <div className="mt-1 text-sm text-muted-foreground">{check.summary}</div>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </Panel>
                    <Panel title="Physical Aircraft"><StatusBadge value={aircraft.operational_status.replaceAll('_', ' ')} /><Detail label="Onboarding" value={aircraft.onboarding_status} /><Detail label="Serial number" value={aircraft.serial_number} /><Detail label="Internal asset" value={aircraft.internal_asset_number} /><Detail label="Operator assignment" value={aircraft.operator_names.length ? aircraft.operator_names.join(', ') : aircraft.operator} /></Panel>
                    <Panel title="Evidence" >
                        <Detail label="Linked documents" value={aircraft.evidence.count.toString()} />
                        {aircraft.evidence.documents.length === 0 ? (
                            <p className="text-sm text-muted-foreground">No governed evidence documents have been linked.</p>
                        ) : aircraft.evidence.documents.map((document) => (
                            <div key={document.id} className="rounded-md border p-3 text-sm">
                                <div className="font-medium">{document.title}</div>
                                <div className="mt-1 text-muted-foreground">{document.category.replaceAll('_', ' ')} - {document.status.replaceAll('_', ' ')} - v{document.version}</div>
                            </div>
                        ))}
                    </Panel>
                    <Panel title="Package Instantiation">
                        <StatusBadge value={aircraft.package_instantiation.state.replaceAll('_', ' ')} />
                        <div className="grid gap-2 sm:grid-cols-3">
                            <Detail label="Batteries" value={aircraft.package_instantiation.battery_count.toString()} />
                            <Detail label="Components" value={aircraft.package_instantiation.component_count.toString()} />
                            <Detail label="Maintenance baseline" value={aircraft.package_instantiation.maintenance_baseline_count.toString()} />
                        </div>
                        <Detail label="Instantiated" value={aircraft.package_instantiation.instantiated_at} />
                        {aircraft.package_instantiation.batteries.length > 0 && (
                            <div className="space-y-2">
                                {aircraft.package_instantiation.batteries.map((battery) => (
                                    <div key={battery.id} className="rounded-md border p-3 text-sm">
                                        <div className="font-medium">{battery.battery_uid}</div>
                                        <div className="mt-1 text-muted-foreground">{battery.model} - {battery.health_status} - {battery.retirement_status}</div>
                                    </div>
                                ))}
                            </div>
                        )}
                        {aircraft.package_instantiation.components.length > 0 && (
                            <div className="space-y-2">
                                {aircraft.package_instantiation.components.map((component) => (
                                    <div key={component.id} className="rounded-md border p-3 text-sm">
                                        <div className="font-medium">{component.name}</div>
                                        <div className="mt-1 text-muted-foreground">{component.component_type.replaceAll('_', ' ')} - {component.status}</div>
                                    </div>
                                ))}
                            </div>
                        )}
                    </Panel>
                    <Panel title="Operational Configuration"><Detail label="Base location" value={aircraft.base_location} /><Detail label="Owner" value={aircraft.owner} /><Detail label="Supplier" value={aircraft.supplier} /><Detail label="Firmware" value={aircraft.firmware_version} /><Detail label="Flight controller serial" value={aircraft.flight_controller_serial} /><Detail label="Remote ID serial" value={aircraft.remote_id_serial} /></Panel>
                    {aircraft.catalogue_model && <Panel title="Catalogue Summary"><Detail label="Source model" value={`${aircraft.catalogue_model.manufacturer.name} ${aircraft.catalogue_model.model}`} /><Detail label="Flight time" value={aircraft.catalogue_model.max_flight_time_min ? `${aircraft.catalogue_model.max_flight_time_min} min` : null} /><Detail label="MTOW" value={aircraft.catalogue_model.mtow_kg ? `${aircraft.catalogue_model.mtow_kg} kg` : null} /><Detail label="Payload" value={aircraft.catalogue_model.max_payload_kg ? `${aircraft.catalogue_model.max_payload_kg} kg` : null} /><Detail label="Verified" value={aircraft.catalogue_model.verified_at} /></Panel>}
                </div>
            </div>
        </AppLayout>
    );
}

function Panel({ title, children }: { title: string; children: React.ReactNode }) {
    return <section className="rounded-lg border bg-card p-4 text-card-foreground shadow-xs"><h2 className="mb-4 text-base font-semibold">{title}</h2><div className="space-y-3">{children}</div></section>;
}

function Detail({ label, value }: { label: string; value?: string | null }) {
    return <div><dt className="text-xs font-medium uppercase text-muted-foreground">{label}</dt><dd className="mt-1 break-words text-sm">{value || 'Not captured'}</dd></div>;
}

function ReadinessPill({ status }: { status: AircraftReadiness['status'] }) {
    const classes = {
        green: 'border-emerald-200 bg-emerald-50 text-emerald-800',
        amber: 'border-amber-200 bg-amber-50 text-amber-800',
        red: 'border-red-200 bg-red-50 text-red-800',
    }[status];

    return <span className={`rounded-md border px-2 py-1 text-xs font-semibold uppercase ${classes}`}>{status}</span>;
}

function ReadinessIcon({ status }: { status: AircraftReadiness['status'] }) {
    if (status === 'green') {
        return <CheckCircle2 className="mt-0.5 h-4 w-4 shrink-0 text-emerald-600" />;
    }

    if (status === 'amber') {
        return <CircleAlert className="mt-0.5 h-4 w-4 shrink-0 text-amber-600" />;
    }

    return <AlertCircle className="mt-0.5 h-4 w-4 shrink-0 text-red-600" />;
}
