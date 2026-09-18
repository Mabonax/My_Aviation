import { Button } from '@/components/ui/button';
import { EmptyState } from '@/components/uas/empty-state';
import { PageHeader } from '@/components/uas/page-header';
import { StatusBadge } from '@/components/uas/status-badge';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { GraduationCap } from 'lucide-react';
import { TrainingCourseListItem } from './types';

export default function Index({ courses }: { courses: TrainingCourseListItem[] }) {
    const breadcrumbs: BreadcrumbItem[] = [{ title: 'Training', href: '/training-courses' }];
    return <AppLayout breadcrumbs={breadcrumbs}><Head title="Training" /><div className="flex flex-1 flex-col gap-6 p-4 sm:p-6"><PageHeader title="Training" description="FR-TRN-001 and FR-TRN-002 learning and compliance catalogue." actions={<Button asChild><Link href="/training-courses/create">New Course</Link></Button>} />{courses.length === 0 ? <EmptyState icon={GraduationCap} title="No courses" description="Create the first training course structure." action={<Link href="/training-courses/create">New Course</Link>} /> : <div className="overflow-hidden rounded-lg border"><table className="w-full text-sm"><thead className="bg-muted text-left"><tr><th className="p-3">Course</th><th className="p-3">Classification</th><th className="p-3">Status</th><th className="p-3">Structure</th></tr></thead><tbody>{courses.map((course) => <tr key={course.id} className="border-t"><td className="p-3"><Link href={`/training-courses/${course.id}`} className="font-medium hover:underline">{course.code} / {course.title}</Link></td><td className="p-3 capitalize">{course.classification.replaceAll('_', ' ')}</td><td className="p-3"><StatusBadge value={course.status} /></td><td className="p-3 text-muted-foreground">{course.modules_count} modules / {course.competencies_count} competencies / {course.records_count} records / {course.compliance_links_count} links</td></tr>)}</tbody></table></div>}</div></AppLayout>;
}
