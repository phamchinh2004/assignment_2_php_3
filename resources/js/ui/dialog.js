const ICONS = {
    success: 'fa-check',
    error: 'fa-xmark',
    warning: 'fa-triangle-exclamation',
    info: 'fa-circle-info',
    question: 'fa-question',
};

let activeDialog = null;
let dialogQueue = Promise.resolve();

function normaliseOptions(options) {
    if (typeof options === 'string') {
        return { text: options };
    }

    return options || {};
}

function buttonLabel(button, fallback) {
    if (typeof button === 'string') return button;
    if (button && typeof button === 'object' && button.text) return button.text;
    return fallback;
}

function mountDialog(mode, rawOptions) {
    const options = normaliseOptions(rawOptions);
    const tone = options.tone || options.icon || (options.dangerMode ? 'warning' : 'info');
    const previousFocus = document.activeElement;
    const overlay = document.createElement('div');
    const panel = document.createElement('section');
    const icon = document.createElement('div');
    const copy = document.createElement('div');
    const title = document.createElement('h2');
    const text = document.createElement('p');
    const actions = document.createElement('div');

    overlay.className = `app-dialog-overlay app-dialog-overlay--${mode}`;
    panel.className = `app-dialog app-dialog--${tone} app-dialog--${mode}`;
    panel.setAttribute('role', mode === 'notice' ? 'status' : 'alertdialog');
    panel.setAttribute('aria-modal', 'true');
    panel.setAttribute('aria-labelledby', 'app-dialog-title');
    panel.setAttribute('aria-describedby', 'app-dialog-text');
    icon.className = 'app-dialog__icon';
    icon.innerHTML = `<i class="fa-solid ${ICONS[tone] || ICONS.info}" aria-hidden="true"></i>`;
    copy.className = 'app-dialog__copy';
    title.id = 'app-dialog-title';
    title.className = 'app-dialog__title';
    title.textContent = options.title || (tone === 'warning' ? 'Cảnh báo' : tone === 'error' ? 'Có lỗi xảy ra' : 'Thông báo');
    text.id = 'app-dialog-text';
    text.className = 'app-dialog__text';
    text.textContent = options.text || '';
    actions.className = 'app-dialog__actions';

    copy.append(title, text);
    panel.append(icon, copy);
    if (mode !== 'notice') panel.append(actions);
    overlay.append(panel);

    return new Promise((resolve) => {
        let settled = false;
        let timer = null;

        const close = (value) => {
            if (settled) return;
            settled = true;
            if (timer) window.clearTimeout(timer);
            document.removeEventListener('keydown', handleKeydown);
            overlay.classList.remove('is-visible');
            document.body.classList.remove('app-dialog-open');
            window.setTimeout(() => {
                overlay.remove();
                activeDialog = null;
                if (previousFocus instanceof HTMLElement) previousFocus.focus();
                resolve(value);
            }, 180);
        };

        const addButton = (label, className, value, autofocus = false) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = `app-dialog__button ${className}`;
            button.textContent = label;
            if (autofocus) button.dataset.autofocus = 'true';
            button.addEventListener('click', () => close(value));
            actions.append(button);
            return button;
        };

        if (mode === 'confirm') {
            const buttons = options.buttons && typeof options.buttons === 'object' ? options.buttons : {};
            addButton(options.cancelText || buttonLabel(buttons.cancel, 'Hủy'), 'app-dialog__button--secondary', false);
            addButton(options.confirmText || buttonLabel(buttons.confirm, 'Xác nhận'), options.dangerMode ? 'app-dialog__button--danger' : 'app-dialog__button--primary', true, true);
        } else if (mode === 'choose') {
            (options.choices || []).forEach((choice, index) => {
                addButton(choice.text, index === 0 ? 'app-dialog__button--secondary' : 'app-dialog__button--primary', choice.value, index === 1);
            });
        } else if (mode === 'alert') {
            addButton(options.confirmText || options.button || 'OK', 'app-dialog__button--primary', true, true);
        }

        const handleKeydown = (event) => {
            if (event.key === 'Escape' && options.closeOnEscape !== false) {
                close(mode === 'choose' ? null : false);
                return;
            }
            if (event.key !== 'Tab') return;
            const focusable = [...panel.querySelectorAll('button:not([disabled]), input:not([disabled]), textarea:not([disabled]), select:not([disabled])')];
            if (!focusable.length) return;
            const first = focusable[0];
            const last = focusable[focusable.length - 1];
            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        };

        overlay.addEventListener('click', (event) => {
            if (event.target === overlay && options.closeOnBackdrop !== false && mode !== 'notice') {
                close(mode === 'choose' ? null : false);
            }
        });
        document.addEventListener('keydown', handleKeydown);
        document.body.append(overlay);
        document.body.classList.add('app-dialog-open');
        activeDialog = { close };
        requestAnimationFrame(() => {
            overlay.classList.add('is-visible');
            panel.querySelector('[data-autofocus="true"]')?.focus();
        });

        if (mode === 'notice') {
            timer = window.setTimeout(() => close(true), Number(options.timer) || 2500);
        }
    });
}

function enqueue(mode, options) {
    const task = () => mountDialog(mode, options);
    const result = dialogQueue.then(task, task);
    dialogQueue = result.catch(() => undefined);
    return result;
}

const AppDialog = {
    alert: (options) => enqueue('alert', options),
    confirm: (options) => enqueue('confirm', options),
    notice: (options) => enqueue('notice', options),
    choose: (options) => enqueue('choose', options),
    close: () => activeDialog?.close(false),
};

window.AppDialog = AppDialog;

export default AppDialog;
