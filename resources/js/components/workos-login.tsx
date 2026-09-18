import { Button } from '@/components/ui/button';

export default function WorkosLogin({ signUp = false }: { signUp?: boolean }) {
    return (
        <div className="space-y-4">
            <Button asChild variant="default" className="w-full">
                <a href={route(signUp ? 'workos.register' : 'workos.login')}>{signUp ? 'Sign up with WorkOS' : 'Continue with WorkOS'}</a>
            </Button>
            {!signUp && <div className="text-muted-foreground text-center text-sm">or use your existing app password</div>}
        </div>
    );
}
