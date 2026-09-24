@php
    $featureAnnouncementPayload = ($featureAnnouncements ?? collect())->map(fn ($announcement) => [
        'id' => $announcement->id,
        'title' => $announcement->title,
        'content' => $announcement->content,
        'priority' => $announcement->priority,
        'action_text' => $announcement->action_text,
        'action_url' => $announcement->action_url,
        'image_url' => $announcement->image_path ? Storage::url($announcement->image_path) : null,
        'acknowledge_url' => route('feature_announcements.acknowledge', $announcement),
    ])->values();
@endphp

@if($featureAnnouncementPayload->isNotEmpty())
    <style>
        .feature-announcement-modal .modal-content { border: 0; border-radius: 18px; overflow: hidden; }
        .feature-announcement-modal .modal-header { border-bottom: 0; padding: 24px 24px 8px; }
        .feature-announcement-modal .modal-body { padding: 12px 24px 24px; }
        .feature-announcement-modal .announcement-image { width: 100%; max-height: 320px; object-fit: contain; border-radius: 12px; background: #f8f9fc; }
        .feature-announcement-modal .announcement-content { white-space: pre-wrap; line-height: 1.65; color: #4a5568; }
        .feature-announcement-modal .announcement-meta { display: flex; align-items: center; justify-content: space-between; gap: 12px; }
        .feature-announcement-modal .announcement-error { display: none; }
    </style>

    <div class="modal fade feature-announcement-modal" id="featureAnnouncementModal" tabindex="-1"
        role="dialog" aria-labelledby="featureAnnouncementTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header d-block">
                    <div class="announcement-meta mb-2">
                        <span class="badge badge-primary px-3 py-2">Tính năng mới</span>
                        <span class="text-muted small" id="featureAnnouncementCounter"></span>
                    </div>
                    <div class="mb-2">
                        <span class="badge" id="featureAnnouncementPriority"></span>
                    </div>
                    <h4 class="modal-title font-weight-bold" id="featureAnnouncementTitle"></h4>
                </div>
                <div class="modal-body">
                    <img id="featureAnnouncementImage" class="announcement-image mb-3 d-none" alt="Ảnh thông báo">
                    <div id="featureAnnouncementContent" class="announcement-content mb-4"></div>
                    <div class="alert alert-danger announcement-error" id="featureAnnouncementError"></div>

                    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-stretch align-items-sm-center" style="gap: 10px;">
                        <a id="featureAnnouncementAction" class="btn btn-outline-primary d-none"
                            target="_blank" rel="noopener noreferrer"></a>
                        <button type="button" id="featureAnnouncementAcknowledge" class="btn btn-primary ml-sm-auto px-4">
                            <i class="fas fa-check mr-1"></i> Đã nắm rõ
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const announcements = @json($featureAnnouncementPayload);
            if (!announcements.length || !window.jQuery) return;

            let currentIndex = 0;
            const modal = window.jQuery('#featureAnnouncementModal');
            const title = document.getElementById('featureAnnouncementTitle');
            const content = document.getElementById('featureAnnouncementContent');
            const counter = document.getElementById('featureAnnouncementCounter');
            const priority = document.getElementById('featureAnnouncementPriority');
            const image = document.getElementById('featureAnnouncementImage');
            const action = document.getElementById('featureAnnouncementAction');
            const acknowledge = document.getElementById('featureAnnouncementAcknowledge');
            const error = document.getElementById('featureAnnouncementError');
            const csrf = document.querySelector('meta[name="csrf-token"]')?.content;

            const priorityMap = {
                normal: { label: 'Bình thường', className: 'badge-secondary' },
                important: { label: 'Quan trọng', className: 'badge-warning' },
                critical: { label: 'Khẩn cấp', className: 'badge-danger' },
            };

            function renderCurrent() {
                const item = announcements[currentIndex];
                if (!item) {
                    modal.modal('hide');
                    return;
                }

                const priorityState = priorityMap[item.priority] || priorityMap.normal;
                title.textContent = item.title;
                content.textContent = item.content;
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

            renderCurrent();
            modal.modal({
                backdrop: 'static',
                keyboard: false,
                show: true,
            });
        });
    </script>
@endif
