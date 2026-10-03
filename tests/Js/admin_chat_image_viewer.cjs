const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');

const template = fs.readFileSync('resources/views/livewire/admin/chat-component.blade.php', 'utf8');
const start = template.lastIndexOf("    document.addEventListener(", template.indexOf('const boundTextareas'));
const end = template.indexOf('    function confirmChangeStatusOfUser', start);
const source = template.slice(start, end);

function element() {
    const listeners = new Map();
    const classes = new Set();
    return {
        dataset: {},
        style: { removeProperty(name) { delete this[name]; } },
        classList: {
            add(...names) { names.forEach(name => classes.add(name)); },
            remove(...names) { names.forEach(name => classes.delete(name)); },
            contains(name) { return classes.has(name); },
        },
        addEventListener(type, callback, options) {
            const handlers = listeners.get(type) || [];
            handlers.push({ callback, once: options?.once });
            listeners.set(type, handlers);
        },
        emit(type, event = {}) {
            for (const handler of [...(listeners.get(type) || [])]) {
                handler.callback.call(this, { target: this, ...event });
                if (handler.once) {
                    listeners.set(type, listeners.get(type).filter(item => item !== handler));
                }
            }
        },
        listenerCount(type) { return (listeners.get(type) || []).length; },
        setAttribute(name, value) { this[name] = value; },
        removeAttribute(name) { delete this[name]; },
        focus() {},
        contains() { return true; },
        clientWidth: 1000,
        clientHeight: 700,
        naturalWidth: 1200,
        naturalHeight: 800,
        complete: true,
    };
}

const ids = [...template.matchAll(/document.getElementById\('([^']+)'\)/g)].map(match => match[1]);
const elements = new Map(ids.map(id => [id, element()]));
let images = [];
function addImage(src) {
    const image = element();
    image.src = src;
    const frame = element();
    image.closest = () => frame;
    images.push(image);
    return image;
}
const first = addImage('/uploads/chat/first.png');
elements.get('messages-container').querySelectorAll = () => images;
const document = element();
document.readyState = 'complete';
document.body = element();
document.body.style.overflow = '';
document.getElementById = id => elements.get(id) || null;
document.querySelectorAll = () => images;
const window = element();
const frames = [];
const timers = [];
window.requestAnimationFrame = callback => frames.push(callback);
window.setTimeout = callback => timers.push(callback);
window.getComputedStyle = () => ({ paddingLeft: '58', paddingRight: '58', paddingTop: '16', paddingBottom: '16' });
const hooks = new Map();
const Livewire = {
    hook(name, callback) { hooks.set(name, callback); },
    on() {},
};
function flushFrames() {
    while (frames.length) frames.shift()();
}
function closeWithEscape() {
    document.emit('keydown', { key: 'Escape' });
    while (timers.length) timers.shift()();
    assert.equal(elements.get('zoomModal').classList.contains('active'), false);
    assert.equal(document.body.style.overflow, '');
}

vm.runInNewContext(source, { document, window, Livewire, AppDialog: {} });
// React inserts the Blade scripts after DOMContentLoaded has already fired.
document.emit('livewire:initialized');
first.emit('click');
const modal = elements.get('zoomModal');
const modalImage = elements.get('zoomModalImage');
assert.equal(modal.classList.contains('active'), true, 'Clicking an image after React mount must open the viewer');
assert.equal(modalImage.src, first.src);
assert.equal(modal['aria-hidden'], 'false');
assert.equal(document.body.style.overflow, 'hidden');
modalImage.emit('load');
flushFrames();
assert.equal(elements.get('zoomContainer').classList.contains('is-ready'), true);
assert.equal(modalImage.style.width, '884px');
elements.get('adminImageViewerZoomIn').emit('click');
assert.equal(elements.get('adminImageViewerScale').textContent, '125%');
closeWithEscape();

// A subsequent conversation render introduces fresh thumbnail DOM nodes.
images = [];
const second = addImage('/uploads/chat/second.png');
const third = addImage('/uploads/chat/third.png');
hooks.get('morph.updated')({ el: elements.get('chat-root') });
flushFrames();
second.emit('click');
assert.equal(modal.classList.contains('active'), true);
assert.equal(modalImage.src, second.src);
assert.equal(elements.get('adminImageViewerCounter').textContent, '1 / 2');
elements.get('adminImageViewerNext').emit('click');
assert.equal(modalImage.src, third.src);
closeWithEscape();

// Repeated initialization must not register the controls twice.
document.emit('livewire:initialized');
assert.equal(elements.get('adminImageViewerZoomIn').listenerCount('click'), 1);
assert.equal(second.listenerCount('click'), 1);
console.log('PASS: admin chat image opens after React mount, supports zoom/gallery/close, and binds images after Livewire morphs');
