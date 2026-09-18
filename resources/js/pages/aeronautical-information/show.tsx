import { PageHeader } from '@/components/uas/page-header';
import AppLayout from '@/layouts/app-layout';
import { Head, Link } from '@inertiajs/react';
import { ItemContent, Section } from './components';
import type { InformationItem } from './types';

export default function Show({
    item,
    history,
}: {
    item: InformationItem;
    history: { id: number; source_identifier: string; source_revision: string; status: string; superseded_at: string | null }[];
}) {
    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Aeronautical Information', href: '/aeronautical-information' },
                { title: item.source_identifier, href: `/aeronautical-information/${item.id}` },
            ]}
        >
            <Head title={item.source_identifier} />
            <div className="mx-auto w-full max-w-5xl space-y-6 p-4 sm:p-6">
                <PageHeader title={item.title} description={`${item.source_identifier} · ${item.type}`} />
                <ItemContent item={item} />
                <Section title="Revision history">
                    <div className="flex flex-wrap gap-3">
                        {history.map((h) => (
                            <Link
                                key={h.id}
                                className="text-primary rounded border px-3 py-2 text-sm underline"
                                href={`/aeronautical-information/${h.id}`}
                            >
                                {h.source_identifier} · {h.source_revision} · {h.superseded_at ? 'Superseded' : h.status}
                            </Link>
                        ))}
                    </div>
                </Section>
            </div>
        </AppLayout>
    );
}
