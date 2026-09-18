import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { PageHeader } from '@/components/uas/page-header';
import { StatusBadge } from '@/components/uas/status-badge';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';
import { OperatorCertificateCaseReport, OperatorManualRevisionReport, OperatorMembershipOptions, OperatorMembershipReport, OperatorProfile } from './types';

export default function Show({ operator, certificateCases, manualRevisions, membershipReport, membershipOptions }: { operator: OperatorProfile; certificateCases: OperatorCertificateCaseReport; manualRevisions: OperatorManualRevisionReport; membershipReport: OperatorMembershipReport; membershipOptions: OperatorMembershipOptions }) {
    const breadcrumbs: BreadcrumbItem[] = [{ title: 'Operators', href: '/operators' }, { title: operator.legal_entity, href: `/operators/${operator.id}` }];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={operator.legal_entity} />
            <div className="flex flex-1 flex-col gap-6 p-4 sm:p-6">
                <PageHeader title={operator.legal_entity} description={operator.trading_name || 'Operator governance profile'} actions={<Button asChild><Link href={`/operators/${operator.id}/edit`}>Edit</Link></Button>} />
                <div className="grid gap-4 xl:grid-cols-2">
                    <Panel title="Certificate"><div className="mb-3"><StatusBadge value={operator.status.replaceAll('_', ' ')} /></div><Detail label="UASOC / ROC" value={operator.uasoc_number} /><Detail label="Registration number" value={operator.registration_number} /><Detail label="Issue date" value={operator.certificate_issue_date} /><Detail label="Expiry date" value={operator.certificate_expiry_date} /></Panel>
                    <Panel title="Post Holders"><Detail label="Accountable manager" value={operator.accountable_manager} /><Detail label="Flight operations" value={operator.responsible_person_flight_operations} /><Detail label="Aircraft" value={operator.responsible_person_aircraft} /><Detail label="Safety manager" value={operator.safety_manager} /><Detail label="Security coordinator" value={operator.security_coordinator} /></Panel>
                    <Panel title="Operational Scope"><List label="Operating bases" values={operator.operating_bases} /><List label="Operations specifications" values={operator.operations_specifications} /><Detail label="Approved aircraft" value={operator.approved_aircraft.length.toString()} /><Detail label="Approved pilots" value={operator.approved_pilots.length.toString()} /></Panel>
                    <Panel title="Evidence" action={<Button size="sm" asChild><Link href="/evidence-documents">Vault</Link></Button>}>
                        <Detail label="Linked documents" value={operator.evidence.count.toString()} />
                        {operator.evidence.documents.length === 0 ? (
                            <p className="text-sm text-muted-foreground">No governed evidence documents have been linked.</p>
                        ) : operator.evidence.documents.map((document) => (
                            <div key={document.id} className="border-t py-3 first:border-t-0 first:pt-0">
                                <div className="flex items-center justify-between gap-3">
                                    <span className="font-medium">{document.title}</span>
                                    <StatusBadge value={document.status.replaceAll('_', ' ')} />
                                </div>
                                <p className="mt-1 text-sm text-muted-foreground">{document.category.replaceAll('_', ' ')} / {document.evidence_role.replaceAll('_', ' ')} / v{document.version}</p>
                            </div>
                        ))}
                    </Panel>
                    <Panel title="Members">
                        <div className="grid gap-2 text-sm sm:grid-cols-3">
                            <Detail label="Active members" value={membershipReport.summary.members_active.toString()} />
                            <Detail label="Pilots" value={membershipReport.summary.pilots_active.toString()} />
                            <Detail label="Aircraft" value={membershipReport.summary.aircraft_active.toString()} />
                        </div>
                        <MembershipForm operatorId={operator.id} options={membershipOptions} />
                        {membershipReport.memberships.length === 0 ? (
                            <p className="text-sm text-muted-foreground">No user memberships have been recorded.</p>
                        ) : membershipReport.memberships.map((membership) => (
                            <div key={membership.id} className="flex flex-col gap-3 border-t py-3 first:border-t-0 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <div className="font-medium">{membership.user?.name || 'Unlinked user'}</div>
                                    <div className="text-sm text-muted-foreground">{membership.membership_role.replaceAll('_', ' ')} / {membership.user?.email || 'No email'}</div>
                                </div>
                                <div className="flex flex-wrap items-center gap-2">
                                    <StatusBadge value={membership.status} />
                                    <MembershipStatusButton membershipId={membership.id} status="active" disabled={membership.status === 'active'} />
                                    <MembershipStatusButton membershipId={membership.id} status="suspended" disabled={membership.status === 'suspended'} />
                                    <MembershipStatusButton membershipId={membership.id} status="ended" disabled={membership.status === 'ended'} />
                                </div>
                            </div>
                        ))}
                    </Panel>
                    <Panel title="Assigned Pilots">
                        {membershipReport.pilots.length === 0 ? <p className="text-sm text-muted-foreground">No first-class pilot assignments yet.</p> : membershipReport.pilots.map((pilot) => <AssignmentRow key={pilot.id} label={pilot.label} role={pilot.assignment_role} status={pilot.status} />)}
                    </Panel>
                    <Panel title="Assigned Aircraft">
                        {membershipReport.aircraft.length === 0 ? <p className="text-sm text-muted-foreground">No first-class aircraft assignments yet.</p> : membershipReport.aircraft.map((aircraft) => <AssignmentRow key={aircraft.id} label={aircraft.label} role={aircraft.assignment_role} status={aircraft.status} />)}
                    </Panel>
                    <Panel title="Operator Missions">
                        <Detail label="Total missions" value={membershipReport.summary.missions_total.toString()} />
                        {membershipReport.missions.length === 0 ? <p className="text-sm text-muted-foreground">No missions have been linked to this operator.</p> : membershipReport.missions.map((mission) => (
                            <div key={mission.id} className="border-t py-3 first:border-t-0">
                                <Link href={`/missions/${mission.id}`} className="font-medium hover:underline">{mission.mission_number}</Link>
                                <p className="mt-1 text-sm text-muted-foreground">{mission.purpose} / {mission.lifecycle_state.replaceAll('_', ' ')}</p>
                            </div>
                        ))}
                    </Panel>
                    <Panel title="Certificate Cases" action={<Button size="sm" asChild><Link href={`/operators/${operator.id}/certificate-cases/create`}>New Case</Link></Button>}>
                        <div className="grid gap-2 text-sm sm:grid-cols-4">
                            <Detail label="Total" value={certificateCases.summary.total.toString()} />
                            <Detail label="Open" value={certificateCases.summary.open.toString()} />
                            <Detail label="Ready" value={certificateCases.summary.ready.toString()} />
                            <Detail label="Submitted" value={certificateCases.summary.submitted.toString()} />
                        </div>
                        {certificateCases.cases.length === 0 ? (
                            <p className="text-sm text-muted-foreground">No certificate lifecycle cases have been recorded.</p>
                        ) : certificateCases.cases.map((item) => (
                            <div key={item.id} className="border-t py-3 first:border-t-0 first:pt-0">
                                <div className="flex items-center justify-between gap-3">
                                    <Link href={`/operator-certificate-cases/${item.id}`} className="font-medium hover:underline">{item.case_number}</Link>
                                    <StatusBadge value={item.status.replaceAll('_', ' ')} />
                                </div>
                                <p className="mt-1 text-sm text-muted-foreground">{item.case_type.replaceAll('_', ' ')} / {item.submission_status.replaceAll('_', ' ')} / {item.outstanding_documents_count} outstanding</p>
                            </div>
                        ))}
                    </Panel>
                    <Panel title="Manual Revisions" action={<Button size="sm" asChild><Link href={`/operators/${operator.id}/manual-revisions/create`}>New Revision</Link></Button>}>
                        <div className="grid gap-2 text-sm sm:grid-cols-4">
                            <Detail label="Total" value={manualRevisions.summary.total.toString()} />
                            <Detail label="Approved" value={manualRevisions.summary.approved.toString()} />
                            <Detail label="Draft" value={manualRevisions.summary.draft.toString()} />
                            <Detail label="Superseded" value={manualRevisions.summary.superseded.toString()} />
                        </div>
                        {manualRevisions.revisions.length === 0 ? (
                            <p className="text-sm text-muted-foreground">No controlled Operations Manual revisions have been recorded.</p>
                        ) : manualRevisions.revisions.map((item) => (
                            <div key={item.id} className="border-t py-3 first:border-t-0 first:pt-0">
                                <div className="flex items-center justify-between gap-3">
                                    <Link href={`/operations-manual-revisions/${item.id}`} className="font-medium hover:underline">{item.manual_name} {item.revision_code}</Link>
                                    <StatusBadge value={item.approval_status.replaceAll('_', ' ')} />
                                </div>
                                <p className="mt-1 text-sm text-muted-foreground">{item.sections_count} sections / effective {item.effective_date || 'not set'} / supersedes {item.superseded_revision || 'none'}</p>
                            </div>
                        ))}
                    </Panel>
                    <Panel title="Regulatory Traceability"><Detail label="Source" value={operator.regulatory_source} /><Detail label="Version" value={operator.regulatory_source_version} /><Detail label="Effective date" value={operator.regulatory_effective_date} /><Detail label="Applicability" value={operator.regulatory_applicability} /><Detail label="Responsible role" value={operator.responsible_role} /></Panel>
                </div>
            </div>
        </AppLayout>
    );
}

function MembershipForm({ operatorId, options }: { operatorId: number; options: OperatorMembershipOptions }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        user_id: '',
        membership_role: 'remote_pilot',
        status: 'active',
    });

    function submit(event: FormEvent) {
        event.preventDefault();
        post(`/operators/${operatorId}/memberships`, { onSuccess: () => reset('user_id') });
    }

    return (
        <form onSubmit={submit} className="grid gap-3 rounded-md border p-3 sm:grid-cols-4">
            <SelectField label="User" value={data.user_id} onChange={(value) => setData('user_id', value)} options={options.users} error={errors.user_id} />
            <SelectMap label="Role" value={data.membership_role} onChange={(value) => setData('membership_role', value)} options={options.roles} error={errors.membership_role} />
            <SelectMap label="Status" value={data.status} onChange={(value) => setData('status', value)} options={options.statuses} error={errors.status} />
            <div className="flex items-end"><Button className="w-full" disabled={processing}>Add member</Button></div>
        </form>
    );
}

function MembershipStatusButton({ membershipId, status, disabled }: { membershipId: number; status: string; disabled: boolean }) {
    const { put, processing } = useForm({ status });

    return (
        <Button size="sm" variant="outline" disabled={disabled || processing} onClick={() => put(`/operator-memberships/${membershipId}/status`)}>
            {status}
        </Button>
    );
}

function AssignmentRow({ label, role, status }: { label: string; role: string; status: string }) {
    return <div className="flex items-center justify-between gap-3 border-t py-3 first:border-t-0"><div><div className="font-medium">{label}</div><div className="text-sm text-muted-foreground">{role.replaceAll('_', ' ')}</div></div><StatusBadge value={status} /></div>;
}

function SelectField({ label, value, onChange, options, error }: { label: string; value: string; onChange: (value: string) => void; options: Array<{ id: number; label: string }>; error?: string }) {
    return <div className="space-y-2"><Label>{label}</Label><Select value={value} onValueChange={onChange}><SelectTrigger><SelectValue placeholder="Select user" /></SelectTrigger><SelectContent>{options.map((option) => <SelectItem key={option.id} value={option.id.toString()}>{option.label}</SelectItem>)}</SelectContent></Select>{error && <p className="text-sm text-destructive">{error}</p>}</div>;
}

function SelectMap({ label, value, onChange, options, error }: { label: string; value: string; onChange: (value: string) => void; options: Record<string, string>; error?: string }) {
    return <div className="space-y-2"><Label>{label}</Label><Select value={value} onValueChange={onChange}><SelectTrigger><SelectValue /></SelectTrigger><SelectContent>{Object.entries(options).map(([key, label]) => <SelectItem key={key} value={key}>{label}</SelectItem>)}</SelectContent></Select>{error && <p className="text-sm text-destructive">{error}</p>}</div>;
}

function Panel({ title, action, children }: { title: string; action?: React.ReactNode; children: React.ReactNode }) {
    return <section className="rounded-lg border bg-card p-4 text-card-foreground shadow-xs"><div className="mb-4 flex items-center justify-between gap-3"><h2 className="text-base font-semibold">{title}</h2>{action}</div><div className="space-y-3">{children}</div></section>;
}

function Detail({ label, value }: { label: string; value?: string | null }) {
    return <div><dt className="text-xs font-medium uppercase text-muted-foreground">{label}</dt><dd className="mt-1 text-sm">{value || 'Not captured'}</dd></div>;
}

function List({ label, values }: { label: string; values: string[] }) {
    return <div><dt className="text-xs font-medium uppercase text-muted-foreground">{label}</dt><dd className="mt-1 text-sm">{values.length ? values.join(', ') : 'Not captured'}</dd></div>;
}
