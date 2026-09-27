export function withQuery(url, params = {}) {
    const target = new URL(url, window.location.origin);
    Object.entries(params).forEach(([key, value]) => {
        if (value !== undefined && value !== null && value !== '') {
            target.searchParams.set(key, value);
        }
    });
    return target.toString();
}

export function money(value) {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
        maximumFractionDigits: 2,
    }).format(Number(value || 0));
}

export function number(value) {
    return new Intl.NumberFormat('vi-VN').format(Number(value || 0));
}

export function dateInput(date) {
    const local = new Date(date.getTime() - date.getTimezoneOffset() * 60000);
    return local.toISOString().slice(0, 10);
}

export function startOfMonth() {
    const now = new Date();
    return dateInput(new Date(now.getFullYear(), now.getMonth(), 1));
}

export function today() {
    return dateInput(new Date());
}

export function daysAgo(days) {
    const date = new Date();
    date.setDate(date.getDate() - days);
    return dateInput(date);
}

export function invalidDateRange(startDate, endDate) {
    return Boolean(startDate && endDate && startDate > endDate);
}

export function inclusiveDays(startDate, endDate, fallback = 30) {
    if (!startDate || !endDate || invalidDateRange(startDate, endDate)) {
        return fallback;
    }

    const start = new Date(`${startDate}T00:00:00`);
    const end = new Date(`${endDate}T00:00:00`);
    const millisecondsPerDay = 24 * 60 * 60 * 1000;

    return Math.max(1, Math.round((end - start) / millisecondsPerDay) + 1);
}

export function statusTag(status) {
    const map = {
        completed: { color: 'success', label: 'Hoàn thành' },
        processing: { color: 'processing', label: 'Đang xử lý' },
        cancelled: { color: 'error', label: 'Đã hủy' },
        pending: { color: 'warning', label: 'Chờ xử lý' },
    };
    return map[status] || { color: 'default', label: status || 'Không rõ' };
}
