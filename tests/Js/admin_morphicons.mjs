import assert from 'node:assert/strict';
import { createRequire } from 'node:module';
import { test } from 'node:test';
import vm from 'node:vm';
import { buildSync } from 'esbuild';
import React from 'react';
import { renderToStaticMarkup } from 'react-dom/server';
import { canonicalD, createMorph } from 'morphicons/dom';
import { ChevronDown, ChevronUp, ChevronsLeft, ChevronsRight, Eye, EyeOff } from 'lucide';

const require = createRequire(import.meta.url);
const componentBundle = buildSync({
    entryPoints: ['resources/js/react/components/admin/AdminMorphIcon.jsx'],
    bundle: true, write: false, platform: 'node', format: 'cjs', packages: 'external', jsx: 'automatic',
});
const exports = { module: { exports: {} }, require };
vm.runInNewContext(componentBundle.outputFiles[0].text, exports);
const { default: AdminMorphIcon, PasswordVisibilityIcon } = exports.module.exports;

test('admin morph icons reserve their size, render the actual SVG and honor reduced motion', () => {
    const element = AdminMorphIcon({ icon: ChevronUp });
    assert.equal(element.props.size, 16);
    assert.equal(element.props.spring, 'snappy');
    assert.equal(element.props.reducedMotion, 'user');
    const html = renderToStaticMarkup(React.createElement(AdminMorphIcon, { icon: ChevronUp }));
    assert.ok(html.includes('width="16"'));
    assert.ok(html.includes('aria-hidden="true"'));
    assert.ok(html.includes(`d="${canonicalD(ChevronUp)}"`));
});

test('password icon swaps eye shapes and activates only with click, Enter or Space', () => {
    let clicks = 0;
    const hidden = PasswordVisibilityIcon({ visible: false, onClick: () => { clicks += 1; } });
    const shown = PasswordVisibilityIcon({ visible: true });
    assert.equal(hidden.props.children.props.icon, EyeOff);
    assert.equal(shown.props.children.props.icon, Eye);
    assert.equal(hidden.props['aria-pressed'], false);
    assert.equal(shown.props['aria-pressed'], true);
    assert.equal(hidden.props.role, 'button');
    assert.equal(hidden.props.tabIndex, 0);
    for (const key of ['Enter', ' ', 'Escape']) {
        let prevented = false;
        hidden.props.onKeyDown({ key, preventDefault() { prevented = true; } });
        assert.equal(prevented, key !== 'Escape');
    }
    hidden.props.onClick();
    assert.equal(clicks, 3);
});

test('chosen sidebar shapes settle after interrupted animation and skip frames with reduced motion', () => {
    const previous = Object.fromEntries(['requestAnimationFrame', 'cancelAnimationFrame', 'matchMedia'].map((key) => [key, globalThis[key]]));
    const frames = new Map();
    let sequence = 0;
    let reduced = false;
    globalThis.requestAnimationFrame = (callback) => { frames.set(++sequence, callback); return sequence; };
    globalThis.cancelAnimationFrame = (id) => frames.delete(id);
    globalThis.matchMedia = () => ({ matches: reduced });
    const path = { setAttribute(_, value) { this.d = value; } };
    const morph = createMorph(path, ChevronsLeft, { reducedMotion: 'user' });
    try {
        morph.morphTo(ChevronsRight, 'snappy');
        let time = 0;
        const step = () => {
            const current = [...frames.values()];
            frames.clear();
            time += 16;
            current.forEach((callback) => callback(time));
        };
        step(); step(); step();
        morph.morphTo(ChevronsLeft, 'snappy');
        morph.morphTo(ChevronsRight, 'snappy');
        for (let count = 0; count < 200 && frames.size; count += 1) step();
        assert.equal(path.d, canonicalD(ChevronsRight));
        assert.equal(frames.size, 0);
        reduced = true;
        morph.morphTo(ChevronsLeft, 'snappy');
        assert.equal(path.d, canonicalD(ChevronsLeft));
        assert.equal(frames.size, 0);
    } finally {
        morph.destroy();
        for (const [key, value] of Object.entries(previous)) {
            if (value === undefined) delete globalThis[key]; else globalThis[key] = value;
        }
    }
});

const sidebarBundle = buildSync({
    entryPoints: ['resources/js/admin/sidebar.js'], bundle: true, write: false,
    platform: 'node', format: 'cjs', packages: 'external',
});

function element() {
    const classes = new Set();
    return {
        attributes: {}, listeners: {}, children: [], style: {},
        classList: {
            add: (name) => classes.add(name), remove: (name) => classes.delete(name),
            contains: (name) => classes.has(name),
            toggle(name, active) { if (active) classes.add(name); else classes.delete(name); },
        },
        setAttribute(key, value) { this.attributes[key] = value; },
        getAttribute(key) { return this.attributes[key]; },
        appendChild(child) { this.children.push(child); },
        replaceChildren(...children) { this.children = children; },
        addEventListener(name, callback) { this.listeners[name] = callback; },
        querySelector: () => null,
    };
}

test('real sidebar wiring preserves stored collapse state and follows Bootstrap or SPA aria changes', () => {
    const previousMedia = globalThis.matchMedia;
    globalThis.matchMedia = () => ({ matches: true });
    try {
        for (const initialCollapsed of [false, true]) {
            const body = element(), sidebar = element(), collapse = element();
            const trigger = element(), chevron = element();
            const desktop = { matches: true, addEventListener() {} };
            trigger.setAttribute('aria-expanded', 'false');
            trigger.querySelector = () => chevron;
            sidebar.querySelectorAll = () => [trigger];
            const observers = [];
            const storage = new Map([['admin-sidebar-collapsed', initialCollapsed ? '1' : '0']]);
            const document = {
                body, createElement: element, createElementNS: element, addEventListener() {},
                getElementById: (id) => ({ accordionSidebar: sidebar, adminSidebarCollapse: collapse })[id] || null,
            };
            vm.runInNewContext(sidebarBundle.outputFiles[0].text, {
                require, document,
                window: {
                    localStorage: { getItem: (key) => storage.get(key), setItem: (key, value) => storage.set(key, value) },
                    matchMedia: () => desktop, requestAnimationFrame() {}, addEventListener() {},
                },
                MutationObserver: class {
                    constructor(callback) { this.callback = callback; observers.push(this); }
                    observe(target, options) { this.target = target; this.options = options; }
                },
            });
            const collapsePath = collapse.children[0].children[0];
            assert.equal(collapsePath.attributes.d, canonicalD(initialCollapsed ? ChevronsRight : ChevronsLeft));
            collapse.listeners.click();
            assert.equal(body.classList.contains('admin-sidebar-collapsed'), !initialCollapsed);
            assert.equal(collapse.attributes['aria-expanded'], initialCollapsed ? 'true' : 'false');
            assert.equal(storage.get('admin-sidebar-collapsed'), initialCollapsed ? '0' : '1');
            assert.equal(collapsePath.attributes.d, canonicalD(initialCollapsed ? ChevronsLeft : ChevronsRight));
            assert.equal(chevron.children[0].children[0].attributes.d, canonicalD(ChevronDown));
            assert.deepEqual(Array.from(observers[0].options.attributeFilter), ['aria-expanded']);
            trigger.setAttribute('aria-expanded', 'true');
            observers[0].callback();
            assert.equal(chevron.children[0].children[0].attributes.d, canonicalD(ChevronUp));
            trigger.setAttribute('aria-expanded', 'false');
            observers[0].callback();
            assert.equal(chevron.children[0].children[0].attributes.d, canonicalD(ChevronDown));
        }
    } finally {
        if (previousMedia === undefined) delete globalThis.matchMedia; else globalThis.matchMedia = previousMedia;
    }
});
