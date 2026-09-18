import { PageHeader } from '@/components/uas/page-header';
import { StatusBadge } from '@/components/uas/status-badge';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { TrainingCourse } from './types';

export default function Show({ course }: { course: TrainingCourse }) {
    const breadcrumbs: BreadcrumbItem[] = [{ title: 'Training', href: '/training-courses' }, { title: course.code, href: `/training-courses/${course.id}` }];
    return <AppLayout breadcrumbs={breadcrumbs}><Head title={course.code} /><div className="flex flex-1 flex-col gap-6 p-4 sm:p-6"><PageHeader title={`${course.code} / ${course.title}`} description={course.summary || 'Training course structure'} />
        <div className="grid gap-4 xl:grid-cols-2">
            <Panel title="Classification"><div className="mb-3 flex gap-2"><StatusBadge value={course.status} /><StatusBadge value={course.classification.replaceAll('_', ' ')} /></div><Detail label="Authority approval reference" value={course.authority_approval_reference} /></Panel>
            <Panel title="Modules & Lessons">{course.modules.map((module) => <div key={module.id} className="border-t py-3 first:border-t-0 first:pt-0"><p className="font-medium">{module.sequence}. {module.title}</p><ul className="mt-2 space-y-1 text-sm text-muted-foreground">{module.lessons.map((lesson) => <li key={lesson.id}>{lesson.sequence}. {lesson.title} / {lesson.resources.length} resources</li>)}</ul></div>)}</Panel>
            <Panel title="Assessments"><List values={course.assessments.map((item) => `${item.title} / ${item.assessment_type}`)} /></Panel>
            <Panel title="Competencies"><List values={course.competencies.map((item) => item.title)} /></Panel>
            <Panel title="Competency Records"><List values={course.competency_records.map((item) => `${item.participant_name} / ${item.competency_title} / ${item.record_status}`)} /></Panel>
            <Panel title="Compliance Links">{course.compliance_links.length === 0 ? <p className="text-sm text-muted-foreground">Not captured</p> : course.compliance_links.map((link) => <div key={link.id} className="border-t py-3 first:border-t-0 first:pt-0"><div className="flex items-center justify-between gap-3"><p className="font-medium">{link.requirement_reference} / {link.title}</p><StatusBadge value={link.link_status} /></div><p className="mt-1 text-sm text-muted-foreground">{link.source_type.replaceAll('_', ' ')} / {link.responsible_role}</p><p className="mt-2 text-sm">{link.evidence_required}</p><p className="mt-2 text-xs text-muted-foreground">Competency: {link.competency_title || 'Not linked'} / Record: {link.competency_record || 'Not linked'}</p></div>)}</Panel>
            <Panel title="Regulatory Traceability"><Detail label="Source" value={course.regulatory_source} /><Detail label="Version" value={course.regulatory_source_version} /><Detail label="Effective date" value={course.regulatory_effective_date} /></Panel>
        </div></div></AppLayout>;
}

function Panel({ title, children }: { title: string; children: React.ReactNode }) { return <section className="rounded-lg border bg-card p-4 text-card-foreground shadow-xs"><h2 className="mb-4 text-base font-semibold">{title}</h2><div className="space-y-3">{children}</div></section>; }
function Detail({ label, value }: { label: string; value?: string | null }) { return <div><dt className="text-xs font-medium uppercase text-muted-foreground">{label}</dt><dd className="mt-1 text-sm">{value || 'Not captured'}</dd></div>; }
function List({ values }: { values: string[] }) { return values.length ? <ul className="space-y-2 text-sm">{values.map((value) => <li key={value} className="rounded-md border px-3 py-2">{value}</li>)}</ul> : <p className="text-sm text-muted-foreground">Not captured</p>; }
