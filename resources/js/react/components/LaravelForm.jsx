import { forwardRef } from 'react';
import { message } from 'antd';
import { spaSubmitForm } from '../navigation';

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

export function fieldError(form, name) {
    const value = form?.errors?.[name];
    return Array.isArray(value) ? value[0] : value || '';
}

export function oldValue(form, name, fallback = '') {
    return Object.prototype.hasOwnProperty.call(form?.old || {}, name)
        ? form.old[name]
        : fallback;
}

const LaravelForm = forwardRef(function LaravelForm({ action, method = 'POST', children, onSubmit, spa = true, ...props }, ref) {
    const normalizedMethod = method.toUpperCase();
    const browserMethod = normalizedMethod === 'GET' ? 'GET' : 'POST';

    const handleSubmit = async (event) => {
        onSubmit?.(event);
        if (event.defaultPrevented || !spa || browserMethod === 'GET' || typeof window.__spaCommitBootstrap !== 'function') return;

        event.preventDefault();
        const form = event.currentTarget;
        const submitter = event.nativeEvent?.submitter;

        try {
            await spaSubmitForm(form, submitter);
        } catch (error) {
            console.error('SPA form submission failed.', error);
            message.error('Không thể hoàn tất thao tác. Vui lòng thử lại.');
        }
    };

    return (
        <form ref={ref} action={action} method={browserMethod} onSubmit={handleSubmit} {...props}>
            {browserMethod !== 'GET' && <input type="hidden" name="_token" value={csrfToken()} />}
            {!['GET', 'POST'].includes(normalizedMethod) && (
                <input type="hidden" name="_method" value={normalizedMethod} />
            )}
            {children}
        </form>
    );
});

export default LaravelForm;
