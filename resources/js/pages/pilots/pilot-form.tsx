import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Link, useForm } from '@inertiajs/react';
import { Save } from 'lucide-react';
import { type FormEvent, type ReactNode } from 'react';
import { PilotOptions, PilotProfile } from './types';

interface PilotFormProps {
    options: PilotOptions;
    pilot?: PilotProfile;
}

type PilotFormData = Record<string, string>;

const splitRatings = (value: string) =>
    value
        .split(',')
        .map((item) => item.trim())
        .filter(Boolean);

export default function PilotForm({ options, pilot }: PilotFormProps) {
    const { data, setData, post, put, processing, errors, transform } = useForm<PilotFormData>({
        user_id: pilot?.user_id?.toString() ?? '',
        employee_number: pilot?.employee_number ?? '',
        first_name: pilot?.first_name ?? '',
        last_name: pilot?.last_name ?? '',
        preferred_name: pilot?.preferred_name ?? '',
        email: pilot?.email ?? '',
        phone: pilot?.phone ?? '',
        nationality: pilot?.nationality ?? '',
        date_of_birth: pilot?.date_of_birth ?? '',
        sacaa_certificate_number: pilot?.sacaa_certificate_number ?? '',
        rpc_category: pilot?.rpc_category ?? 'unknown',
        ratings: pilot?.ratings?.join(', ') ?? '',
        medical_status: pilot?.medical_status ?? 'unverified',
        radiotelephony_qualification: pilot?.radiotelephony_qualification ?? 'unverified',
        language_proficiency: pilot?.language_proficiency ?? '',
        profile_status: pilot?.profile_status ?? 'draft',
        notes: pilot?.notes ?? '',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();

        transform((payload) => ({
            ...payload,
            user_id: payload.user_id || null,
            employee_number: payload.employee_number || null,
            preferred_name: payload.preferred_name || null,
            email: payload.email || null,
            phone: payload.phone || null,
            nationality: payload.nationality || null,
            date_of_birth: payload.date_of_birth || null,
            sacaa_certificate_number: payload.sacaa_certificate_number || null,
            ratings: splitRatings(payload.ratings),
            language_proficiency: payload.language_proficiency || null,
            notes: payload.notes || null,
        }));

        if (pilot) {
            put(`/pilots/${pilot.id}`);
            return;
        }

        post('/pilots');
    };

    return (
        <form onSubmit={submit} className="space-y-8">
            <div className="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                <Field label="First name" error={errors.first_name}>
                    <Input value={data.first_name} onChange={(event) => setData('first_name', event.target.value)} required />
                </Field>
                <Field label="Last name" error={errors.last_name}>
                    <Input value={data.last_name} onChange={(event) => setData('last_name', event.target.value)} required />
                </Field>
                <Field label="Preferred name" error={errors.preferred_name}>
                    <Input value={data.preferred_name} onChange={(event) => setData('preferred_name', event.target.value)} />
                </Field>
                <Field label="Email" error={errors.email}>
                    <Input type="email" value={data.email} onChange={(event) => setData('email', event.target.value)} />
                </Field>
                <Field label="Phone" error={errors.phone}>
                    <Input value={data.phone} onChange={(event) => setData('phone', event.target.value)} />
                </Field>
                <Field label="Employee number" error={errors.employee_number}>
                    <Input value={data.employee_number} onChange={(event) => setData('employee_number', event.target.value)} />
                </Field>
                <Field label="Nationality" error={errors.nationality}>
                    <Input value={data.nationality} onChange={(event) => setData('nationality', event.target.value)} />
                </Field>
                <Field label="Date of birth" error={errors.date_of_birth}>
                    <Input type="date" value={data.date_of_birth} onChange={(event) => setData('date_of_birth', event.target.value)} />
                </Field>
                <Field label="SACAA certificate number" error={errors.sacaa_certificate_number}>
                    <Input value={data.sacaa_certificate_number} onChange={(event) => setData('sacaa_certificate_number', event.target.value)} />
                </Field>
                <Field label="RPC category" error={errors.rpc_category}>
                    <OptionSelect value={data.rpc_category} options={options.rpcCategories} onChange={(value) => setData('rpc_category', value)} />
                </Field>
                <Field label="Medical status" error={errors.medical_status}>
                    <OptionSelect value={data.medical_status} options={options.medicalStatuses} onChange={(value) => setData('medical_status', value)} />
                </Field>
                <Field label="Radiotelephony" error={errors.radiotelephony_qualification}>
                    <OptionSelect
                        value={data.radiotelephony_qualification}
                        options={options.radiotelephonyQualifications}
                        onChange={(value) => setData('radiotelephony_qualification', value)}
                    />
                </Field>
                <Field label="Language proficiency" error={errors.language_proficiency}>
                    <Input value={data.language_proficiency} onChange={(event) => setData('language_proficiency', event.target.value)} />
                </Field>
                <Field label="Profile status" error={errors.profile_status}>
                    <OptionSelect value={data.profile_status} options={options.profileStatuses} onChange={(value) => setData('profile_status', value)} />
                </Field>
                <Field label="Ratings" error={errors.ratings} className="xl:col-span-2">
                    <Input value={data.ratings} onChange={(event) => setData('ratings', event.target.value)} placeholder="BVLOS, instructor, night operations" />
                </Field>
            </div>

            <Field label="Notes" error={errors.notes}>
                <textarea
                    value={data.notes}
                    onChange={(event) => setData('notes', event.target.value)}
                    className="min-h-32 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus:outline-hidden focus:ring-2 focus:ring-ring focus:ring-offset-2"
                />
            </Field>

            <div className="flex flex-wrap items-center gap-3">
                <Button disabled={processing}>
                    <Save />
                    Save profile
                </Button>
                <Button variant="outline" asChild>
                    <Link href="/pilots">Cancel</Link>
                </Button>
            </div>
        </form>
    );
}

function OptionSelect({ value, options, onChange }: { value: string; options: Record<string, string>; onChange: (value: string) => void }) {
    return (
        <Select value={value} onValueChange={onChange}>
            <SelectTrigger>
                <SelectValue />
            </SelectTrigger>
            <SelectContent>
                {Object.entries(options).map(([key, label]) => (
                    <SelectItem key={key} value={key}>
                        {label}
                    </SelectItem>
                ))}
            </SelectContent>
        </Select>
    );
}

function Field({ label, error, className, children }: { label: string; error?: string; className?: string; children: ReactNode }) {
    return (
        <div className={className}>
            <Label>{label}</Label>
            <div className="mt-2">{children}</div>
            <InputError message={error} className="mt-2" />
        </div>
    );
}
