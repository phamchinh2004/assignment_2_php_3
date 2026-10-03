const normalize = (value) => String(value ?? '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/đ/g, 'd').replace(/Đ/g, 'D').toLocaleLowerCase('vi').trim();

const moneyFormatter = new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

export const matchesOperationsSearch = (values, query) => {
    const haystack = normalize(values.filter((value) => value !== null && value !== undefined).join(' '));
    return normalize(query).split(/\s+/).filter(Boolean).every((word) => haystack.includes(word));
};

export const operationsMoney = (value) => `${moneyFormatter.format(Number(value || 0))} $`;
