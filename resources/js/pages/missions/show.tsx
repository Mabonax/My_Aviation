import { Button } from '@/components/ui/button';
import InputError from '@/components/input-error';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { PageHeader } from '@/components/uas/page-header';
import { StatusBadge } from '@/components/uas/status-badge';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { AlertCircle, CheckCircle2, CircleAlert, Rocket, SendToBack } from 'lucide-react';
import { MissionBatteryReport, MissionChecklistReport, MissionComplianceSummary, MissionCrewReport, MissionDefectReport, MissionProfile, MissionTrackReport, PostFlightPropagationSummary, SpatialRuleReview } from './types';

export default function Show({ mission, missionCompliance, spatialRuleReview, preFlightChecklist, postFlightChecklist, postFlightPropagation, crew, tracks, batteries, defects }: { mission: MissionProfile; missionCompliance: MissionComplianceSummary; spatialRuleReview: SpatialRuleReview; preFlightChecklist: MissionChecklistReport; postFlightChecklist: MissionChecklistReport; postFlightPropagation: PostFlightPropagationSummary; crew: MissionCrewReport; tracks: MissionTrackReport; batteries: MissionBatteryReport; defects: MissionDefectReport }) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Missions', href: '/missions' },
        { title: mission.mission_number, href: `/missions/${mission.id}` },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={mission.mission_number} />
            <div className="flex flex-1 flex-col gap-6 p-4 sm:p-6">
                <PageHeader title={mission.mission_number} description={mission.purpose} actions={<div className="flex gap-2"><Button variant="outline" asChild><Link href="/missions">Back</Link></Button><Button variant="outline" asChild><Link href={`/missions/${mission.id}/briefing`}>Briefing</Link></Button><Button disabled={missionCompliance.status === 'red'} onClick={() => router.post(`/missions/${mission.id}/release`)}><Rocket />{missionCompliance.status === 'green' ? 'Release Mission' : missionCompliance.status === 'amber' ? 'Review & Release' : 'Release Blocked'}</Button></div>} />

                <div className="grid gap-4 xl:grid-cols-2">
                    <Panel title="Mission Release Readiness">
                        <div className="rounded-lg border border-blue-100 bg-blue-50/60 p-4">
                            <div className="flex items-start justify-between gap-3">
                                <div>
                                    <div className="text-sm font-semibold">{missionCompliance.label}</div>
                                    <div className="mt-1 text-xs text-muted-foreground">{missionCompliance.blocking_count} blocking / {missionCompliance.warning_count} warning</div>
                                </div>
                                <ReadinessPill status={missionCompliance.status} />
                            </div>
                        </div>
                        {(missionCompliance.controls ?? []).map((control) => (
                            <div key={control.key} className="flex gap-3 rounded-md border p-3">
                                <ReadinessIcon status={control.status} />
                                <div className="min-w-0 flex-1">
                                    <div className="flex items-center justify-between gap-3">
                                        <span className="text-sm font-medium">{control.label}</span>
                                        <StatusBadge value={control.status} />
                                    </div>
                                    <p className="mt-1 text-sm text-muted-foreground">{control.summary}</p>
                                    {control.reasons.length > 0 && <p className="mt-1 text-xs text-muted-foreground">{control.reasons[0]}</p>}
                                    {control.action_href && <Link href={control.action_href} className="mt-2 inline-block text-xs font-medium text-primary">Review</Link>}
                                </div>
                            </div>
                        ))}
                    </Panel>

                    <Panel title="Mission Plan">
                        <Detail label="Location" value={mission.location} />
                        <Detail label="Location search" value={mission.location_search_query} />
                        <Detail label="Client / project" value={mission.client_project} />
                        <Detail label="Pilot" value={mission.pilot?.display_name ?? null} />
                        <Detail label="Aircraft" value={mission.aircraft ? `${mission.aircraft.registration} ${mission.aircraft.model}` : null} />
                        <Detail label="Visibility" value={mission.operation_visibility} />
                        <Detail label="Day / night" value={mission.day_night} />
                        <Detail label="Maximum altitude" value={mission.maximum_altitude_ft ? `${mission.maximum_altitude_ft} ft` : null} />
                    </Panel>

                    <Panel title="Evidence" action={<Button size="sm" asChild><Link href="/evidence-documents">Vault</Link></Button>}>
                        <Detail label="Linked documents" value={mission.evidence.count.toString()} />
                        {mission.evidence.documents.length === 0 ? (
                            <p className="text-sm text-muted-foreground">No governed evidence documents have been linked.</p>
                        ) : mission.evidence.documents.map((document) => (
                            <div key={document.id} className="rounded-md border p-3 text-sm">
                                <div className="font-medium">{document.title}</div>
                                <div className="mt-1 text-muted-foreground">{document.category.replaceAll('_', ' ')} - {document.status.replaceAll('_', ' ')} - v{document.version}</div>
                            </div>
                        ))}
                    </Panel>

                    <Panel title="Map Geometry">
                        <Detail label="Take-off point" value={formatPoint(mission.takeoff_point)} />
                        <Detail label="Landing point" value={formatPoint(mission.landing_point)} />
                        <Detail label="Mission polygon" value={`${mission.mission_polygon.length} points`} />
                        <Detail label="Flight route" value={`${mission.flight_route.length} points`} />
                        <Detail label="Flight radius" value={mission.flight_radius_m ? `${mission.flight_radius_m} m` : null} />
                    </Panel>
                </div>

                <div className="grid gap-4 xl:grid-cols-2">
                    <Panel title="Stored Release Gate Evidence">
                        <div className="mb-3 flex gap-2"><StatusBadge value={mission.lifecycle_state.replaceAll('_', ' ')} /><StatusBadge value={mission.release_gate_state} /></div>
                        {(mission.release_gate_results.checks ?? []).map((check) => (
                            <div key={check.label} className="border-t py-3 first:border-t-0 first:pt-0">
                                <div className="flex items-center justify-between gap-3"><span className="font-medium">{check.label}</span><StatusBadge value={check.result} /></div>
                                <p className="mt-1 text-sm text-muted-foreground">{check.message}</p>
                                <p className="mt-1 text-xs uppercase text-muted-foreground">{check.basis.replace('_', ' ')}</p>
                            </div>
                        ))}
                    </Panel>

                    <ChecklistPanel title="Pre-flight Checklist" href={`/missions/${mission.id}/pre-flight-checklist`} checklist={preFlightChecklist} emptyText="No pre-flight checklist has been recorded for this mission." />

                    <ChecklistPanel title="Post-flight Checklist" href={`/missions/${mission.id}/post-flight-checklist`} checklist={postFlightChecklist} emptyText="No post-flight checklist has been recorded for this mission." />

                    <PostFlightPropagationPanel missionId={mission.id} propagation={postFlightPropagation} />

                    <Panel title="Crew" action={<Button size="sm" asChild><Link href={`/missions/${mission.id}/crew/create`}>Add Crew</Link></Button>}>
                        <div className="grid gap-2 text-sm sm:grid-cols-4">
                            <Detail label="Total" value={crew.summary.total.toString()} />
                            <Detail label="Briefed" value={crew.summary.briefed.toString()} />
                            <Detail label="Accepted" value={crew.summary.accepted.toString()} />
                            <Detail label="Attention" value={crew.summary.attention_required.toString()} />
                        </div>
                        {crew.members.length === 0 ? (
                            <p className="text-sm text-muted-foreground">No dedicated crew assignments have been captured.</p>
                        ) : (
                            crew.members.map((member) => (
                                <div key={member.id} className="border-t py-3 first:border-t-0 first:pt-0">
                                    <div className="flex items-center justify-between gap-3">
                                        <span className="font-medium">{member.display_name}</span>
                                        <StatusBadge value={member.crew_role.replaceAll('_', ' ')} />
                                    </div>
                                    <p className="mt-1 text-sm text-muted-foreground">{member.email || member.phone || 'No contact captured'}</p>
                                    <p className="mt-1 text-xs uppercase text-muted-foreground">Briefing: {member.briefing_status.replaceAll('_', ' ')} / Competency: {member.competency_status.replaceAll('_', ' ')} / Acceptance: {member.acceptance_status.replaceAll('_', ' ')}</p>
                                </div>
                            ))
                        )}
                    </Panel>
                </div>

                <div className="grid gap-4 xl:grid-cols-2">
                    <Panel title="Spatial Rule Review">
                        <div className="mb-3 flex flex-wrap items-center gap-2">
                            <StatusBadge value={spatialRuleReview.state.replaceAll('_', ' ')} />
                            <span className="text-sm text-muted-foreground">{spatialRuleReview.summary}</span>
                        </div>
                        {spatialRuleReview.matches.length === 0 ? (
                            <p className="text-sm text-muted-foreground">{spatialRuleReview.source_boundary}</p>
                        ) : (
                            spatialRuleReview.matches.map((match) => (
                                <div key={`${match.zone_type}-${match.identifier ?? match.zone_name}`} className="border-t py-3 first:border-t-0 first:pt-0">
                                    <div className="flex items-center justify-between gap-3">
                                        <span className="font-medium">{match.zone_name}</span>
                                        <StatusBadge value={match.result.replaceAll('_', ' ')} />
                                    </div>
                                    <p className="mt-1 text-sm text-muted-foreground">{match.message}</p>
                                    <p className="mt-1 text-xs uppercase text-muted-foreground">{match.zone_type.replaceAll('_', ' ')} / {match.basis.replaceAll('_', ' ')}</p>
                                    <p className="mt-1 text-xs text-muted-foreground">{match.source.publisher} - {match.source.source_version}</p>
                                </div>
                            ))
                        )}
                    </Panel>

                    <Panel title="Flight Tracks" action={<Button size="sm" asChild><Link href={`/missions/${mission.id}/tracks/create`}>Add Track</Link></Button>}>
                        <div className="grid gap-2 text-sm sm:grid-cols-4">
                            <Detail label="Tracks" value={tracks.summary.total.toString()} />
                            <Detail label="Points" value={tracks.summary.total_points.toString()} />
                            <Detail label="Distance" value={`${tracks.summary.total_distance_km} km`} />
                            <Detail label="Anomalies" value={tracks.summary.anomaly_count.toString()} />
                        </div>
                        {tracks.tracks.length === 0 ? (
                            <p className="text-sm text-muted-foreground">No flight track has been recorded for this mission.</p>
                        ) : (
                            tracks.tracks.map((track) => (
                                <div key={track.id} className="border-t py-3 first:border-t-0 first:pt-0">
                                    <div className="flex items-center justify-between gap-3">
                                        <span className="font-medium">{track.track_reference || track.source_type.replaceAll('_', ' ')}</span>
                                        <StatusBadge value={track.source_type.replaceAll('_', ' ')} />
                                    </div>
                                    <p className="mt-1 text-sm text-muted-foreground">{track.point_count} points / {track.total_distance_km ?? '0.000'} km / max {track.max_altitude_ft ?? 'n/a'} ft</p>
                                    <p className="mt-1 text-xs text-muted-foreground">Captured by {track.captured_by ?? 'unknown'} / {track.regulatory_source_version}</p>
                                </div>
                            ))
                        )}
                    </Panel>

                    <Panel title="Batteries" action={<Button size="sm" asChild><Link href={`/missions/${mission.id}/batteries/create`}>Record Use</Link></Button>}>
                        <div className="grid gap-2 text-sm sm:grid-cols-3">
                            <Detail label="Batteries" value={batteries.summary.total_batteries.toString()} />
                            <Detail label="Cycles" value={batteries.summary.cycles_added.toString()} />
                            <Detail label="Attention" value={batteries.summary.attention_required.toString()} />
                        </div>
                        {batteries.usages.length === 0 ? (
                            <p className="text-sm text-muted-foreground">No mission battery usage has been recorded.</p>
                        ) : (
                            batteries.usages.map((usage) => (
                                <div key={usage.id} className="border-t py-3 first:border-t-0 first:pt-0">
                                    <div className="flex items-center justify-between gap-3">
                                        <span className="font-medium">{usage.battery_uid}</span>
                                        <StatusBadge value={usage.battery_health_status.replaceAll('_', ' ')} />
                                    </div>
                                    <p className="mt-1 text-sm text-muted-foreground">{usage.serial_number} / +{usage.cycles_added} cycles / {usage.state_of_charge_start ?? 'n/a'}% to {usage.state_of_charge_end ?? 'n/a'}%</p>
                                    <p className="mt-1 text-xs text-muted-foreground">Recorded by {usage.recorded_by ?? 'unknown'} / total cycles {usage.battery_cycle_count}</p>
                                </div>
                            ))
                        )}
                    </Panel>

                    <Panel title="Defects" action={<Button size="sm" asChild><Link href={`/missions/${mission.id}/defects/create`}>Report Defect</Link></Button>}>
                        <div className="grid gap-2 text-sm sm:grid-cols-4">
                            <Detail label="Total" value={defects.summary.total.toString()} />
                            <Detail label="Open" value={defects.summary.open.toString()} />
                            <Detail label="Impacts" value={defects.summary.serviceability_impacts.toString()} />
                            <Detail label="Grounding" value={defects.summary.grounding.toString()} />
                        </div>
                        {defects.defects.length === 0 ? (
                            <p className="text-sm text-muted-foreground">No defects have been reported for this mission.</p>
                        ) : (
                            defects.defects.map((defect) => (
                                <div key={defect.id} className="border-t py-3 first:border-t-0 first:pt-0">
                                    <div className="flex items-center justify-between gap-3">
                                        <span className="font-medium">{defect.defect_number} / {defect.title}</span>
                                        <StatusBadge value={defect.severity.replaceAll('_', ' ')} />
                                    </div>
                                    <p className="mt-1 text-sm text-muted-foreground">{defect.source.replaceAll('_', ' ')} / {defect.serviceability_impact.replaceAll('_', ' ')} / {defect.status}</p>
                                    <p className="mt-1 text-xs text-muted-foreground">Reported by {defect.reported_by ?? 'unknown'} / {defect.regulatory_source_version}</p>
                                </div>
                            ))
                        )}
                    </Panel>

                    <Panel title="Regulatory Traceability">
                        <Detail label="Source" value={mission.regulatory_source} />
                        <Detail label="Version" value={mission.regulatory_source_version} />
                        <Detail label="Effective date" value={mission.regulatory_effective_date} />
                        <Detail label="Applicability" value={mission.regulatory_applicability} />
                        <Detail label="Responsible role" value={mission.responsible_role} />
                    </Panel>
                </div>
            </div>
        </AppLayout>
    );
}


function PostFlightPropagationPanel({ missionId, propagation }: { missionId: number; propagation: PostFlightPropagationSummary }) {
    const { data, setData, post, processing, errors } = useForm({
        actual_takeoff_at: toLocalDateTime(propagation.actual_takeoff_at),
        actual_landing_at: toLocalDateTime(propagation.actual_landing_at),
        pilot_confirmed: propagation.post_flight_declaration?.pilot_confirmed ?? false,
        aircraft_confirmed: propagation.post_flight_declaration?.aircraft_confirmed ?? false,
        defects_declared: propagation.post_flight_declaration?.defects_declared ?? false,
        occurrence_declared: propagation.post_flight_declaration?.occurrence_declared ?? false,
        closure_notes: propagation.post_flight_declaration?.closure_notes ?? '',
    });

    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        post(`/missions/${missionId}/post-flight-propagation`);
    };

    return (
        <Panel title="Post-flight Propagation">
            <div className="mb-3 flex flex-wrap items-center gap-2">
                <StatusBadge value={propagation.label} />
                {propagation.latest_checklist_state && <StatusBadge value={propagation.latest_checklist_state.replaceAll('_', ' ')} />}
            </div>
            <div className="grid gap-2 text-sm sm:grid-cols-3">
                <Detail label="Pilot log" value={propagation.pilot_log_entry_id ? `#${propagation.pilot_log_entry_id}` : null} />
                <Detail label="Aircraft folio" value={propagation.aircraft_flight_folio_id ? `#${propagation.aircraft_flight_folio_id}` : null} />
                <Detail label="Propagated at" value={propagation.propagated_at ?? null} />
                <Detail label="Battery cycles" value={propagation.results?.battery_cycles_summarised?.toString() ?? '0'} />
                <Detail label="Tracks" value={propagation.results?.flight_track_count?.toString() ?? '0'} />
                <Detail label="Open defects" value={propagation.results?.open_defect_count?.toString() ?? '0'} />
            </div>
            {(propagation.blocking_reasons ?? []).length > 0 && (
                <p className="text-sm text-muted-foreground">{propagation.blocking_reasons?.[0]}</p>
            )}
            <form onSubmit={submit} className="space-y-3 border-t pt-3">
                <div className="grid gap-3 sm:grid-cols-2">
                    <Field label="Actual takeoff" error={errors.actual_takeoff_at}><Input type="datetime-local" value={data.actual_takeoff_at} onChange={(event) => setData('actual_takeoff_at', event.target.value)} disabled={!!propagation.propagated_at} /></Field>
                    <Field label="Actual landing" error={errors.actual_landing_at}><Input type="datetime-local" value={data.actual_landing_at} onChange={(event) => setData('actual_landing_at', event.target.value)} disabled={!!propagation.propagated_at} /></Field>
                </div>
                <div className="grid gap-2 text-sm sm:grid-cols-2">
                    <CheckField label="Pilot confirmed" checked={data.pilot_confirmed} disabled={!!propagation.propagated_at} onChange={(checked) => setData('pilot_confirmed', checked)} error={errors.pilot_confirmed} />
                    <CheckField label="Aircraft confirmed" checked={data.aircraft_confirmed} disabled={!!propagation.propagated_at} onChange={(checked) => setData('aircraft_confirmed', checked)} error={errors.aircraft_confirmed} />
                    <CheckField label="Defects declared" checked={data.defects_declared} disabled={!!propagation.propagated_at} onChange={(checked) => setData('defects_declared', checked)} error={errors.defects_declared} />
                    <CheckField label="Occurrence declared" checked={data.occurrence_declared} disabled={!!propagation.propagated_at} onChange={(checked) => setData('occurrence_declared', checked)} error={errors.occurrence_declared} />
                </div>
                <Field label="Closure notes" error={errors.closure_notes}><Input value={data.closure_notes} onChange={(event) => setData('closure_notes', event.target.value)} disabled={!!propagation.propagated_at} /></Field>
                <Button size="sm" disabled={!propagation.can_propagate || !!propagation.propagated_at || processing}><SendToBack />Complete Mission</Button>
            </form>
        </Panel>
    );
}

function ChecklistPanel({ title, href, checklist, emptyText }: { title: string; href: string; checklist: MissionChecklistReport; emptyText: string }) {
    return (
        <Panel title={title} action={<Button size="sm" asChild><Link href={href}>Record</Link></Button>}>
            <Detail label="Template" value={checklist.template ? `${checklist.template.name} ${checklist.template.version}` : null} />
            {checklist.latest ? (
                <>
                    <div className="mb-3 flex gap-2"><StatusBadge value={checklist.latest.state.replaceAll('_', ' ')} /><span className="text-sm text-muted-foreground">{checklist.latest.performed_by ?? 'Unknown performer'}</span></div>
                    <Detail label="Performed at" value={checklist.latest.performed_at} />
                    <Detail label="Version used" value={checklist.latest.checklist_version} />
                    <Detail label="Exceptions" value={checklist.latest.exceptions} />
                </>
            ) : (
                <p className="text-sm text-muted-foreground">{emptyText}</p>
            )}
        </Panel>
    );
}
function formatPoint(point: MissionProfile['takeoff_point']) {
    if (!point) {
        return null;
    }

    return `${point.latitude}, ${point.longitude}${point.label ? ` - ${point.label}` : ''}`;
}

function Panel({ title, action, children }: { title: string; action?: React.ReactNode; children: React.ReactNode }) {
    return <section className="rounded-lg border bg-card p-4 text-card-foreground shadow-xs"><div className="mb-4 flex items-center justify-between gap-3"><h2 className="text-base font-semibold">{title}</h2>{action}</div><div className="space-y-3">{children}</div></section>;
}

function Detail({ label, value }: { label: string; value?: string | null }) {
    return <div><dt className="text-xs font-medium uppercase text-muted-foreground">{label}</dt><dd className="mt-1 text-sm">{value || 'Not captured'}</dd></div>;
}

function Field({ label, error, children }: { label: string; error?: string; children: React.ReactNode }) {
    return <div><Label>{label}</Label><div className="mt-2">{children}</div><InputError message={error} className="mt-2" /></div>;
}

function CheckField({ label, checked, disabled, onChange, error }: { label: string; checked: boolean; disabled: boolean; onChange: (checked: boolean) => void; error?: string }) {
    return <label className="rounded-md border p-3"><span className="flex items-center gap-2"><input type="checkbox" checked={checked} disabled={disabled} onChange={(event) => onChange(event.target.checked)} />{label}</span><InputError message={error} className="mt-2" /></label>;
}

function toLocalDateTime(value?: string | null) {
    if (!value) {
        return '';
    }

    return value.slice(0, 16);
}

function ReadinessPill({ status }: { status: MissionComplianceSummary['status'] }) {
    const classes = {
        green: 'border-emerald-200 bg-emerald-50 text-emerald-800',
        amber: 'border-amber-200 bg-amber-50 text-amber-800',
        red: 'border-red-200 bg-red-50 text-red-800',
    }[status];

    return <span className={`rounded-md border px-2 py-1 text-xs font-semibold uppercase ${classes}`}>{status}</span>;
}

function ReadinessIcon({ status }: { status: MissionComplianceSummary['status'] }) {
    if (status === 'green') {
        return <CheckCircle2 className="mt-0.5 h-4 w-4 shrink-0 text-emerald-600" />;
    }

    if (status === 'amber') {
        return <CircleAlert className="mt-0.5 h-4 w-4 shrink-0 text-amber-600" />;
    }

    return <AlertCircle className="mt-0.5 h-4 w-4 shrink-0 text-red-600" />;
}
