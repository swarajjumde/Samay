import * as THREE from 'three';
import { RoundedBoxGeometry } from 'three/examples/jsm/geometries/RoundedBoxGeometry.js';

/*
 * Landing page ka 3D scene (Three.js).
 *
 * Idea: beech mein ek ghadi, aur uske charon taraf "time-slot cards" ka ghoomta hua ghera.
 * Hare cards = booked, peela card = jo slot abhi chuna ja raha hai.
 *
 * createHeroScene(canvas) scene shuru karta hai aur ek dispose() function lautata hai,
 * jisko page chhodte waqt bulana zaroori hai (warna GPU memory leak hoti hai).
 */
export function createHeroScene(canvas, { reducedMotion = false } = {}) {
    const renderer = new THREE.WebGLRenderer({ canvas, antialias: true, alpha: true, powerPreference: 'high-performance' });
    renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));
    renderer.toneMapping = THREE.ACESFilmicToneMapping;
    renderer.toneMappingExposure = 1.15;

    const scene = new THREE.Scene();
    scene.fog = new THREE.Fog(0x070b10, 9, 19);

    const camera = new THREE.PerspectiveCamera(36, 1, 0.1, 60);
    camera.position.set(0, 0.5, 10.5);

    // ---------- Lights ----------
    scene.add(new THREE.AmbientLight(0x9fb4c8, 0.55));

    const key = new THREE.DirectionalLight(0xffffff, 2.2);
    key.position.set(4, 6, 7);
    scene.add(key);

    const emeraldGlow = new THREE.PointLight(0x34d399, 30, 14, 2);
    emeraldGlow.position.set(0, 0.5, 1.5);
    const amberGlow = new THREE.PointLight(0xfbbf24, 14, 12, 2);
    amberGlow.position.set(-4, -2, 3);

    // rig = poora model. Pointer hilane par ye thoda jhukta hai (parallax).
    const rig = new THREE.Group();
    rig.add(emeraldGlow, amberGlow);
    scene.add(rig);

    const tilt = new THREE.Group();
    tilt.rotation.set(0.36, 0, -0.12);
    rig.add(tilt);

    const disposables = [];
    const track = (item) => {
        disposables.push(item);
        return item;
    };

    // ---------- Slot cards ka ghera ----------
    const carousel = new THREE.Group();
    tilt.add(carousel);

    const CARD = { count: 14, radius: 3.15, width: 1.05, height: 1.45, depth: 0.1 };
    const cardGeometry = track(new RoundedBoxGeometry(CARD.width, CARD.height, CARD.depth, 4, 0.09));
    const barGeometry = track(new THREE.BoxGeometry(1, 0.075, 0.02));
    const dotGeometry = track(new THREE.CircleGeometry(0.11, 24));

    const materials = {
        free: track(new THREE.MeshStandardMaterial({ color: 0x22313f, roughness: 0.5, metalness: 0.25 })),
        booked: track(new THREE.MeshStandardMaterial({ color: 0x10b981, emissive: 0x047857, emissiveIntensity: 0.6, roughness: 0.35, metalness: 0.2 })),
        picked: track(new THREE.MeshStandardMaterial({ color: 0xfbbf24, emissive: 0xd97706, emissiveIntensity: 0.7, roughness: 0.3, metalness: 0.2 })),
        freeInk: track(new THREE.MeshBasicMaterial({ color: 0x4f6478 })),
        bookedInk: track(new THREE.MeshBasicMaterial({ color: 0x053d2e })),
        pickedInk: track(new THREE.MeshBasicMaterial({ color: 0x6b3a05 }))
    };

    const BOOKED = new Set([1, 4, 5, 9, 12]);
    const PICKED = 7;
    const cards = [];

    for (let i = 0; i < CARD.count; i++) {
        const kind = i === PICKED ? 'picked' : BOOKED.has(i) ? 'booked' : 'free';
        const angle = (i / CARD.count) * Math.PI * 2;

        const card = new THREE.Group();
        card.add(new THREE.Mesh(cardGeometry, materials[kind]));

        // Card ke upar chhoti "likhawat": ek gol nishaan aur do lakeerein.
        const ink = materials[`${kind}Ink`];
        const face = CARD.depth / 2 + 0.011;

        const dot = new THREE.Mesh(dotGeometry, ink);
        dot.position.set(-CARD.width / 2 + 0.26, CARD.height / 2 - 0.28, face);
        card.add(dot);

        [0.62, 0.42].forEach((length, row) => {
            const bar = new THREE.Mesh(barGeometry, ink);
            bar.scale.x = length;
            bar.position.set(-CARD.width / 2 + 0.16 + length / 2, 0.1 - row * 0.2, face);
            card.add(bar);
        });

        card.position.set(Math.sin(angle) * CARD.radius, 0, Math.cos(angle) * CARD.radius);
        card.rotation.y = angle;
        card.userData = { phase: i * 0.9, picked: kind === 'picked' };
        carousel.add(card);
        cards.push(card);
    }

    // ---------- Beech ki ghadi ----------
    const clock = new THREE.Group();
    rig.add(clock);

    const clockMaterial = track(new THREE.MeshStandardMaterial({ color: 0x34d399, emissive: 0x059669, emissiveIntensity: 0.55, roughness: 0.35 }));
    const softMaterial = track(new THREE.MeshStandardMaterial({ color: 0xdfe8f0, roughness: 0.4, metalness: 0.1 }));

    const ringGeometry = track(new THREE.TorusGeometry(1.15, 0.05, 20, 120));
    clock.add(new THREE.Mesh(ringGeometry, clockMaterial));

    const tickGeometry = track(new THREE.BoxGeometry(0.035, 0.16, 0.035));
    for (let i = 0; i < 12; i++) {
        const tick = new THREE.Mesh(tickGeometry, softMaterial);
        const angle = (i / 12) * Math.PI * 2;
        tick.position.set(Math.sin(angle) * 0.93, Math.cos(angle) * 0.93, 0);
        tick.rotation.z = -angle;
        if (i % 3 === 0) tick.scale.set(1.6, 1.5, 1);
        clock.add(tick);
    }

    const makeHand = (length, thickness, material) => {
        const pivot = new THREE.Group();
        const geometry = track(new THREE.BoxGeometry(thickness, length, thickness));
        const hand = new THREE.Mesh(geometry, material);
        hand.position.y = length / 2 - 0.08;
        pivot.add(hand);
        clock.add(pivot);
        return pivot;
    };

    const hourHand = makeHand(0.52, 0.07, softMaterial);
    const minuteHand = makeHand(0.8, 0.05, clockMaterial);
    hourHand.rotation.z = -1.1;
    minuteHand.rotation.z = 0.6;

    const hubGeometry = track(new THREE.SphereGeometry(0.085, 24, 24));
    clock.add(new THREE.Mesh(hubGeometry, softMaterial));

    // ---------- Peeche ke taare ----------
    const STAR_COUNT = 420;
    const starPositions = new Float32Array(STAR_COUNT * 3);
    for (let i = 0; i < STAR_COUNT; i++) {
        starPositions[i * 3] = (Math.random() - 0.5) * 26;
        starPositions[i * 3 + 1] = (Math.random() - 0.5) * 16;
        starPositions[i * 3 + 2] = -Math.random() * 12 - 1;
    }
    const starGeometry = track(new THREE.BufferGeometry());
    starGeometry.setAttribute('position', new THREE.BufferAttribute(starPositions, 3));
    const starMaterial = track(new THREE.PointsMaterial({ color: 0x8fb5a8, size: 0.035, transparent: true, opacity: 0.7, sizeAttenuation: true }));
    const stars = new THREE.Points(starGeometry, starMaterial);
    scene.add(stars);

    // ---------- Screen ke size ke hisaab se jamana ----------
    function resize() {
        const width = canvas.clientWidth || 1;
        const height = canvas.clientHeight || 1;
        const aspect = width / height;

        renderer.setSize(width, height, false);
        camera.aspect = aspect;
        camera.updateProjectionMatrix();

        if (aspect > 1.15) {
            // Chaudi screen: model daayin taraf, text baayin taraf.
            rig.position.set(Math.min(2.45, aspect * 1.35), 0.1, 0);
            rig.scale.setScalar(Math.min(0.88, aspect * 0.56));
        } else {
            // Phone: model upar, text neeche.
            rig.position.set(0, 1.3, 0);
            rig.scale.setScalar(Math.max(0.44, Math.min(0.72, aspect * 1.22)));
        }

        if (!running) render(lastTime);
    }

    // ---------- Pointer aur scroll ----------
    const pointer = { x: 0, y: 0 };
    const onPointerMove = (event) => {
        pointer.x = (event.clientX / window.innerWidth) * 2 - 1;
        pointer.y = (event.clientY / window.innerHeight) * 2 - 1;
    };

    let scrollProgress = 0;
    const onScroll = () => {
        scrollProgress = Math.min(1, window.scrollY / Math.max(1, window.innerHeight));
    };

    // ---------- Animation loop ----------
    let frame = 0;
    let running = false;
    let lastTime = 0;

    function render(time) {
        lastTime = time;
        const t = time / 1000;

        carousel.rotation.y = t * 0.16;
        for (const card of cards) {
            card.position.y = Math.sin(t * 1.1 + card.userData.phase) * 0.09;
            if (card.userData.picked) card.scale.setScalar(1.08 + Math.sin(t * 2.4) * 0.045);
        }

        minuteHand.rotation.z = 0.6 - t * 0.7;
        hourHand.rotation.z = -1.1 - t * 0.058;
        stars.rotation.y = t * 0.01;

        // Halka sa parallax: target ki taraf dheere-dheere khisakna (easing).
        rig.rotation.y += (pointer.x * 0.22 - rig.rotation.y) * 0.05;
        rig.rotation.x += (pointer.y * 0.12 - rig.rotation.x) * 0.05;
        camera.position.y = 0.5 - scrollProgress * 1.4;
        camera.lookAt(0, -scrollProgress * 0.6, 0);

        renderer.render(scene, camera);
    }

    function loop(time) {
        render(time);
        frame = requestAnimationFrame(loop);
    }

    function start() {
        if (running || reducedMotion) return;
        running = true;
        frame = requestAnimationFrame(loop);
    }

    function stop() {
        running = false;
        cancelAnimationFrame(frame);
    }

    // Scene dikh hi nahi raha (scroll karke neeche chale gaye / tab badal diya) to battery mat jalao.
    const visibility = new IntersectionObserver(([entry]) => (entry.isIntersecting && !document.hidden ? start() : stop()));
    visibility.observe(canvas);
    const onVisibilityChange = () => (document.hidden ? stop() : start());

    const resizeObserver = new ResizeObserver(resize);
    resizeObserver.observe(canvas);

    window.addEventListener('pointermove', onPointerMove, { passive: true });
    window.addEventListener('scroll', onScroll, { passive: true });
    document.addEventListener('visibilitychange', onVisibilityChange);

    resize();
    render(reducedMotion ? 4200 : 0); // reduced motion: ek sundar sa ruka hua frame
    start();

    return function dispose() {
        stop();
        visibility.disconnect();
        resizeObserver.disconnect();
        window.removeEventListener('pointermove', onPointerMove);
        window.removeEventListener('scroll', onScroll);
        document.removeEventListener('visibilitychange', onVisibilityChange);
        disposables.forEach((item) => item.dispose());
        renderer.dispose();
    };
}
