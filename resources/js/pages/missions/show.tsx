import { Button } from '@/components/ui/button';
import { PageHeader } from '@/components/uas/page-header';
import { StatusBadge } from '@/components/uas/status-badge';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { MissionBatteryReport, MissionChecklistReport, MissionCrewReport, MissionDefectReport, MissionProfile, MissionTrackReport, SpatialRuleReview } from './types';

export default function Show({ mission, spatialRuleReview, preFlightChecklist, postFlightChecklist, crew, tracks, batteries, defects }: { mission: MissionProfile; spatialRuleReview: SpatialRuleReview; preFlightChecklist: MissionChecklistReport; postFlightChecklist: MissionChecklistReport; crew: MissionCrewReport; tracks: MissionTrackReport; batteries: MissionBatteryReport; defects: MissionDefectReport }) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Missions', href: '/missions' },
        { title: mission.mission_number, href: `/missions/${mission.id}` },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={mission.mission_number} />
            <div className="flex flex-1 flex-col gap-6 p-4 sm:p-6">
                <PageHeader title={mission.mission_number} description={mission.purpose} actions={<Button variant="outline" asChild><Link href="/missions">Back</Link></Button>} />

                <div className="grid gap-4 xl:grid-cols-2">
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

                    <Panel title="Map Geometry">
                        <Detail label="Take-off point" value={formatPoint(mission.takeoff_point)} />
                        <Detail label="Landing point" value={formatPoint(mission.landing_point)} />
                        <Detail label="Mission polygon" value={`${mission.mission_polygon.length} points`} />
                        <Detail label="Flight route" value={`${mission.flight_route.length} points`} />
                        <Detail label="Flight radius" value={mission.flight_radius_m ? `${mission.flight_radius_m} m` : null} />
                    </Panel>
                </div>

                <div className="grid gap-4 xl:grid-cols-2">
                    <Panel title="Release Gate">
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