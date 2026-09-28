import { DatePicker } from 'antd';
import dayjs from 'dayjs';
import 'dayjs/locale/vi';

dayjs.locale('vi');

function pickerValue(value) {
    if (!value) return null;

    const parsed = dayjs(value);
    return parsed.isValid() ? parsed : null;
}

export function AdminDatePicker({ value, onChange, placeholder = 'Chọn ngày', style, ...props }) {
    return (
        <DatePicker
            {...props}
            value={pickerValue(value)}
            format="DD/MM/YYYY"
            placeholder={placeholder}
            style={{ width: '100%', ...style }}
            onChange={(date) => onChange?.(date ? date.format('YYYY-MM-DD') : '')}
        />
    );
}

export function AdminDateTimePicker({ value, onChange, placeholder = 'Chọn ngày và giờ', style, ...props }) {
    return (
        <DatePicker
            {...props}
            value={pickerValue(value)}
            format="DD/MM/YYYY HH:mm"
            showTime={{ format: 'HH:mm' }}
            placeholder={placeholder}
            style={{ width: '100%', ...style }}
            onChange={(date) => onChange?.(date ? date.format('YYYY-MM-DDTHH:mm') : '')}
        />
    );
}
