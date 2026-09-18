import { GeometryPreview } from '@/components/uas/mission-geometry-editor';
import { PageHeader } from '@/components/uas/page-header';
import { StatusBadge } from '@/components/uas/status-badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import { Head, Link, useForm } from '@inertiajs/react';
import { useState } from 'react';
import { ItemContent, Providers, Section } from './components';
import type { BriefingPayload } from './types';

export default function Briefing({ mission, briefing, compliance, revisions, permissions }: BriefingPayload) {
    const context = mission.aeronautical_context;
    const generate = useForm({
        aeronautical_context: {
            altitude_reference: context?.altitude_reference ?? '',
            minimum_altitude_ft: context?.minimum_altitude_ft ?? '',
            fir_codes: context?.fir_codes ?? [],
            aerodrome_codes: context?.aerodrome_codes ?? [],
        },
    });
    const ack = useForm<{ reviewed: boolean }>({ reviewed: false });
    const [reviewedBriefingId, setReviewedBriefingId] = useState<number | null>(null);
    const active = briefing?.id === compliance.briefing_id;
    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Missions', href: '/missions' },
                { title: mission.mission_number, href: `/missions/${mission.id}` },
                { title: 'Briefing', href: `/missions/${mission.id}/briefing` },
            ]}
        >
            <Head title={`${mission.mission_number} briefing`} />
            <div className="space-y-6 p-4 sm:p-6">
                <PageHeader
                    title="Pre-flight briefing"
                    description={`${mission.mission_number} · ${mission.location}`}
                    actions={
                        <Button variant="outline" asChild>
                            <Link href={`/missions/${mission.id}`}>Mission</Link>
                        </Button>
                    }
                />
                <Section title="Current release control">
                    <div className="flex flex-wrap gap-3">
                        <StatusBadge value={compliance.status} />
                        <StatusBadge value={compliance.freshness} />
                        <span className="text-sm">
                            {compliance.blockers} hard blockers · {compliance.warnings} warnings
                        </span>
                    </div>
                    {compliance.reasons.map((reason, i) => (
                        <p key={i} className="text-destructive text-sm">
                            {reason}
                        </p>
                    ))}
                    <p className="text-muted-foreground text-sm">Release consumes this control together with the other mission readiness checks.</p>
                </Section>
                <Providers providers={compliance.providers} />
                {permissions.generate && (
                    <Section title="Generate a briefing revision">
                        <form
                            className="space-y-3"
                            onSubmit={(e) => {
                                e.preventDefault();
                                generate.post(`/missions/${mission.id}/briefing`);
                            }}
                        >
                            <p className="text-muted-foreground text-sm">
                                Confirm altitude datum when known. Unknown or incompatible altitude references remain review items.
                            </p>
                            <label className="block text-sm">
                                Mission altitude reference{' '}
                                <select
                                    className="ml-2 rounded border p-2"
                                    value={generate.data.aeronautical_context.altitude_reference}
                                    onChange={(e) =>
                                        generate.setData('aeronautical_context', {
                                            ...generate.data.aeronautical_context,
                                            altitude_reference: e.target.value,
                                        })
                                    }
                                >
                                    <option value="">Unknown</option>
                                    <option>AGL</option>
                                    <option>AMSL</option>
                                </select>
                            </label>
                            {generate.data.aeronautical_context.altitude_reference === 'AMSL' && (
                                <label className="block text-sm">
                                    Minimum altitude (ft AMSL)
                                    <Input
                                        type="number"
                                        value={generate.data.aeronautical_context.minimum_altitude_ft}
                                        onChange={(e) =>
                                            generate.setData('aeronautical_context', {
                                                ...generate.data.aeronautical_context,
                                                minimum_altitude_ft: e.target.value,
                                            })
                                        }
                                    />
                                </label>
                            )}
                            {Object.values(generate.errors).map((error, i) => (
                                <p key={i} role="alert" className="text-destructive">
                                    {error}
                                </p>
                            ))}
                            <Button disabled={generate.processing}>{generate.processing ? 'Generating…' : 'Generate new briefing'}</Button>
                        </form>
                    </Section>
                )}
                {briefing ? (
                    <>
                        <Section title={`Briefing revision ${briefing.revision}`}>
                            <div className="flex flex-wrap gap-3">
                                <StatusBadge value={briefing.status} />
                                <span className="text-sm">Status when generated · {active ? 'Latest revision' : 'Historical revision'}</span>
                            </div>
                            <p className="text-sm">
                                Generated {briefing.generated_at} · Valid until {briefing.valid_until}
                            </p>
                            <p className="text-muted-foreground text-xs break-all">
                                Dataset {briefing.source_dataset_hash} · {briefing.assessment_version}
                            </p>
                            <p className="text-sm">
                                {briefing.summary.items} relevant items · {briefing.summary.blockers} blockers
                            </p>
                            {briefing.empty_data_message && <p>{briefing.empty_data_message}</p>}
                            {briefing.acknowledgements.map((a, i) => (
                                <p key={i} className="text-sm">
                                    Acknowledged by user {a.user_id} at {a.acknowledged_at}
                                </p>
                            ))}
                            {briefing.acknowledgement_required && !briefing.acknowledged && active && permissions.acknowledge && (
                                <form
                                    className="space-y-3 border-t pt-3"
                                    onSubmit={(e) => {
                                        e.preventDefault();
                                        if (ack.data.reviewed && reviewedBriefingId === briefing.id) {
                                            ack.post(`/missions/${mission.id}/briefing/${briefing.id}/acknowledge`);
                                        }
                                    }}
                                >
                                    <label className="flex items-center gap-2 text-sm">
                                        <input
                                            type="checkbox"
                                            checked={ack.data.reviewed && reviewedBriefingId === briefing.id}
                                            onChange={(e) => {
                                                ack.setData('reviewed', e.target.checked);
                                                setReviewedBriefingId(e.target.checked ? briefing.id : null);
                                            }}
                                        />
                                        I have reviewed this briefing and its source references.
                                    </label>
                                    {Object.values(ack.errors).map((error, i) => (
                                        <p key={i} role="alert" className="text-destructive">
                                            {error}
                                        </p>
                                    ))}
                                    <Button disabled={!ack.data.reviewed || reviewedBriefingId !== briefing.id || ack.processing}>
                                        Acknowledge briefing
                                    </Button>
                                </form>
                            )}
                        </Section>
                        <Section title="Mission geometry at briefing generation">
                            <GeometryPreview
                                takeoffPoint={{ latitude: briefing.mission.latitude ?? '', longitude: briefing.mission.longitude ?? '' }}
                                landingPoint={{ latitude: '', longitude: '' }}
                                polygon={(briefing.mission.mission_polygon ?? []).map((p) => ({
                                    latitude: String(p.latitude),
                                    longitude: String(p.longitude),
                                }))}
                                route={(briefing.mission.flight_route ?? []).map((p) => ({
                                    latitude: String(p.latitude),
                                    longitude: String(p.longitude),
                                }))}
                            />
                            <p className="text-sm">Operating radius: {briefing.mission.flight_radius_m ?? 'Unspecified'} m</p>
                        </Section>
                        {briefing.items.map((item) => (
                            <Section key={item.id} title={`${item.source_identifier} · ${item.title}`}>
                                <div className="flex gap-2">
                                    <StatusBadge value={item.severity ?? 'info'} />
                                    <StatusBadge value={item.release_effect ?? 'none'} />
                                </div>
                                <p className="text-sm">{item.reason}</p>
                                <details>
                                    <summary className="text-primary cursor-pointer text-sm">
                                        Source and interpretation presented in this briefing
                                    </summary>
                                    <div className="mt-4">
                                        <ItemContent item={item} />
                                    </div>
                                </details>
                            </Section>
                        ))}
                    </>
                ) : (
                    <Section title="Briefing not generated">
                        <p className="text-sm">No briefing evidence has been recorded for this mission.</p>
                    </Section>
                )}
                {revisions.length > 0 && (
                    <Section title="Previous briefing revisions">
                        <div className="flex flex-wrap gap-3">
                            {revisions.map((r) => (
                                <Link
                                    key={r.id}
                                    className="text-primary rounded border p-3 text-sm underline"
                                    href={`/missions/${mission.id}/briefing?revision=${r.revision}`}
                                >
                                    Revision {r.revision} · {r.overall_status}
                                </Link>
                            ))}
                        </div>
                    </Section>
                )}
            </div>
        </AppLayout>
    );
}
