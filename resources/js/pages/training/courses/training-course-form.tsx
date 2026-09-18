import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useForm } from '@inertiajs/react';
import { Save } from 'lucide-react';
import { FormEvent, ReactNode } from 'react';
import { TrainingCourseOptions } from './types';

type CourseFormData = {
    code: string;
    title: string;
    classification: string;
    status: string;
    summary: string;
    authority_approval_reference: string;
    modules: string[];
    lessons: string[];
    resources: string[];
    assessments: string[];
    competencies: string[];
    competency_records: string[];
    compliance_links: string[];
    regulatory_requirement_ids: number[];
};

export default function TrainingCourseForm({ options }: { options: TrainingCourseOptions }) {
    const { data, setData, post, processing, errors, transform } = useForm<CourseFormData>({
        code: '', title: '', classification: 'operator_internal_competency', status: 'draft', summary: '', authority_approval_reference: '',
        modules: [], lessons: [], resources: [], assessments: [], competencies: [], competency_records: [], compliance_links: [], regulatory_requirement_ids: [],
    });

    function submit(event: FormEvent) {
        event.preventDefault();
        transform((payload) => ({ ...payload, summary: payload.summary || null, authority_approval_reference: payload.authority_approval_reference || null }));
        post('/training-courses');
    }

    return (
        <form onSubmit={submit} className="space-y-8">
            <section className="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                <Field label="Course code" error={errors.code}><Input value={data.code} onChange={(event) => setData('code', event.target.value)} placeholder="TRN-ERP-001" /></Field>
                <Field label="Title" error={errors.title}><Input value={data.title} onChange={(event) => setData('title', event.target.value)} /></Field>
                <Field label="Classification" error={errors.classification}><OptionSelect value={data.classification} options={options.classifications} onChange={(value) => setData('classification', value)} /></Field>
                <Field label="Status" error={errors.status}><OptionSelect value={data.status} options={options.statuses} onChange={(value) => setData('status', value)} /></Field>
                <Field label="Authority approval reference" error={errors.authority_approval_reference}><Input value={data.authority_approval_reference} onChange={(event) => setData('authority_approval_reference', event.target.value)} /></Field>
            </section>
            <section className="grid gap-5 md:grid-cols-2">
                <Field label="Summary" error={errors.summary}><textarea value={data.summary} onChange={(event) => setData('summary', event.target.value)} className="min-h-28 w-full rounded-md border border-input bg-background px-3 py-2 text-sm" /></Field>
                <TextList label="Modules" value={data.modules} onChange={(value) => setData('modules', value)} error={errors.modules} />
                <TextList label="Lessons" value={data.lessons} onChange={(value) => setData('lessons', value)} error={errors.lessons} />
                <TextList label="Resources" value={data.resources} onChange={(value) => setData('resources', value)} error={errors.resources} />
                <TextList label="Assessments" value={data.assessments} onChange={(value) => setData('assessments', value)} error={errors.assessments} />
                <TextList label="Competencies" value={data.competencies} onChange={(value) => setData('competencies', value)} error={errors.competencies} />
                <TextList label="Initial competency records" value={data.competency_records} onChange={(value) => setData('competency_records', value)} error={errors.competency_records} />
                <TextList label="Compliance links" value={data.compliance_links} onChange={(value) => setData('compliance_links', value)} error={errors.compliance_links} />
                <RegulatoryRequirementSelect value={data.regulatory_requirement_ids} options={options.regulatory_requirements} onChange={(value) => setData('regulatory_requirement_ids', value)} error={errors.regulatory_requirement_ids} />
            </section>
            <Button disabled={processing}><Save />Save course</Button>
        </form>
    );
}

function lines(value: string) { return value.split(/\r?\n/).map((line) => line.trim()).filter(Boolean); }
function OptionSelect({ value, options, onChange }: { value: string; options: Record<string, string>; onChange: (value: string) => void }) { return <Select value={value} onValueChange={onChange}><SelectTrigger><SelectValue /></SelectTrigger><SelectContent>{Object.entries(options).map(([key, label]) => <SelectItem key={key} value={key}>{label}</SelectItem>)}</SelectContent></Select>; }
function TextList({ label, value, onChange, error }: { label: string; value: string[]; onChange: (value: string[]) => void; error?: string }) { return <Field label={label} error={error}><textarea value={value.join('\n')} onChange={(event) => onChange(lines(event.target.value))} className="min-h-28 w-full rounded-md border border-input bg-background px-3 py-2 text-sm" /></Field>; }
function RegulatoryRequirementSelect({ value, options, onChange, error }: { value: number[]; options: TrainingCourseOptions['regulatory_requirements']; onChange: (value: number[]) => void; error?: string }) {
    function toggle(id: number, checked: boolean) {
        onChange(checked ? [...value, id] : value.filter((selected) => selected !== id));
    }

    return <Field label="Regulatory requirements" error={error}>{options.length === 0 ? <p className="text-sm text-muted-foreground">No active regulatory requirements available.</p> : <div className="max-h-72 space-y-2 overflow-y-auto rounded-md border p-3">{options.map((requirement) => <label key={requirement.id} className="flex gap-3 rounded-md border px-3 py-2 text-sm"><Checkbox checked={value.includes(requirement.id)} onCheckedChange={(checked) => toggle(requirement.id, checked === true)} /><span><span className="font-medium">{requirement.requirement_id} / {requirement.title}</span><span className="block text-xs text-muted-foreground">{requirement.regulation_part}{requirement.clause_reference ? ` / ${requirement.clause_reference}` : ''}</span></span></label>)}</div>}</Field>;
}
function Field({ label, error, children }: { label: string; error?: string; children: ReactNode }) { return <div><Label>{label}</Label><div className="mt-2">{children}</div><InputError message={error} className="mt-2" /></div>; }
