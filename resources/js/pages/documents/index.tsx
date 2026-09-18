import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { PageHeader } from '@/components/uas/page-header';
import { StatusBadge } from '@/components/uas/status-badge';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Evidence Documents', href: '/evidence-documents' }];

interface EvidenceDocument {
    id: number;
    document_uid: string;
    title: string;
    category: string;
    status: string;
    stored_status: string;
    original_filename: string;
    mime_type: string | null;
    size_bytes: number;
    checksum_sha256: string | null;
    version: number;
    access_level: string;
    expires_at: string | null;
    operator: { id: number; legal_entity: string } | null;
    links: Array<{
        id: number;
        evidenceable_type: string | null;
        evidenceable_label: string | null;
        evidence_role: string;
        requirement_id: string | null;
    }>;
    created_at: string | null;
}

interface Options {
    operators: Array<{ id: number; label: string }>;
    categories: Record<string, string>;
    access_levels: Record<string, string>;
}

export default function Index({ documents, options }: { documents: EvidenceDocument[]; options: Options }) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Evidence Documents" />
            <div className="flex flex-1 flex-col gap-6 p-4 sm:p-6">
                <PageHeader title="Evidence Documents" description="Governed operator-scoped evidence, source metadata, expiry and retention records." />
                <div className="grid gap-4 xl:grid-cols-[minmax(280px,380px)_1fr]">
                    <UploadPanel options={options} />
                    <section className="rounded-lg border bg-card p-4 text-card-foreground shadow-xs">
                        <h2 className="mb-4 text-base font-semibold">Vault Register</h2>
                        <div className="space-y-3">
                            {documents.length === 0 ? (
                                <p className="text-sm text-muted-foreground">No governed evidence documents have been uploaded.</p>
                            ) : documents.map((document) => (
                                <div key={document.id} className="rounded-md border p-3">
                                    <div className="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                        <div>
                                            <div className="font-medium">{document.title}</div>
                                            <div className="mt-1 text-sm text-muted-foreground">{document.document_uid} / v{document.version} / {document.original_filename}</div>
                                        </div>
                                        <StatusBadge value={document.status.replaceAll('_', ' ')} />
                                    </div>
                                    <div className="mt-3 grid gap-2 text-sm sm:grid-cols-3">
                                        <Detail label="Operator" value={document.operator?.legal_entity ?? null} />
                                        <Detail label="Category" value={document.category.replaceAll('_', ' ')} />
                                        <Detail label="Access" value={document.access_level.replaceAll('_', ' ')} />
                                        <Detail label="Size" value={`${Math.ceil(document.size_bytes / 1024)} KB`} />
                                        <Detail label="Expires" value={document.expires_at} />
                                        <Detail label="Links" value={document.links.length.toString()} />
                                    </div>
                                    {document.links.length > 0 && (
                                        <div className="mt-3 space-y-1 text-sm text-muted-foreground">
                                            {document.links.map((link) => (
                                                <div key={link.id}>{link.evidence_role.replaceAll('_', ' ')}: {link.evidenceable_label ?? link.evidenceable_type ?? 'target'}{link.requirement_id ? ` / ${link.requirement_id}` : ''}</div>
                                            ))}
                                        </div>
                                    )}
                                </div>
                            ))}
                        </div>
                    </section>
                </div>
            </div>
        </AppLayout>
    );
}

function UploadPanel({ options }: { options: Options }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        file: null as File | null,
        title: '',
        category: 'other',
        uas_operator_id: '',
        access_level: 'operator',
        evidence_role: 'supporting',
        requirement_id: '',
        effective_date: '',
        expires_at: '',
        retention_ends_at: '',
    });

    function submit(event: FormEvent) {
        event.preventDefault();
        post('/evidence-documents', {
            forceFormData: true,
            onSuccess: () => reset('file', 'title', 'requirement_id', 'effective_date', 'expires_at', 'retention_ends_at'),
        });
    }

    return (
        <section className="rounded-lg border bg-card p-4 text-card-foreground shadow-xs">
            <h2 className="mb-4 text-base font-semibold">Upload Evidence</h2>
            <form onSubmit={submit} className="space-y-4">
                <Field label="File" error={errors.file}>
                    <Input type="file" onChange={(event) => setData('file', event.target.files?.[0] ?? null)} />
                </Field>
                <Field label="Title" error={errors.title}>
                    <Input value={data.title} onChange={(event) => setData('title', event.target.value)} />
                </Field>
                <SelectField label="Operator" value={data.uas_operator_id} onChange={(value) => setData('uas_operator_id', value)} options={options.operators} placeholder="Select operator" error={errors.uas_operator_id} />
                <SelectMap label="Category" value={data.category} onChange={(value) => setData('category', value)} options={options.categories} error={errors.category} />
                <SelectMap label="Access" value={data.access_level} onChange={(value) => setData('access_level', value)} options={options.access_levels} error={errors.access_level} />
                <Field label="Requirement ID" error={errors.requirement_id}>
                    <Input value={data.requirement_id} onChange={(event) => setData('requirement_id', event.target.value)} />
                </Field>
                <div className="grid gap-3 sm:grid-cols-2">
                    <Field label="Effective date" error={errors.effective_date}>
                        <Input type="date" value={data.effective_date} onChange={(event) => setData('effective_date', event.target.value)} />
                    </Field>
                    <Field label="Expires at" error={errors.expires_at}>
                        <Input type="date" value={data.expires_at} onChange={(event) => setData('expires_at', event.target.value)} />
                    </Field>
                </div>
                <Field label="Retention ends" error={errors.retention_ends_at}>
                    <Input type="date" value={data.retention_ends_at} onChange={(event) => setData('retention_ends_at', event.target.value)} />
                </Field>
                <Button disabled={processing}>Upload</Button>
            </form>
        </section>
    );
}

function Field({ label, error, children }: { label: string; error?: string; children: React.ReactNode }) {
    return <div><Label>{label}</Label><div className="mt-2">{children}</div><InputError message={error} className="mt-2" /></div>;
}

function SelectField({ label, value, onChange, options, placeholder, error }: { label: string; value: string; onChange: (value: string) => void; options: Array<{ id: number; label: string }>; placeholder: string; error?: string }) {
    return <div className="space-y-2"><Label>{label}</Label><Select value={value} onValueChange={onChange}><SelectTrigger><SelectValue placeholder={placeholder} /></SelectTrigger><SelectContent>{options.map((option) => <SelectItem key={option.id} value={option.id.toString()}>{option.label}</SelectItem>)}</SelectContent></Select><InputError message={error} /></div>;
}

function SelectMap({ label, value, onChange, options, error }: { label: string; value: string; onChange: (value: string) => void; options: Record<string, string>; error?: string }) {
    return <div className="space-y-2"><Label>{label}</Label><Select value={value} onValueChange={onChange}><SelectTrigger><SelectValue /></SelectTrigger><SelectContent>{Object.entries(options).map(([key, label]) => <SelectItem key={key} value={key}>{label}</SelectItem>)}</SelectContent></Select><InputError message={error} /></div>;
}

function Detail({ label, value }: { label: string; value?: string | null }) {
    return <div><dt className="text-xs font-medium uppercase text-muted-foreground">{label}</dt><dd className="mt-1 break-words">{value || 'Not captured'}</dd></div>;
}
