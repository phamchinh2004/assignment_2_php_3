(() => {
    const config = window.orderHistoryConfig || {};
    const listRoute = config.routes?.list || '';
    const orderRoute = config.routes?.order || '/order';
    const csrf = config.csrf || document.querySelector('meta[name="csrf-token"]')?.content || '';
    const userBalance = Number(config.userBalance || 0);
    const labels = config.labels || {};

    const statusConfig = {
        pending: { label: 'Chờ xử lý', tone: 'warning' },
        confirmed: { label: 'Đã xác nhận', tone: 'progress' },
        preparing: { label: 'Đang chuẩn bị', tone: 'progress' },
        transit: { label: 'Đang trung chuyển', tone: 'progress' },
        shipping: { label: 'Đang vận chuyển', tone: 'progress' },
        delivered: { label: 'Đã giao hàng', tone: 'success' },
        completed: { label: 'Hoàn thành', tone: 'success' },
        cancelled: { label: 'Đã huỷ', tone: 'danger' },
        canceled: { label: 'Đã huỷ', tone: 'danger' },
    };

    let activeFilterId = 'btn_tat_ca';
    let activeRequest = null;

    const escapeHtml = (value) => String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');

    const numberOrNull = (value) => {
        if (value === null || value === undefined || value === '') return null;
        const number = Number(value);
        return Number.isFinite(number) ? number : null;
    };

    const formatMoney = (value) => {
        const number = numberOrNull(value);
        if (number === null) return 'Chưa xác định';

        if (typeof window.format_currency === 'function') {
            try {
                return window.format_currency(number);
            } catch (_) {
                // Fall through to the local formatter.
            }
        }

        return `$${new Intl.NumberFormat('vi-VN', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 6,
        }).format(number)}`;
    };

    const formatPercent = (value) => {
        const number = numberOrNull(value);
        if (number === null) return '—';
        return `${new Intl.NumberFormat('vi-VN', { maximumFractionDigits: 2 }).format(number)}%`;
    };

    const formatDateTime = (dateString) => {
        if (!dateString) return 'Không có dữ liệu';
        const date = new Date(dateString);
        if (Number.isNaN(date.getTime())) return 'Không có dữ liệu';

        return new Intl.DateTimeFormat('vi-VN', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        }).format(date);
    };

    const storageImage = (path) => {
        if (!path) return '';
        const normalized = String(path)
            .replaceAll('\\', '/')
            .replace(/^\/?storage\//, '')
            .replace(/^\/+/, '');

        if (!normalized || normalized.includes('..')) return '';
        return `/storage/${encodeURI(normalized)}`;
    };

    const getStatus = (status) => {
        const key = status || 'pending';
        return statusConfig[key] || { label: key, tone: 'neutral' };
    };

    const loadingMarkup = () => `
        <div class="history-loading" aria-label="Đang tải lịch sử đơn hàng">
            ${Array.from({ length: 3 }, () => `
                <div class="history-skeleton">
                    <span class="history-skeleton__line history-skeleton__line--short"></span>
                    <span class="history-skeleton__block"></span>
                    <span class="history-skeleton__line"></span>
                </div>
            `).join('')}
        </div>
    `;

    const stateMarkup = (type, title, description, withRetry = false) => `
        <div class="history-state history-state--${type}">
            <span class="history-state__icon">
                <i class="fas ${type === 'error' ? 'fa-rotate-right' : 'fa-box-open'}"></i>
            </span>
            <strong>${escapeHtml(title)}</strong>
            <p>${escapeHtml(description)}</p>
            ${withRetry ? '<button type="button" class="history-retry" data-history-retry><i class="fas fa-rotate-right"></i> Thử lại</button>' : ''}
        </div>
    `;

    const deadlineMarkup = (order) => {
        if (Number(order.is_frozen) !== 1 || Number(order.spun) !== 1) return '';

        const receivedAt = new Date(order.updated_at || order.order_date || order.created_at);
        if (Number.isNaN(receivedAt.getTime())) return '';

        const limitHours = numberOrNull(order.processing_time_limit) || 24;
        const durationMs = limitHours * 60 * 60 * 1000;
        const deadline = new Date(receivedAt.getTime() + durationMs);
        const left = deadline.getTime() - Date.now();

        if (left <= 0) {
            return `
                <div class="history-deadline is-expired">
                    <span class="history-deadline__icon"><i class="fas fa-triangle-exclamation"></i></span>
                    <div>
                        <strong>Đã quá thời hạn xử lý</strong>
                        <small>Quá hạn có thể phát sinh tiền phạt 30% giá trị đơn theo quy định hệ thống.</small>
                    </div>
                </div>
            `;
        }

        const hours = Math.floor(left / 3600000);
        const minutes = Math.floor((left % 3600000) / 60000);
        const seconds = Math.floor((left % 60000) / 1000);
        let tone = 'is-safe';
        if (left < 3600000) tone = 'is-critical';
        else if (left < 3 * 3600000) tone = 'is-danger';
        else if (left < 6 * 3600000) tone = 'is-warning';
        const remainingPercent = Math.max(0, Math.min(100, (left / durationMs) * 100));

        return `
            <div class="history-deadline ${tone}" data-history-deadline="${escapeHtml(deadline.toISOString())}" data-history-duration-ms="${escapeHtml(durationMs)}" style="--deadline-progress: ${remainingPercent.toFixed(2)}%">
                <span class="history-deadline__icon"><i class="fas fa-stopwatch"></i></span>
                <div class="history-deadline__content">
                    <div class="history-deadline__message">
                        <strong>Thời hạn xử lý · ${escapeHtml(limitHours)} giờ</strong>
                        <small>Xử lý trước hạn để tránh phát sinh mức phạt 30% giá trị đơn.</small>
                    </div>
                    <div class="history-deadline__countdown">
                        <div class="history-deadline__timer-row">
                            <strong class="history-deadline__timer">
                                <span data-hours>${String(hours).padStart(2, '0')}</span>:<span data-minutes>${String(minutes).padStart(2, '0')}</span>:<span data-seconds>${String(seconds).padStart(2, '0')}</span>
                            </strong>
                            <span class="history-deadline__remaining">Còn lại</span>
                        </div>
                        <span class="history-deadline__progress" aria-hidden="true"><span data-deadline-progress></span></span>
                    </div>
                </div>
            </div>
        `;
    };

    const penaltyMarkup = ({ order, penaltyAmount, orderAmount, isPenaltySettled, penaltySettlement }) => {
        if (!(penaltyAmount > 0)) return '';

        const penaltyRate = orderAmount > 0 ? (penaltyAmount / orderAmount) * 100 : null;
        const penaltyRateLabel = penaltyRate === null ? '' : ` · ${formatPercent(penaltyRate)}`;

        if (Number(order.is_frozen) === 1) {
            const depositNeeded = orderAmount === null ? null : Math.max(0, orderAmount - userBalance);
            return `
                <div class="history-notice history-notice--danger">
                    <span class="history-notice__icon"><i class="fas fa-triangle-exclamation"></i></span>
                    <div>
                        <strong>Đơn đang chịu tiền phạt quá hạn</strong>
                        <p>Tiền phạt: <b>${escapeHtml(formatMoney(penaltyAmount))}${escapeHtml(penaltyRateLabel)}</b>. Hệ thống đã ghi nhận cảnh báo quá hạn cho đơn này.</p>
                        ${depositNeeded !== null && depositNeeded > 0 ? `<small>Cần nạp thêm ${escapeHtml(formatMoney(depositNeeded))} để đủ giá trị xử lý đơn.</small>` : ''}
                    </div>
                </div>
            `;
        }

        if (isPenaltySettled) {
            const exactRefund = penaltySettlement?.is_exact === true
                ? formatMoney(penaltySettlement.refund_amount)
                : 'Chưa đủ dữ liệu giao dịch để xác định chính xác';
            return `
                <div class="history-notice history-notice--settled">
                    <span class="history-notice__icon"><i class="fas fa-circle-info"></i></span>
                    <div>
                        <strong>Tiền phạt đã được xử lý</strong>
                        <p>${escapeHtml(formatMoney(penaltyAmount))}${escapeHtml(penaltyRateLabel)} đã được trừ khi hoàn tất đơn.</p>
                        <small>Hoàn nhập thực tế: ${escapeHtml(exactRefund)}</small>
                    </div>
                </div>
            `;
        }

        return '';
    };

    const highValueOrderMarkup = (order, isHighValueOrder, isPenalized) => {
        if (!isHighValueOrder) return '';

        const completed = Number(order.is_frozen) === 0 || order.status === 'completed';
        return `
            <div class="history-notice history-notice--hvo ${isPenalized ? 'has-penalty' : ''}">
                <span class="history-notice__icon"><i class="fas fa-gem"></i></span>
                <div class="history-hvo-copy">
                    <strong>${isPenalized ? 'Đơn hàng giá trị cao có phát sinh tiền phạt' : (completed ? 'Đơn hàng giá trị cao đã hoàn thành' : 'Đơn hàng giá trị cao')}</strong>
                    <p>${isPenalized
                        ? 'Đây vẫn là đơn may mắn thuộc chương trình sự kiện cặp đôi; trạng thái phạt được hiển thị riêng bên cạnh.'
                        : (completed
                            ? 'Đơn may mắn từ chương trình sự kiện cặp đôi đã hoàn thành.'
                            : 'Đơn may mắn thuộc chương trình sự kiện cặp đôi và được hệ thống đánh dấu đặc biệt.')}</p>
                    <small><i class="fas fa-gift"></i> Chính sách hiện tại: thưởng 10% khi hoàn thành phân phối.</small>
                </div>
                <div class="history-hvo-features">
                    <span><i class="fas fa-bolt"></i><b>Ưu tiên<br>xử lý</b></span>
                    <span><i class="fas fa-shield-halved"></i><b>Hoa hồng<br>hấp dẫn</b></span>
                    <span><i class="fas fa-gift"></i><b>Cơ hội<br>đặc biệt</b></span>
                </div>
            </div>
        `;
    };

    const renderOrder = (order) => {
        const statusKey = order.status || 'pending';
        const status = getStatus(statusKey);
        const isHighValueOrder = order.custom_price !== null && order.custom_price !== undefined;
        const penaltyAmount = numberOrNull(order.penalty_amount) || 0;
        const isPenalized = penaltyAmount > 0;
        const isPenaltySettled = isPenalized && statusKey === 'completed' && Number(order.commission_paid) === 1;
        const penaltySettlement = order.penalty_settlement || null;

        const quantity = numberOrNull(order.display_quantity);
        const unitPrice = numberOrNull(order.display_unit_price);
        const orderAmount = numberOrNull(order.snapshot_order_value);
        const commissionAmount = numberOrNull(order.snapshot_commission_value);
        const commissionPercent = numberOrNull(order.commission_percentage);

        let refundAmount = null;
        let refundSuffix = '';
        if (isPenaltySettled && penaltySettlement?.is_exact === true) {
            refundAmount = numberOrNull(penaltySettlement.refund_amount);
        } else if (orderAmount !== null && commissionAmount !== null) {
            refundAmount = orderAmount + commissionAmount - (isPenalized ? penaltyAmount : 0);
            if (isPenalized) refundSuffix = ' · dự kiến';
        }

        const code = order.display_order_code || order.order_id || `#${order.id}`;
        const name = order.display_name || 'Sản phẩm chưa có dữ liệu';
        const image = storageImage(order.display_image);
        const detailUrl = `${orderRoute.replace(/\/$/, '')}/${encodeURIComponent(order.id)}`;
        const cardClasses = [
            'order_item',
            isHighValueOrder ? 'high-value-order' : 'normal-order',
            `status-${escapeHtml(statusKey)}`,
            isPenalized && !isPenaltySettled ? 'penalized' : '',
            isPenaltySettled ? 'penalized-completed' : '',
        ].filter(Boolean).join(' ');

        const productMeta = [
            unitPrice !== null ? formatMoney(unitPrice) : 'Đơn giá chưa xác định',
            quantity !== null ? `x${quantity}` : 'SL chưa xác định',
        ];

        return `
            <article class="${cardClasses}" data-order-id="${escapeHtml(order.id)}">
                <div class="order_item_inner">
                    <div class="order_top">
                        <div class="order_meta">
                            <span class="order_time"><i class="far fa-clock"></i>${escapeHtml(labels.time || 'Thời gian đặt phân phối:')} ${escapeHtml(formatDateTime(order.updated_at || order.order_date || order.created_at))}</span>
                            <span class="order_code"><i class="fas fa-hashtag"></i>${escapeHtml(labels.orderCode || 'Mã đơn hàng:')} <strong>${escapeHtml(code)}</strong></span>
                        </div>
                        <div class="order_badges">
                            ${isHighValueOrder ? '<span class="order_badge hvo_badge"><i class="fas fa-gem"></i> Đơn hàng giá trị cao</span>' : ''}
                            ${isPenalized ? '<span class="order_badge penalty_badge"><i class="fas fa-triangle-exclamation"></i> Bị phạt</span>' : ''}
                            <span class="status-badge is-${escapeHtml(status.tone)}"><i></i>${escapeHtml(status.label)}</span>
                        </div>
                    </div>

                    ${deadlineMarkup(order)}

                    <div class="order_info">
                        ${isHighValueOrder ? `
                            <div class="order_hero_fx" aria-hidden="true">
                                <span class="order_hero_glow"></span>
                                <span class="order_hero_ribbon order_hero_ribbon--back"></span>
                                <span class="order_hero_ribbon order_hero_ribbon--mid"></span>
                                <span class="order_hero_ribbon order_hero_ribbon--front"></span>
                                <span class="order_hero_streak order_hero_streak--one"></span>
                                <span class="order_hero_streak order_hero_streak--two"></span>
                                <span class="order_hero_spark order_hero_spark--one"></span>
                                <span class="order_hero_spark order_hero_spark--two"></span>
                                <span class="order_hero_spark order_hero_spark--three"></span>
                            </div>
                        ` : ''}
                        <div class="order_product_main">
                            <div class="order_div_image">
                                ${image ? `<img class="order_image" src="${escapeHtml(image)}" alt="${escapeHtml(name)}" loading="lazy">` : '<span class="order_image_placeholder"><i class="fas fa-box-open" aria-hidden="true"></i></span>'}
                                ${isHighValueOrder ? '<span class="order_image_premium"><i class="fas fa-gem"></i></span>' : ''}
                            </div>
                            <div class="order_info_text">
                                <span class="order_name">${escapeHtml(name)}</span>
                                <div class="order_product_meta">
                                    <span><small>Đơn giá</small><strong>${escapeHtml(productMeta[0])}</strong></span>
                                    <span><small>Số lượng</small><strong>${escapeHtml(productMeta[1])}</strong></span>
                                </div>
                            </div>
                        </div>
                        ${isHighValueOrder ? `
                            <div class="order_hvo_visual" aria-hidden="true">
                                <span class="order_hvo_orb order_hvo_orb--one"></span>
                                <span class="order_hvo_orb order_hvo_orb--two"></span>
                                <i class="fas fa-leaf order_hvo_laurel order_hvo_laurel--left"></i>
                                <i class="fas fa-leaf order_hvo_laurel order_hvo_laurel--right"></i>
                                <i class="fas fa-crown"></i>
                                <span>ĐƠN HÀNG</span>
                                <strong>GIÁ TRỊ CAO</strong>
                                <small>CƠ HỘI GIÁ TRỊ · ƯU TIÊN XỬ LÝ</small>
                            </div>
                        ` : ''}
                    </div>

                    <div class="order_financial">
                        <table class="order_financial_table">
                            <tbody>
                                <tr>
                                    <td><span class="finance_label"><i class="fas fa-cart-shopping"></i><span>${escapeHtml(labels.orderTotal || 'Tổng tiền đơn hàng')}</span></span></td>
                                    <th>${escapeHtml(formatMoney(orderAmount))}</th>
                                </tr>
                                <tr class="commission_row">
                                    <td><span class="finance_label"><i class="fas fa-percent"></i><span>${escapeHtml(labels.commission || 'Chiết khấu')}</span><span class="finance_rate">${escapeHtml(formatPercent(commissionPercent))}</span></span></td>
                                    <th>${escapeHtml(formatMoney(commissionAmount))}</th>
                                </tr>
                                ${isPenalized ? `
                                    <tr class="penalty_row">
                                        <td><span class="finance_label"><i class="fas fa-triangle-exclamation"></i><span>Tiền phạt quá hạn</span></span></td>
                                        <th>-${escapeHtml(formatMoney(penaltyAmount))}</th>
                                    </tr>
                                ` : ''}
                                <tr class="refund_row ${isHighValueOrder ? 'hvo_row' : ''}">
                                    <td><span class="finance_label"><i class="fas fa-wallet"></i><span>${escapeHtml(labels.refund || 'Số tiền hoàn nhập')}</span></span> ${refundSuffix ? `<small>${escapeHtml(refundSuffix.replace('·', '').trim())}</small>` : ''}</td>
                                    <th class="total ${isHighValueOrder ? 'hvo-total' : ''}">${escapeHtml(formatMoney(refundAmount))}</th>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    ${penaltyMarkup({ order, penaltyAmount, orderAmount, isPenaltySettled, penaltySettlement })}
                    ${highValueOrderMarkup(order, isHighValueOrder, isPenalized)}

                    <div class="order_actions">
                        <span class="order_source_note"><i class="fas fa-circle-info"></i>${isHighValueOrder ? 'Đơn hàng giá trị cao theo dữ liệu hệ thống' : 'Dữ liệu tại thời điểm phân phối'}</span>
                        <a class="history-detail-btn ${statusKey === 'pending' ? 'is-primary' : ''}" href="${escapeHtml(detailUrl)}">
                            <span>Xem chi tiết</span><i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </article>
        `;
    };

    const updateCountdowns = () => {
        document.querySelectorAll('[data-history-deadline]').forEach((deadline) => {
            const target = new Date(deadline.dataset.historyDeadline);
            const left = target.getTime() - Date.now();

            if (Number.isNaN(target.getTime())) return;
            if (left <= 0) {
                deadline.className = 'history-deadline is-expired';
                deadline.removeAttribute('data-history-deadline');
                deadline.innerHTML = `
                    <span class="history-deadline__icon"><i class="fas fa-triangle-exclamation"></i></span>
                    <div>
                        <strong>Đã quá thời hạn xử lý</strong>
                        <small>Quá hạn có thể phát sinh tiền phạt 30% giá trị đơn theo quy định hệ thống.</small>
                    </div>
                `;
                return;
            }

            const hours = Math.floor(left / 3600000);
            const minutes = Math.floor((left % 3600000) / 60000);
            const seconds = Math.floor((left % 60000) / 1000);
            deadline.querySelector('[data-hours]')?.replaceChildren(String(hours).padStart(2, '0'));
            deadline.querySelector('[data-minutes]')?.replaceChildren(String(minutes).padStart(2, '0'));
            deadline.querySelector('[data-seconds]')?.replaceChildren(String(seconds).padStart(2, '0'));
            const durationMs = Number(deadline.dataset.historyDurationMs);
            if (Number.isFinite(durationMs) && durationMs > 0) {
                const remainingPercent = Math.max(0, Math.min(100, (left / durationMs) * 100));
                deadline.style.setProperty('--deadline-progress', `${remainingPercent.toFixed(2)}%`);
            }

            deadline.classList.remove('is-safe', 'is-warning', 'is-danger', 'is-critical');
            if (left < 3600000) deadline.classList.add('is-critical');
            else if (left < 3 * 3600000) deadline.classList.add('is-danger');
            else if (left < 6 * 3600000) deadline.classList.add('is-warning');
            else deadline.classList.add('is-safe');
        });
    };

    const setActiveFilter = (button) => {
        document.querySelectorAll('.history-filter-chip').forEach((item) => {
            item.classList.toggle('is-active', item === button);
            item.setAttribute('aria-pressed', item === button ? 'true' : 'false');
        });

        activeFilterId = button.dataset.filterId || 'btn_tat_ca';
        const tab = button.dataset.tab || 'tat-ca';
        localStorage.setItem('tab_order', tab);

        const title = button.querySelector('span')?.textContent?.trim() || 'Đơn hàng';
        const feedTitle = document.getElementById('history-feed-title');
        if (feedTitle) feedTitle.textContent = title === 'Tất cả' ? 'Tất cả đơn hàng' : title;

        button.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
    };

    const loadOrders = async (filterId = activeFilterId) => {
        const list = document.getElementById('list_orders');
        const count = document.getElementById('historyResultCount');
        if (!list || !listRoute) return;

        activeRequest?.abort();
        const request = new AbortController();
        activeRequest = request;
        list.setAttribute('aria-busy', 'true');
        list.innerHTML = loadingMarkup();
        if (count) count.textContent = 'Đang tải';

        try {
            const response = await fetch(listRoute, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                },
                body: JSON.stringify({ tabId: filterId }),
                signal: request.signal,
            });

            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            const payload = await response.json();
            if (payload.status !== 200 || !Array.isArray(payload.list_orders)) {
                throw new Error(payload.message || 'Không thể tải lịch sử đơn hàng');
            }

            const orders = payload.list_orders;
            if (count) count.textContent = `${orders.length} đơn`;
            list.innerHTML = orders.length
                ? orders.map(renderOrder).join('')
                : stateMarkup('empty', 'Chưa có đơn hàng trong mục này', labels.empty || 'Không có dữ liệu');
            updateCountdowns();
        } catch (error) {
            if (error.name === 'AbortError') return;
            console.error('Order history load failed:', error);
            if (count) count.textContent = 'Không tải được';
            list.innerHTML = stateMarkup(
                'error',
                'Không thể tải lịch sử',
                'Kết nối hoặc máy chủ đang có vấn đề. Bạn có thể thử tải lại danh sách.',
                true,
            );
        } finally {
            if (activeRequest === request) {
                list.setAttribute('aria-busy', 'false');
            }
        }
    };

    document.addEventListener('DOMContentLoaded', () => {
        const savedTab = localStorage.getItem('tab_order') || 'tat-ca';
        const initialButton = Array.from(document.querySelectorAll('.history-filter-chip'))
            .find((button) => button.dataset.tab === savedTab)
            || document.querySelector('.history-filter-chip[data-tab="tat-ca"]');

        if (initialButton) {
            setActiveFilter(initialButton);
            loadOrders(activeFilterId);
        }

        document.querySelectorAll('.history-filter-chip').forEach((button) => {
            button.addEventListener('click', () => {
                if (button.classList.contains('is-active')) return;
                setActiveFilter(button);
                loadOrders(activeFilterId);
            });
        });

        document.getElementById('list_orders')?.addEventListener('click', (event) => {
            if (!event.target.closest('[data-history-retry]')) return;
            loadOrders(activeFilterId);
        });

        window.setInterval(updateCountdowns, 1000);
    });
})();
