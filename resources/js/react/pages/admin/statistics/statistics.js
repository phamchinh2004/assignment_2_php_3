import { adminDateInput } from '../../../../shared/datetime';

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
    return adminDateInput(date);
}

export function startOfMonth() {
    return today().slice(0, 8) + '01';
}

export function today() {
    return dateInput(new Date());
}

export function daysAgo(days) {
    const date = new Date(`${today()}T00:00:00Z`);
    date.setUTCDate(date.getUTCDate() - days);
    return date.toISOString().slice(0, 10);
}

export function invalidDateRange(startDate, endDate) {
    return Boolean(startDate && endDate && startDate > endDate);
}

export function inclusiveDays(startDate, endDate, fallback = 30) {
    if (!startDate || !endDate || invalidDateRange(startDate, endDate)) {
        return fallback;
    }

    const start = new Date(`${startDate}T00:00:00Z`);
    const end = new Date(`${endDate}T00:00:00Z`);
    const millisecondsPerDay = 24 * 60 * 60 * 1000;

    return Math.max(1, Math.round((end - start) / millisecondsPerDay) + 1);
}

export function statusTag(status) {
    const map = {
        completed: { color: 'success', label: 'Hoàn thành' },
        processing: { color: 'processing', label: 'Đang xử lý' },
        cancelled: { color: 'error', label: 'Đã hủy' },
        pending: { color: 'warning', label: 'Chờ xử lý' },
        rejected: { color: 'error', label: 'Từ chối' },
        failed: { color: 'error', label: 'Thất bại' },
    };
    return map[status] || { color: 'default', label: status || 'Không rõ' };
}
