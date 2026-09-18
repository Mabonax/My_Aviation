import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { GeoPoint, MissionGeometryEditor } from '@/components/uas/mission-geometry-editor';
import { PageHeader } from '@/components/uas/page-header';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';
import { MissionOptions } from './types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Missions', href: '/missions' },
    { title: 'Create', href: '/missions/create' },
];

type MissionFormData = {
    purpose: string;
    client_project: string;
    location: string;
    location_search_query: string;
    latitude: string;
    longitude: string;
    takeoff_point: GeoPoint;
    landing_point: GeoPoint;
    mission_polygon: GeoPoint[];
    flight_route: GeoPoint[];
    flight_radius_m: string;
    operation_category: string;
    uas_operator_id: string;
    uas_aircraft_id: string;
    uas_pilot_id: string;
    planned_start_at: string;
    planned_end_at: string;
    maximum_altitude_ft: string;
    planned_distance_km: string;
    operation_visibility: string;
    day_night: string;
    weather: string;
    airspace_assessment: string;
    emergency_arrangements: string;
};

export default function Create({ options }: { options: MissionOptions }) {
    const { data, setData, post, processing, errors, transform } = useForm<MissionFormData>({
        purpose: '',
        client_project: '',
        location: '',
        location_search_query: '',
        latitude: '',
        longitude: '',
        takeoff_point: { latitude: '', longitude: '', label: 'Take-off' },
        landing_point: { latitude: '', longitude: '', label: 'Landing' },
        mission_polygon: [],
        flight_route: [],
        flight_radius_m: '',
        operation_category: 'standard',
        uas_operator_id: '',
        uas_aircraft_id: '',
        uas_pilot_id: '',
        planned_start_at: '',
        planned_end_at: '',
        maximum_altitude_ft: '',
        planned_distance_km: '',
        operation_visibility: 'vlos',
        day_night: 'day',
        weather: '',
        airspace_assessment: '',
        emergency_arrangements: '',
    });

    function submit(event: FormEvent) {
        event.preventDefault();

        transform((values) => ({
            ...values,
            uas_operator_id: values.uas_operator_id || null,
            uas_aircraft_id: values.uas_aircraft_id || null,
            uas_pilot_id: values.uas_pilot_id || null,
            latitude: values.latitude || null,
            longitude: values.longitude || null,
            takeoff_point: cleanPoint(values.takeoff_point),
            landing_point: cleanPoint(values.landing_point),
            mission_polygon: cleanPoints(values.mission_polygon),
            flight_route: cleanPoints(values.flight_route),
            flight_radius_m: values.flight_radius_m || null,
            maximum_altitude_ft: values.maximum_altitude_ft || null,
            planned_distance_km: values.planned_distance_km || null,
            risk_assessment: values.emergency_arrangements ? { emergency_arrangements_reviewed: true } : null,
        }));

        post('/missions');
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Create mission" />
            <form onSubmit={submit} className="flex flex-1 flex-col gap-6 p-4 sm:p-6">
                <PageHeader title="Create mission" description="Capture the mission plan, geometry and release-gate evidence before operational release." />

                <section className="grid gap-4 rounded-lg border bg-card p-4 text-card-foreground shadow-xs md:grid-cols-2">
                    <Field label="Purpose" error={errors.purpose}><Input value={data.purpose} onChange={(event) => setData('purpose', event.target.value)} /></Field>
                    <Field label="Client / project" error={errors.client_project}><Input value={data.client_project} onChange={(event) => setData('client_project', event.target.value)} /></Field>
                    <Field label="Location" error={errors.location}><Input value={data.location} onChange={(event) => setData('location', event.target.value)} /></Field>
                    <Field label="Location search" error={errors.location_search_query}><Input value={data.location_search_query} onChange={(event) => setData('location_search_query', event.target.value)} /></Field>
                    <Field label="Latitude" error={errors.latitude}><Input value={data.latitude} onChange={(event) => setData('latitude', event.target.value)} /></Field>
                    <Field label="Longitude" error={errors.longitude}><Input value={data.longitude} onChange={(event) => setData('longitude', event.target.value)} /></Field>
                    <SelectField label="Operator" value={data.uas_operator_id} onChange={(value) => setData('uas_operator_id', value)} options={options.operators} placeholder="Select operator" error={errors.uas_operator_id} />
                    <SelectField label="Aircraft" value={data.uas_aircraft_id} onChange={(value) => setData('uas_aircraft_id', value)} options={options.aircraft} placeholder="Select aircraft" error={errors.uas_aircraft_id} />
                    <SelectField label="Pilot" value={data.uas_pilot_id} onChange={(value) => setData('uas_pilot_id', value)} options={options.pilots} placeholder="Select pilot" error={errors.uas_pilot_id} />
                    <SelectMap label="Visibility" value={data.operation_visibility} onChange={(value) => setData('operation_visibility', value)} options={options.visibility_modes} />
                    <SelectMap label="Day / night" value={data.day_night} onChange={(value) => setData('day_night', value)} options={options.day_night_modes} />
                    <Field label="Planned start" error={errors.planned_start_at}><Input type="datetime-local" value={data.planned_start_at} onChange={(event) => setData('planned_start_at', event.target.value)} /></Field>
                    <Field label="Maximum altitude ft" error={errors.maximum_altitude_ft}><Input value={data.maximum_altitude_ft} onChange={(event) => setData('maximum_altitude_ft', event.target.value)} /></Field>
                    <Field label="Planned distance km" error={errors.planned_distance_km}><Input value={data.planned_distance_km} onChange={(event) => setData('planned_distance_km', event.target.value)} /></Field>
                </section>

                <MissionGeometryEditor
                    takeoffPoint={data.takeoff_point}
                    landingPoint={data.landing_point}
                    polygon={data.mission_polygon}
                    route={data.flight_route}
                    radius={data.flight_radius_m}
                    onTakeoffPointChange={(point) => setData('takeoff_point', point)}
                    onLandingPointChange={(point) => setData('landing_point', point)}
                    onPolygonChange={(points) => setData('mission_polygon', points)}
                    onRouteChange={(points) => setData('flight_route', points)}
                    onRadiusChange={(radius) => setData('flight_radius_m', radius)}
                />

                <section className="grid gap-4 rounded-lg border bg-card p-4 text-card-foreground shadow-xs md:grid-cols-2">
                    <Field label="Weather" error={errors.weather}><TextArea value={data.weather} onChange={(event) => setData('weather', event.target.value)} /></Field>
                    <Field label="Airspace assessment" error={errors.airspace_assessment}><TextArea value={data.airspace_assessment} onChange={(event) => setData('airspace_assessment', event.target.value)} /></Field>
                    <Field label="Emergency arrangements" error={errors.emergency_arrangements}><TextArea value={data.emergency_arrangements} onChange={(event) => setData('emergency_arrangements', event.target.value)} /></Field>
                </section>

                <div className="flex gap-3">
                    <Button disabled={processing}>Create mission</Button>
                    <Button variant="outline" asChild><Link href="/missions">Cancel</Link></Button>
                </div>
            </form>
        </AppLayout>
    );
}

function cleanPoint(point: GeoPoint) {
    return point.latitude && point.longitude ? { latitude: Number(point.latitude), longitude: Number(point.longitude), label: point.label || null } : null;
}

function cleanPoints(points: GeoPoint[]) {
    return points.map(cleanPoint).filter(Boolean);
}

function Field({ label, error, children }: { label: string; error?: string; children: React.ReactNode }) {
    return <div className="space-y-2"><Label>{label}</Label>{children}{error && <p className="text-sm text-destructive">{error}</p>}</div>;
}

function SelectField({ label, value, onChange, options, placeholder, error }: { label: string; value: string; onChange: (value: string) => void; options: Array<{ id: number; label: string }>; placeholder: string; error?: string }) {
    return <div className="space-y-2"><Label>{label}</Label><Select value={value} onValueChange={onChange}><SelectTrigger><SelectValue placeholder={placeholder} /></SelectTrigger><SelectContent>{options.map((option) => <SelectItem key={option.id} value={option.id.toString()}>{option.label}</SelectItem>)}</SelectContent></Select>{error && <p className="text-sm text-destructive">{error}</p>}</div>;
}

function SelectMap({ label, value, onChange, options }: { label: string; value: string; onChange: (value: string) => void; options: Record<string, string> }) {
    return <div className="space-y-2"><Label>{label}</Label><Select value={value} onValueChange={onChange}><SelectTrigger><SelectValue /></SelectTrigger><SelectContent>{Object.entries(options).map(([key, label]) => <SelectItem key={key} value={key}>{label}</SelectItem>)}</SelectContent></Select></div>;
}

function TextArea({ value, onChange }: { value: string; onChange: (event: React.ChangeEvent<HTMLTextAreaElement>) => void }) {
    return <textarea value={value} onChange={onChange} className="min-h-24 w-full rounded-md border border-input bg-background px-3 py-2 text-base ring-offset-background placeholder:text-muted-foreground focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 md:text-sm" />;
}
