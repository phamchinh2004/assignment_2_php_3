import { useEffect, useRef, useState } from 'react';
import { Alert, Button, Modal } from 'antd';
import { ArrowRightOutlined, CheckCircleOutlined, LockOutlined, UnlockOutlined, UserOutlined } from '@ant-design/icons';
import '../../../../css/admin/account-status-confirm.css';

export function accountStatusAction(status) {
    if (status === 'activated') return { label: 'Khóa tài khoản', current: 'Đã kích hoạt', next: 'Bị khóa', danger: true, icon: LockOutlined, description: 'Tài khoản sẽ bị chặn truy cập cho đến khi được mở khóa.' };
    if (status === 'inactivated') return { label: 'Kích hoạt tài khoản', current: 'Chưa kích hoạt', next: 'Đã kích hoạt', danger: false, icon: CheckCircleOutlined, description: 'Tài khoản sẽ được kích hoạt và có thể truy cập hệ thống.' };
    return { label: 'Mở khóa tài khoản', current: 'Bị khóa', next: 'Đã kích hoạt', danger: false, icon: UnlockOutlined, description: 'Tài khoản sẽ được mở khóa và có thể truy cập trở lại.' };
}

export default function AccountStatusConfirm({ account, onCancel, onConfirm }) {
    const [pending, setPending] = useState(false);
    const [error, setError] = useState('');
    const submitting = useRef(false);
    const action = accountStatusAction(account?.status);
    const Icon = action.icon;

    useEffect(() => { setError(''); }, [account]);

    const submit = async () => {
        if (!account || submitting.current) return;
        submitting.current = true;
        setPending(true);
        setError('');
        try {
            await onConfirm(account);
            onCancel();
        } catch (failure) {
            setError(failure.message || 'Không thể thay đổi trạng thái. Vui lòng thử lại.');
        } finally {
            submitting.current = false;
            setPending(false);
        }
    };

    return <Modal
        open={Boolean(account)} centered width={460} zIndex={1700}
        rootClassName="account-status-confirm-root"
        className={`account-status-confirm account-status-confirm--${action.danger ? 'danger' : 'success'}`}
        title={<div className="account-status-confirm__heading">
            <span className="account-status-confirm__icon" aria-hidden="true"><Icon /></span>
            <div><span className="account-status-confirm__eyebrow">Xác nhận thao tác</span><span className="account-status-confirm__title">{action.label}?</span></div>
        </div>}
        onCancel={() => { if (!submitting.current) onCancel(); }}
        closable={!pending} maskClosable={!pending} keyboard={!pending}
        footer={<>
            <Button disabled={pending} onClick={onCancel}>Hủy</Button>
            <Button type="primary" danger={action.danger} loading={pending} icon={<Icon aria-hidden="true" />} onClick={submit}>{action.label}</Button>
        </>}
    >
        <div className="account-status-confirm__account">
            <span className="account-status-confirm__avatar" aria-hidden="true"><UserOutlined /></span>
            <div><strong>{account?.full_name || account?.username || `#${account?.id}`}</strong><span>{account?.username ? `@${account.username} · ` : ''}ID: {account?.id}</span></div>
        </div>
        <p className="account-status-confirm__description">{action.description}</p>
        <div className="account-status-confirm__transition" aria-label={`Trạng thái: ${action.current} → ${action.next}`}>
            <span>{action.current}</span><ArrowRightOutlined aria-hidden="true" /><strong>{action.next}</strong>
        </div>
        {error && <Alert type="error" showIcon message={error} role="alert" className="account-status-confirm__error" />}
    </Modal>;
}
