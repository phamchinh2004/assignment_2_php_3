const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const { test } = require('node:test');

function setup(fetch) {
    const source = fs.readFileSync('resources/js/admin/account-profile.js', 'utf8');
    const elements = {};
    const modalEvents = {};
    for (const id of ['adminAccountForm', 'updateAccountModal', 'adminAccountSubmit', 'adminAccountStatus',
        'adminAccountFullName', 'adminAccountFullNameError', 'adminAccountEmail', 'adminAccountEmailError']) {
        const classes = new Set();
        elements[id] = {
            value: '', defaultValue: '', textContent: '', disabled: false, readOnly: false,
            listeners: {}, attributes: {}, focused: false,
            classList: { add: (...names) => names.forEach((name) => classes.add(name)),
                remove: (...names) => names.forEach((name) => classes.delete(name)), contains: (name) => classes.has(name) },
            addEventListener(type, listener) { this.listeners[type] = listener; },
            setAttribute(name, value) { this.attributes[name] = value; },
            removeAttribute(name) { delete this.attributes[name]; },
            focus() { this.focused = true; },
        };
    }
    elements.adminAccountFullName.value = elements.adminAccountFullName.defaultValue = 'Tên cũ';
    elements.adminAccountEmail.value = elements.adminAccountEmail.defaultValue = 'old@example.test';
    elements.adminAccountForm.action = 'http://localhost/admin/account';
    elements.adminAccountForm.reset = () => {
        for (const id of ['adminAccountFullName', 'adminAccountEmail']) elements[id].value = elements[id].defaultValue;
    };
    const jquery = { on: (type, listener) => { modalEvents[type] = listener; return jquery; } };
    const calls = [];
    vm.runInNewContext(source, {
        document: { getElementById: (id) => elements[id] },
        window: { jQuery: () => jquery },
        FormData: class {
            constructor() {
                this.data = { full_name: elements.adminAccountFullName.value, email: elements.adminAccountEmail.value };
            }
        },
        fetch: async (...args) => { calls.push(args); return fetch(...args); },
    });
    return { elements, modalEvents, calls, submit: () => elements.adminAccountForm.listeners.submit({ preventDefault() {} }) };
}

test('submit blocks duplicates, shows loading and retains saved values on reopening', async () => {
    let finish;
    const page = setup(() => new Promise((resolve) => { finish = resolve; }));
    const { elements, calls, modalEvents } = page;
    elements.adminAccountFullName.value = 'Tên mới';
    elements.adminAccountEmail.value = 'new@example.test';
    const pending = page.submit();
    await page.submit();
    assert.equal(calls.length, 1);
    assert.equal(calls[0][0], 'http://localhost/admin/account');
    assert.equal(calls[0][1].method, 'POST');
    assert.equal(calls[0][1].headers.Accept, 'application/json');
    assert.equal(calls[0][1].body.data.full_name, 'Tên mới');
    assert.equal(elements.adminAccountSubmit.disabled, true);
    assert.equal(elements.adminAccountSubmit.textContent, 'Đang lưu...');
    assert.equal(elements.adminAccountEmail.readOnly, true);
    finish({ ok: true, status: 200, json: async () => ({
        message: 'Đã lưu', user: { full_name: 'Tên mới', email: 'new@example.test' },
    }) });
    await pending;
    assert.equal(elements.adminAccountSubmit.disabled, false);
    assert.equal(elements.adminAccountStatus.textContent, 'Đã lưu');
    assert.equal(elements.adminAccountStatus.classList.contains('alert-success'), true);
    assert.equal(elements.adminAccountEmail.readOnly, false);
    elements.adminAccountEmail.value = 'unsaved@example.test';
    modalEvents['show.bs.modal']();
    assert.equal(elements.adminAccountEmail.value, 'new@example.test');
    assert.equal(elements.adminAccountFullName.value, 'Tên mới');
    assert.equal(elements.adminAccountStatus.classList.contains('d-none'), true);
    modalEvents['shown.bs.modal']();
    assert.equal(elements.adminAccountFullName.focused, true);
});

test('field errors preserve input, focus the invalid field and clear on edit', async () => {
    const page = setup(async () => ({ ok: false, status: 422,
        json: async () => ({ errors: { email: ['Email đã được sử dụng.'] } }) }));
    const { elements } = page;
    elements.adminAccountEmail.value = 'duplicate@example.test';
    await page.submit();
    assert.equal(elements.adminAccountEmail.value, 'duplicate@example.test');
    assert.equal(elements.adminAccountEmail.classList.contains('is-invalid'), true);
    assert.equal(elements.adminAccountEmail.attributes['aria-invalid'], 'true');
    assert.equal(elements.adminAccountEmailError.textContent, 'Email đã được sử dụng.');
    assert.equal(elements.adminAccountEmail.focused, true);
    assert.equal(elements.adminAccountSubmit.disabled, false);
    elements.adminAccountEmail.listeners.input();
    assert.equal(elements.adminAccountEmail.classList.contains('is-invalid'), false);
    assert.equal(elements.adminAccountEmail.attributes['aria-invalid'], undefined);
    assert.equal(elements.adminAccountEmailError.textContent, '');
});

test('permission and network failures show feedback and allow retry', async () => {
    for (const failure of [
        async () => ({ ok: false, status: 403, json: async () => ({ message: 'Bạn không có quyền.' }) }),
        async () => { throw new Error('Không thể kết nối.'); },
    ]) {
        const { elements, submit } = setup(failure);
        await submit();
        assert.equal(elements.adminAccountStatus.classList.contains('alert-danger'), true);
        assert.equal(elements.adminAccountStatus.classList.contains('d-none'), false);
        assert.ok(elements.adminAccountStatus.textContent.length > 0);
        assert.equal(elements.adminAccountSubmit.disabled, false);
        assert.equal(elements.adminAccountEmail.readOnly, false);
    }
});
