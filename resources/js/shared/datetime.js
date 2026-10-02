const DEFAULT_LOCALE = 'vi-VN';

export function parseDateTime(value) {
    if (!value) return null;

    const date = value instanceof Date ? value : new Date(value);
    return Number.isNaN(date.getTime()) ? null : date;
}

export function formatLocalDateTime(value, options = {}, fallback = '—') {
    const date = parseDateTime(value);
    if (!date) return fallback;

    const formatOptions = Object.keys(options).length > 0
        ? options
        : { dateStyle: 'short', timeStyle: 'short' };

    return new Intl.DateTimeFormat(DEFAULT_LOCALE, formatOptions).format(date);
}

export function formatLocalDate(value, options = {}, fallback = '—') {
    return formatLocalDateTime(
        value,
        Object.keys(options).length > 0 ? options : { dateStyle: 'short' },
        fallback,
    );
}

const LOCAL_DATETIME_PRESETS = {
    short: { dateStyle: 'short', timeStyle: 'short' },
    medium: { dateStyle: 'short', timeStyle: 'medium' },
    date: { dateStyle: 'short' },
    time: { hour: '2-digit', minute: '2-digit' },
};

export function hydrateLocalDateTimes(root = document) {
    const elements = [];

    if (root instanceof Element && root.matches('[data-local-datetime]')) {
        elements.push(root);
    }

    if (typeof root.querySelectorAll === 'function') {
        elements.push(...root.querySelectorAll('[data-local-datetime]'));
    }

    elements.forEach((element) => {
        const preset = element.dataset.localFormat || 'short';
        const options = LOCAL_DATETIME_PRESETS[preset] || LOCAL_DATETIME_PRESETS.short;

        element.textContent = formatLocalDateTime(
            element.dataset.localDatetime,
            options,
            element.textContent || '—',
        );
    });
}

export function observeLocalDateTimes(root = document.body) {
    if (!root) return null;

    hydrateLocalDateTimes(root);

    const observer = new MutationObserver((records) => {
        records.forEach((record) => {
            record.addedNodes.forEach((node) => {
                if (node.nodeType === Node.ELEMENT_NODE) {
                    hydrateLocalDateTimes(node);
                }
            });
        });
    });

    observer.observe(root, { childList: true, subtree: true });
    return observer;
}
