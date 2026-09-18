import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Link, useForm } from '@inertiajs/react';
import { Save } from 'lucide-react';
import { FormEvent, ReactNode } from 'react';
import { CertificateCaseOptions, OperatorCertificateCase, OperatorProfile } from '../types';

type CaseFormData = {
    case_number: string;
    case_type: string;
    status: string;
    deadline_at: string;
    evidence_requirements: string[];
    outstanding_documents: string[];
    fleet_scope: string[];
    personnel_scope: string[];
    ops_spec_scope: string[];
    operations_manual_revision: string;
    fees: string[];
    authority_correspondence: string[];
    outcome: string;
    submitted_at: string;
    decided_at: string;
};

export default function CertificateCaseForm({ operator, certificateCase, options }: { operator?: OperatorProfile; certificateCase?: OperatorCertificateCase; options: CertificateCaseOptions }) {
    const operatorId = operator?.id ?? certificateCase?.operator.id;
    const { data, setData, post, put, processing, errors, transform } = useForm<CaseFormData>({
        case_number: certificateCase?.case_number ?? '',
        case_type: certificateCase?.case_type ?? 'application',
        status: certificateCase?.status ?? 'draft',
        deadline_at: certificateCase?.deadline_at ?? '',
        evidence_requirements: certificateCase?.evidence_requirements ?? [],
        outstanding_documents: certificateCase?.outstanding_documents ?? [],
        fleet_scope: certificateCase?.fleet_scope ?? [],
        personnel_scope: certificateCase?.personnel_scope ?? [],
        ops_spec_scope: certificateCase?.ops_spec_scope ?? [],
        operations_manual_revision: certificateCase?.operations_manual_revision ?? '',
        fees: certificateCase?.fees ?? [],
        authority_correspondence: certificateCase?.authority_correspondence ?? [],
        outcome: certificateCase?.outcome ?? '',
        submitted_at: toLocalDateTime(certificateCase?.submitted_at),
        decided_at: toLocalDateTime(certificateCase?.decided_at),
    });

    function submit(event: FormEvent) {
        event.preventDefault();
        transform((payload) => ({
            ...payload,
            case_number: payload.case_number || null,
            deadline_at: payload.deadline_at || null,
            operations_manual_revision: payload.operations_manual_revision || null,
            outcome: payload.outcome || null,
            submitted_at: payload.submitted_at || null,
            decided_at: payload.decided_at || null,
        }));

        if (certificateCase) {
            put(`/operator-certificate-cases/${certificateCase.id}`);
            return;
        }

        post(`/operators/${operatorId}/certificate-cases`);
    }

    return (
        <form onSubmit={submit} className="space-y-8">
            <section className="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                <Field label="Case number" error={errors.case_number}><Input value={data.case_number} onChange={(event) => setData('case_number', event.target.value)} placeholder="Auto-generated if blank" /></Field>
                <Field label="Case type" error={errors.case_type}><OptionSelect value={data.case_type} options={options.types} onChange={(value) => setData('case_type', value)} /></Field>
                <Field label="Status" error={errors.status}><OptionSelect value={data.status} options={options.statuses} onChange={(value) => setData('status', value)} /></Field>
                <Field label="Deadline" error={errors.deadline_at}><Input type="date" value={data.deadline_at} onChange={(event) => setData('deadline_at', event.target.value)} /></Field>
                <Field label="Operations Manual revision" error={errors.operations_manual_revision}><Input value={data.operations_manual_revision} onChange={(event) => setData('operations_manual_revision', event.target.value)} /></Field>
                <Field label="Submitted at" error={errors.submitted_at}><Input type="datetime-local" value={data.submitted_at} onChange={(event) => setData('submitted_at', event.target.value)} /></Field>
                <Field label="Decided at" error={errors.decided_at}><Input type="datetime-local" value={data.decided_at} onChange={(event) => setData('decided_at', event.target.value)} /></Field>
            </section>

            <section className="grid gap-5 md:grid-cols-2">
                <TextList label="Evidence requirements" value={data.evidence_requirements} onChange={(value) => setData('evidence_requirements', value)} error={errors.evidence_requirements} />
                <TextList label="Outstanding documents" value={data.outstanding_documents} onChange={(value) => setData('outstanding_documents', value)} error={errors.outstanding_documents} />
                <TextList label="Fleet" value={data.fleet_scope} onChange={(value) => setData('fleet_scope', value)} error={errors.fleet_scope} />
                <TextList label="Personnel" value={data.personnel_scope} onChange={(value) => setData('personnel_scope', value)} error={errors.personnel_scope} />
                <TextList label="OpsSpec" value={data.ops_spec_scope} onChange={(value) => setData('ops_spec_scope', value)} error={errors.ops_spec_scope} />
                <TextList label="Fees" value={data.fees} onChange={(value) => setData('fees', value)} error={errors.fees} />
                <TextList label="Authority correspondence" value={data.authority_correspondence} onChange={(value) => setData('authority_correspondence', value)} error={errors.authority_correspondence} />
                <Field label="Outcome" error={errors.outcome}><textarea value={data.outcome} onChange={(event) => setData('outcome', event.target.value)} className="min-h-28 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2" /></Field>
            </section>

            <div className="flex flex-wrap items-center gap-3">
                <Button disabled={processing}><Save />Save case</Button>
                <Button variant="outline" asChild><Link href={certificateCase ? `/operator-certificate-cases/${certificateCase.id}` : `/operators/${operatorId}`}>Cancel</Link></Button>
            </div>
        </form>
    );
}

function toLocalDateTime(value?: string | null) {
    return value ? value.slice(0, 16) : '';
}

function lines(value: string) {
    return value.split(/\r?\n/).map((line) => line.trim()).filter(Boolean);
}

function OptionSelect({ value, options, onChange }: { value: string; options: Record<string, string>; onChange: (value: string) => void }) {
    return <Select value={value} onValueChange={onChange}><SelectTrigger><SelectValue /></SelectTrigger><SelectContent>{Object.entries(options).map(([key, label]) => <SelectItem key={key} value={key}>{label}</SelectItem>)}</SelectContent></Select>;
}

function TextList({ label, value, onChange, error }: { label: string; value: string[]; onChange: (value: string[]) => void; error?: string }) {
    return <Field label={label} error={error}><textarea value={value.join('\n')} onChange={(event) => onChange(lines(event.target.value))} className="min-h-28 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2" /></Field>;
}

function Field({ label, error, children }: { label: string; error?: string; children: ReactNode }) {
    return <div><Label>{label}</Label><div className="mt-2">{children}</div><InputError message={error} className="mt-2" /></div>;
}