import { type BreadcrumbItem, type SharedData } from '@/types';
import { Transition } from '@headlessui/react';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { FormEventHandler } from 'react';

import DeleteUser from '@/components/delete-user';
import HeadingSmall from '@/components/heading-small';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Profile settings',
        href: '/settings/profile',
    },
];

interface ProfileProps {
    mustVerifyEmail: boolean;
    status?: string;
    workosEnabled: boolean;
    workosConnected: boolean;
    workosError?: string;
}

export default function Profile({ mustVerifyEmail, status, workosEnabled, workosConnected, workosError }: ProfileProps) {
    const { auth } = usePage<SharedData>().props;

    const { data, setData, patch, errors, processing, recentlySuccessful } = useForm({
        name: auth.user.name,
        email: auth.user.email,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        patch(route('profile.update'));
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Profile settings" />

            <SettingsLayout>
                {workosError && (
                    <p role="alert" className="text-destructive text-sm">
                        {workosError}
                    </p>
                )}
                {status === 'WorkOS connected successfully.' && (
                    <p role="status" className="text-sm text-green-600">
                        {status}
                    </p>
                )}
                {(workosEnabled || workosConnected) && (
                    <div className="space-y-3">
                        <HeadingSmall
                            title="WorkOS login"
                            description={
                                workosConnected
                                    ? 'WorkOS is connected to your account.'
                                    : 'Connect WorkOS to sign in to this account. You will confirm your app password first.'
                            }
                        />
                        {!workosConnected && workosEnabled && (
                            <Button asChild variant="outline">
                                <a href={route('workos.link')}>Connect WorkOS</a>
                            </Button>
                        )}
                        {workosConnected && (
                            <p className="text-muted-foreground text-sm">
                                Your app roles and pilot records stay with this account. Local password settings apply to email and password login. If
                                you signed up with WorkOS, use Forgot password on the login page to set a local password.
                            </p>
                        )}
                    </div>
                )}
                <div className="space-y-6">
                    <HeadingSmall title="Profile information" description="Update your name and email address" />

                    <form onSubmit={submit} className="space-y-6">
                        <div className="grid gap-2">
                            <Label htmlFor="name">Name</Label>

                            <Input
                                id="name"
                                className="mt-1 block w-full"
                                value={data.name}
                                onChange={(e) => setData('name', e.target.value)}
                                required
                                autoComplete="name"
                                placeholder="Full name"
                            />

                            <InputError className="mt-2" message={errors.name} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="email">Email address</Label>

                            <Input
                                id="email"
                                type="email"
                                className="mt-1 block w-full"
                                value={data.email}
                                readOnly={workosConnected}
                                onChange={(e) => setData('email', e.target.value)}
                                required
                                autoComplete="username"
                                placeholder="Email address"
                            />

                            <InputError className="mt-2" message={errors.email} />
                            {workosConnected && (
                                <p className="text-muted-foreground text-sm">
                                    Contact your administrator to change the email on a connected account.
                                </p>
                            )}
                        </div>

                        {mustVerifyEmail && auth.user.email_verified_at === null && (
                            <div>
                                <p className="mt-2 text-sm text-neutral-800">
                                    Your email address is unverified.
                                    <Link
                                        href={route('verification.send')}
                                        method="post"
                                        as="button"
                                        className="rounded-md text-sm text-neutral-600 underline hover:text-neutral-900 focus:ring-2 focus:ring-offset-2 focus:outline-hidden"
                                    >
                                        Click here to re-send the verification email.
                                    </Link>
                                </p>

                                {status === 'verification-link-sent' && (
                                    <div className="mt-2 text-sm font-medium text-green-600">
                                        A new verification link has been sent to your email address.
                                    </div>
                                )}
                            </div>
                        )}

                        <div className="flex items-center gap-4">
                            <Button disabled={processing}>Save</Button>

                            <Transition
                                show={recentlySuccessful}
                                enter="transition ease-in-out"
                                enterFrom="opacity-0"
                                leave="transition ease-in-out"
                                leaveTo="opacity-0"
                            >
                                <p className="text-sm text-neutral-600">Saved</p>
                            </Transition>
                        </div>
                    </form>
                </div>

                <DeleteUser />
            </SettingsLayout>
        </AppLayout>
    );
}
