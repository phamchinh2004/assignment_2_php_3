@php
    $featureAnnouncementPayload = ($featureAnnouncements ?? collect())->map(fn ($announcement) => [
        'id' => $announcement->id,
        'title' => $announcement->title,
        'content' => $announcement->content,
        'priority' => $announcement->priority,
        'action_text' => $announcement->action_text,
        'action_url' => $announcement->action_url,
        'image_url' => $announcement->image_path
            ? Storage::disk('public')->url($announcement->image_path)
            : null,
        'acknowledge_url' => route('feature_announcements.acknowledge', $announcement),
    ])->values();
@endphp

<style>
    .feature-announcement-overlay {
        position: fixed;
        inset: 0;
        z-index: 20000;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 20px;
        background: rgba(15, 23, 42, 0.68);
        backdrop-filter: blur(2px);
    }

    .feature-announcement-overlay.is-visible {
        display: flex;
    }

    .feature-announcement-dialog {
        width: min(760px, 100%);
        max-height: calc(100vh - 40px);
        overflow-y: auto;
        background: #ffffff;
        border-radius: 18px;
        box-shadow: 0 24px 60px rgba(15, 23, 42, 0.28);
    }

    .feature-announcement-dialog__header {
        padding: 24px 24px 10px;
    }

    .feature-announcement-dialog__body {
        padding: 12px 24px 24px;
    }

    .feature-announcement-dialog__meta {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 10px;
    }

    .feature-announcement-dialog__image {
        width: 100%;
        max-height: 340px;
        object-fit: contain;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        background: #f8fafc;
        margin-bottom: 18px;
    }

    .feature-announcement-dialog__content {
        white-space: pre-wrap;
        line-height: 1.65;
        color: #475569;
        margin-bottom: 20px;
    }

    .feature-announcement-dialog__actions {
        display: flex;
        justify-content: flex-end;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }

    .feature-announcement-dialog__error {
        display: none;
        margin-bottom: 16px;
    }

    @media (max-width: 576px) {
        .feature-announcement-overlay { padding: 10px; }
        .feature-announcement-dialog__header { padding: 18px 18px 8px; }
        .feature-announcement-dialog__body { padding: 10px 18px 18px; }
        .feature-announcement-dialog__actions { align-items: stretch; flex-direction: column; }
        .feature-announcement-dialog__actions .btn { width: 100%; }
    }
</style>

<div id="featureAnnouncementOverlay" class="feature-announcement-overlay" aria-hidden="true">
    <div class="feature-announcement-dialog" role="dialog" aria-modal="true" aria-labelledby="featureAnnouncementTitle">
        <div class="feature-announcement-dialog__header">
            <div class="feature-announcement-dialog__meta">
                <span class="badge badge-primary px-3 py-2">Tính năng mới</span>
                <span class="text-muted small" id="featureAnnouncementCounter"></span>
            </div>
            <div class="mb-2">
                <span class="badge" id="featureAnnouncementPriority"></span>
            </div>
            <h4 class="font-weight-bold mb-0" id="featureAnnouncementTitle"></h4>
        </div>

        <div class="feature-announcement-dialog__body">
            <img id="featureAnnouncementImage" class="feature-announcement-dialog__image d-none" alt="Ảnh thông báo">
            <div id="featureAnnouncementContent" class="feature-announcement-dialog__content"></div>
            <div class="alert alert-danger feature-announcement-dialog__error" id="featureAnnouncementError"></div>

            <div class="feature-announcement-dialog__actions">
                <a id="featureAnnouncementAction" class="btn btn-outline-primary d-none"
                    target="_blank" rel="noopener noreferrer"></a>
                <button type="button" id="featureAnnouncementAcknowledge" class="btn btn-primary px-4">
                    <i class="fas fa-check mr-1"></i> Đã nắm rõ
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        let announcements = @json($featureAnnouncementPayload);
        let currentIndex = 0;
        let syncInFlight = false;

        const unreadUrl = @json(route('feature_announcements.unread'));
        const overlay = document.getElementById('featureAnnouncementOverlay');
        const title = document.getElementById('featureAnnouncementTitle');
        const content = document.getElementById('featureAnnouncementContent');
        const counter = document.getElementById('featureAnnouncementCounter');
        const priority = document.getElementById('featureAnnouncementPriority');
        const image = document.getElementById('featureAnnouncementImage');
        const action = document.getElementById('featureAnnouncementAction');
        const acknowledge = document.getElementById('featureAnnouncementAcknowledge');
        const error = document.getElementById('featureAnnouncementError');
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content;

        if (!overlay || !acknowledge) return;

        const priorityMap = {
            normal: { label: 'Bình thường', className: 'badge-secondary' },
            important: { label: 'Quan trọng', className: 'badge-warning' },
            critical: { label: 'Khẩn cấp', className: 'badge-danger' },
        };

        function overlayIsOpen() {
            return overlay.classList.contains('is-visible');
        }

        function showOverlay() {
            overlay.classList.add('is-visible');
            overlay.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
        }

        function hideOverlay() {
            overlay.classList.remove('is-visible');
            overlay.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
        }

        function renderCurrent() {
            const item = announcements[currentIndex];

            if (!item) {
                announcements = [];
                currentIndex = 0;
                hideOverlay();
                window.setTimeout(syncUnread, 500);
                return;
            }

            const priorityState = priorityMap[item.priority] || priorityMap.normal;
            title.textContent = item.title || '';
            content.textContent = item.content || '';
            counter.textContent = `${currentIndex + 1} / ${announcements.length}`;
            priority.textContent = priorityState.label;
            priority.className = `badge ${priorityState.className}`;
            error.style.display = 'none';
            error.textContent = '';

            if (item.image_url) {
                image.src = item.image_url;
                image.classList.remove('d-none');
            } else {
                image.removeAttribute('src');
                image.classList.add('d-none');
            }

            if (item.action_text && item.action_url) {
                action.textContent = item.action_text;
                action.href = item.action_url;
                action.classList.remove('d-none');
            } else {
                action.removeAttribute('href');
                action.classList.add('d-none');
            }

            acknowledge.disabled = false;
            acknowledge.innerHTML = '<i class="fas fa-check mr-1"></i> Đã nắm rõ';
            showOverlay();
        }

        async function syncUnread() {
            if (syncInFlight || overlayIsOpen()) return;

            syncInFlight = true;

            try {
                const response = await fetch(unreadUrl, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    cache: 'no-store',
                });

                if (!response.ok) return;

                const payload = await response.json();
                announcements = Array.isArray(payload.announcements) ? payload.announcements : [];
                currentIndex = 0;

                if (announcements.length) {
                    renderCurrent();
                }
            } catch (requestError) {
                console.warn('Không thể tải thông báo tính năng mới.', requestError);
            } finally {
                syncInFlight = false;
            }
        }

        acknowledge.addEventListener('click', async function () {
            const item = announcements[currentIndex];
            if (!item) return;

            acknowledge.disabled = true;
            acknowledge.innerHTML = '<span class="spinner-border spinner-border-sm mr-1"></span> Đang lưu...';

            try {
                const response = await fetch(item.acknowledge_url, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                if (response.ok || response.status === 404 || response.status === 409) {
                    currentIndex += 1;
                    renderCurrent();
                    return;
                }

                const payload = await response.json().catch(() => ({}));
                throw new Error(payload.message || 'Không thể ghi nhận xác nhận. Vui lòng thử lại.');
            } catch (requestError) {
                error.textContent = requestError.message;
                error.style.display = 'block';
                acknowledge.disabled = false;
                acknowledge.innerHTML = '<i class="fas fa-check mr-1"></i> Đã nắm rõ';
            }
        });

        if (announcements.length) {
            renderCurrent();
        } else {
            syncUnread();
        }

        window.addEventListener('focus', syncUnread);
        document.addEventListener('visibilitychange', function () {
            if (!document.hidden) syncUnread();
        });
        window.setInterval(syncUnread, 10000);
    });
</script>
