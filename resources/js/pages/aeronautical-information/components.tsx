import { GeometryPreview, type GeoPoint } from '@/components/uas/mission-geometry-editor';
import { StatusBadge } from '@/components/uas/status-badge';
import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';
import type { InformationItem, SourceHealth } from './types';

export function Section({ title, children }: { title: string; children: ReactNode }) {
    return (
        <section className="dark:bg-background space-y-3 rounded-xl border bg-white p-5 shadow-sm">
            <h2 className="text-primary font-semibold">{title}</h2>
            {children}
        </section>
    );
}
export function Providers({ providers }: { providers: SourceHealth[] }) {
    return (
        <Section title="Information source health">
            <div className="grid gap-3 md:grid-cols-2">
                {providers.map((p) => (
                    <div key={p.provider} className="space-y-2 rounded-lg border p-3 text-sm">
                        <div className="flex flex-wrap justify-between gap-2">
                            <strong>{p.provider.replaceAll('_', ' ')}</strong>
                            <StatusBadge value={p.health_status ?? p.status} />
                            {p.reason && <p className="text-sm text-muted-foreground">{p.reason}</p>}
                        </div>
                        <p>
                            {p.required ? 'Required for release' : 'Supporting source'} ·{' '}
                            {p.operational_authority ? 'Operational provider' : 'Reference or unavailable provider'}
                        </p>
                        <p className="text-muted-foreground">Dataset: {p.dataset_timestamp ?? 'Unavailable'}</p>
                        {p.error && <p className="text-destructive">{p.error}</p>}
                    </div>
                ))}
            </div>
        </Section>
    );
}
export function ItemGeometry({ item }: { item: InformationItem }) {
    const g = item.geometry;
    const point = { latitude: String(g.latitude ?? ''), longitude: String(g.longitude ?? '') };
    const shape = g.geojson;
    let polygon: GeoPoint[] = [];
    let route: GeoPoint[] = [];
    const fromCoordinates = (coordinates: unknown): GeoPoint[] =>
        Array.isArray(coordinates)
            ? coordinates
                  .filter((p) => Array.isArray(p) && p.length >= 2 && Number.isFinite(Number(p[0])) && Number.isFinite(Number(p[1])))
                  .map((p) => ({ latitude: String(p[1]), longitude: String(p[0]) }))
            : [];
    if (shape?.type?.toLowerCase() === 'polygon') {
        if (Array.isArray(shape.coordinates)) polygon = fromCoordinates(shape.coordinates[0]);
        else if (Array.isArray(shape.points))
            polygon = shape.points
                .filter((p) => p && typeof p === 'object' && 'latitude' in p && 'longitude' in p)
                .map((p) => ({ latitude: String(p.latitude), longitude: String(p.longitude) }));
    }
    if (shape?.type?.toLowerCase() === 'linestring') route = fromCoordinates(shape.coordinates);
    if (shape?.type?.toLowerCase() === 'point') Object.assign(point, fromCoordinates([shape.coordinates])[0] ?? {});
    return (
        <Section title="Geometry">
            <GeometryPreview takeoffPoint={point} landingPoint={{ latitude: '', longitude: '' }} polygon={polygon} route={route} />
            <p className="text-muted-foreground text-sm">
                Coordinate sketch. Affected radius: {g.radius_nm ?? 'Unspecified'} NM. Geometry completeness and applicability are assessed by YAW.
            </p>
        </Section>
    );
}
export function ItemContent({ item }: { item: InformationItem }) {
    return (
        <div className="space-y-4">
            <Section title="Source content">
                <div className="flex flex-wrap gap-2">
                    <StatusBadge value={item.type} />
                    <StatusBadge value={item.source_classification} />
                    <StatusBadge value={item.status} />
                </div>
                <p className="text-sm">
                    {item.usable_for_release
                        ? 'Operational source content; YAW interpretation is shown separately.'
                        : 'Reference only. Operational authority has not been established.'}
                </p>
                <pre className="bg-muted/40 max-h-80 overflow-auto rounded-lg p-4 text-sm break-words whitespace-pre-wrap">
                    {item.source.raw_message ?? 'No raw message supplied. See the retained source payload.'}
                </pre>
                {item.source.source_url && (
                    <a className="text-primary text-sm underline" href={item.source.source_url} target="_blank" rel="noreferrer">
                        Open source reference
                    </a>
                )}
                <details>
                    <summary className="cursor-pointer text-sm">Retained source payload</summary>
                    <pre className="max-h-80 overflow-auto text-xs break-words whitespace-pre-wrap">
                        {JSON.stringify(item.source.raw_payload, null, 2)}
                    </pre>
                </details>
            </Section>
            <Section title="YAW normalized interpretation">
                <p className="text-muted-foreground text-sm">This interpretation is separate from the source publication.</p>
                {item.summary && <p>{item.summary}</p>}
                <pre className="max-h-72 overflow-auto text-sm break-words whitespace-pre-wrap">{JSON.stringify(item.interpretation, null, 2)}</pre>
                {item.reason && <p className="rounded-lg bg-blue-50 p-3 text-sm text-blue-950">{item.reason}</p>}
            </Section>
            <Section title="Applicability and provenance">
                <dl className="grid gap-3 text-sm sm:grid-cols-2">
                    {Object.entries({
                        Identifier: item.source_identifier,
                        Provider: item.provider,
                        Revision: item.source_revision,
                        Issued: item.issued_at,
                        From: item.effective_from,
                        Until: item.permanent ? 'Permanent' : item.effective_until,
                        Received: item.received_at,
                        Checksum: item.checksum,
                    }).map(([label, value]) => (
                        <div key={label} className="min-w-0">
                            <dt className="text-muted-foreground">{label}</dt>
                            <dd className="break-all">{value ?? 'Unknown'}</dd>
                        </div>
                    ))}
                </dl>
                {item.supersedes_id && (
                    <Link className="text-primary underline" href={`/aeronautical-information/${item.supersedes_id}`}>
                        Previous source interpretation
                    </Link>
                )}
            </Section>
            <ItemGeometry item={item} />
        </div>
    );
}
