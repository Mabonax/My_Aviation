import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Plus, Trash2 } from 'lucide-react';

export type GeoPoint = {
    latitude: string;
    longitude: string;
    label?: string;
};

interface MissionGeometryEditorProps {
    takeoffPoint: GeoPoint;
    landingPoint: GeoPoint;
    polygon: GeoPoint[];
    route: GeoPoint[];
    radius: string;
    onTakeoffPointChange: (point: GeoPoint) => void;
    onLandingPointChange: (point: GeoPoint) => void;
    onPolygonChange: (points: GeoPoint[]) => void;
    onRouteChange: (points: GeoPoint[]) => void;
    onRadiusChange: (radius: string) => void;
}

export function MissionGeometryEditor({ takeoffPoint, landingPoint, polygon, route, radius, onTakeoffPointChange, onLandingPointChange, onPolygonChange, onRouteChange, onRadiusChange }: MissionGeometryEditorProps) {
    return (
        <section className="rounded-lg border bg-card p-4 text-card-foreground shadow-xs">
            <div className="grid gap-4 lg:grid-cols-[1fr_18rem]">
                <div className="space-y-4">
                    <div className="grid gap-4 md:grid-cols-2">
                        <PointFields title="Take-off point" point={takeoffPoint} onChange={onTakeoffPointChange} />
                        <PointFields title="Landing point" point={landingPoint} onChange={onLandingPointChange} />
                    </div>
                    <div className="grid gap-4 md:grid-cols-2">
                        <PointList title="Mission polygon" points={polygon} minRows={3} onChange={onPolygonChange} />
                        <PointList title="Flight route" points={route} minRows={2} onChange={onRouteChange} />
                    </div>
                    <div className="max-w-sm space-y-2">
                        <Label>Flight radius m</Label>
                        <Input value={radius} onChange={(event) => onRadiusChange(event.target.value)} inputMode="numeric" />
                    </div>
                </div>
                <GeometryPreview takeoffPoint={takeoffPoint} landingPoint={landingPoint} polygon={polygon} route={route} />
            </div>
        </section>
    );
}

function PointFields({ title, point, onChange }: { title: string; point: GeoPoint; onChange: (point: GeoPoint) => void }) {
    return (
        <div className="space-y-3 rounded-md border p-3">
            <h3 className="text-sm font-medium">{title}</h3>
            <div className="grid grid-cols-2 gap-3">
                <div className="space-y-2"><Label>Latitude</Label><Input value={point.latitude} onChange={(event) => onChange({ ...point, latitude: event.target.value })} /></div>
                <div className="space-y-2"><Label>Longitude</Label><Input value={point.longitude} onChange={(event) => onChange({ ...point, longitude: event.target.value })} /></div>
            </div>
            <div className="space-y-2"><Label>Label</Label><Input value={point.label ?? ''} onChange={(event) => onChange({ ...point, label: event.target.value })} /></div>
        </div>
    );
}

function PointList({ title, points, minRows, onChange }: { title: string; points: GeoPoint[]; minRows: number; onChange: (points: GeoPoint[]) => void }) {
    const rows = points.length ? points : Array.from({ length: minRows }, () => ({ latitude: '', longitude: '', label: '' }));

    function update(index: number, point: GeoPoint) {
        onChange(rows.map((row, rowIndex) => rowIndex === index ? point : row));
    }

    function remove(index: number) {
        onChange(rows.filter((_, rowIndex) => rowIndex !== index));
    }

    return (
        <div className="space-y-3 rounded-md border p-3">
            <div className="flex items-center justify-between gap-3">
                <h3 className="text-sm font-medium">{title}</h3>
                <Button type="button" size="icon" variant="outline" onClick={() => onChange([...rows, { latitude: '', longitude: '', label: '' }])} aria-label={`Add ${title} point`}><Plus className="size-4" /></Button>
            </div>
            <div className="space-y-2">
                {rows.map((point, index) => (
                    <div key={index} className="grid grid-cols-[1fr_1fr_2rem] gap-2">
                        <Input value={point.latitude} placeholder="Latitude" onChange={(event) => update(index, { ...point, latitude: event.target.value })} />
                        <Input value={point.longitude} placeholder="Longitude" onChange={(event) => update(index, { ...point, longitude: event.target.value })} />
                        <Button type="button" size="icon" variant="ghost" onClick={() => remove(index)} aria-label={`Remove ${title} point`}><Trash2 className="size-4" /></Button>
                    </div>
                ))}
            </div>
        </div>
    );
}

export function GeometryPreview({ takeoffPoint, landingPoint, polygon, route }: { takeoffPoint: GeoPoint; landingPoint: GeoPoint; polygon: GeoPoint[]; route: GeoPoint[] }) {
    const validPolygon = polygon.filter(isValidPoint);
    const validRoute = route.filter(isValidPoint);
    const points = [takeoffPoint, landingPoint, ...validPolygon, ...validRoute].filter(isValidPoint);

    if (points.length === 0) {
        return <div className="flex min-h-72 items-center justify-center rounded-md border bg-muted/30 text-sm text-muted-foreground">No coordinates captured</div>;
    }

    const latitudes = points.map((point) => Number(point.latitude));
    const longitudes = points.map((point) => Number(point.longitude));
    const minLatitude = Math.min(...latitudes);
    const maxLatitude = Math.max(...latitudes);
    const minLongitude = Math.min(...longitudes);
    const maxLongitude = Math.max(...longitudes);
    const latitudeSpan = Math.max(maxLatitude - minLatitude, 0.0001);
    const longitudeSpan = Math.max(maxLongitude - minLongitude, 0.0001);
    const project = (point: GeoPoint) => `${((Number(point.longitude) - minLongitude) / longitudeSpan) * 240 + 20},${240 - ((Number(point.latitude) - minLatitude) / latitudeSpan) * 200}`;

    return (
        <div className="rounded-md border bg-muted/30 p-3">
            <svg viewBox="0 0 280 260" className="h-72 w-full" role="img" aria-label="Mission geometry preview">
                <rect x="0" y="0" width="280" height="260" rx="8" className="fill-background" />
                {validPolygon.length >= 3 && <polygon points={validPolygon.map(project).join(' ')} className="fill-primary/10 stroke-primary" strokeWidth="2" />}
                {validRoute.length >= 2 && <polyline points={validRoute.map(project).join(' ')} className="fill-none stroke-foreground" strokeWidth="2" strokeDasharray="5 4" />}
                {isValidPoint(takeoffPoint) && <circle cx={project(takeoffPoint).split(',')[0]} cy={project(takeoffPoint).split(',')[1]} r="5" className="fill-green-600" />}
                {isValidPoint(landingPoint) && <circle cx={project(landingPoint).split(',')[0]} cy={project(landingPoint).split(',')[1]} r="5" className="fill-red-600" />}
            </svg>
            <div className="mt-2 grid grid-cols-2 gap-2 text-xs text-muted-foreground">
                <span>Polygon: {validPolygon.length} points</span>
                <span>Route: {validRoute.length} points</span>
            </div>
        </div>
    );
}

function isValidPoint(point: GeoPoint) {
    return point.latitude !== '' && point.longitude !== '' && Number.isFinite(Number(point.latitude)) && Number.isFinite(Number(point.longitude));
}
