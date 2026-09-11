import { Badge } from '@/components/ui/badge';
import { PageHeader } from '@/components/uas/page-header';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { Layers } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Aviation overlays', href: '/aviation-overlays' }];

interface OverlaySource {
    id: number;
    name: string;
    publisher: string;
    source_url: string;
    source_version: string;
    effective_date: string | null;
    authoritative: boolean;
    usage_notes: string | null;
    zones_count: number;
}

interface OverlayZone {
    id: number;
    zone_type: string;
    name: string;
    identifier: string | null;
    operational_notes: string | null;
    source: { name: string; publisher: string; source_url: string; source_version: string; authoritative: boolean };
}

interface OverlayReport {
    generated_at: string;
    layer_summary: Record<string, number>;
    sources: OverlaySource[];
    zones: OverlayZone[];
}

export default function Overlays({ report }: { report: OverlayReport }) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Aviation overlays" />
            <div className="flex flex-1 flex-col gap-6 p-4 sm:p-6">
                <PageHeader title="Aviation overlays" description="FR-GEO-002 source-aware overlay registry for airspace and operating-zone review." />

                <section className="grid gap-4 md:grid-cols-3 xl:grid-cols-6">
                    {Object.entries(report.layer_summary).map(([type, total]) => (
                        <div key={type} className="rounded-lg border bg-card p-4 text-card-foreground shadow-xs">
                            <div className="flex items-center justify-between gap-3"><Layers className="size-5 text-primary" /><Badge variant="secondary">{total}</Badge></div>
                            <div className="mt-3 text-sm font-medium capitalize">{type.replaceAll('_', ' ')}</div>
                        </div>
                    ))}
                </section>

                <section className="rounded-lg border bg-card text-card-foreground shadow-xs">
                    <h2 className="border-b px-4 py-3 text-base font-semibold">Sources</h2>
                    <div className="divide-y">
                        {report.sources.map((source) => (
                            <div key={source.id} className="grid gap-2 px-4 py-4 lg:grid-cols-[1fr_10rem_8rem] lg:items-center">
                                <div>
                                    <div className="font-medium">{source.name}</div>
                                    <div className="text-sm text-muted-foreground">{source.publisher} - {source.source_version}</div>
                                    <a className="text-sm text-primary underline-offset-4 hover:underline" href={source.source_url}>{source.source_url}</a>
                                    {source.usage_notes && <p className="mt-1 text-sm text-muted-foreground">{source.usage_notes}</p>}
                                </div>
                                <Badge variant={source.authoritative ? 'default' : 'secondary'}>{source.authoritative ? 'Authoritative' : 'Reference'}</Badge>
                                <div className="text-sm text-muted-foreground">{source.zones_count} zones</div>
                            </div>
                        ))}
                    </div>
                </section>

                <section className="rounded-lg border bg-card text-card-foreground shadow-xs">
                    <h2 className="border-b px-4 py-3 text-base font-semibold">Active Layers</h2>
                    <div className="divide-y">
                        {report.zones.map((zone) => (
                            <div key={zone.id} className="grid gap-2 px-4 py-4 lg:grid-cols-[12rem_1fr_16rem]">
                                <div><Badge variant="outline">{zone.zone_type.replaceAll('_', ' ')}</Badge></div>
                                <div>
                                    <div className="font-medium">{zone.name}</div>
                                    <div className="text-sm text-muted-foreground">{zone.identifier || 'No identifier'}</div>
                                    {zone.operational_notes && <p className="mt-1 text-sm text-muted-foreground">{zone.operational_notes}</p>}
                                </div>
                                <div className="text-sm text-muted-foreground">{zone.source.publisher}</div>
                            </div>
                        ))}
                    </div>
                </section>
            </div>
        </AppLayout>
    );
}
