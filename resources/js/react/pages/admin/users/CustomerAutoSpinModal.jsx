import { useEffect, useRef, useState } from 'react';
import { Alert, Avatar, Button, InputNumber, Modal, Progress, Skeleton } from 'antd';
import { AimOutlined, ArrowRightOutlined, CheckCircleOutlined, PlayCircleOutlined, SafetyOutlined, ThunderboltOutlined, SyncOutlined, UserOutlined } from '@ant-design/icons';
import { requestJson } from '../../../lib/http';

export default function CustomerAutoSpinModal({ user, url, onClose, onProgress }) {
    const [state, setState] = useState(null);
    const [target, setTarget] = useState(null);
    const [loading, setLoading] = useState(true);
    const [running, setRunning] = useState(false);
    const [stopping, setStopping] = useState(false);
    const [feedback, setFeedback] = useState(null);
    const [confirmed, setConfirmed] = useState(0);
    const control = useRef({ stopped: false, busy: false, controller: new AbortController() });

    useEffect(() => {
        const run = { stopped: false, busy: false, controller: new AbortController() };
        control.current = run;
        requestJson(url, { signal: run.controller.signal }).then((payload) => {
            setState(payload);
            onProgress?.(user.id, payload);
            setTarget(Math.min(payload.total_spins, payload.current_spin + 1));
        }).catch((error) => {
            if (error.name !== 'AbortError') setFeedback({ type: 'error', text: error.message });
        }).finally(() => {
            if (!run.controller.signal.aborted) setLoading(false);
        });
        return () => {
            run.stopped = true;
            run.controller.abort();
        };
    }, [url, user.id, onProgress]);

    const stop = () => {
        control.current.stopped = true;
        setStopping(true);
    };

    const start = async () => {
        const run = control.current;
        if (run.busy || !state || !Number.isInteger(target)) return;
        run.busy = true;
        run.stopped = false;
        setRunning(true);
        setStopping(false);
        setFeedback(null);
        setConfirmed(0);
        let current = state;
        let count = 0;
        try {
            while (!run.stopped) {
                const payload = await requestJson(url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        target_spin: target,
                        expected_spin: current.current_spin,
                        expected_pending_order_id: current.pending_order_id,
                    }),
                    signal: run.controller.signal,
                });
                if (payload.status !== 200) throw new Error(payload.message || 'Không thể xử lý đơn hàng.');
                current = payload;
                setState(payload);
                onProgress?.(user.id, payload);
                if (payload.confirmed_order_id) count += 1;
                setConfirmed(count);
                if (payload.done) {
                    setFeedback({ type: 'success', text: `Đã xác nhận tới đơn ${target}. Phiên này đã xác nhận ${count} đơn.` });
                    return;
                }
            }
            if (!run.controller.signal.aborted) {
                setFeedback({ type: 'info', text: `Đã dừng ở lượt ${current.current_spin}. Phiên này đã xác nhận ${count} đơn.` });
            }
        } catch (error) {
            if (error.name === 'AbortError') return;
            // An error can occur after receiving a new order; show its actual position and allow retry.
            try {
                current = await requestJson(url, { signal: run.controller.signal });
                setState(current);
                onProgress?.(user.id, current);
            } catch {
                // Retain the last known progress if refreshing is unavailable.
            }
            if (!run.controller.signal.aborted) {
                setFeedback({ type: 'error', text: `Đã dừng ở lượt ${current.current_spin}: ${error.message}` });
            }
        } finally {
            run.busy = false;
            if (!run.controller.signal.aborted) {
                setRunning(false);
                setStopping(false);
            }
        }
    };

    const minimum = state ? Math.max(1, state.current_spin + (state.pending_order_id ? 0 : 1)) : 1;
    const validTarget = state && Number.isInteger(target) && target >= minimum && target <= state.total_spins;
    const done = state && state.total_spins > 0 && !state.pending_order_id && state.current_spin >= state.total_spins;
    const remaining = state && Number.isInteger(target)
        ? Math.max(0, target - state.current_spin + (state.pending_order_id ? 1 : 0))
        : 0;
    const percent = target && state
        ? Math.max(0, Math.min(100, Math.round((state.current_spin - (state.pending_order_id ? 1 : 0)) / target * 100)))
        : 0;
    const statusLabel = running ? (stopping ? 'Đang dừng' : 'Đang quay')
        : feedback?.type === 'success' ? 'Hoàn tất'
            : feedback?.type === 'error' ? 'Cần kiểm tra'
                : feedback?.type === 'info' ? 'Đã dừng' : 'Sẵn sàng';
    const statusTone = running ? 'active' : feedback?.type || 'ready';

    return (
        <Modal
            open
            centered
            width={760}
            // Above the fixed admin header/sidebar and flash notifications (1030–1600).
            zIndex={1700}
            rootClassName="customer-auto-spin-overlay"
            className={`customer-auto-spin-modal${running ? ' is-running' : ''}${feedback?.type === 'success' ? ' is-complete' : ''}`}
            title={(
                <div className="customer-auto-spin-heading">
                    <div className="customer-auto-spin-ambient" aria-hidden="true">
                        <span /><span /><span />
                        {Array.from({ length: 8 }, (_, index) => <i key={index} style={{ '--particle': index }} />)}
                    </div>
                    <span className="customer-auto-spin-heading__icon" aria-hidden="true"><ThunderboltOutlined /></span>
                    <div className="customer-auto-spin-heading__copy">
                        <span className="customer-auto-spin-eyebrow"><i aria-hidden="true" /> PHÂN PHỐI ĐƠN HÀNG</span>
                        <strong>Tự động quay đơn</strong>
                        <p>Nhận và xác nhận đơn tới lượt bạn chọn.</p>
                    </div>
                </div>
            )}
            onCancel={onClose}
            closable={!running}
            maskClosable={!running}
            keyboard={!running}
            footer={(
                <div className="customer-auto-spin-footer">
                    <span className="customer-auto-spin-footer__note"><SafetyOutlined aria-hidden="true" /> Dừng khi thiếu số dư hoặc gặp lỗi</span>
                    <div>
                        <Button key="close" disabled={running} onClick={onClose}>Đóng</Button>
                        {running ? (
                            <Button key="stop" onClick={stop} disabled={stopping} loading={stopping}>
                                {stopping ? 'Đang dừng…' : 'Dừng sau đơn hiện tại'}
                            </Button>
                        ) : (
                            <Button key="start" type="primary" icon={<PlayCircleOutlined />} onClick={start} disabled={loading || !validTarget || done}>
                                Bắt đầu quay
                            </Button>
                        )}
                    </div>
                </div>
            )}
        >
            {feedback?.type === 'success' && (
                <div className="customer-auto-spin-celebration" aria-hidden="true">
                    {Array.from({ length: 12 }, (_, index) => <i key={index} style={{ '--particle': index }} />)}
                </div>
            )}
            <div className="customer-auto-spin-account">
                <Avatar size={44} src={user.avatar_url} icon={<UserOutlined />} />
                <div className="customer-auto-spin-account__identity">
                    <strong>{user.full_name || user.username}</strong>
                    <span>@{user.username} <i aria-hidden="true">·</i> ID {user.id}</span>
                </div>
                <span className="customer-auto-spin-account__kind">{user.clone_account ? 'Tài khoản clone' : 'Khách hàng'}</span>
            </div>
            {loading ? (
                <div className="customer-auto-spin-loading" role="status">
                    <Skeleton active paragraph={{ rows: 3 }} title={false} />
                    <span>Đang tải tiến trình quay…</span>
                </div>
            ) : state && (
                <>
                    <div className="customer-auto-spin-metrics">
                        <div><span><SyncOutlined aria-hidden="true" /> Lượt hiện tại</span><strong key={state.current_spin}>{state.current_spin}<small> / {state.total_spins}</small></strong></div>
                        <div><span><CheckCircleOutlined aria-hidden="true" /> Đã xác nhận trong phiên</span><strong key={confirmed}>{confirmed}<small> đơn</small></strong></div>
                        <div><span><AimOutlined aria-hidden="true" /> Còn tới đơn đích</span><strong key={remaining}>{remaining}<small> đơn</small></strong></div>
                    </div>
                    <div className="customer-auto-spin-workspace">
                        <div className="customer-auto-spin-target">
                            <div className="customer-auto-spin-target__label">
                                <label htmlFor="auto-spin-target"><AimOutlined aria-hidden="true" /> Quay tới đơn số</label>
                                <span>Tối đa {state.total_spins} lượt</span>
                            </div>
                            <InputNumber
                                id="auto-spin-target"
                                value={target}
                                onChange={setTarget}
                                min={minimum}
                                max={state.total_spins}
                                precision={0}
                                disabled={running || done}
                                size="large"
                                addonAfter="đơn"
                                aria-describedby="auto-spin-help"
                            />
                            <p id="auto-spin-help">Xác nhận từng đơn theo thứ tự, bao gồm đơn đích.
                                {state.pending_order_id ? ' Đơn đã nhận sẽ được xác nhận trước khi quay tiếp.' : ''}</p>
                        </div>
                        <div className="customer-auto-spin-progress">
                            <div className="customer-auto-spin-progress__heading">
                                <strong>Tiến trình tới đơn đích</strong>
                                <span className={`customer-auto-spin-status customer-auto-spin-status--${statusTone}`}>
                                    <i aria-hidden="true" />{statusLabel}
                                </span>
                            </div>
                            <div className="customer-auto-spin-progress__display" aria-live="polite" role="status">
                                <Progress
                                    type="circle"
                                    size={126}
                                    strokeWidth={7}
                                    strokeColor={feedback?.type === 'error' ? undefined : { '0%': '#6366f1', '100%': feedback?.type === 'success' ? '#10b981' : '#a855f7' }}
                                    format={(value) => <span className="customer-auto-spin-ring-value"><strong>{value}%</strong><small>tới đơn đích</small></span>}
                                    percent={percent}
                                    status={feedback?.type === 'error' ? 'exception' : running ? 'active' : 'normal'}
                                />
                                <div className="customer-auto-spin-progress__caption">
                                    <span>Lượt hiện tại <b>{state.current_spin}</b></span>
                                    <span>Đơn đích <b>{target ?? '—'}</b></span>
                                </div>
                                {running && <p className="customer-auto-spin-progress__activity">
                                    <SyncOutlined spin={!stopping} aria-hidden="true" />
                                    {stopping ? 'Đang xử lý xong đơn hiện tại trước khi dừng…' : `Đã xác nhận ${confirmed} đơn trong phiên này`}
                                </p>}
                            </div>
                        </div>
                    </div>
                    <div className="customer-auto-spin-flow" aria-label="Quy trình quay tự động">
                        <span><SyncOutlined aria-hidden="true" /> Nhận đơn</span>
                        <ArrowRightOutlined aria-hidden="true" />
                        <span><CheckCircleOutlined aria-hidden="true" /> Xác nhận</span>
                        <ArrowRightOutlined aria-hidden="true" />
                        <span>Lượt tiếp theo</span>
                    </div>
                    <p className="customer-auto-spin-help">Giữ trang này mở trong khi chạy. Các đơn đã xác nhận tiếp tục xử lý theo quy trình hiện tại.</p>
                    {done && <Alert type="info" showIcon message="Tài khoản đã dùng hết lượt quay." />}
                    {state.total_spins === 0 && <Alert type="warning" showIcon message="Tài khoản chưa có lượt quay. Vui lòng kiểm tra cấp bậc và tiến trình quay." />}
                </>
            )}
            {feedback && <Alert className="customer-auto-spin-feedback" type={feedback.type} showIcon message={feedback.text} />}
        </Modal>
    );
}
