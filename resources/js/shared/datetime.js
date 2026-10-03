const DEFAULT_LOCALE = 'vi-VN';

export const ADMIN_TIME_ZONE = 'Asia/Ho_Chi_Minh';

const fixedTimeZoneFormatters = new Map();
const adminInputFormatter = new Intl.DateTimeFormat('en-US', {
    timeZone: ADMIN_TIME_ZONE, year: 'numeric', month: '2-digit', day: '2-digit',
});

function dateTimeFormatter(options) {
    // Keep device time live; only reuse formatters with an explicit timezone.
    const cacheable = Object.getPrototypeOf(options) === Object.prototype
        && Object.values(Object.getOwnPropertyDescriptors(options)).every(({ value, enumerable, get, set }) =>
            enumerable && !get && !set && (value === undefined
                || typeof value === 'string' || typeof value === 'boolean'
                || (typeof value === 'number' && Number.isFinite(value))))
        && typeof options.timeZone === 'string';
    if (!cacheable) return new Intl.DateTimeFormat(DEFAULT_LOCALE, options);

    const key = JSON.stringify(Object.entries(options));
    if (!fixedTimeZoneFormatters.has(key)) {
        const formatter = new Intl.DateTimeFormat(DEFAULT_LOCALE, options);
        if (fixedTimeZoneFormatters.size >= 32) fixedTimeZoneFormatters.delete(fixedTimeZoneFormatters.keys().next().value);
        fixedTimeZoneFormatters.set(key, formatter);
    }
    return fixedTimeZoneFormatters.get(key);
}

export function parseDateTime(value) {
    if (!value) return null;

    // Unzoned database timestamps belong to the application's Vietnam timezone.
    const normalized = typeof value === 'string'
        && /^\d{4}-\d{2}-\d{2}[T ]\d{2}:\d{2}(?::\d{2}(?:\.\d+)?)?$/.test(value)
        ? `${value.replace(' ', 'T')}+07:00`
        : value;
    const date = normalized instanceof Date ? normalized : new Date(normalized);
    return Number.isNaN(date.getTime()) ? null : date;
}

export function formatLocalDateTime(value, options = {}, fallback = '—') {
    const date = parseDateTime(value);
    if (!date) return fallback;

    const formatOptions = Object.keys(options).length > 0
        ? options
        : { dateStyle: 'short', timeStyle: 'short' };

    return dateTimeFormatter(formatOptions).format(date);
}

export function formatLocalDate(value, options = {}, fallback = '—') {
    return formatLocalDateTime(
        value,
        Object.keys(options).length > 0 ? options : { dateStyle: 'short' },
        fallback,
    );
}

export function formatAdminDateTime(value, options = {}, fallback = '—') {
    return formatLocalDateTime(value, {
        ...(Object.keys(options).length ? options : { dateStyle: 'short', timeStyle: 'short' }),
        timeZone: ADMIN_TIME_ZONE,
    }, fallback);
}

export function adminDateInput(value = new Date()) {
    const date = parseDateTime(value);
    if (!date) return '';
    const parts = adminInputFormatter.formatToParts(date);
    const part = (type) => parts.find((item) => item.type === type).value;
    return `${part('year')}-${part('month')}-${part('day')}`;
}

const LOCAL_DATETIME_PRESETS = {
    short: { dateStyle: 'short', timeStyle: 'short' },
    medium: { dateStyle: 'short', timeStyle: 'medium' },
    date: { dateStyle: 'short' },
    time: { hour: '2-digit', minute: '2-digit' },
    'chat-date': { day: 'numeric', month: 'short', year: 'numeric' },
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

        const timeZone = element.dataset.timeZone
            || document.documentElement.dataset.displayTimeZone;
        const text = formatLocalDateTime(
            element.dataset.localDatetime,
            timeZone ? { ...options, timeZone } : options,
            element.textContent || '—',
        );
        if (element.textContent !== text) element.textContent = text;
        element.title = timeZone || Intl.DateTimeFormat().resolvedOptions().timeZone;
    });

    const separators = [];
    if (root instanceof Element && root.matches('[data-local-date-separator]')) separators.push(root);
    if (typeof root.querySelectorAll === 'function') {
        separators.push(...root.querySelectorAll('[data-local-date-separator]'));
    }
    separators.forEach((element) => {
        const current = formatLocalDate(element.dataset.localDateSeparator);
        const next = element.dataset.nextDatetime;
        const hidden = Boolean(next && current === formatLocalDate(next));
        if (element.hidden !== hidden) element.hidden = hidden;
        element.setAttribute('aria-label', current);
    });
}

export function observeLocalDateTimes(root = document.body) {
    if (!root) return null;

    hydrateLocalDateTimes(root);

    const observer = new MutationObserver((records) => {
        records.forEach((record) => {
            // Livewire may update attributes or text on existing nodes during a morph.
            if (record.type === 'attributes' && record.attributeName === 'hidden') {
                if (record.target.matches('[data-local-date-separator]')) hydrateLocalDateTimes(record.target);
            } else if (record.type === 'attributes' || record.type === 'characterData') {
                hydrateLocalDateTimes(record.target.parentElement || record.target);
            } else {
                if (record.target instanceof Element && record.target.matches('[data-local-datetime]')) {
                    hydrateLocalDateTimes(record.target);
                } else {
                    record.addedNodes.forEach((node) => {
                        if (node.nodeType === Node.ELEMENT_NODE) hydrateLocalDateTimes(node);
                    });
                }
            }
        });
    });

    observer.observe(root, {
        childList: true, subtree: true, characterData: true, attributes: true,
        attributeFilter: ['data-local-datetime', 'data-local-format', 'data-time-zone', 'data-local-date-separator', 'data-next-datetime', 'hidden'],
    });
    return observer;
}
