import test from 'node:test';
import assert from 'node:assert/strict';
import { initReveal } from '../../resources/js/reveal.js';

function fixture({ reduced = false, native = false, observer = true, bottom = 1200, top = bottom - 100 } = {}) {
    const classes = new Set(['reveal']);
    const element = {
        classList: { add: (...names) => names.forEach(name => classes.add(name)), remove: (...names) => names.forEach(name => classes.delete(name)), contains: name => classes.has(name) },
        getBoundingClientRect: () => ({ top, bottom }),
    };
    const events = {};
    const root = { querySelectorAll: selector => selector === '.reveal' ? [element] : [], addEventListener: (name, callback) => { events[name] = callback; } };
    const motion = { matches: reduced, addEventListener: (_name, callback) => { events.motion = callback; } };
    const viewport = { innerHeight: 800, matchMedia: () => motion, CSS: { supports: () => native } };
    const state = { classes, element, events, root, viewport, observed: new Set(), disconnected: false };
    if (observer) viewport.IntersectionObserver = class {
        constructor(callback) { state.enter = callback; }
        observe(node) { state.observed.add(node); }
        unobserve(node) { state.observed.delete(node); }
        disconnect() { state.disconnected = true; state.observed.clear(); }
    };
    return state;
}

test('fallback reveals once, unobserves, then clears the animation', () => {
    const state = fixture();
    initReveal(state.root, state.viewport);
    assert.ok(state.classes.has('reveal-pending'));
    state.enter([{ target: state.element, isIntersecting: false }]);
    assert.ok(state.classes.has('reveal-pending'));
    state.enter([{ target: state.element, isIntersecting: true }]);
    assert.ok(state.classes.has('reveal-enter'));
    assert.equal(state.observed.size, 0);
    state.events.animationend({ target: state.element, animationName: 'reveal-in' });
    assert.ok(state.classes.has('is-revealed'));
    assert.ok(!state.classes.has('reveal-enter'));
});

test('native scroll timeline skips the observer and freezes completed reveals', () => {
    const state = fixture({ native: true });
    initReveal(state.root, state.viewport);
    assert.equal(state.observed.size, 0);
    assert.ok(!state.classes.has('reveal-pending'));
    state.events.animationend({ target: state.element, animationName: 'reveal-in' });
    assert.ok(state.classes.has('is-revealed'));
});

test('native content already in view remains visible', () => {
    const state = fixture({ native: true, bottom: 500 });
    initReveal(state.root, state.viewport);
    assert.ok(state.classes.has('is-revealed'));
});

test('reduced motion skips reveal effects', () => {
    const state = fixture({ reduced: true });
    initReveal(state.root, state.viewport);
    assert.ok(state.classes.has('is-revealed'));
    assert.equal(state.observed.size, 0);
});

test('switching reduced motion on makes all pending content visible', () => {
    const state = fixture();
    initReveal(state.root, state.viewport);
    state.events.motion({ matches: true });
    assert.ok(state.disconnected);
    assert.ok(state.classes.has('is-revealed'));
    assert.ok(!state.classes.has('reveal-pending'));
});

test('missing browser support leaves content visible', () => {
    const state = fixture({ observer: false });
    initReveal(state.root, state.viewport);
    assert.deepEqual([...state.classes], ['reveal']);
});

test('keyboard focus reveals content immediately and permanently', () => {
    const state = fixture();
    initReveal(state.root, state.viewport);
    state.events.focusin({ target: { closest: () => state.element } });
    assert.ok(state.classes.has('is-revealed'));
    assert.ok(!state.classes.has('reveal-pending'));
    assert.equal(state.observed.size, 0);
});

test('initially visible content keeps load animation without a second scroll reveal', () => {
    const state = fixture({ top: 750, bottom: 950 });
    initReveal(state.root, state.viewport);
    assert.ok(state.classes.has('is-revealed'));
    assert.ok(!state.classes.has('reveal-pending'));
    assert.equal(state.observed.size, 0);
});

test('below-fold load sections skip entrance while descendants keep scroll reveal', () => {
    const state = fixture();
    const classes = new Set();
    const section = { getBoundingClientRect: () => ({ top: 1100 }), classList: { add: name => classes.add(name) } };
    state.root.querySelectorAll = selector => selector === '.reveal' ? [state.element] : [section];
    initReveal(state.root, state.viewport);
    assert.ok(classes.has('load-skip'));
    assert.ok(state.classes.has('reveal-pending'));
    assert.equal(state.observed.size, 1);
});
