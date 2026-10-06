import { type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowRight, Check, ChevronDown, FileCheck, Globe2, MapPin, Menu, Plane, ShieldCheck, Smartphone, Users, X } from 'lucide-react';
import { useState } from 'react';
import '../../../css/public-site.css';

const pages = {
    solutions: {
        label: 'Solutions',
        title: 'Everything your operation needs.',
        intro: 'Bring mission planning, pilot readiness, aircraft records and compliance workflows together in one connected operating environment.',
        image: 'planning',
        eyebrow: 'THE YAW PLATFORM',
        features: ['Plan missions with your team', 'Keep aircraft and pilot records connected', 'Prepare evidence for reviews and renewals'],
        section: 'One platform. A clearer picture.',
    },
    pilots: {
        label: 'Pilots',
        title: 'Your next flight starts here.',
        intro: 'Keep your qualifications, competency records and flight history organised. Arrive at every mission with a clearer view of your readiness.',
        image: 'pilot',
        eyebrow: 'FOR REMOTE PILOTS',
        features: ['Manage qualifications and currency', 'Build your digital flight logbook', 'Review mission briefings and checklists'],
        section: 'Focus on the flight ahead.',
    },
    operators: {
        label: 'Operators',
        title: 'Your operation. Connected.',
        intro: 'Give your people, aircraft and missions a shared workspace. From preparation to post-flight records, keep your team working from the same picture.',
        image: 'operations',
        eyebrow: 'FOR UAS OPERATORS',
        features: ['Organise crew and aircraft assignments', 'Prepare and review missions', 'Keep operational evidence in one place'],
        section: 'Built around your team.',
    },
    compliance: {
        label: 'Compliance',
        title: 'Confidence comes from clarity.',
        intro: 'Connect requirements, responsible people and supporting evidence. Keep the records behind your operation visible and ready for review.',
        image: 'planning',
        eyebrow: 'COMPLIANCE & GOVERNANCE',
        features: ['Organise requirements and evidence', 'Track validity and renewal actions', 'Prepare supporting application records'],
        section: 'Make evidence part of the workflow.',
    },
    'how-it-works': {
        label: 'How it works',
        title: 'From setup to take-off.',
        intro: 'Start with your workspace, connect your pilots and aircraft, then prepare missions with the records and checks your operation needs.',
        image: 'operations',
        eyebrow: 'GETTING STARTED',
        features: ['Create your account and workspace', 'Add your people and aircraft', 'Plan, review and manage operations'],
        section: 'Four steps to a connected operation.',
    },
    about: {
        label: 'About',
        title: 'A safer, smarter tomorrow.',
        intro: 'YAW is a UAS operations platform from Various Media Technologies. Built around the people, processes and evidence that professional drone operations depend on.',
        image: 'training',
        eyebrow: 'ABOUT YAW',
        features: [
            'Designed for professional UAS workflows',
            'Built with South African operations in mind',
            'Connects pilots, operators and technical teams',
        ],
        section: 'People at the centre. Operations in focus.',
    },
} as const;
export type PublicPageKey = keyof typeof pages;
const services = [
    {
        title: 'Mission planning',
        text: 'From the first waypoint to the final review. Bring your mission, crew and records together.',
        image: 'planning',
        href: 'solutions',
    },
    {
        title: 'Pilot readiness',
        text: 'Keep qualifications, competency and flight records ready for the next mission.',
        image: 'pilot',
        href: 'pilots',
    },
    {
        title: 'Fleet readiness',
        text: 'Know your aircraft. Organise maintenance, defects and supporting records.',
        image: 'maintenance',
        href: 'operators',
    },
    {
        title: 'Compliance workflows',
        text: 'Connect requirements and evidence with the people responsible for them.',
        image: 'operations',
        href: 'compliance',
    },
    {
        title: 'Training & competency',
        text: 'Support learning, track progress and keep competency records connected.',
        image: 'training',
        href: 'pilots',
    },
    {
        title: 'Operator workspace',
        text: 'A shared view of your people, aircraft and operational responsibilities.',
        image: 'tower',
        href: 'operators',
    },
] as const;
const roles = [
    {
        label: 'Operators',
        title: 'Bring your whole operation together.',
        text: 'Manage your team, aircraft and mission preparation in a connected workspace. Keep responsibilities clear and the records behind every flight within reach.',
        image: 'operations',
        href: 'operators',
        bullets: ['Coordinate pilots and aircraft', 'Prepare and review missions', 'Keep operational records connected'],
    },
    {
        label: 'Pilots',
        title: 'Be ready for your next mission.',
        text: 'Give your flying career a home. Organise qualifications, maintain flight records and review the information you need before taking to the sky.',
        image: 'pilot',
        href: 'pilots',
        bullets: ['Manage qualifications and currency', 'Maintain your digital logbook', 'Review briefings and checklists'],
    },
    {
        label: 'Training organisations',
        title: 'Help the next generation take flight.',
        text: 'Bring learning and competency records into the operational picture. Support your learners with clear progress and organised supporting evidence.',
        image: 'training',
        href: 'solutions',
        bullets: ['Organise courses and learning records', 'Track learner progress', 'Connect training and competency'],
    },
    {
        label: 'Technical teams',
        title: 'Keep your fleet mission-ready.',
        text: 'Bring aircraft maintenance, defects and serviceability records together. Make the work behind every aircraft easier to follow.',
        image: 'maintenance',
        href: 'operators',
        bullets: ['Organise maintenance records', 'Track aircraft defects', 'Support serviceability reviews'],
    },
] as const;
const steps = [
    ['Create your workspace', 'Set up your account and organise your operation.'],
    ['Connect pilots and aircraft', 'Add your people, fleet and supporting records.'],
    ['Prepare your missions', 'Plan the flight, review readiness and complete your checks.'],
    ['Manage your operations', 'Keep flight records, evidence and follow-up actions connected.'],
];
const asset = (name: string) => `/images/yaw/${name}.jpg`;
const pageUrl = (key: string) => route(`public.${key}`);

export default function PublicSite({ page }: { page?: PublicPageKey }) {
    const { auth } = usePage<SharedData>().props;
    const [menuOpen, setMenuOpen] = useState(false);
    const [roleIndex, setRoleIndex] = useState(0);
    const detail = page ? pages[page] : null;
    const role = roles[roleIndex];
    const startUrl = route(auth.user ? 'dashboard' : 'register');
    const startLabel = auth.user ? 'Open workspace' : 'Get started';
    return (
        <div className="yaw-public">
            <Head title={detail ? `${detail.label} | YAW` : 'YAW | Plan. Fly. Comply.'}>
                <meta
                    name="description"
                    content={
                        detail?.intro ??
                        'YAW connects mission planning, pilot readiness, fleet records and compliance workflows for professional UAS operations.'
                    }
                />
                <meta property="og:title" content={detail?.title ?? 'YAW — Plan. Fly. Comply.'} />
                <meta property="og:description" content={detail?.intro ?? 'A connected operating environment for professional drone operations.'} />
            </Head>
            <a className="yaw-skip" href="#main">
                Skip to content
            </a>
            <header className="yaw-header">
                <div className="yaw-container yaw-header-inner">
                    <Link href={route('home')} aria-label="YAW home" className="yaw-logo">
                        <img src="/images/yaw/logo.svg" alt="YAW UAS Operations — Plan. Fly. Comply." />
                    </Link>
                    <nav className="yaw-desktop-nav" aria-label="Main navigation">
                        {Object.entries(pages).map(([key, value]) => (
                            <Link key={key} href={pageUrl(key)} aria-current={page === key ? 'page' : undefined}>
                                {value.label}
                            </Link>
                        ))}
                    </nav>
                    <div className="yaw-header-actions">
                        <Link className="yaw-login" href={route(auth.user ? 'dashboard' : 'login')}>
                            {auth.user ? 'Workspace' : 'Log in'}
                        </Link>
                        <Link className="yaw-button yaw-button-small" href={startUrl}>
                            {startLabel} <ArrowRight size={16} />
                        </Link>
                        <button
                            className="yaw-menu-button"
                            aria-label={menuOpen ? 'Close navigation' : 'Open navigation'}
                            aria-expanded={menuOpen}
                            aria-controls="yaw-mobile-nav"
                            onClick={() => setMenuOpen(!menuOpen)}
                        >
                            {menuOpen ? <X /> : <Menu />}
                        </button>
                    </div>
                </div>
                {menuOpen && (
                    <nav id="yaw-mobile-nav" className="yaw-mobile-nav" aria-label="Mobile navigation">
                        {Object.entries(pages).map(([key, value]) => (
                            <Link key={key} href={pageUrl(key)} onClick={() => setMenuOpen(false)} aria-current={page === key ? 'page' : undefined}>
                                {value.label}
                                <ArrowRight size={18} />
                            </Link>
                        ))}
                        <Link href={route(auth.user ? 'dashboard' : 'login')}>{auth.user ? 'Workspace' : 'Log in'}</Link>
                    </nav>
                )}
            </header>
            <main id="main">
                <section className={`yaw-hero ${detail ? 'yaw-detail-hero' : ''}`}>
                    <div className="yaw-container yaw-hero-inner">
                        <div className="yaw-hero-copy">
                            <p className="yaw-eyebrow">{detail?.eyebrow ?? 'UAS OPERATIONS, ALL CONNECTED'}</p>
                            <h1>
                                {detail?.title ?? (
                                    <>
                                        Plan. Fly.
                                        <br />
                                        <span>Comply.</span>
                                    </>
                                )}
                            </h1>
                            <p className="yaw-hero-description">
                                {detail?.intro ??
                                    'Your people. Your aircraft. Your next mission. One platform to bring it all together — from planning to operational confidence.'}
                            </p>
                            <div className="yaw-button-row">
                                <Link className="yaw-button" href={startUrl}>
                                    {startLabel}
                                    <ArrowRight size={19} />
                                </Link>
                                <a className="yaw-text-link" href={detail ? '#intro' : '#discover'}>
                                    Explore {detail ? detail.label.toLowerCase() : 'YAW'}
                                    <ChevronDown size={18} />
                                </a>
                            </div>
                            <div className="yaw-hero-note">
                                <ShieldCheck size={19} /> Built for professional drone operations
                            </div>
                        </div>
                        <div className="yaw-hero-photo">
                            <img
                                src={asset(detail?.image ?? 'pilot')}
                                alt={
                                    detail
                                        ? `${detail.label} in professional aviation operations`
                                        : 'Remote pilot with a drone controller overlooking Cape Town'
                                }
                                fetchPriority="high"
                                width="1254"
                                height="1254"
                            />
                            <div className="yaw-photo-label">
                                <span className="yaw-dot" /> Plan. Fly. Comply.
                            </div>
                        </div>
                    </div>
                </section>
                <div className="yaw-brand-strip">
                    <div className="yaw-container">
                        <span>Built around your operation</span>
                        <span>Pilots</span>
                        <span>Operators</span>
                        <span>Training organisations</span>
                        <span>Technical teams</span>
                    </div>
                </div>
                {detail ? (
                    <>
                        <section className="yaw-section yaw-container" id="intro">
                            <div className="yaw-split">
                                <img
                                    className="yaw-feature-image"
                                    src={asset(page === 'about' ? 'airport' : page === 'operators' ? 'maintenance' : 'planning')}
                                    alt="Connected aviation operations"
                                    loading="lazy"
                                    width="1254"
                                    height="1254"
                                />
                                <div>
                                    <p className="yaw-eyebrow">{detail.label}</p>
                                    <h2>{detail.section}</h2>
                                    <p className="yaw-body-copy">{detail.intro}</p>
                                    <ul className="yaw-check-list">
                                        {detail.features.map((feature) => (
                                            <li key={feature}>
                                                <Check size={19} />
                                                {feature}
                                            </li>
                                        ))}
                                    </ul>
                                    <Link className="yaw-button" href={startUrl}>
                                        {startLabel}
                                        <ArrowRight size={18} />
                                    </Link>
                                </div>
                            </div>
                        </section>
                        {page === 'how-it-works' ? <Steps /> : <Services />}
                        {page === 'compliance' && (
                            <section className="yaw-container yaw-authority">
                                <ShieldCheck size={28} />
                                <div>
                                    <h3>Supporting compliance. Keeping authority clear.</h3>
                                    <p>
                                        YAW helps organise workflows and evidence. It does not issue licences, certificates or regulatory approvals.
                                        SACAA remains the regulatory authority; flight decisions depend on the applicable requirements and verified
                                        information.
                                    </p>
                                </div>
                            </section>
                        )}
                    </>
                ) : (
                    <>
                        <Services />
                        <section className="yaw-section yaw-container">
                            <div className="yaw-split">
                                <img
                                    className="yaw-feature-image"
                                    src={asset('planning')}
                                    alt="Mission planner reviewing mapping information at her workstation"
                                    width="1254"
                                    height="1254"
                                    loading="lazy"
                                />
                                <div>
                                    <p className="yaw-eyebrow">ONE CONNECTED ECOSYSTEM</p>
                                    <h2>
                                        A clearer view.
                                        <br />A better way to operate.
                                    </h2>
                                    <p className="yaw-body-copy">
                                        Great operations start with connected people and information. YAW brings pilots, aircraft, missions and
                                        compliance records into one shared operating environment.
                                    </p>
                                    <Link className="yaw-button" href={pageUrl('how-it-works')}>
                                        See how YAW works
                                        <ArrowRight size={18} />
                                    </Link>
                                </div>
                            </div>
                        </section>
                        <section className="yaw-role-section">
                            <div className="yaw-container">
                                <div className="yaw-section-heading">
                                    <p className="yaw-eyebrow">BUILT FOR YOUR ROLE</p>
                                    <h2>Your team. Your way forward.</h2>
                                    <p>From the flight line to the workshop, find your place in YAW.</p>
                                </div>
                                <div className="yaw-role-tabs" role="group" aria-label="Choose your role">
                                    {roles.map((item, index) => (
                                        <button key={item.label} aria-pressed={index === roleIndex} onClick={() => setRoleIndex(index)}>
                                            {item.label}
                                        </button>
                                    ))}
                                </div>
                                <div className="yaw-split yaw-role-content">
                                    <div>
                                        <h3>{role.title}</h3>
                                        <p className="yaw-body-copy">{role.text}</p>
                                        <ul className="yaw-check-list">
                                            {role.bullets.map((bullet) => (
                                                <li key={bullet}>
                                                    <Check size={18} />
                                                    {bullet}
                                                </li>
                                            ))}
                                        </ul>
                                        <Link className="yaw-button" href={pageUrl(role.href)}>
                                            Explore solutions
                                            <ArrowRight size={18} />
                                        </Link>
                                    </div>
                                    <img
                                        className="yaw-feature-image"
                                        src={asset(role.image)}
                                        alt={`${role.label} at work`}
                                        width="1254"
                                        height="1254"
                                        loading="lazy"
                                    />
                                </div>
                            </div>
                        </section>
                        <section className="yaw-section yaw-container">
                            <div className="yaw-split">
                                <img
                                    className="yaw-feature-image"
                                    src={asset('maintenance')}
                                    alt="YAW technician inspecting a drone"
                                    width="1254"
                                    height="1254"
                                    loading="lazy"
                                />
                                <div>
                                    <p className="yaw-eyebrow">READY FOR WHAT COMES NEXT</p>
                                    <h2>
                                        Confidence starts
                                        <br />
                                        before take-off.
                                    </h2>
                                    <p className="yaw-body-copy">
                                        A mission is more than a flight plan. Keep the qualifications, aircraft records, checks and evidence behind it
                                        connected, so your team can prepare with clarity.
                                    </p>
                                    <Link className="yaw-text-link yaw-blue-link" href={pageUrl('compliance')}>
                                        Discover compliance workflows
                                        <ArrowRight size={20} />
                                    </Link>
                                </div>
                            </div>
                        </section>
                        <MobileApp startUrl={startUrl} />
                        <Steps />
                    </>
                )}
                <section className="yaw-cta yaw-container">
                    <div>
                        <p className="yaw-eyebrow">THE NEXT MISSION STARTS WITH YOU</p>
                        <h2>Bring your operations together.</h2>
                        <p>Build a clearer picture of your people, aircraft and missions with YAW.</p>
                    </div>
                    <Link className="yaw-button yaw-button-white" href={startUrl}>
                        {startLabel}
                        <ArrowRight size={20} />
                    </Link>
                </section>
                <section className="yaw-faq yaw-container">
                    <h2>A few things to know</h2>
                    {[
                        [
                            'Who is YAW for?',
                            'YAW is designed for remote pilots, UAS operators, training organisations, compliance personnel and technical teams.',
                        ],
                        [
                            'Does YAW issue aviation approvals?',
                            'No. YAW supports operational workflows and supporting records. SACAA remains the authority for licences, certificates and regulatory approvals.',
                        ],
                        [
                            'How do I get started?',
                            'Create an account using Get started, then follow the workspace setup flow. Already have an account? Log in to continue your operations.',
                        ],
                    ].map(([question, answer]) => (
                        <details key={question}>
                            <summary>
                                {question}
                                <ChevronDown size={20} />
                            </summary>
                            <p>{answer}</p>
                        </details>
                    ))}
                </section>
            </main>
            <footer className="yaw-footer">
                <div className="yaw-container yaw-footer-grid">
                    <div>
                        <Link href={route('home')} className="yaw-logo">
                            <img src="/images/yaw/logo.svg" alt="YAW" loading="lazy" />
                        </Link>
                        <p>Plan. Fly. Comply.</p>
                        <span className="yaw-location">
                            <Globe2 size={17} /> South Africa · English
                        </span>
                    </div>
                    <div>
                        <h3>Platform</h3>
                        {(['solutions', 'compliance', 'how-it-works'] as const).map((key) => (
                            <Link key={key} href={pageUrl(key)}>
                                {pages[key].label}
                            </Link>
                        ))}
                    </div>
                    <div>
                        <h3>For your team</h3>
                        <Link href={pageUrl('pilots')}>Pilots</Link>
                        <Link href={pageUrl('operators')}>Operators</Link>
                        <Link href={pageUrl('solutions')}>Training & technical teams</Link>
                    </div>
                    <div>
                        <h3>YAW</h3>
                        <Link href={pageUrl('about')}>About us</Link>
                        <Link href={startUrl}>{startLabel}</Link>
                        <Link href={route(auth.user ? 'dashboard' : 'login')}>{auth.user ? 'Workspace' : 'Log in'}</Link>
                    </div>
                </div>
                <div className="yaw-container yaw-footer-bottom">
                    <span>© {new Date().getFullYear()} Various Media Technologies. All rights reserved.</span>
                    <span>YAW UAS Operations Platform</span>
                </div>
            </footer>
        </div>
    );
}
function Services() {
    return (
        <section className="yaw-section yaw-container" id="discover">
            <div className="yaw-section-heading">
                <p className="yaw-eyebrow">OUR SOLUTIONS</p>
                <h2>Everything your operation needs.</h2>
                <p>Connected tools for the work before, during and after every flight.</p>
            </div>
            <div className="yaw-service-grid">
                {services.map((service) => (
                    <Link className="yaw-service-card" href={pageUrl(service.href)} key={service.title}>
                        <div className="yaw-card-photo">
                            <img src={asset(service.image)} alt="" loading="lazy" width="1254" height="1254" />
                        </div>
                        <div className="yaw-card-copy">
                            <h3>{service.title}</h3>
                            <p>{service.text}</p>
                            <span>
                                Learn more
                                <ArrowRight size={18} />
                            </span>
                        </div>
                    </Link>
                ))}
            </div>
        </section>
    );
}
function Steps() {
    return (
        <section className="yaw-section yaw-container">
            <div className="yaw-section-heading">
                <p className="yaw-eyebrow">HOW IT WORKS</p>
                <h2>From setup to operations.</h2>
                <p>Four simple steps to a more connected workflow.</p>
            </div>
            <div className="yaw-step-grid">
                {steps.map(([title, text], index) => (
                    <div className="yaw-step" key={title}>
                        <span>{String(index + 1).padStart(2, '0')}</span>
                        <h3>{title}</h3>
                        <p>{text}</p>
                    </div>
                ))}
            </div>
        </section>
    );
}

function MobileApp({ startUrl }: { startUrl: string }) {
    return (
        <section className="yaw-mobile-section" id="mobile-app" aria-labelledby="mobile-app-title">
            <div className="yaw-container yaw-mobile-grid">
                <div className="yaw-mobile-copy">
                    <p className="yaw-eyebrow">THE YAW MOBILE APP</p>
                    <h2 id="mobile-app-title">
                        Your operations.
                        <br />
                        Anytime, anywhere.
                    </h2>
                    <p className="yaw-body-copy">
                        Take your operating workspace into the field. Keep missions, aircraft records and compliance information within reach — from
                        the office to the flight line.
                    </p>
                    <Link className="yaw-button" href={startUrl}>
                        Get started with YAW
                        <ArrowRight size={18} />
                    </Link>
                    <div className="yaw-store-badges" aria-label="Mobile app stores">
                        <div className="yaw-store-badge" aria-label="Apple App Store — coming soon">
                            <svg viewBox="0 0 24 28" aria-hidden="true">
                                <path
                                    fill="currentColor"
                                    d="M17.2 0c.2 2-1.1 4-2.4 5.1-1.4 1.2-3.1 1.9-4.7 1.7-.2-1.9 1.1-3.9 2.3-5C13.8.6 15.8 0 17.2 0ZM22.7 20.3c-.6 1.4-.9 2-1.7 3.2-1.1 1.7-2.7 3.8-4.7 3.8-1.8 0-2.3-1.2-4.8-1.2-2.4 0-3 1.2-4.8 1.2-2 0-3.5-1.9-4.7-3.6C-1.3 18.9-.8 11.7 4.1 8.7c1.7-1 4-1.4 6.1-.5 1.1.5 1.8.5 2.8.1 2.5-1 5.9-.8 8 1.6-4.8 2.8-4 8.6 1.7 10.4Z"
                                />
                            </svg>
                            <span>
                                <small>Coming soon on the</small>
                                <strong>App Store</strong>
                            </span>
                        </div>
                        <div className="yaw-store-badge" aria-label="Google Play — coming soon">
                            <svg viewBox="0 0 28 30" aria-hidden="true">
                                <path fill="#39d5e2" d="M1 1 16 15 1 29Z" />
                                <path fill="#45d27c" d="m1 1 19 10-4 4Z" />
                                <path fill="#ffce45" d="m20 11 7 4-7 4-4-4Z" />
                                <path fill="#ff5d6c" d="m1 29 15-14 4 4Z" />
                            </svg>
                            <span>
                                <small>Coming soon on</small>
                                <strong>Google Play</strong>
                            </span>
                        </div>
                    </div>
                    <p className="yaw-mobile-availability">
                        <Smartphone size={17} /> Mobile app access through your YAW workspace.
                    </p>
                </div>
                <div className="yaw-phone-stage" aria-label="Illustrative YAW mobile interface">
                    <div className="yaw-phone-glow" />
                    <div className="yaw-phone">
                        <div className="yaw-phone-notch" />
                        <div className="yaw-phone-screen">
                            <div className="yaw-phone-status">
                                <span>9:41</span>
                                <span>••• ▰</span>
                            </div>
                            <img className="yaw-phone-logo" src="/images/yaw/logo.svg" alt="YAW" loading="lazy" />
                            <p className="yaw-phone-greeting">Ready for your next mission?</p>
                            <div className="yaw-phone-actions">
                                <span>
                                    <Plane size={19} />
                                    Missions
                                </span>
                                <span>
                                    <ShieldCheck size={19} />
                                    Compliance
                                </span>
                            </div>
                            <div className="yaw-phone-map">
                                <img src={asset('planning')} alt="" loading="lazy" />
                                <div>
                                    <MapPin size={18} />
                                    <span>Your mission workspace</span>
                                </div>
                            </div>
                            <p className="yaw-phone-heading">Everything in one place</p>
                            <div className="yaw-phone-row">
                                <Plane size={19} />
                                <span>
                                    Aircraft records<small>Know your fleet</small>
                                </span>
                                <ArrowRight size={14} />
                            </div>
                            <div className="yaw-phone-row">
                                <FileCheck size={19} />
                                <span>
                                    Mission briefings<small>Prepare with clarity</small>
                                </span>
                                <ArrowRight size={14} />
                            </div>
                            <div className="yaw-phone-row">
                                <Users size={19} />
                                <span>
                                    Your team<small>Keep people connected</small>
                                </span>
                                <ArrowRight size={14} />
                            </div>
                        </div>
                    </div>
                    <p className="yaw-phone-caption">Illustrative app preview</p>
                </div>
                <div className="yaw-mobile-features">
                    {[
                        { icon: MapPin, title: 'Keep missions in view', text: 'Review mission details and briefings on the go.' },
                        { icon: Plane, title: 'Know your aircraft', text: 'Access the aircraft records behind your operation.' },
                        { icon: ShieldCheck, title: 'Keep compliance close', text: 'Bring your readiness information into the field.' },
                        { icon: Smartphone, title: 'A connected workspace', text: 'Carry your operational information with you.' },
                    ].map(({ icon: Icon, title, text }) => (
                        <div className="yaw-mobile-feature" key={title}>
                            <span>
                                <Icon size={24} />
                            </span>
                            <div>
                                <h3>{title}</h3>
                                <p>{text}</p>
                            </div>
                        </div>
                    ))}
                </div>
            </div>
        </section>
    );
}
