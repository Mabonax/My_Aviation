import PublicSite, { type PublicPageKey } from '@/components/public/public-site';

export default function PublicPage({ page }: { page: PublicPageKey }) {
    return <PublicSite page={page} />;
}
