import { Button } from '@/components/ui/button';
import { EmptyState } from '@/components/uas/empty-state';
import { PageHeader } from '@/components/uas/page-header';
import { StatusBadge } from '@/components/uas/status-badge';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Map, Plus } from 'lucide-react';
import { GisProjectListItem } from './types';

export default function Index({ projects }: { projects: GisProjectListItem[] }) {
    const breadcrumbs: BreadcrumbItem[] = [{ title: 'GIS projects', href: '/gis-projects' }];

    return <AppLayout breadcrumbs={breadcrumbs}><Head title="GIS projects" /><div className="flex flex-1 flex-col gap-6 p-4 sm:p-6"><PageHeader title="GIS projects" description="Independent geospatial project register for mapping, analysis and reporting work." actions={<Button asChild><Link href="/gis-projects/create">New Project<Plus /></Link></Button>} />{projects.length === 0 ? <EmptyState icon={Map} title="No GIS projects" description="Create the first mapping project to start the Phase 5 workflow." action={<Link href="/gis-projects/create">New Project</Link>} /> : <div className="overflow-hidden rounded-lg border"><table className="w-full text-sm"><thead className="bg-muted text-left"><tr><th className="p-3">Project</th><th className="p-3">Area</th><th className="p-3">Workflow</th><th className="p-3">Owner</th></tr></thead><tbody>{projects.map((project) => <tr key={project.id} className="border-t"><td className="p-3"><Link href={`/gis-projects/${project.id}`} className="font-medium hover:underline">{project.project_code} / {project.name}</Link><p className="mt-1 text-xs text-muted-foreground">{project.project_type.replaceAll('_', ' ')} / {project.client_or_stakeholder || 'Internal'}</p></td><td className="p-3 text-muted-foreground">{project.area_name}</td><td className="p-3"><StatusBadge value={project.lifecycle_state} /></td><td className="p-3 text-muted-foreground">{project.responsible_role}</td></tr>)}</tbody></table></div>}</div></AppLayout>;
}
