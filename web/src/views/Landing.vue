<script setup>
import BrandLogo from '@/components/BrandLogo.vue';
import { brand } from '@/config/brand';
import { api } from '@/lib/api';
import { money, prettyDuration, prettyTime } from '@/lib/format';
import { session } from '@/lib/session';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

const canvas = ref(null);
const sceneFailed = ref(false);
const scrolled = ref(false);

const services = ref([]);
const servicesState = ref('loading'); // loading | ready | empty | error
const business = ref(null);

const loggedIn = computed(() => !!session.user);
const isOwner = computed(() => session.user?.role === 'admin');
const appLink = computed(() => (loggedIn.value ? '/app' : '/register'));
const hours = computed(() => (business.value ? `${prettyTime(business.value.open)} – ${prettyTime(business.value.close)}` : null));

const steps = [
    { title: 'Pick a service', text: 'See what is on offer, how long it takes and what it costs. No hidden charges.' },
    { title: 'Choose a free slot', text: 'Only times that are actually free are shown, so two people never get the same slot.' },
    { title: 'Get confirmed', text: 'The owner confirms your request and you can track or cancel it from your account.' }
];

const ownerPoints = [
    { icon: 'pi pi-inbox', title: 'Every request in one place', text: 'Confirm, reject or complete bookings with one tap.' },
    { icon: 'pi pi-briefcase', title: 'Your services, your rules', text: 'Set the price and duration, or switch a service off for a while.' },
    { icon: 'pi pi-chart-bar', title: 'Know your week', text: 'See how busy the next seven days are before they arrive.' }
];

let disposeScene = null;
let revealObserver = null;

const onScroll = () => {
    scrolled.value = window.scrollY > 12;
};

async function loadServices() {
    try {
        const [list, info] = await Promise.all([api.get('/services'), api.get('/business').catch(() => null)]);
        services.value = list.slice(0, 6);
        business.value = info;
        servicesState.value = list.length ? 'ready' : 'empty';
    } catch {
        servicesState.value = 'error';
    }
}

onMounted(async () => {
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
    loadServices();

    // Scroll karne par sections dheere se upar aate hain.
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    revealObserver = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-in');
                    revealObserver.unobserve(entry.target);
                }
            });
        },
        { threshold: 0.12 }
    );
    document.querySelectorAll('.landing .reveal').forEach((el) => revealObserver.observe(el));

    // Three.js bhaari hai, isliye sirf landing page pe aur alag se load hota hai (dynamic import).
    try {
        const { createHeroScene } = await import('@/components/landing/heroScene.js');
        if (canvas.value) disposeScene = createHeroScene(canvas.value, { reducedMotion });
    } catch {
        sceneFailed.value = true; // purana phone / WebGL band: sirf halka sa glow dikhega
    }
});

onBeforeUnmount(() => {
    window.removeEventListener('scroll', onScroll);
    revealObserver?.disconnect();
    disposeScene?.();
});
</script>

<template>
    <div class="landing">
        <a href="#main" class="skip">Skip to content</a>

        <header class="nav" :class="{ 'nav-solid': scrolled }">
            <router-link to="/" class="brand" :aria-label="brand.name + ' home'">
                <BrandLogo :size="30" />
                <span>{{ brand.name }}</span>
            </router-link>
            <nav class="nav-links" aria-label="Sections">
                <a href="#services">Services</a>
                <a href="#how">How it works</a>
                <a href="#owners">For owners</a>
            </nav>
            <div class="nav-actions">
                <template v-if="loggedIn">
                    <router-link to="/app" class="btn btn-primary btn-sm">{{ isOwner ? 'Open dashboard' : 'My bookings' }}</router-link>
                </template>
                <template v-else>
                    <router-link to="/login" class="btn btn-ghost btn-sm">Log in</router-link>
                    <router-link to="/register" class="btn btn-primary btn-sm">Sign up</router-link>
                </template>
            </div>
        </header>

        <main id="main">
            <section class="hero">
                <canvas ref="canvas" class="hero-canvas" :class="{ 'is-hidden': sceneFailed }" aria-hidden="true"></canvas>
                <div class="hero-glow" aria-hidden="true"></div>

                <div class="hero-copy">
                    <p class="eyebrow"><span class="pulse"></span>Online booking, made simple</p>
                    <h1>
                        Your time,<br />
                        booked in <em>seconds.</em>
                    </h1>
                    <p class="lead">{{ brand.description }}</p>
                    <div class="hero-actions">
                        <router-link :to="loggedIn && !isOwner ? '/app/services' : appLink" class="btn btn-primary">
                            {{ isOwner ? 'Open dashboard' : 'Book a service' }}
                            <i class="pi pi-arrow-right"></i>
                        </router-link>
                        <a href="#how" class="btn btn-ghost">See how it works</a>
                    </div>
                    <ul class="hero-points">
                        <li><i class="pi pi-check"></i>Live slot availability</li>
                        <li><i class="pi pi-check"></i>No double bookings</li>
                        <li v-if="hours"><i class="pi pi-clock"></i>Open {{ hours }}</li>
                    </ul>
                </div>

                <div class="legend" aria-hidden="true">
                    <span><i class="chip chip-free"></i>Free</span>
                    <span><i class="chip chip-booked"></i>Booked</span>
                    <span><i class="chip chip-picked"></i>Your pick</span>
                </div>
            </section>

            <section id="services" class="section">
                <div class="section-head reveal">
                    <p class="kicker">Services</p>
                    <h2>Pick what you need</h2>
                    <p class="sub">Clear prices and timings. Book any of these in under a minute.</p>
                </div>

                <div v-if="servicesState === 'loading'" class="cards" aria-busy="true">
                    <div v-for="n in 3" :key="n" class="card card-skeleton"></div>
                </div>

                <p v-else-if="servicesState === 'error'" class="notice">We could not load the services right now. Please refresh the page in a moment.</p>
                <p v-else-if="servicesState === 'empty'" class="notice">Services will show up here as soon as the owner adds them.</p>

                <div v-else class="cards">
                    <article v-for="(service, index) in services" :key="service.id" class="card reveal is-in" :style="{ '--delay': index * 60 + 'ms' }">
                        <div class="card-icon"><i :class="service.icon || 'pi pi-star'"></i></div>
                        <h3>{{ service.name }}</h3>
                        <p>{{ service.description }}</p>
                        <div class="card-foot">
                            <div>
                                <strong>{{ money(service.price) }}</strong>
                                <span>{{ prettyDuration(service.duration_minutes) }}</span>
                            </div>
                            <router-link to="/app/services" class="btn btn-outline btn-sm" :aria-label="`Book ${service.name}`">Book</router-link>
                        </div>
                    </article>
                </div>
            </section>

            <section id="how" class="section section-alt">
                <div class="section-head reveal">
                    <p class="kicker">How it works</p>
                    <h2>Three steps. That is all.</h2>
                </div>
                <ol class="steps">
                    <li v-for="(step, index) in steps" :key="step.title" class="step reveal" :style="{ '--delay': index * 90 + 'ms' }">
                        <span class="step-no">0{{ index + 1 }}</span>
                        <h3>{{ step.title }}</h3>
                        <p>{{ step.text }}</p>
                    </li>
                </ol>
            </section>

            <section id="owners" class="section owners">
                <div class="owners-copy reveal">
                    <p class="kicker">For the owner</p>
                    <h2>Run your day from one screen</h2>
                    <ul class="owner-list">
                        <li v-for="point in ownerPoints" :key="point.title">
                            <i :class="point.icon"></i>
                            <div>
                                <h3>{{ point.title }}</h3>
                                <p>{{ point.text }}</p>
                            </div>
                        </li>
                    </ul>
                    <router-link to="/login" class="btn btn-ghost">Owner login <i class="pi pi-arrow-right"></i></router-link>
                </div>

                <!-- Dashboard ki ek jhalak (sirf sajawat, asli data nahi) -->
                <div class="panel reveal" aria-hidden="true">
                    <div class="panel-bar"><span></span><span></span><span></span><b>Bookings · sample view</b></div>
                    <div class="panel-row">
                        <div><strong>Standard Service</strong><small>Today · 10:00 AM</small></div>
                        <em class="tag tag-pending">Pending</em>
                    </div>
                    <div class="panel-row">
                        <div><strong>Quick Consultation</strong><small>Today · 12:30 PM</small></div>
                        <em class="tag tag-confirmed">Confirmed</em>
                    </div>
                    <div class="panel-row">
                        <div><strong>Home Visit</strong><small>Tomorrow · 4:00 PM</small></div>
                        <em class="tag tag-done">Completed</em>
                    </div>
                    <div class="panel-actions"><span class="mini mini-yes">Confirm</span><span class="mini">Reject</span></div>
                </div>
            </section>

            <section class="cta reveal">
                <h2>Ready when you are.</h2>
                <p>Create a free account and book your first slot today.</p>
                <router-link :to="appLink" class="btn btn-primary">{{ loggedIn ? 'Open the app' : 'Create free account' }} <i class="pi pi-arrow-right"></i></router-link>
            </section>
        </main>

        <footer class="footer">
            <div class="brand">
                <BrandLogo :size="22" />
                <span>{{ brand.name }}</span>
            </div>
            <p>© {{ new Date().getFullYear() }} {{ brand.name }}. Built with Laravel, Vue and Three.js.</p>
        </footer>
    </div>
</template>

<style scoped>
.landing {
    --bg: #070b10;
    --bg-soft: #0c121a;
    --surface: #111a24;
    --line: rgba(255, 255, 255, 0.09);
    --ink: #edf2f7;
    --muted: #9aa8b7;
    --accent: #34d399;
    --accent-deep: #10b981;
    --amber: #fbbf24;
    --display: 'Sora Variable', 'Lato', system-ui, sans-serif;

    background: var(--bg);
    color: var(--ink);
    min-height: 100vh;
    overflow-x: clip;
    font-size: 1.05rem;
    line-height: 1.6;
}

.landing h1,
.landing h2,
.landing h3 {
    margin: 0;
    color: inherit;
    font-family: var(--display);
    line-height: 1.1;
    letter-spacing: -0.02em;
}

.landing p {
    margin: 0;
}

.skip {
    position: absolute;
    left: 1rem;
    top: -4rem;
    z-index: 100;
    background: var(--accent);
    color: #04130d;
    padding: 0.6rem 1rem;
    border-radius: 0.6rem;
    font-weight: 700;
}

.skip:focus {
    top: 1rem;
}

/* ---------- Buttons ---------- */
.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.6rem;
    padding: 0.9rem 1.5rem;
    border-radius: 999px;
    border: 1px solid transparent;
    font-weight: 700;
    font-size: 1rem;
    line-height: 1;
    white-space: nowrap;
    cursor: pointer;
    transition:
        transform 0.15s ease,
        background-color 0.15s ease,
        border-color 0.15s ease;
}

.btn:focus-visible {
    outline: 2px solid var(--accent);
    outline-offset: 3px;
}

.btn:active {
    transform: scale(0.98);
}

.btn .pi {
    font-size: 0.85rem;
}

.btn-sm {
    padding: 0.65rem 1.1rem;
    font-size: 0.95rem;
}

.btn-primary {
    background: var(--accent);
    color: #04130d;
}

.btn-primary:hover {
    background: #6ee7b7;
}

.btn-ghost {
    color: var(--ink);
    border-color: var(--line);
    background: rgba(255, 255, 255, 0.03);
}

.btn-ghost:hover {
    border-color: rgba(255, 255, 255, 0.28);
}

.btn-outline {
    color: var(--accent);
    border-color: rgba(52, 211, 153, 0.45);
}

.btn-outline:hover {
    background: rgba(52, 211, 153, 0.12);
}

/* ---------- Nav ---------- */
.nav {
    position: fixed;
    inset: 0 0 auto 0;
    z-index: 20;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: 1rem clamp(1rem, 4vw, 3rem);
    transition:
        background-color 0.25s ease,
        border-color 0.25s ease;
    border-bottom: 1px solid transparent;
}

.nav-solid {
    background: rgba(7, 11, 16, 0.82);
    backdrop-filter: blur(12px);
    border-bottom-color: var(--line);
}

.brand {
    display: inline-flex;
    align-items: center;
    gap: 0.55rem;
    color: var(--accent);
    font-family: var(--display);
    font-weight: 700;
    font-size: 1.3rem;
}

.brand span {
    color: var(--ink);
}

.nav-links {
    display: none;
    gap: 2rem;
}

.nav-links a {
    color: var(--muted);
    font-weight: 700;
    transition: color 0.15s ease;
}

.nav-links a:hover,
.nav-links a:focus-visible {
    color: var(--ink);
}

.nav-actions {
    display: flex;
    gap: 0.5rem;
}

/* ---------- Hero ---------- */
.hero {
    position: relative;
    min-height: 100svh;
    display: flex;
    align-items: flex-end;
    padding: 6rem clamp(1.25rem, 5vw, 4rem) 4.5rem;
    isolation: isolate;
}

.hero-canvas {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    z-index: -1;
}

.hero-canvas.is-hidden {
    display: none;
}

.hero-glow {
    position: absolute;
    inset: 0;
    z-index: -2;
    background:
        radial-gradient(60rem 34rem at 78% 30%, rgba(16, 185, 129, 0.2), transparent 62%), radial-gradient(40rem 26rem at 12% 88%, rgba(251, 191, 36, 0.08), transparent 60%),
        linear-gradient(180deg, transparent 55%, var(--bg) 100%);
}

.hero-copy {
    width: 100%;
    max-width: 38rem;
}

/* Phone pe text ke peeche halka parda, taaki 3D model ke upar bhi saaf padha jaye */
.hero-copy::before {
    content: '';
    position: absolute;
    inset: 38% 0 0 0;
    z-index: -1;
    background: linear-gradient(180deg, transparent, rgba(7, 11, 16, 0.86) 34%, var(--bg) 100%);
    pointer-events: none;
}

.eyebrow {
    display: inline-flex;
    align-items: center;
    gap: 0.6rem;
    padding: 0.4rem 0.9rem;
    border: 1px solid var(--line);
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.04);
    color: var(--muted);
    font-size: 0.9rem;
    font-weight: 700;
    margin-bottom: 1.4rem !important;
}

.pulse {
    width: 0.5rem;
    height: 0.5rem;
    border-radius: 50%;
    background: var(--accent);
    box-shadow: 0 0 0 0 rgba(52, 211, 153, 0.6);
    animation: pulse 2s infinite;
}

.hero h1 {
    font-size: clamp(2.6rem, 9.5vw, 5.4rem);
    font-weight: 700;
    line-height: 1.02;
    letter-spacing: -0.035em;
    text-wrap: balance;
}

.hero h1 em {
    font-style: normal;
    color: var(--accent);
}

.lead {
    margin-top: 1.4rem !important;
    color: var(--muted);
    font-size: clamp(1.05rem, 2.2vw, 1.25rem);
    max-width: 32rem;
}

.hero-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
    margin-top: 2rem;
}

.hero-points {
    list-style: none;
    padding: 0;
    margin: 2rem 0 0;
    display: flex;
    flex-wrap: wrap;
    gap: 0.6rem 1.5rem;
    color: var(--muted);
    font-size: 0.95rem;
}

.hero-points i {
    color: var(--accent);
    font-size: 0.8rem;
    margin-right: 0.5rem;
}

.legend {
    display: none;
    position: absolute;
    right: clamp(1.25rem, 5vw, 4rem);
    bottom: 2.5rem;
    gap: 1.25rem;
    color: var(--muted);
    font-size: 0.85rem;
}

.legend span {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
}

.chip {
    width: 0.7rem;
    height: 0.9rem;
    border-radius: 0.2rem;
}

.chip-free {
    background: #2a3a4b;
}

.chip-booked {
    background: var(--accent-deep);
}

.chip-picked {
    background: var(--amber);
}

/* ---------- Sections ---------- */
.section {
    scroll-margin-top: 3.5rem; /* fixed nav ke neeche heading na chhupe */
    padding: clamp(4rem, 9vw, 7rem) clamp(1.25rem, 5vw, 4rem);
    max-width: 78rem;
    margin: 0 auto;
}

.section-alt {
    max-width: none;
    background: var(--bg-soft);
    border-block: 1px solid var(--line);
}

.section-alt > * {
    max-width: 70rem;
    margin-inline: auto;
}

.section-head {
    margin-bottom: 2.75rem;
}

.kicker {
    color: var(--accent);
    font-weight: 700;
    font-size: 0.85rem;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    margin-bottom: 0.9rem !important;
}

.landing h2 {
    font-size: clamp(1.9rem, 5vw, 3rem);
    font-weight: 700;
    text-wrap: balance;
}

.sub {
    margin-top: 0.9rem !important;
    color: var(--muted);
    max-width: 34rem;
}

.notice {
    color: var(--muted);
    border: 1px dashed var(--line);
    border-radius: 1rem;
    padding: 1.5rem;
}

/* ---------- Service cards ---------- */
.cards {
    display: grid;
    gap: 1.25rem;
    grid-template-columns: repeat(auto-fill, minmax(min(100%, 19rem), 1fr));
}

.card {
    display: flex;
    flex-direction: column;
    background: linear-gradient(180deg, var(--surface), #0d151e);
    border: 1px solid var(--line);
    border-radius: 1.25rem;
    padding: 1.6rem;
    margin: 0;
    transition:
        border-color 0.2s ease,
        transform 0.2s ease;
}

.card:hover {
    border-color: rgba(52, 211, 153, 0.45);
    transform: translateY(-3px);
}

.card-skeleton {
    min-height: 13rem;
    background: linear-gradient(100deg, var(--surface) 30%, #172331 50%, var(--surface) 70%);
    background-size: 200% 100%;
    animation: shimmer 1.4s linear infinite;
}

.card-icon {
    width: 2.9rem;
    height: 2.9rem;
    display: grid;
    place-items: center;
    border-radius: 0.9rem;
    background: rgba(52, 211, 153, 0.12);
    color: var(--accent);
    margin-bottom: 1.2rem;
}

.card-icon i {
    font-size: 1.25rem;
}

.card h3 {
    font-size: 1.25rem;
    font-weight: 600;
}

.card p {
    color: var(--muted);
    margin-top: 0.6rem;
    flex: 1;
}

.card-foot {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    margin-top: 1.5rem;
    padding-top: 1.2rem;
    border-top: 1px solid var(--line);
}

.card-foot strong {
    display: block;
    font-family: var(--display);
    font-size: 1.35rem;
    line-height: 1.2;
}

.card-foot span {
    color: var(--muted);
    font-size: 0.9rem;
}

/* ---------- Steps ---------- */
.steps {
    list-style: none;
    padding: 0;
    margin: 0 auto;
    display: grid;
    gap: 1.25rem;
    grid-template-columns: repeat(auto-fit, minmax(min(100%, 17rem), 1fr));
}

.step {
    padding: 1.75rem;
    border-radius: 1.25rem;
    border: 1px solid var(--line);
    background: var(--bg);
}

.step-no {
    display: block;
    font-family: var(--display);
    font-size: 2.6rem;
    font-weight: 700;
    line-height: 1;
    color: transparent;
    -webkit-text-stroke: 1.5px var(--accent);
    margin-bottom: 1.4rem;
}

.step h3 {
    font-size: 1.25rem;
    font-weight: 600;
}

.step p {
    color: var(--muted);
    margin-top: 0.6rem;
}

/* ---------- Owners ---------- */
.owners {
    display: grid;
    gap: 3rem;
    align-items: center;
}

.owner-list {
    list-style: none;
    padding: 0;
    margin: 2.25rem 0;
    display: grid;
    gap: 1.5rem;
}

.owner-list li {
    display: flex;
    gap: 1rem;
}

.owner-list i {
    flex: none;
    width: 2.6rem;
    height: 2.6rem;
    display: grid;
    place-items: center;
    border-radius: 0.8rem;
    background: rgba(52, 211, 153, 0.12);
    color: var(--accent);
}

.owner-list h3 {
    font-size: 1.1rem;
    font-weight: 600;
    line-height: 1.3;
}

.owner-list p {
    color: var(--muted);
    margin-top: 0.2rem;
}

.panel {
    border: 1px solid var(--line);
    border-radius: 1.4rem;
    background: linear-gradient(180deg, var(--surface), #0c141d);
    padding: 1.25rem;
    box-shadow: 0 40px 80px -40px rgba(16, 185, 129, 0.35);
    transform: perspective(900px) rotateY(-6deg) rotateX(3deg);
}

.panel-bar {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    padding-bottom: 1rem;
    margin-bottom: 0.5rem;
    border-bottom: 1px solid var(--line);
}

.panel-bar span {
    width: 0.6rem;
    height: 0.6rem;
    border-radius: 50%;
    background: #2a3a4b;
}

.panel-bar b {
    margin-left: 0.6rem;
    color: var(--muted);
    font-size: 0.85rem;
    font-weight: 700;
}

.panel-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: 0.9rem 0.25rem;
    border-bottom: 1px solid var(--line);
}

.panel-row strong {
    display: block;
}

.panel-row small {
    color: var(--muted);
}

.tag {
    font-style: normal;
    font-size: 0.8rem;
    font-weight: 700;
    padding: 0.3rem 0.7rem;
    border-radius: 999px;
    white-space: nowrap;
}

.tag-pending {
    background: rgba(251, 191, 36, 0.14);
    color: var(--amber);
}

.tag-confirmed {
    background: rgba(96, 165, 250, 0.14);
    color: #93c5fd;
}

.tag-done {
    background: rgba(52, 211, 153, 0.14);
    color: var(--accent);
}

.panel-actions {
    display: flex;
    gap: 0.6rem;
    padding-top: 1rem;
}

.mini {
    padding: 0.45rem 1rem;
    border-radius: 999px;
    border: 1px solid var(--line);
    color: var(--muted);
    font-size: 0.85rem;
    font-weight: 700;
}

.mini-yes {
    background: var(--accent);
    border-color: var(--accent);
    color: #04130d;
}

/* ---------- CTA + footer ---------- */
.cta {
    margin: 0 clamp(1.25rem, 5vw, 4rem) clamp(4rem, 8vw, 6rem);
    padding: clamp(2.5rem, 7vw, 4.5rem) 1.5rem;
    max-width: 70rem;
    border-radius: 2rem;
    border: 1px solid rgba(52, 211, 153, 0.3);
    background: radial-gradient(50rem 20rem at 50% 0%, rgba(16, 185, 129, 0.22), transparent 70%), var(--bg-soft);
    text-align: center;
}

.cta p {
    color: var(--muted);
    margin: 1rem 0 2rem;
}

.footer {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: 2rem clamp(1.25rem, 5vw, 4rem);
    border-top: 1px solid var(--line);
    color: var(--muted);
    font-size: 0.9rem;
}

.footer .brand {
    font-size: 1.05rem;
}

/* ---------- Motion ---------- */
.reveal {
    opacity: 0;
    transform: translateY(18px);
    transition:
        opacity 0.6s ease var(--delay, 0ms),
        transform 0.6s ease var(--delay, 0ms);
}

.reveal.is-in {
    opacity: 1;
    transform: none;
}

.panel.reveal.is-in {
    transform: perspective(900px) rotateY(-6deg) rotateX(3deg);
}

@keyframes pulse {
    70% {
        box-shadow: 0 0 0 0.6rem rgba(52, 211, 153, 0);
    }
    100% {
        box-shadow: 0 0 0 0 rgba(52, 211, 153, 0);
    }
}

@keyframes shimmer {
    to {
        background-position: -200% 0;
    }
}

@media (min-width: 62rem) {
    .cta {
        margin-inline: auto;
    }
}

@media (min-width: 56rem) {
    .nav-links {
        display: flex;
    }

    .hero {
        align-items: center;
        padding-top: 7rem;
    }

    .hero-copy {
        max-width: min(38rem, 48vw);
    }

    .hero-copy::before {
        display: none;
    }

    .legend {
        display: flex;
    }

    .owners {
        grid-template-columns: 1fr 1fr;
        gap: 4rem;
    }
}

@media (prefers-reduced-motion: reduce) {
    .reveal {
        opacity: 1;
        transform: none;
        transition: none;
    }

    .pulse,
    .card-skeleton {
        animation: none;
    }

    .btn,
    .card {
        transition: none;
    }
}
</style>
