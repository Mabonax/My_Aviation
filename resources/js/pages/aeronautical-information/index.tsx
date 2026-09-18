import { PageHeader } from '@/components/uas/page-header';
import { StatusBadge } from '@/components/uas/status-badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import { Providers, Section } from './components';
import type { InformationItem, SourceHealth } from './types';

interface Props {
    items: { data: InformationItem[]; total: number; current_page: number; last_page: number };
    filters: Record<string, string>;
    types: string[];
    providers: SourceHealth[];
    canImport: boolean;
}
export default function Index({ items, filters, types, providers, canImport }: Props) {
    const [search, setSearch] = useState(filters.search ?? '');
    const upload = useForm<{ provider: string; file: File | null }>({ provider: 'manual', file: null });
    function filter(key: string, value: string) {
        router.get('/aeronautical-information', { ...filters, [key]: value, page: 1 }, { preserveState: true });
    }
    return (
        <AppLayout breadcrumbs={[{ title: 'Aeronautical Information', href: '/aeronautical-information' }]}>
            <Head title="Aeronautical Information" />
            <div className="space-y-6 p-4 sm:p-6">
                <PageHeader title="Aeronautical Information" description="Source records and YAW interpretations for operational briefing." />
                <Providers providers={providers} />
                <Section title="Information register">
                    <form
                        className="flex flex-wrap gap-3"
                        onSubmit={(e) => {
                            e.preventDefault();
                            filter('search', search);
                        }}
                    >
                        <Input
                            className="w-64"
                            aria-label="Search information"
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder="Identifier, title or summary"
                        />
                        <Button>Search</Button>
                        {[
                            ['type', ['', ...types]],
                            ['provider', ['', ...providers.map((p) => p.provider)]],
                            ['validity', ['', 'active', 'expired', 'future']],
                            ['status', ['current', 'active', 'cancelled', 'superseded', 'all']],
                        ].map(([key, options]) => (
                            <label key={key as string} className="text-sm">
                                {key as string}
                                <select
                                    className="ml-2 rounded-md border p-2"
                                    value={filters[key as string] ?? (key === 'status' ? 'current' : '')}
                                    onChange={(e) => filter(key as string, e.target.value)}
                                >
                                    {(options as string[]).map((option) => (
                                        <option key={option} value={option}>
                                            {option || 'All'}
                                        </option>
                                    ))}
                                </select>
                            </label>
                        ))}
                    </form>
                    <p className="text-muted-foreground text-sm">{items.total} records. Unknown validity remains visible for review.</p>
                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-sm">
                            <thead>
                                <tr className="border-b">
                                    {['Information', 'Source', 'Validity', 'State'].map((h) => (
                                        <th key={h} className="p-3">
                                            {h}
                                        </th>
                                    ))}
                                </tr>
                            </thead>
                            <tbody>
                                {items.data.map((item) => (
                                    <tr key={item.id} className="border-b">
                                        <td className="p-3">
                                            <Link className="text-primary font-medium underline" href={`/aeronautical-information/${item.id}`}>
                                                {item.source_identifier} · {item.title}
                                            </Link>
                                            <p>{item.type}</p>
                                        </td>
                                        <td className="p-3">
                                            {item.provider}
                                            <p className="text-muted-foreground text-xs">
                                                {item.source_classification} · {item.usable_for_release ? 'Operational' : 'Reference only'}
                                            </p>
                                        </td>
                                        <td className="p-3">
                                            {item.effective_from ?? 'Unknown start'}
                                            <p className="text-xs">{item.permanent ? 'Permanent' : (item.effective_until ?? 'Unknown end')}</p>
                                        </td>
                                        <td className="p-3">
                                            <StatusBadge value={item.status} />
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                    {items.data.length === 0 && (
                        <p className="text-muted-foreground py-6 text-center">No matching records. Source availability must be checked separately.</p>
                    )}
                    <div className="flex items-center gap-3">
                        <Button
                            variant="outline"
                            disabled={items.current_page <= 1}
                            onClick={() => router.get('/aeronautical-information', { ...filters, page: items.current_page - 1 })}
                        >
                            Previous
                        </Button>
                        <span className="text-sm">
                            {items.current_page} / {items.last_page}
                        </span>
                        <Button
                            variant="outline"
                            disabled={items.current_page >= items.last_page}
                            onClick={() => router.get('/aeronautical-information', { ...filters, page: items.current_page + 1 })}
                        >
                            Next
                        </Button>
                    </div>
                </Section>
                {canImport && (
                    <Section title="Import reference metadata">
                        <form
                            className="space-y-3"
                            onSubmit={(e) => {
                                e.preventDefault();
                                upload.post('/aeronautical-information/import');
                            }}
                        >
                            <p className="text-muted-foreground text-sm">
                                Upload a local JSON dataset. Manual, SACAA publication references and fixtures cannot establish operational clearance.
                            </p>
                            <label className="text-sm">
                                Provider{' '}
                                <select
                                    className="rounded border p-2"
                                    value={upload.data.provider}
                                    onChange={(e) => upload.setData('provider', e.target.value)}
                                >
                                    {['manual', 'sacaa_publications', 'fixture'].map((p) => (
                                        <option key={p}>{p}</option>
                                    ))}
                                </select>
                            </label>
                            <Input
                                aria-label="Reference JSON file"
                                type="file"
                                accept=".json,application/json"
                                onChange={(e) => upload.setData('file', e.target.files?.[0] ?? null)}
                            />
                            {Object.values(upload.errors).map((e, i) => (
                                <p key={i} role="alert" className="text-destructive text-sm">
                                    {e}
                                </p>
                            ))}
                            <Button disabled={upload.processing || !upload.data.file}>Import references</Button>
                        </form>
                    </Section>
                )}
            </div>
        </AppLayout>
    );
}
