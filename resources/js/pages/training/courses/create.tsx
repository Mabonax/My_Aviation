import { PageHeader } from '@/components/uas/page-header';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import TrainingCourseForm from './training-course-form';
import { TrainingCourseOptions } from './types';

export default function Create({ options }: { options: TrainingCourseOptions }) {
    const breadcrumbs: BreadcrumbItem[] = [{ title: 'Training', href: '/training-courses' }, { title: 'New Course', href: '/training-courses/create' }];
    return <AppLayout breadcrumbs={breadcrumbs}><Head title="New training course" /><div className="flex flex-1 flex-col gap-6 p-4 sm:p-6"><PageHeader title="New training course" description="Build the course, module, lesson, resource, assessment and competency structure." /><TrainingCourseForm options={options} /></div></AppLayout>;
}
