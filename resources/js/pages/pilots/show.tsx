import { Button } from '@/components/ui/button';
import { PageHeader } from '@/components/uas/page-header';
import { StatusBadge } from '@/components/uas/status-badge';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Edit } from 'lucide-react';
import { type ReactNode } from 'react';
import { PilotProfile } from './types';

export default function Show({ pilot }: { pilot: PilotProfile }) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Pilots', href: '/pilots' },
        { title: pilot.display_name, href: `/pilots/${pilot.id}` },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={pilot.display_name} />
            <div className="flex flex-1 flex-col gap-6 p-4 sm:p-6">
                <PageHeader
                    title={pilot.display_name}
                    description={pilot.sacaa_certificate_number || 'SACAA certificate not captured'}
                    actions={
                        <Button asChild>
                            <Link href={`/pilots/${pilot.id}/edit`}>
                                <Edit />
                                Edit profile
                            </Link>
                        </Button>
                    }
                />

                <div className="grid gap-6 xl:grid-cols-[2fr_1fr]">
                    <section className="space-y-6">
                        <Panel title="Profile">
                            <Detail label="Preferred name" value={pilot.preferred_name} />
                            <Detail label="Employee number" value={pilot.employee_number} />
                            <Detail label="Email" value={pilot.email} />
                            <Detail label="Phone" value={pilot.phone} />
                            <Detail label="Nationality" value={pilot.nationality} />
                            <Detail label="Date of birth" value={pilot.date_of_birth} />
                            <div>
                                <dt className="text-xs font-medium uppercase tracking-normal text-muted-foreground">Status</dt>
                                <dd className="mt-1"><StatusBadge value={pilot.profile_status} /></dd>
                            </div>
                        </Panel>

                        <Panel title="Remote Pilot Record">
                            <Detail label="RPC category" value={pilot.rpc_category.replace('_', ' ')} />
                            <Detail label="Ratings" value={pilot.ratings.length ? pilot.ratings.join(', ') : null} />
                            <Detail label="Medical status" value={pilot.medical_status} />
                            <Detail label="Radiotelephony" value={pilot.radiotelephony_qualification} />
                            <Detail label="Language proficiency" value={pilot.language_proficiency} />
                        </Panel>

                        <Panel title="Notes">
                            <p className="whitespace-pre-wrap text-sm text-muted-foreground">{pilot.notes || 'No notes captured.'}</p>
                        </Panel>
                    </section>

                    <aside className="space-y-6">
                        <Panel title="Traceability">
                            <Detail label="Requirement" value="FR-PIL-001" />
                            <Detail label="Source" value={pilot.regulatory_source} />
                            <Detail label="Version" value={pilot.regulatory_source_version} />
                            <Detail label="Effective date" value={pilot.regulatory_effective_date} />
                            <Detail label="Applicability" value={pilot.regulatory_applicability} />
                            <Detail label="Responsible role" value={pilot.responsible_role} />
                        </Panel>
                    </aside>
                </div>
            </div>
        </AppLayout>
    );
}

function Panel({ title, children }: { title: string; children: ReactNode }) {
    return (
        <section className="rounded-lg border bg-card p-4 text-card-foreground shadow-xs">
            <h2 className="mb-4 text-sm font-semibold uppercase tracking-normal text-muted-foreground">{title}</h2>
            <div className="grid gap-4 sm:grid-cols-2">{children}</div>
        </section>
    );
}

function Detail({ label, value }: { label: string; value?: string | null }) {
    return (
        <div>
            <dt className="text-xs font-medium uppercase tracking-normal text-muted-foreground">{label}</dt>
            <dd className="mt-1 break-words text-sm">{value || 'Not captured'}</dd>
        </div>
    );
}
