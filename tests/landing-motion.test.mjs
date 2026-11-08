import { readFileSync } from 'node:fs';
import assert from 'node:assert/strict';

const source = readFileSync(new URL('../resources/js/app.js', import.meta.url), 'utf8');

const landing = readFileSync(new URL('../resources/views/welcome.blade.php', import.meta.url), 'utf8');
const bootstrap = landing.match(/<script>([\s\S]*?)<\/script>/)[1];

function setup({ reduced = false, observerSupported = true, animationSupported = true, focused = false } = {}) {
    const classes = new Set();
    const timers = new Map();
    const observed = new Set();
    const animations = [];
    const wordAnimations = [];
    let reveal;
    let preferenceChanged;
    let navigationCleanup;
    let cancelled = 0;
    let disconnected = false;
    const root = {
        classList: { add: (name) => classes.add(name), remove: (name) => classes.delete(name) },
        ...(animationSupported ? { animate() {} } : {}),
    };
    const animate = (log) => (frames, options) => {
        log.push({ frames, options });
        return { cancel: () => cancelled++, addEventListener() {} };
    };
    const target = (attributes = []) => {
        const values = new Set(attributes);
        return {
            hasAttribute: (name) => values.has(name),
            setAttribute: (name) => values.add(name),
            matches: () => focused,
            animate: animate(animations),
        };
    };
    const section = target(['data-landing-reveal']);
    const heading = target(['data-landing-heading']);
    heading.querySelectorAll = () => Array.from({ length: 12 }, () => ({ animate: animate(wordAnimations) }));
    const preference = { matches: reduced, addEventListener: (_, callback) => { preferenceChanged = callback; }, removeEventListener() {} };
    const document = {
        readyState: 'complete', documentElement: root,
        querySelector: () => null,
        querySelectorAll: () => [section, heading],
        addEventListener: (name, callback) => { if (name === 'livewire:navigating') navigationCleanup = callback; },
    };
    class Observer {
        constructor(callback) { reveal = callback; }
        observe(element) { observed.add(element); }
        unobserve(element) { observed.delete(element); }
        disconnect() { disconnected = true; observed.clear(); }
    }
    const window = {
        matchMedia: () => preference,
        setTimeout: (callback) => { timers.set(1, callback); return 1; },
        clearTimeout: (id) => timers.delete(id),
        ...(observerSupported ? { IntersectionObserver: Observer } : {}),
    };
    new Function('document', 'window', bootstrap)(document, window);
    return {
        classes, timers, observed, animations, wordAnimations, section, heading, preference,
        initialize: () => new Function('document', 'window', 'IntersectionObserver', source)(document, window, Observer),
        intersect: (element, isIntersecting = true) => reveal([{ target: element, isIntersecting }]),
        reduceMotion: () => { preference.matches = true; preferenceChanged(); },
        cleanup: () => navigationCleanup(),
        get cancelled() { return cancelled; },
        get disconnected() { return disconnected; },
    };
}

const page = setup();
assert.equal(page.classes.has('landing-motion-enabled'), true, 'head script prepares hidden words before the first paint');
assert.equal(page.heading.hasAttribute('data-landing-revealed'), false);
page.initialize();
assert.equal(page.timers.size, 0, 'loaded bundle cancels the startup fallback');
assert.equal(page.observed.has(page.heading), true, 'heading has its own viewport trigger');
page.intersect(page.section);
assert.equal(page.animations.length, 1);
assert.equal(page.animations[0].options.duration, 1400);
assert.equal(page.animations[0].options.delay, 180);
assert.equal(page.heading.hasAttribute('data-landing-revealed'), false, 'section entry cannot reveal an offscreen heading');
assert.equal(page.wordAnimations.length, 0);
page.intersect(page.heading, false);
assert.equal(page.wordAnimations.length, 0);
page.intersect(page.heading);
assert.equal(page.heading.hasAttribute('data-landing-revealed'), true);
assert.equal(page.observed.has(page.heading), false);
assert.equal(page.wordAnimations.length, 12);
assert.equal(page.wordAnimations[0].frames[0].opacity, 0, 'words start completely hidden');
assert.equal(page.wordAnimations[0].frames[1].opacity, 1);
assert.equal(page.wordAnimations[0].options.fill, 'backwards', 'words stay hidden during the delay');
assert.equal(page.wordAnimations[0].options.duration, 1200);
assert.equal(page.wordAnimations[0].options.delay, 260);
assert.equal(page.wordAnimations[1].options.delay, 350);
assert.equal(page.wordAnimations[11].options.delay, 980);
page.intersect(page.heading);
assert.equal(page.wordAnimations.length, 12, 'revealed words do not replay');
page.reduceMotion();
assert.equal(page.cancelled, 13);
assert.equal(page.classes.has('landing-motion-enabled'), false, 'preference change immediately exposes all pending text');
page.cleanup();
assert.equal(page.disconnected, true);

for (const options of [{ reduced: true }, { observerSupported: false }, { animationSupported: false }]) {
    const fallback = setup(options);
    assert.equal(fallback.classes.has('landing-motion-enabled'), false);
    fallback.initialize();
    assert.equal(fallback.observed.size, 0);
    assert.equal(fallback.classes.has('landing-motion-enabled'), false);
}
const failedBundle = setup();
failedBundle.timers.get(1)();
assert.equal(failedBundle.classes.has('landing-motion-enabled'), false, 'failed bundle leaves headings readable');
const focused = setup({ focused: true });
focused.initialize();
focused.intersect(focused.heading);
assert.equal(focused.wordAnimations.length, 0);
assert.equal(focused.heading.hasAttribute('data-landing-revealed'), true);
console.log('Heading visibility, viewport timing, and fallback checks passed.');

let headerHeight = 76;
let resizeHeader;
let cleanHeader;
let headerDisconnected = false;
const headerProperties = new Map();
const headerListeners = new Map();
new Function('document', 'window', source)({
    readyState: 'complete',
    documentElement: { classList: { remove() {} }, style: {
        setProperty: (name, value) => headerProperties.set(name, value),
        removeProperty: (name) => headerProperties.delete(name),
    } },
    querySelector: () => ({ getBoundingClientRect: () => ({ height: headerHeight }) }),
    querySelectorAll: () => [],
    addEventListener: (name, callback) => { if (name === 'livewire:navigating') cleanHeader = callback; },
}, {
    clearTimeout() {},
    matchMedia: () => ({ matches: false }),
    addEventListener: (name, callback) => headerListeners.set(name, callback),
    removeEventListener: (name) => headerListeners.delete(name),
    ResizeObserver: class {
        constructor(callback) { resizeHeader = callback; }
        observe() {}
        disconnect() { headerDisconnected = true; }
    },
});
assert.equal(headerProperties.get('--landing-header-height'), '76px');
headerHeight = 180;
resizeHeader();
assert.equal(headerProperties.get('--landing-header-height'), '180px');
cleanHeader();
assert.equal(headerProperties.size, 0);
assert.equal(headerListeners.size, 0);
assert.equal(headerDisconnected, true);
console.log('Sticky header sizing and cleanup checks passed.');

assert.equal(source.includes("addEventListener('wheel'"), false, 'scrolling stays native');
assert.equal(source.includes('scrollTo('), false, 'no programmatic scroll interception');
console.log('Native scrolling checks passed.');
