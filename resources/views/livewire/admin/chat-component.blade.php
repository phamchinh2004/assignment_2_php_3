@php
    use Illuminate\Support\Facades\Storage;
    $isOwner = auth()->user()->role === \App\Models\User::ROLE_OWNER;
    $isManagementUser = in_array(auth()->user()->role, \App\Models\User::MANAGEMENT_ROLES, true);
    $canManageAllChats = app(\App\Services\AuthorizationService::class)->can(
        auth()->user(),
        config('authorization.capabilities.chats_view_all')
    );
@endphp

@push('css')
    @vite('resources/css/admin/chat.css')
@endpush

<div class="chat-workspace" id="chat-root" data-chat-conversation="{{ $selectedConversationId }}" data-chat-viewer="{{ auth()->id() }}"
    x-data="{
        mobileListOpen: !$wire.selectedConversationId,
        contextOpen: window.matchMedia('(min-width: 1280px)').matches,
        wide: window.matchMedia('(min-width: 1280px)').matches,
        syncLayout() {
            const nextWide = window.matchMedia('(min-width: 1280px)').matches;
            if (nextWide !== this.wide) this.contextOpen = nextWide;
            this.wide = nextWide;
        },
        closeContext() {
            this.contextOpen = false;
            this.$nextTick(() => this.$refs.contextToggle?.focus());
        }
    }"
    x-init="$watch('$wire.selectedConversationId', value => { mobileListOpen = !value; contextOpen = wide; })"
    x-on:resize.window.debounce.100ms="syncLayout()"
    x-on:conversation-selected.window="mobileListOpen = false"
    x-on:chat-quick-message-selected.window="if (!wide) { contextOpen = false; $nextTick(() => document.getElementById('message-input-textarea')?.focus()); }"
    x-on:keydown.escape="if (contextOpen && !$event.defaultPrevented && !document.getElementById('zoomModal')?.classList.contains('active')) closeContext()"
    :class="{ 'is-mobile-list': mobileListOpen, 'is-context-open': contextOpen }">
    <aside class="chat-sidebar-shell" aria-label="Danh sách hội thoại">
        @include('livewire.admin.sidebar-chat', ['isMobile' => false])
    </aside>
    <div class="chat-panel-resizer chat-sidebar-resizer" data-chat-resizer="left" role="separator" tabindex="0"
        aria-orientation="vertical" aria-label="Điều chỉnh độ rộng danh sách hội thoại"
        aria-valuemin="220" aria-valuemax="520" title="Kéo để thay đổi độ rộng · Nhấp đúp để đặt lại"></div>

    @if($dispatchConversationId)
        @php
            $dispatchingConversation = $conversations->firstWhere('id', $dispatchConversationId);
        @endphp
        @if($dispatchingConversation)
            <div class="chat-dispatch-overlay" wire:key="chat-dispatch-dialog" wire:click.self="closeDispatchDialog"
                x-data x-on:keydown.escape.window="$wire.closeDispatchDialog()">
                <div class="chat-dispatch-dialog" role="dialog" aria-modal="true" aria-labelledby="chat-dispatch-title">
                    <div class="chat-dispatch-heading">
                        <div>
                            <h2 id="chat-dispatch-title">Điều phối hội thoại</h2>
                            <p>Chọn người tiếp nhận hội thoại với <img src="{{ get_user_avatar($dispatchingConversation->user) }}" alt="" width="28" height="28" class="rounded-circle" style="object-fit: cover;"> <strong>{{ $dispatchingConversation->user->full_name }}</strong>.</p>
                        </div>
                        <button type="button" class="chat-dispatch-close" wire:click="closeDispatchDialog"
                            x-ref="dispatchClose" x-init="$nextTick(() => $refs.dispatchClose.focus())" aria-label="Đóng điều phối">
                            <i class="fas fa-times" aria-hidden="true"></i>
                        </button>
                    </div>
                    <div class="chat-dispatch-candidates">
                        @forelse($this->dispatchCandidates as $operator)
                            <button type="button" class="chat-dispatch-candidate"
                                wire:click="dispatchConversationTo({{ $operator->id }})"
                                wire:loading.attr="disabled" wire:target="dispatchConversationTo"
                                @disabled((int) $dispatchingConversation->staff_id === (int) $operator->id)>
                                <span class="chat-dispatch-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($operator->full_name, 0, 1)) }}</span>
                                <span class="chat-dispatch-operator">
                                    <strong>{{ $operator->full_name }}</strong>
                                    <small>{{ $operator->role === \App\Models\User::ROLE_ADMIN ? 'Admin' : 'Nhân viên' }}</small>
                                </span>
                                @if((int) $dispatchingConversation->staff_id === (int) $operator->id)
                                    <span class="chat-dispatch-current">Đang phụ trách</span>
                                @else
                                    <i class="fas fa-chevron-right" aria-hidden="true"></i>
                                @endif
                            </button>
                        @empty
                            <p class="chat-dispatch-empty">Chưa có nhân viên đang hoạt động để tiếp nhận.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        @endif
    @endif

    <!-- Khu vực chat chính -->
    <div class="chat-main position-relative {{ $this->selectedConversation ? 'has-conversation' : '' }}">
        <!-- Loading Spinner Overlay: Tự động hiện khi chọn cuộc hội thoại -->
        <div id="chat-loading-spinner"
            wire:loading
            wire:target="selectConversation, selectUserForChat, openConversationFromNotification"
            class="chat-loading-overlay position-absolute w-100 h-100 d-none align-items-center justify-content-center"
            role="status" aria-live="polite"
            wire:loading.class.remove="d-none"
            wire:loading.class="d-flex">
            <div class="chat-loading-card text-center">
                <div class="spinner-border text-primary mb-3" role="status">
                    <span class="visually-hidden">Đang tải...</span>
                </div>
                <p class="text-muted fw-semibold mb-0">Đang tải hội thoại...</p>
            </div>
        </div>

        @if($this->selectedConversation)
            <!-- Header chat -->
            <div wire:key="chat-header-{{ $this->selectedConversation->id }}"
                class="chat-header" :inert="contextOpen && !wide">
                <div class="chat-identity-row d-flex align-items-center">
                    <button type="button" class="chat-back-button chat-icon-button" @click="mobileListOpen = true; contextOpen = false; $nextTick(() => $refs.inboxSearch.focus())"
                        aria-label="Quay lại danh sách hội thoại" title="Hộp thư">
                        <i class="fas fa-arrow-left" aria-hidden="true"></i>
                    </button>
                    <div class="chat-contact-avatar position-relative">
                        @if($this->selectedConversation->user->avatar && Storage::disk('public')->exists($this->selectedConversation->user->avatar))
                            <img src="{{ asset('storage/' . $this->selectedConversation->user->avatar) }}"
                                alt="{{ $this->selectedConversation->user->full_name }}" class="rounded-circle"
                                style="width: 100%; height: 100%; object-fit: cover;">
                        @else
                            <div class="bg-primary w-100 h-100 d-flex align-items-center justify-content-center rounded-circle">
                                <i class="fas fa-user text-white" style="font-size: 20px;"></i>
                            </div>
                        @endif
                        @php
                            $isOnline = $this->selectedConversation->user->last_seen &&
                                $this->selectedConversation->user->last_seen->diffInMinutes(now()) <= 5;
                        @endphp
                    </div>
                    <div class="chat-contact-info flex-grow-1">
                        <div class="chat-contact-heading d-flex flex-wrap align-items-center gap-2">
                            <h2 class="chat-contact-name mb-0">
                                @if($this->selectedConversation->user->hasPenalizedOrders())
                                    <i class="fas fa-exclamation-triangle text-warning me-1" title="Người dùng đang bị phạt"></i>
                                @endif
                                {{ $this->selectedConversation->user->full_name }}
                            </h2>

                            

                        </div>
                       <span class="chat-header-chip">
                                <span class="rounded-circle me-1 {{ $isOnline ? 'bg-success' : 'bg-secondary' }}" style="width: 7px; height: 7px; display: inline-block;"></span>
                                {{ $isOnline ? 'Đang hoạt động' : ($this->selectedConversation->user->last_seen ? 'Hoạt động ' . $this->selectedConversation->user->last_seen->diffForHumans() : 'Chưa từng online') }}
                            </span>
                    </div>
                    @php
                        $conversationNotificationMute = $this->selectedConversationNotificationMute;
                    @endphp
                    <div class="chat-header-actions d-flex gap-2">
                        @if(in_array(auth()->user()->role, [\App\Models\User::ROLE_ADMIN, \App\Models\User::ROLE_OWNER], true))
                            <button type="button" class="chat-dispatch-button" wire:click="openDispatchDialog({{ $this->selectedConversation->id }})"
                                wire:loading.attr="disabled" wire:target="openDispatchDialog" aria-label="Điều phối hội thoại" title="Điều phối hội thoại">
                                <i class="fas fa-share" aria-hidden="true"></i><span>Điều phối</span>
                            </button>
                        @endif
                        <button type="button" class="chat-icon-button" x-ref="contextToggle" @click="contextOpen = !contextOpen; if (contextOpen && !wide) $nextTick(() => $refs.contextClose.focus())"
                            :aria-expanded="contextOpen" aria-controls="chat-context-panel" aria-label="Thông tin khách hàng và trả lời nhanh" title="Thông tin khách hàng">
                            <i class="fas fa-circle-info" aria-hidden="true"></i>
                        </button>
                        <div class="dropdown">
                            <button class="btn chat-icon-button {{ $conversationNotificationMute ? 'is-muted' : '' }}"
                                type="button" data-bs-toggle="dropdown" aria-expanded="false"
                                title="{{ $conversationNotificationMute ? 'Đang tắt thông báo hội thoại' : 'Tắt thông báo hội thoại' }}"
                                aria-label="{{ $conversationNotificationMute ? 'Đang tắt thông báo hội thoại' : 'Tắt thông báo hội thoại' }}">
                                <i class="fas {{ $conversationNotificationMute ? 'fa-bell-slash' : 'fa-bell' }}" aria-hidden="true"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end chat-mute-menu">
                                @if($conversationNotificationMute)
                                    <li>
                                        <span class="dropdown-item-text chat-mute-status">
                                            <i class="fas fa-bell-slash me-2"></i>
                                            @if($conversationNotificationMute->muted_until)
                                                Đã tắt đến {{ $conversationNotificationMute->muted_until->format('H:i d/m/Y') }}
                                            @else
                                                Đã tắt đến khi bật lại
                                            @endif
                                        </span>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <button type="button" class="dropdown-item text-primary"
                                            wire:click="unmuteSelectedConversation">
                                            <i class="fas fa-bell me-2"></i>Bật lại thông báo
                                        </button>
                                    </li>
                                @else
                                    <li><h6 class="dropdown-header">Tắt thông báo trong</h6></li>
                                    <li>
                                        <button type="button" class="dropdown-item" wire:click="muteSelectedConversation('15m')">
                                            <i class="far fa-clock me-2"></i>15 phút
                                        </button>
                                    </li>
                                    <li>
                                        <button type="button" class="dropdown-item" wire:click="muteSelectedConversation('1h')">
                                            <i class="far fa-clock me-2"></i>1 giờ
                                        </button>
                                    </li>
                                    <li>
                                        <button type="button" class="dropdown-item" wire:click="muteSelectedConversation('8h')">
                                            <i class="far fa-clock me-2"></i>8 giờ
                                        </button>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <button type="button" class="dropdown-item" wire:click="muteSelectedConversation('forever')">
                                            <i class="fas fa-infinity me-2"></i>Đến khi bật lại
                                        </button>
                                    </li>
                                @endif
                            </ul>
                        </div>
                        <div class="dropdown">
                            <button class="btn chat-icon-button" type="button" data-bs-toggle="dropdown"
                                aria-expanded="false" title="Thao tác hội thoại" aria-label="Thao tác hội thoại">
                                <i class="fas fa-ellipsis-h" aria-hidden="true"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a class="dropdown-item text-primary"
                                    href="{{ route('user.index') }}#user-{{ $this->selectedConversation->user->id }}">
                                    <i class="fas fa-user-cog me-2"></i>Quản lý tài khoản
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item text-warning"
                                    href="{{ route('user.frozen.order.interface', $this->selectedConversation->user->id) }}">
                                    <i class="fas fa-snowflake me-2"></i>Đóng băng đơn hàng
                                </a>
                            </li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            @if($this->selectedConversation->user->status === "activated")
                                <li>
                                    <a class="dropdown-item text-danger" href="javascript:void(0)"
                                        onclick="confirmChangeStatusOfUser({{ $this->selectedConversation->user->id }},'{{ $this->selectedConversation->user->status }}')">
                                        <i class="fas fa-lock me-2"></i>Khóa tài khoản
                                    </a>
                                </li>
                            @elseif($this->selectedConversation->user->status === "banned")
                                <li>
                                    <a class="dropdown-item text-success" href="javascript:void(0)"
                                        onclick="confirmChangeStatusOfUser({{ $this->selectedConversation->user->id }},'{{ $this->selectedConversation->user->status }}')">
                                        <i class="fas fa-lock-open me-2"></i>Mở khóa tài khoản
                                    </a>
                                </li>
                            @endif
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            @if($isOwner)
                                <li>
                                    <a class="dropdown-item text-warning" href="javascript:void(0)"
                                        onclick="confirmDeleteMessages()">
                                        <i class="fas fa-eraser me-2"></i>Xóa tin nhắn
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item text-danger" href="javascript:void(0)"
                                        onclick="confirmDeleteConversation()">
                                        <i class="fas fa-trash-alt me-2"></i>Xóa hội thoại
                                    </a>
                                </li>
                            @endif
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Khu vực tin nhắn -->
            <div wire:key="messages-container-{{ $this->selectedConversation->id }}"
                class="chat-message-list flex-grow-1 overflow-auto custom-scrollbar position-relative" id="messages-container"
                aria-label="Tin nhắn trong hội thoại" :inert="contextOpen && !wide">

                @if (empty($messages))
                    <div class="w-100 h-100 d-flex justify-content-center align-items-center">
                        <div class="flex-grow-1 d-flex align-items-center justify-content-center">
                            <div class="chat-empty-state text-center">
                                <div class="chat-empty-icon mx-auto">
                                    <i class="far fa-comment-dots" aria-hidden="true"></i>
                                </div>
                                <h3>Bắt đầu cuộc trò chuyện</h3>
                                <p>Gửi lời chào đầu tiên hoặc mở mẫu trả lời nhanh để hỗ trợ khách hàng.</p>
                            </div>
                        </div>
                    </div>
                @else
                    <!-- Nội dung tin nhắn: dùng column-reverse để đảo ngược CSS (tin nhắn mới nhất index 0 sẽ ghim xuống dưới đáy) -->
                    <div class="chat-message-thread">

                        @foreach($messages as $index => $message)
                            @php
                                $isCurrentUser = $message['sender_id'] == auth()->id();
                                $currentUserRole = auth()->user()->role;
                                $senderRole = $message['sender']['role'] ?? 'member';
                                $messageKind = $message['kind'] ?? $message['type'] ?? 'text';
                                $isReferenceMessage = str_ends_with($messageKind, '_reference');
                                $isImageMessage = !$isReferenceMessage && !empty($message['image_path']);
                                $messageDate = \Carbon\Carbon::parse($message['created_at'])->setTimezone('Asia/Ho_Chi_Minh');
                                $messageMinute = $messageDate->format('Y-m-d H:i');
                                $newerMessage = $messages[$index - 1] ?? null;
                                $olderMessage = $messages[$index + 1] ?? null;
                                $isGroupedWithNewer = $newerMessage
                                    && (int) ($newerMessage['sender_id'] ?? 0) === (int) $message['sender_id']
                                    && \Carbon\Carbon::parse($newerMessage['created_at'])->setTimezone('Asia/Ho_Chi_Minh')->format('Y-m-d H:i') === $messageMinute;
                                $isGroupedWithOlder = $olderMessage
                                    && (int) ($olderMessage['sender_id'] ?? 0) === (int) $message['sender_id']
                                    && \Carbon\Carbon::parse($olderMessage['created_at'])->setTimezone('Asia/Ho_Chi_Minh')->format('Y-m-d H:i') === $messageMinute;
                                $isGroupFooter = !$isGroupedWithNewer;
                                $olderCreatedAt = $olderMessage['created_at'] ?? null;
                                $showDateSeparator = !$olderCreatedAt
                                    || !$messageDate->isSameDay(\Carbon\Carbon::parse($olderCreatedAt)->setTimezone('Asia/Ho_Chi_Minh'));

                                // Xác định classes cho message
                                if ($isCurrentUser) {
                                    $containerClass = 'justify-content-end';
                                    $bubbleClass = 'sent-message text-white';
                                    $tailClass = '';
                                    $tailColor = 'transparent';
                                } else {
                                    // Tin nhắn của người khác - luôn hiển thị bên trái
                                    $containerClass = 'justify-content-start';
                                    $tailClass = '';

                                    switch ($senderRole) {
                                        case 'own':
                                            $bubbleClass = 'admin-message text-white';
                                            $tailColor = '#7c3aed';
                                            break;
                                        case 'admin':
                                            $bubbleClass = 'admin-message text-white';
                                            $tailColor = '#dc3545';
                                            break;
                                        case 'staff':
                                            $bubbleClass = 'staff-message text-white';
                                            $tailColor = '#198754';
                                            break;
                                        case 'member':
                                            $bubbleClass = 'member-message text-dark';
                                            $tailColor = '#f0f0f0';
                                            break;
                                        default:
                                            $bubbleClass = 'member-message text-dark';
                                            $tailColor = '#ffffff';
                                    }
                                }
                            @endphp

                            <div class="message-item d-flex {{ $containerClass }} {{ $isCurrentUser ? 'is-own-message' : 'is-other-message' }} {{ $isGroupedWithNewer ? 'is-grouped-with-newer' : 'is-message-group-footer' }} {{ $isGroupedWithOlder ? 'is-grouped-with-older' : 'is-message-group-start' }}"
                                wire:key="message-{{ $message['id'] ?? $index }}">
                                @if(!$isCurrentUser)
                                    @if($isGroupFooter)
                                        <span class="chat-message-avatar avatar-tone-{{ abs(crc32((string) $message['sender_id'])) % 6 }}" title="{{ $message['sender']['full_name'] ?? 'Người gửi' }}">
                                            @if(!empty($message['sender']['avatar']) && Storage::disk('public')->exists($message['sender']['avatar']))
                                                <img src="{{ Storage::disk('public')->url($message['sender']['avatar']) }}" alt="{{ $message['sender']['full_name'] ?? 'Người gửi' }}" loading="lazy">
                                            @else
                                                <span aria-label="{{ $message['sender']['full_name'] ?? 'Người gửi' }}">{{ mb_strtoupper(mb_substr($message['sender']['full_name'] ?? '?', 0, 1)) }}</span>
                                            @endif
                                        </span>
                                    @else
                                        <span class="chat-message-avatar-spacer" aria-hidden="true"></span>
                                    @endif
                                @endif
                                <div class="chat-message-body">
                                <div class="position-relative {{ $isReferenceMessage ? 'chat-structured-message admin-chat-structured-message' : ($isImageMessage ? 'admin-chat-image-message ' . ($isCurrentUser ? 'is-sent' : 'is-received') : 'message-bubble ' . $bubbleClass) }}">

                                    <!-- Nội dung tin nhắn -->
                                    @if($isReferenceMessage)
                                        <x-chat.reference-card :message="$message" audience="admin" />
                                    @elseif(isset($message['image_path']) && $message['image_path'])
                                        <div class="panzoom-parent admin-chat-image-frame">
                                            <span class="admin-chat-image-loading" aria-hidden="true"><i class="fas fa-circle-notch fa-spin"></i></span>
                                            <img src="{{ Storage::disk('public')->url($message['image_path']) }}" alt="Ảnh"
                                                class="zoomable-image" loading="lazy" decoding="async">
                                            <span class="admin-chat-image-error"><i class="fas fa-image"></i> Không thể tải ảnh</span>
                                        </div>
                                        @if(!empty($message['message']) && trim($message['message']) !== '')
                                            <div class="chat-message-content admin-chat-image-caption">{{ trim($message['message']) }}</div>
                                        @endif
                                    @elseif($message['message'])
                                        <div class="chat-message-content">{{ trim($message['message']) }}</div>
                                    @endif

                                    <!-- Thao tác tin nhắn -->
                                    @if(($isCurrentUser && ($message['type'] ?? 'text') === 'text') || $isOwner)
                                    <div class="message-actions d-flex" aria-label="Thao tác tin nhắn">
                                        @if($isCurrentUser && ($message['type'] ?? 'text') === 'text')
                                            <button type="button" class="btn btn-link btn-sm text-muted" title="Sửa tin nhắn" aria-label="Sửa tin nhắn"
                                                    wire:click="editMessage({{ $message['id'] }})">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        @endif
                                        @if($isOwner)
                                            <button type="button" class="btn btn-link btn-sm text-danger" title="Xóa tin nhắn" aria-label="Xóa tin nhắn"
                                                    onclick="confirmDeleteSingleMessage({{ $message['id'] }})">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        @endif
                                    </div>
                                    @endif

                                </div>
                                    @if($isGroupFooter)
                                        <!-- Thời gian và trạng thái của tin nhắn cuối cụm -->
                                        <div class="chat-message-meta d-flex flex-wrap align-items-center gap-2">
                                            <time datetime="{{ $messageDate->toIso8601String() }}" title="{{ $messageDate->format('d/m/Y H:i') }}">
                                                {{ $messageDate->format('H:i') }}
                                            </time>
                                            <span class="chat-read-status" data-message-id="{{ $message['id'] }}"
                                                data-seen-status="{{ ($message['is_read'] ?? false) ? 'true' : 'false' }}"
                                                title="{{ (int) $message['sender_id'] === (int) $this->selectedConversation->user_id ? 'Trạng thái đọc của nhân viên phụ trách' : 'Trạng thái đọc của khách hàng' }}">
                                                <i class="fas {{ ($message['is_read'] ?? false) ? 'fa-check-double' : 'fa-check' }}" aria-hidden="true"></i>
                                                <!-- <span>{{ ($message['is_read'] ?? false) ? 'Đã đọc' : 'Chưa đọc' }}</span> -->
                                            </span>
                                        </div>
                                    @endif

                                </div>
                            </div>

                            <div class="chat-date-separator" wire:key="date-separator-{{ $message['id'] ?? $index }}"
                                @if(!$showDateSeparator) hidden @endif aria-label="{{ $messageDate->format('d/m/Y') }}">
                                <span data-local-datetime="{{ $messageDate->toIso8601String() }}"
                                    data-local-format="chat-date" data-time-zone="Asia/Ho_Chi_Minh">{{ $messageDate->locale('vi')->translatedFormat('d M Y') }}</span>
                            </div>
                        @endforeach

                        <!-- Indicators cho load more đặt sau foreach để column-reverse lật nó lên trên đỉnh -->
                        @if($hasMoreMessages)
                            <div class="text-center py-2 mb-2" wire:loading wire:target="loadMoreMessages">
                                <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                                <small class="text-muted ms-2">Đang tải tin nhắn cũ hơn...</small>
                            </div>

                            <div class="text-center py-2 mb-3" wire:loading.remove wire:target="loadMoreMessages">
                                <button onclick="loadMoreMessagesAdmin()" class="btn btn-sm btn-outline-primary rounded-pill" type="button">
                                    <i class="fas fa-chevron-up me-1"></i>
                                    Tải tin nhắn cũ hơn
                                </button>
                            </div>
                        @endif

                    </div><!-- End wrapper -->
                @endif

            </div>

            <!-- Input tin nhắn -->
            <div wire:key="message-input-{{ $this->selectedConversation->id }}"
                class="message-input position-relative" :inert="contextOpen && !wide">
                <div class="chat-typing-status" data-chat-typing-status wire:ignore hidden role="status" aria-live="polite">
                    <span class="chat-typing-dots" aria-hidden="true"><i></i><i></i><i></i></span>
                    <span data-chat-typing-label></span>
                </div>
                @if(count($quickMessages) > 0)
                    <div class="chat-quick-strip" aria-label="Tin nhắn nhanh">
                        @foreach($quickMessages as $messageKey => $messageText)
                            <button type="button" class="chat-quick-chip" data-message="{{ $messageText }}"
                                onclick="copyQuickMessage(this.dataset.message)" title="{{ $messageText }}" aria-label="{{ $messageText }}">
                                {{ implode(' ', array_slice(preg_split('/\s+/u', trim($messageText)), 0, 2)) }}...
                            </button>
                        @endforeach
                        <button type="button" class="chat-quick-access" @click="contextOpen = true; $nextTick(() => { $refs.quickMessages.scrollIntoView({ block: 'nearest' }); $refs.quickMessages.focus(); })" aria-label="Quản lý tin nhắn nhanh" title="Quản lý tin nhắn nhanh"><i class="fas fa-sliders" aria-hidden="true"></i></button>
                    </div>
                @endif

                @if($editingMessageId)
                    <div class="editing-banner bg-light p-2 mb-2 border rounded-3 d-flex justify-content-between align-items-center">
                        <span class="small text-primary fw-semibold"><i class="fas fa-edit me-2"></i>Đang sửa tin nhắn...</span>
                        <button type="button" class="btn-close" style="font-size: 0.7rem;" wire:click="cancelEdit" aria-label="Hủy sửa tin nhắn"></button>
                    </div>
                @endif

                @if ($image && !$editingMessageId)
                    <div class="chat-attachment-preview mb-2 d-flex align-items-center">
                        <div class="position-relative me-2">
                            <img src="{{ $image->temporaryUrl() }}" alt="Ảnh đính kèm" class="rounded"
                                style="height: 60px; object-fit: cover;">
                            <button type="button" class="btn-close position-absolute top-0 end-0 bg-white rounded-circle"
                                style="transform: scale(0.7);" wire:click="$set('image', null)"
                                aria-label="Xóa ảnh xem trước"></button>
                        </div>
                        <span>Ảnh đính kèm <small class="d-block text-muted">Sẵn sàng gửi</small></span>
                    </div>
                @endif
                <form wire:submit.prevent="{{ $editingMessageId ? 'updateMessage' : 'sendMessage' }}" class="chat-composer {{ $editingMessageId ? 'is-editing' : '' }} d-flex align-items-end gap-2"
                    x-data="{ emojiOpen: false, insertEmoji(value) { const input = document.getElementById('message-input-textarea'); input.setRangeText(value, input.selectionStart, input.selectionEnd, 'end'); input.dispatchEvent(new Event('input', { bubbles: true })); input.focus(); this.emojiOpen = false; } }"
                    @keydown.escape.stop="emojiOpen = false" @click.outside="emojiOpen = false">
                    @if(!$editingMessageId)
                        <input type="file" wire:model="image" accept="image/*" class="visually-hidden" id="upload-image-admin" aria-label="Chọn ảnh để gửi">
                        <button type="button" onclick="document.getElementById('upload-image-admin').click()" aria-label="Gửi ảnh"
                            class="btn chat-attachment-button d-flex align-items-center justify-content-center m-0 position-relative"
                            title="Đính kèm ảnh">
                            <i class="fas fa-image" style="font-size: 16px;" wire:loading.remove wire:target="image"></i>
                            <span class="spinner-border spinner-border-sm text-primary" wire:loading wire:target="image"></span>
                        </button>
                    @endif
                    <div class="chat-composer-input flex-grow-1 position-relative">
                        <textarea id="message-input-textarea" @if(!$editingMessageId) data-chat-typing-input @endif wire:model="{{ $editingMessageId ? 'editingMessageText' : 'messageText' }}"
                            placeholder="{{ $editingMessageId ? 'Sửa nội dung tin nhắn...' : 'Nhập tin nhắn cho khách hàng...' }}" class="form-control"
                            aria-label="{{ $editingMessageId ? 'Nội dung tin nhắn cần sửa' : 'Nội dung tin nhắn' }}"
                            rows="1"
                            ></textarea>
                    </div>
                    <button type="button" class="chat-emoji-toggle chat-icon-button" @click="emojiOpen = !emojiOpen" :aria-expanded="emojiOpen" aria-controls="chat-emoji-picker" aria-label="Chọn emoji" title="Emoji"><i class="far fa-face-smile" aria-hidden="true"></i></button>
                    <div id="chat-emoji-picker" class="chat-emoji-picker" x-show="emojiOpen" x-cloak role="group" aria-label="Emoji">
                        @foreach(['😀' => 'Cười', '😊' => 'Vui vẻ', '😍' => 'Yêu thích', '🥰' => 'Yêu mến', '😄' => 'Cười tươi', '😅' => 'Cười ngại', '😂' => 'Cười lớn', '😉' => 'Nháy mắt', '👍' => 'Đồng ý', '👏' => 'Vỗ tay', '🙏' => 'Cảm ơn', '❤️' => 'Trái tim', '🎉' => 'Chúc mừng', '✅' => 'Hoàn tất', '🤝' => 'Bắt tay', '👋' => 'Xin chào', '😢' => 'Buồn', '🤔' => 'Suy nghĩ', '👌' => 'Được', '💐' => 'Bó hoa'] as $emoji => $emojiLabel)
                            <button type="button" @click="insertEmoji('{{ $emoji }}')" aria-label="{{ $emojiLabel }}" title="{{ $emojiLabel }}">{{ $emoji }}</button>
                        @endforeach
                    </div>
                    <button type="submit"
                        wire:loading.attr="disabled" wire:target="sendMessage, updateMessage, image"
                        class="btn chat-send-button {{ $editingMessageId ? 'btn-success' : 'send-btn-gradient' }} d-flex align-items-center justify-content-center position-relative"
                        title="{{ $editingMessageId ? 'Cập nhật' : 'Gửi tin nhắn' }}"
                        aria-label="{{ $editingMessageId ? 'Cập nhật tin nhắn' : 'Gửi tin nhắn' }}">
                        <i class="fas {{ $editingMessageId ? 'fa-check' : 'fa-paper-plane' }}" style="font-size: 15px;" wire:loading.remove wire:target="{{ $editingMessageId ? 'updateMessage' : 'sendMessage' }}"></i>
                        <span class="spinner-border spinner-border-sm text-white" wire:loading wire:target="{{ $editingMessageId ? 'updateMessage' : 'sendMessage' }}"></span>
                        <span class="chat-send-label">{{ $editingMessageId ? 'Lưu' : 'Gửi' }}</span>
                        @if(!$editingMessageId)
                            <span class="chat-send-shortcut" aria-hidden="true">Enter ↵</span>
                        @endif
                    </button>
                </form>
                @error('image') <div class="chat-input-error" role="alert">{{ $message }}</div> @enderror
                @error('messageText') <div class="chat-input-error" role="alert">{{ $message }}</div> @enderror
                @error('editingMessageText') <div class="chat-input-error" role="alert">{{ $message }}</div> @enderror
            </div>
            <aside id="chat-context-panel" class="chat-context-panel" x-show="contextOpen" x-cloak
                aria-label="Thông tin khách hàng và trả lời nhanh">
                <div class="chat-panel-resizer chat-context-resizer" data-chat-resizer="right" role="separator" tabindex="0"
                    aria-orientation="vertical" aria-label="Điều chỉnh độ rộng thông tin khách hàng"
                    aria-valuemin="280" aria-valuemax="520" title="Kéo để thay đổi độ rộng · Nhấp đúp để đặt lại"></div>
                <div class="chat-details-heading">
                    <h3>Thông tin khách hàng</h3>
                    <button type="button" class="chat-icon-button" x-ref="contextClose" @click="closeContext()"
                        aria-label="Đóng thông tin khách hàng" title="Đóng thông tin">
                        <i class="fas fa-xmark" aria-hidden="true"></i>
                    </button>
                </div>
                <div class="chat-details-scroll custom-scrollbar">
                    <section class="chat-customer-profile">
                        <div class="chat-customer-profile-main">
                            <img src="{{ get_user_avatar($this->selectedConversation->user) }}" alt="" class="chat-profile-avatar">
                            <div class="chat-customer-profile-copy">
                                <h4>{{ $this->selectedConversation->user->full_name }}</h4>
                                <span>{{ $this->selectedConversation->user->username }}</span>
                            </div>
                        </div>
                        <a class="chat-profile-link" href="{{ route('user.index') }}#user-{{ $this->selectedConversation->user->id }}">
                            <i class="far fa-user" aria-hidden="true"></i> Xem hồ sơ
                        </a>
                    </section>
                    <section class="chat-customer-overview">
                        <h4>Thông tin hội thoại</h4>
                        <div class="chat-contact-meta d-flex flex-wrap align-items-center gap-2">
                            <span class="chat-contact-username">{{ $this->selectedConversation->user->username }}</span>
                            @if($isManagementUser)
                                <span class="chat-header-chip chip-staff">
                                    <i class="fas fa-user-shield me-1"></i>Phụ trách: {{ $this->selectedConversation->staff->full_name }}
                                </span>
                            @endif

                            <span class="chat-header-chip chip-location">
                                <i class="fas fa-location-dot me-1 text-primary"></i>
                                @php
                                    $chatCountryCode = $this->selectedConversation->user->location_country_code
                                        ?: $this->selectedConversation->user->approx_location_country_code;
                                    $chatCountry = $this->selectedConversation->user->location_country
                                        ?: $this->selectedConversation->user->approx_location_country;
                                @endphp
                                @if($chatCountryCode)
                                    <span class="me-1">{{ country_flag($chatCountryCode) }}</span>
                                @endif
                                @if($this->selectedConversation->user->location_city)
                                    {{ $this->selectedConversation->user->location_city }}
                                    @if($chatCountry), {{ $chatCountry }}@endif
                                @elseif($chatCountry)
                                    {{ $chatCountry }}
                                @elseif($chatCountryCode)
                                    {{ $chatCountryCode }}
                                @else
                                    Chưa xác định
                                @endif
                                @if($this->selectedConversation->user->location_permission !== 'granted')
                                    <span class="text-warning ms-1" style="font-size: 10px;">(Vị trí tương đối)</span>
                                @endif
                            </span>

                            @if($this->selectedConversation->user->location_latitude !== null && $this->selectedConversation->user->location_longitude !== null)
                                <a href="https://www.google.com/maps/search/?api=1&amp;query={{ $this->selectedConversation->user->location_latitude }},{{ $this->selectedConversation->user->location_longitude }}"
                                   class="chat-header-chip text-primary fw-semibold"
                                   target="_blank"
                                   rel="noopener noreferrer"
                                   title="Xem vị trí người dùng trên Google Maps"
                                   aria-label="Xem vị trí người dùng trên Google Maps"
                                   style="text-decoration: none;">
                                    <i class="fas fa-map-location-dot me-1"></i> Bản đồ
                                </a>
                            @endif
                        </div>
                    </section>
                    @include('livewire.admin.partials.customer-context', ['context' => $this->customerContext])
                    <section class="chat-quick-section" x-ref="quickMessages" tabindex="-1">
                        <div class="chat-quick-section-heading">
                            <h4><i class="fas fa-bolt" aria-hidden="true"></i> Trả lời nhanh</h4>
                            <button class="quick-msg-add-btn chat-quick-add-btn" type="button" wire:click="startAddingQuickMessage"
                                title="Thêm tin nhắn nhanh" aria-label="Thêm tin nhắn nhanh">
                                <i class="fas fa-plus" aria-hidden="true"></i>
                                <span>Thêm</span>
                            </button>
                        </div>
                        <p class="chat-details-hint">Chọn mẫu để đưa vào ô soạn tin.</p>
                        <div class="quick-msg-list">
                            @foreach($quickMessages as $messageKey => $messageText)
                                @include('livewire.admin.partials.quick-message-item', [
                                    'messageKey' => $messageKey,
                                    'messageText' => $messageText,
                                    'messageNumber' => $loop->iteration,
                                    'context' => 'database',
                                ])
                            @endforeach
                            @include('livewire.admin.partials.quick-message-create', ['context' => 'database'])
                        </div>
                    </section>
                </div>
            </aside>
        @else
            <!-- Trạng thái chưa chọn conversation -->
            <div class="chat-welcome flex-grow-1 d-flex align-items-center justify-content-center p-4">
                <div class="chat-empty-state text-center">
                    <div class="chat-empty-icon mx-auto">
                        <i class="far fa-comments" aria-hidden="true"></i>
                    </div>
                    <span class="chat-eyebrow">HỘP THƯ HỖ TRỢ</span>
                    <h2>Chọn một cuộc trò chuyện</h2>
                    <p>Chọn một hội thoại trong danh sách để xem tin nhắn và tiếp tục hỗ trợ khách hàng.</p>
                    <button type="button" class="btn chat-empty-action d-lg-none" @click="mobileListOpen = true">
                        <i class="fas fa-comments me-2" aria-hidden="true"></i>Mở danh sách hội thoại
                    </button>
                </div>
            </div>
        @endif
    </div>
    <!-- Image viewer -->
    <div class="zoom-modal chat-image-viewer-overlay" id="zoomModal" role="dialog" aria-modal="true" aria-labelledby="adminImageViewerTitle" aria-hidden="true" wire:ignore>
        <div class="chat-image-viewer-shell">
            <header class="chat-image-viewer-header">
                <div class="chat-image-viewer-title">
                    <strong id="adminImageViewerTitle">Xem ảnh</strong>
                    <small>Ảnh trong cuộc trò chuyện</small>
                </div>
                <div class="chat-image-viewer-header-actions">
                    <span class="chat-image-viewer-counter" id="adminImageViewerCounter" hidden></span>
                    <a class="chat-image-viewer-action is-secondary is-open-original" id="adminImageViewerOpenOriginal" href="#" target="_blank" rel="noopener" aria-label="Mở ảnh gốc" title="Mở ảnh gốc">
                        <i class="fas fa-arrow-up-right-from-square" aria-hidden="true"></i>
                    </a>
                    <a class="chat-image-viewer-action is-secondary" id="adminImageViewerDownload" href="#" download aria-label="Tải ảnh" title="Tải ảnh">
                        <i class="fas fa-download" aria-hidden="true"></i>
                    </a>
                    <button type="button" class="chat-image-viewer-action is-close" id="closeModal" aria-label="Đóng viewer" title="Đóng">
                        <i class="fas fa-xmark" aria-hidden="true"></i>
                    </button>
                </div>
            </header>

            <div class="zoom-container chat-image-viewer-stage is-loading" id="zoomContainer">
                <span class="chat-image-viewer-loading" aria-hidden="true"><i class="fas fa-circle-notch fa-spin"></i></span>
                <div class="chat-image-viewer-error" role="status">
                    <i class="fas fa-image" aria-hidden="true"></i>
                    <strong>Không thể tải ảnh</strong>
                    <small>Ảnh có thể đã được di chuyển hoặc không còn khả dụng.</small>
                </div>
                <button type="button" class="chat-image-viewer-nav is-prev" id="adminImageViewerPrev" aria-label="Ảnh trước" hidden>
                    <i class="fas fa-chevron-left" aria-hidden="true"></i>
                </button>
                <img src="" alt="Ảnh trong cuộc trò chuyện" class="zoom-modal-image chat-image-viewer-image" id="zoomModalImage" draggable="false">
                <button type="button" class="chat-image-viewer-nav is-next" id="adminImageViewerNext" aria-label="Ảnh tiếp theo" hidden>
                    <i class="fas fa-chevron-right" aria-hidden="true"></i>
                </button>
            </div>

            <footer class="chat-image-viewer-footer">
                <div class="chat-image-viewer-meta">Cuộn chuột để zoom · Kéo ảnh khi đã phóng to · Double-click để đặt lại</div>
                <div class="chat-image-viewer-controls">
                    <button type="button" class="chat-image-viewer-control" id="adminImageViewerZoomOut" aria-label="Thu nhỏ" title="Thu nhỏ"><i class="fas fa-minus"></i></button>
                    <span class="chat-image-viewer-scale" id="adminImageViewerScale">100%</span>
                    <button type="button" class="chat-image-viewer-control" id="adminImageViewerZoomIn" aria-label="Phóng to" title="Phóng to"><i class="fas fa-plus"></i></button>
                    <button type="button" class="chat-image-viewer-control" id="adminImageViewerReset" aria-label="Đặt lại zoom" title="Đặt lại zoom"><i class="fas fa-expand"></i></button>
                </div>
            </footer>
        </div>
    </div>

    <!-- Modal Xác nhận xóa tin nhắn -->
    <div class="modal fade" id="deleteMessagesModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content warning-modal">
                <div class="modal-body text-center p-4">
                    <div class="warning-icon mb-3">
                        <i class="fas fa-eraser"></i>
                    </div>
                    <h5 class="mb-3 fw-bold text-warning">Xóa tin nhắn?</h5>
                    <p class="text-muted mb-4">
                        Bạn có chắc chắn muốn xóa tất cả tin nhắn trong hội thoại này?<br>
                        <small class="text-muted">(Hội thoại vẫn được giữ lại)</small>
                    </p>
                    <div class="d-flex gap-2 justify-content-center">
                        <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i>Hủy
                        </button>
                        <button type="button" class="btn btn-warning-gradient px-4" id="confirmDeleteMessagesBtn">
                            <i class="fas fa-eraser me-2"></i>Xóa tin nhắn
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Xác nhận xóa hội thoại -->
    <div class="modal fade" id="deleteConversationModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content error-modal">
                <div class="modal-body text-center p-4">
                    <div class="error-icon mb-3">
                        <i class="fas fa-trash-alt"></i>
                    </div>
                    <h5 class="mb-3 fw-bold text-danger">Xóa hội thoại?</h5>
                    <p class="text-muted mb-4">
                        Bạn có chắc chắn muốn xóa <strong>hoàn toàn</strong> hội thoại này?<br>
                        <small class="text-danger fw-bold">Tất cả tin nhắn sẽ bị xóa vĩnh viễn!</small>
                    </p>
                    <div class="d-flex gap-2 justify-content-center">
                        <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i>Hủy
                        </button>
                        <button type="button" class="btn btn-danger-gradient px-4" id="confirmDeleteConversationBtn">
                            <i class="fas fa-trash-alt me-2"></i>Xóa hội thoại
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Thông báo xóa thành công -->
    <div class="modal fade" id="deleteSuccessModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content success-modal">
                <div class="modal-body text-center p-4">
                    <div class="success-icon mb-3">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h5 class="mb-3 fw-bold text-success">Đã xóa!</h5>
                    <p class="text-muted mb-4" id="deleteSuccessMessage">Hội thoại đã được xóa thành công</p>
                    <button type="button" class="btn btn-success-gradient px-4" data-bs-dismiss="modal">
                        <i class="fas fa-check me-2"></i>Đồng ý
                    </button>
                </div>
            </div>
        </div>
    </div>
<script>
    let currentChannel = null;

    // Notification Sound Function
    function playNotificationSound() {
        try {
            const audio = new Audio('/audio/notification_fb.mp3');
            audio.volume = 0.5;
            audio.play().catch(error => {
                console.log('Không thể phát âm thanh thông báo:', error);
            });
        } catch (error) {
            console.log('Lỗi khi phát âm thanh:', error);
        }
    }

    document.addEventListener('livewire:initialized', () => {
        // Kiểm tra nếu đã khởi tạo rồi thì bỏ qua
        if (window.chatComponentInitialized) {
            return;
        }
        window.chatComponentInitialized = true;

        function whenEchoReady(callback) {
            if (window.Echo) {
                callback();
                return;
            }

            window.addEventListener('echo:ready', callback, { once: true });
        }

        // Listen to chat notification event (clickable notification)
        Livewire.on('chat-notification', (data) => {
            const eventData = Array.isArray(data) ? data[0] : data;
            const { conversationId, userId, staffId, senderName, message } = eventData;

            if (window.isAdminConversationMuted?.(conversationId)) {
                return;
            }

            // Phát âm thanh thông báo
            playNotificationSound();

            // Hiển thị notification bằng toastr có thể click
            const root = document.getElementById('chat-root');
            const component = Livewire.find(root.getAttribute('wire:id'));

            toastr.options = {
                closeButton: true,
                progressBar: true,
                positionClass: "toast-top-right",
                timeOut: "8000",
                extendedTimeOut: "2000",
                showMethod: "fadeIn",
                hideMethod: "fadeOut",
                onclick: function () {
                    component.call('openConversationFromNotification', conversationId, userId, staffId);
                }
            };

            // Sử dụng toastr.success() để có nền màu xanh success
            const toast = toastr.success(message, '💬 ' + senderName);
            const toastElement = toast?.[0];

            if (!toastElement) {
                return;
            }

            toastElement.classList.add('chat-message-toast');
            toastElement.dataset.conversationId = String(conversationId);

            const moreButton = document.createElement('button');
            moreButton.type = 'button';
            moreButton.className = 'chat-toast-more';
            moreButton.setAttribute('aria-label', 'Tùy chọn thông báo');
            moreButton.setAttribute('aria-expanded', 'false');
            moreButton.innerHTML = '<i class="fas fa-ellipsis-h" aria-hidden="true"></i>';

            const muteMenu = document.createElement('div');
            muteMenu.className = 'chat-toast-mute-menu';
            muteMenu.hidden = true;
            muteMenu.setAttribute('role', 'menu');

            const muteOptions = [
                ['15m', 'Tắt trong 15 phút'],
                ['1h', 'Tắt trong 1 giờ'],
                ['8h', 'Tắt trong 8 giờ'],
                ['forever', 'Tắt đến khi bật lại'],
            ];

            muteOptions.forEach(([duration, label]) => {
                const option = document.createElement('button');
                option.type = 'button';
                option.className = 'chat-toast-mute-option';
                option.textContent = label;
                option.setAttribute('role', 'menuitem');

                option.addEventListener('click', (event) => {
                    event.preventDefault();
                    event.stopPropagation();
                    option.disabled = true;

                    component.call('muteConversationFromNotification', conversationId, duration)
                        .catch((error) => {
                            option.disabled = false;
                            console.error('Không thể tắt thông báo hội thoại:', error);

                            const toastOnClick = toastr.options.onclick;
                            toastr.options.onclick = null;
                            toastr.error('Không thể tắt thông báo hội thoại. Vui lòng thử lại.', 'Có lỗi xảy ra');
                            toastr.options.onclick = toastOnClick;
                        });
                });

                muteMenu.appendChild(option);
            });

            moreButton.addEventListener('click', (event) => {
                event.preventDefault();
                event.stopPropagation();

                muteMenu.hidden = !muteMenu.hidden;
                moreButton.setAttribute('aria-expanded', String(!muteMenu.hidden));
            });

            muteMenu.addEventListener('click', (event) => {
                event.stopPropagation();
            });

            toastElement.appendChild(moreButton);
            toastElement.appendChild(muteMenu);
        });

        Livewire.on('conversation-notification-mute-updated', (data) => {
            const eventData = Array.isArray(data) ? data[0] : data;
            const { conversationId, muted, mutedUntil } = eventData;

            window.setAdminConversationMute?.(conversationId, muted, mutedUntil);

            if (muted) {
                document.querySelectorAll('.chat-message-toast').forEach((toastElement) => {
                    if (toastElement.dataset.conversationId === String(conversationId)) {
                        toastr.clear($(toastElement));
                    }
                });

                toastr.options.onclick = null;
                toastr.info(
                    mutedUntil ? 'Thông báo của hội thoại đã được tắt tạm thời.' : 'Thông báo của hội thoại đã được tắt đến khi bạn bật lại.',
                    'Đã tắt thông báo'
                );
            } else {
                toastr.options.onclick = null;
                toastr.success('Bạn sẽ tiếp tục nhận thông báo từ hội thoại này.', 'Đã bật thông báo');
            }
        });

        // Listen to scroll-to-conversation event
        Livewire.on('scroll-to-conversation', (data) => {
            const eventData = Array.isArray(data) ? data[0] : data;
            const { conversationId, isStaffConversation } = eventData;

            // Đợi DOM update sau khi expand
            setTimeout(() => {
                // Tìm conversation item theo conversationId
                let conversationItem = null;

                if (isStaffConversation) {
                    // Tìm trong danh sách staff users
                    const allConversationItems = document.querySelectorAll('.conversation-item');
                    allConversationItems.forEach(item => {
                        // Check nếu item này được selected (có background xanh)
                        if (item.classList.contains('bg-primary')) {
                            conversationItem = item;
                        }
                    });
                } else {
                    // Tìm trong danh sách "Tin nhắn của tôi"
                    const allConversationItems = document.querySelectorAll('.conversation-item');
                    allConversationItems.forEach(item => {
                        if (item.classList.contains('bg-primary')) {
                            conversationItem = item;
                        }
                    });
                }

                if (conversationItem) {
                    // Scroll đến conversation item
                    conversationItem.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });

                    // Thêm hiệu ứng pulse để highlight
                    conversationItem.style.animation = 'pulse 1.5s ease-in-out 2';

                    setTimeout(() => {
                        conversationItem.style.animation = '';
                    }, 3000);
                }
            }, 500);
        });

        // Listen MessageSent event trên staff channel để update sidebar khi có tin nhắn mới
        @if($isManagementUser)
            const staffChannel = `staff.{{ auth()->id() }}`;
            const currentUserId = {{ auth()->id() }};

            whenEchoReady(() => window.Echo.private(staffChannel)
                .listen('.MessageSent', (e) => {
                    const root = document.getElementById('chat-root');
                    const component = Livewire.find(root.getAttribute('wire:id'));
                    const isAutoReply = e.message.kind === 'auto_reply';

                    // Lấy selectedConversationId từ component
                    const selectedConversationId = component.get('selectedConversationId');

                    // Nếu tin nhắn thuộc conversation đang focus
                    // staff channel đóng vai trò fallback để UI vẫn realtime ngay cả khi
                    // conversation channel chưa kịp subscribe sau một lần React mount.
                    if (selectedConversationId && selectedConversationId == e.message.conversation_id) {
                        component.call('messageReceived', e.message);
                        return;
                    }

                    // Tin nhắn thủ công của chính staff đã được sendMessage thêm vào UI.
                    // Auto-reply do queue tạo dùng sender_id của staff nên vẫn phải đi qua realtime.
                    if (e.message.sender_id === currentUserId && !isAutoReply) {
                        // Chỉ reload sidebar, không hiển thị notification
                        component.call('loadConversations');
                        @if($canManageAllChats)
                            component.call('loadStaffUsersAlternative');
                        @endif
                                    return;
                    }

                    // Tin nhắn của người khác và conversation khác
                    // → Reload sidebar và gọi messageReceived() để hiển thị notification
                    component.call('loadConversations');
                    @if($canManageAllChats)
                        component.call('loadStaffUsersAlternative');
                    @endif
                    component.call('messageReceived', e.message);
                })
                .listen('.UserJoinChat', (e) => {
                    // Update sidebar khi user join chat
                    const root = document.getElementById('chat-root');
                    const component = Livewire.find(root.getAttribute('wire:id'));
                    component.call('loadConversations');
                    @if($canManageAllChats)
                        component.call('loadStaffUsersAlternative');
                    @endif
                            })
                .listen('.ConversationAssigned', () => {
                    const root = document.getElementById('chat-root');
                    if (root) {
                        Livewire.find(root.getAttribute('wire:id')).call('refreshChatState');
                    }
                })
                .error((error) => {
                    console.error('Staff Echo error:', error);
                }));
        @endif

        // Join conversation channel
        Livewire.on('join-conversation-channel', (data) => {
            const newChannel = `chat.conversation.${data.conversationId}`;

            // Leave previous channel if exists
            if (currentChannel) {
                window.Echo.leave(currentChannel);
            }

            // Update current channel
            currentChannel = newChannel;

            whenEchoReady(() => window.Echo.private(currentChannel)
                .error((error) => {
                    console.error('❌ ERROR joining conversation channel:', currentChannel, error);
                })
                .listen('.MessageSent', (e) => {
                    const message = e.message;
                    const currentUserId = {{ auth()->id() }};
                    const isAutoReply = message.kind === 'auto_reply';
                    const root = document.getElementById('chat-root');

                    // React keeps this Echo callback alive after leaving the chat page.
                    // Ignore it once the chat DOM is gone so the global admin listener
                    // remains the single owner of notification sound off this page.
                    if (!root) {
                        return;
                    }

                    // Tin nhắn gửi thủ công của chính mình đã được sendMessage thêm vào UI.
                    // Auto-reply được job tạo với sender_id của staff nên vẫn phải nhận realtime tại đây.
                    if (message.sender_id !== currentUserId || isAutoReply) {
                        if (!isAutoReply && !window.isAdminConversationMuted?.(message.conversation_id)) {
                            playNotificationSound();
                        }

                        const component = Livewire.find(root.getAttribute('wire:id'));

                        // Đẩy cho Backend Livewire xử lý (update Array, mark as read, scroll, update Sidebar)
                        component.call('messageReceived', message);
                    }
                })
                .listen('.MessageRead', (e) => {
                    if (Number(e.user_id) === {{ auth()->id() }}) return;
                    const root = document.getElementById('chat-root');
                    if (root) Livewire.find(root.getAttribute('wire:id')).call('refreshReadReceipts');
                })
                .listen('.ConversationRead', (e) => {
                    if (Number(e.user_id) === {{ auth()->id() }}) return;
                    const root = document.getElementById('chat-root');
                    if (root) Livewire.find(root.getAttribute('wire:id')).call('refreshReadReceipts');
                })
                .error((error) => {
                    console.error('Echo error:', error);
                }));
            window.dispatchEvent(new CustomEvent('chat:channel-changed'));
        });

        Livewire.on('leave-conversation-channel', () => {
            if (!currentChannel) return;

            window.Echo.leave(currentChannel);
            currentChannel = null;
        });

        window.loadMoreMessagesAdmin = function () {
            const container = document.getElementById('messages-container');
            if (container) {
                const root = document.getElementById('chat-root');
                const component = Livewire.find(root.getAttribute('wire:id'));
                component.call('loadMoreMessages');
            }
        }
    });

    // AdminChatPage mounts the Livewire markup after the component has already
    // produced its initial browser events. Re-emit the current conversation after
    // each React mount so the persistent listener above always subscribes it.
    document.addEventListener('admin-chat:mounted', () => {
        const root = document.getElementById('chat-root');
        const conversationId = Number(root?.dataset.chatConversation || 0);
        if (!conversationId || !window.Livewire) return;

        window.Livewire.dispatch('join-conversation-channel', { conversationId });
    });

    // React mounts this markup after DOMContentLoaded and then signals Livewire readiness.
    document.addEventListener('livewire:initialized', function () {
        const boundTextareas = new WeakSet();
        const CHAT_PANEL_LIMITS = {
            left: { min: 220, max: 520, contentMin: 480, cssVar: '--chat-sidebar-width' },
            right: { min: 280, max: 520, contentMin: 420, cssVar: '--chat-context-width' },
        };
        let activePanelResize = null;

        function chatPanelStorageKey(side) {
            const viewerId = document.getElementById('chat-root')?.dataset.chatViewer || 'guest';
            return `admin-chat:${viewerId}:${side}-panel-width`;
        }

        function readStoredPanelWidth(side) {
            try {
                const value = Number(window.localStorage.getItem(chatPanelStorageKey(side)));
                return Number.isFinite(value) && value > 0 ? value : null;
            } catch (_) {
                return null;
            }
        }

        function storePanelWidth(side, width) {
            try {
                window.localStorage.setItem(chatPanelStorageKey(side), String(Math.round(width)));
            } catch (_) {
                // localStorage can be unavailable in restricted browsing modes.
            }
        }

        function clearStoredPanelWidth(side) {
            try {
                window.localStorage.removeItem(chatPanelStorageKey(side));
            } catch (_) {
                // localStorage can be unavailable in restricted browsing modes.
            }
        }

        function getPanelBounds(side, root) {
            const config = CHAT_PANEL_LIMITS[side];
            if (!config || !root) return null;

            const container = side === 'right' ? root.querySelector('.chat-main') : root;
            const availableWidth = container?.getBoundingClientRect().width || root.getBoundingClientRect().width;
            const maxByContent = Math.max(config.min, availableWidth - config.contentMin);

            return {
                min: config.min,
                max: Math.max(config.min, Math.min(config.max, maxByContent)),
            };
        }

        function setPanelWidth(side, requestedWidth, persist = false) {
            const root = document.getElementById('chat-root');
            const config = CHAT_PANEL_LIMITS[side];
            const bounds = getPanelBounds(side, root);
            if (!root || !config || !bounds) return null;

            const width = Math.min(bounds.max, Math.max(bounds.min, requestedWidth));
            root.style.setProperty(config.cssVar, `${Math.round(width)}px`);

            const handle = root.querySelector(`[data-chat-resizer="${side}"]`);
            if (handle) {
                handle.setAttribute('aria-valuemin', String(Math.round(bounds.min)));
                handle.setAttribute('aria-valuemax', String(Math.round(bounds.max)));
                handle.setAttribute('aria-valuenow', String(Math.round(width)));
            }

            if (persist) storePanelWidth(side, width);
            return width;
        }

        function applyStoredPanelWidths() {
            const root = document.getElementById('chat-root');
            if (!root) return;

            if (window.innerWidth >= 992) {
                const leftWidth = readStoredPanelWidth('left');
                if (leftWidth !== null) setPanelWidth('left', leftWidth);
            }

            if (window.innerWidth >= 1280) {
                const rightWidth = readStoredPanelWidth('right');
                if (rightWidth !== null) setPanelWidth('right', rightWidth);
            }
        }

        function currentPanelWidth(side, root) {
            if (side === 'right') {
                return root.querySelector('.chat-context-panel')?.getBoundingClientRect().width || CHAT_PANEL_LIMITS.right.min;
            }
            return root.querySelector('.chat-sidebar-shell')?.getBoundingClientRect().width || CHAT_PANEL_LIMITS.left.min;
        }

        document.addEventListener('pointerdown', (event) => {
            const handle = event.target.closest?.('[data-chat-resizer]');
            const root = handle?.closest('#chat-root');
            const side = handle?.dataset.chatResizer;
            if (!root || !CHAT_PANEL_LIMITS[side] || event.button !== 0) return;
            if (side === 'left' && window.innerWidth < 992) return;
            if (side === 'right' && window.innerWidth < 1280) return;

            activePanelResize = { side, handle, root };
            root.classList.add('is-resizing-panels');
            handle.classList.add('is-active');
            handle.setPointerCapture?.(event.pointerId);
            event.preventDefault();
        });

        document.addEventListener('pointermove', (event) => {
            if (!activePanelResize) return;

            const { side, root } = activePanelResize;
            const container = side === 'right' ? root.querySelector('.chat-main') : root;
            const rect = container.getBoundingClientRect();
            const requestedWidth = side === 'right' ? rect.right - event.clientX : event.clientX - rect.left;
            setPanelWidth(side, requestedWidth);
        });

        function finishPanelResize() {
            if (!activePanelResize) return;

            const { side, root, handle } = activePanelResize;
            const width = currentPanelWidth(side, root);
            setPanelWidth(side, width, true);
            root.classList.remove('is-resizing-panels');
            handle.classList.remove('is-active');
            activePanelResize = null;
        }

        document.addEventListener('pointerup', finishPanelResize);
        document.addEventListener('pointercancel', finishPanelResize);

        document.addEventListener('keydown', (event) => {
            const handle = event.target.closest?.('[data-chat-resizer]');
            const root = handle?.closest('#chat-root');
            const side = handle?.dataset.chatResizer;
            if (!root || !CHAT_PANEL_LIMITS[side] || !['ArrowLeft', 'ArrowRight'].includes(event.key)) return;

            const direction = event.key === 'ArrowRight' ? 1 : -1;
            const signedDirection = side === 'right' ? -direction : direction;
            const step = event.shiftKey ? 50 : 20;
            setPanelWidth(side, currentPanelWidth(side, root) + (signedDirection * step), true);
            event.preventDefault();
        });

        document.addEventListener('dblclick', (event) => {
            const handle = event.target.closest?.('[data-chat-resizer]');
            const root = handle?.closest('#chat-root');
            const side = handle?.dataset.chatResizer;
            const config = CHAT_PANEL_LIMITS[side];
            if (!root || !config) return;

            root.style.removeProperty(config.cssVar);
            handle.removeAttribute('aria-valuenow');
            clearStoredPanelWidth(side);
            event.preventDefault();
        });

        window.addEventListener('resize', () => {
            const root = document.getElementById('chat-root');
            if (!root) return;

            if (window.innerWidth >= 992) {
                const inlineLeftWidth = root.style.getPropertyValue(CHAT_PANEL_LIMITS.left.cssVar);
                const storedLeftWidth = readStoredPanelWidth('left');
                if (inlineLeftWidth) {
                    setPanelWidth('left', currentPanelWidth('left', root));
                } else if (storedLeftWidth !== null) {
                    setPanelWidth('left', storedLeftWidth);
                }
            }
            if (window.innerWidth >= 1280) {
                const inlineRightWidth = root.style.getPropertyValue(CHAT_PANEL_LIMITS.right.cssVar);
                const storedRightWidth = readStoredPanelWidth('right');
                if (inlineRightWidth) {
                    setPanelWidth('right', currentPanelWidth('right', root));
                } else if (storedRightWidth !== null) {
                    setPanelWidth('right', storedRightWidth);
                }
            }
        });

        document.addEventListener('admin-chat:mounted', applyStoredPanelWidths);
        applyStoredPanelWidths();

        // ===== Xử lý textarea tự động điều chỉnh chiều cao =====
        function autoResizeTextarea() {
            const textarea = document.getElementById('message-input-textarea');
            if (textarea) {
                textarea.style.height = 'auto';
                textarea.style.height = Math.min(textarea.scrollHeight, 150) + 'px';
            }
        }

        // Xử lý Enter và Shift+Enter
        function handleTextareaKeydown(e) {
            if (e.key === 'Enter' && !e.shiftKey && !e.isComposing) {
                e.preventDefault();
                const form = e.target.closest('form');
                if (form && !form.querySelector('button[type="submit"]')?.disabled) {
                    // Respect the form's send/update action and retain text on validation errors.
                    form.requestSubmit();
                }
            }
        }

        // Attach event listeners
        function attachTextareaEvents() {
            const textarea = document.getElementById('message-input-textarea');
            if (!textarea || boundTextareas.has(textarea)) return;

            boundTextareas.add(textarea);
            textarea.addEventListener('keydown', handleTextareaKeydown);
            textarea.addEventListener('input', autoResizeTextarea);
        }

        // Initialize textarea events
        attachTextareaEvents();

        // ===== Xử lý zoom ảnh =====
        const modal = document.getElementById('zoomModal');
        const modalImage = document.getElementById('zoomModalImage');
        const closeBtn = document.getElementById('closeModal');
        const zoomContainer = document.getElementById('zoomContainer');
        const viewerCounter = document.getElementById('adminImageViewerCounter');
        const viewerPrev = document.getElementById('adminImageViewerPrev');
        const viewerNext = document.getElementById('adminImageViewerNext');
        const viewerZoomOut = document.getElementById('adminImageViewerZoomOut');
        const viewerZoomIn = document.getElementById('adminImageViewerZoomIn');
        const viewerReset = document.getElementById('adminImageViewerReset');
        const viewerScale = document.getElementById('adminImageViewerScale');
        const viewerOpenOriginal = document.getElementById('adminImageViewerOpenOriginal');
        const viewerDownload = document.getElementById('adminImageViewerDownload');
        let currentScale = 1;
        let currentX = 0;
        let currentY = 0;
        let isDragging = false;
        let startX = 0;
        let startY = 0;
        let viewerImages = [];
        let viewerIndex = 0;
        let previousBodyOverflow = '';
        let lifecycleSyncFrame = null;
        const boundThumbnailImages = new WeakSet();
        const boundViewerImages = new WeakSet();

        function collectViewerImages() {
            const seen = new Set();
            return Array.from(document.querySelectorAll('#messages-container .zoomable-image, #chat-context-panel .zoomable-image'))
                .map(img => img.currentSrc || img.src)
                .filter(src => src && !seen.has(src) && seen.add(src));
        }

        function setThumbnailState(image, state) {
            const frame = image.closest('.admin-chat-image-frame');
            if (!frame) return;

            frame.classList.remove('is-loading', 'is-loaded', 'is-error');
            if (state === 'loading') frame.classList.add('is-loading');
            if (state === 'loaded') frame.classList.add('is-loaded');
            if (state === 'error') frame.classList.add('is-error');
        }

        function syncThumbnailImage(image) {
            if (!boundThumbnailImages.has(image)) {
                boundThumbnailImages.add(image);

                image.addEventListener('load', () => {
                    setThumbnailState(image, 'loaded');
                });

                image.addEventListener('error', () => {
                    setThumbnailState(image, 'error');
                });
            }

            if (!image.complete) {
                setThumbnailState(image, 'loading');
                return;
            }

            setThumbnailState(image, image.naturalWidth > 0 ? 'loaded' : 'error');
        }

        function bindViewerImage(image) {
            if (boundViewerImages.has(image)) return;

            boundViewerImages.add(image);
            image.addEventListener('click', function () {
                openZoomModal(this.currentSrc || this.src);
            });
        }

        function syncConversationImages() {
            const messagesContainer = document.getElementById('messages-container');
            if (!messagesContainer) return;

            messagesContainer.querySelectorAll('.admin-chat-image-frame .zoomable-image').forEach(image => {
                syncThumbnailImage(image);
                bindViewerImage(image);
            });
            document.querySelectorAll('#chat-context-panel .zoomable-image').forEach(bindViewerImage);
        }

        function scheduleConversationLifecycleSync() {
            if (lifecycleSyncFrame !== null) return;

            lifecycleSyncFrame = window.requestAnimationFrame(() => {
                lifecycleSyncFrame = null;
                applyStoredPanelWidths();
                syncConversationImages();
                attachTextareaEvents();
            });
        }

        function fitToScreen() {
            if (!zoomContainer || !modalImage?.naturalWidth || !modalImage?.naturalHeight) return;

            const stageStyle = window.getComputedStyle(zoomContainer);
            const horizontalPadding = parseFloat(stageStyle.paddingLeft || 0) + parseFloat(stageStyle.paddingRight || 0);
            const verticalPadding = parseFloat(stageStyle.paddingTop || 0) + parseFloat(stageStyle.paddingBottom || 0);
            const availableWidth = Math.max(1, zoomContainer.clientWidth - horizontalPadding);
            const availableHeight = Math.max(1, zoomContainer.clientHeight - verticalPadding);
            const fitRatio = Math.min(
                1,
                availableWidth / modalImage.naturalWidth,
                availableHeight / modalImage.naturalHeight
            );

            modalImage.style.width = `${modalImage.naturalWidth * fitRatio}px`;
            modalImage.style.height = `${modalImage.naturalHeight * fitRatio}px`;
            currentScale = 1;
            currentX = 0;
            currentY = 0;
            isDragging = false;
            updateTransform();
        }

        function resetZoom() {
            fitToScreen();
        }

        function updateViewerChrome() {
            const hasGallery = viewerImages.length > 1;
            viewerCounter.hidden = !hasGallery;
            viewerCounter.textContent = `${viewerIndex + 1} / ${viewerImages.length}`;
            viewerPrev.hidden = !hasGallery;
            viewerNext.hidden = !hasGallery;
            viewerScale.textContent = `${Math.round(currentScale * 100)}%`;
            viewerZoomOut.disabled = currentScale <= 1;
            viewerZoomIn.disabled = currentScale >= 4;
        }

        function renderViewerImage(index) {
            if (!viewerImages.length) return;
            viewerIndex = (index + viewerImages.length) % viewerImages.length;
            const src = viewerImages[viewerIndex];
            currentScale = 1;
            currentX = 0;
            currentY = 0;
            isDragging = false;
            updateTransform();
            zoomContainer.classList.remove('is-ready', 'is-error');
            zoomContainer.classList.add('is-loading');
            modalImage.src = src;
            viewerOpenOriginal.href = src;
            viewerDownload.href = src;
            updateViewerChrome();
        }

        function openZoomModal(imageSrc) {
            viewerImages = collectViewerImages();
            if (!viewerImages.includes(imageSrc)) viewerImages.push(imageSrc);
            viewerIndex = Math.max(0, viewerImages.indexOf(imageSrc));
            previousBodyOverflow = document.body.style.overflow;
            modal.classList.remove('is-closing');
            modal.classList.add('active');
            modal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('chat-image-viewer-open');
            document.body.style.overflow = 'hidden';
            renderViewerImage(viewerIndex);
            closeBtn.focus({ preventScroll: true });
        }

        function closeZoomModal() {
            if (!modal.classList.contains('active')) return;
            modal.classList.remove('active');
            modal.classList.add('is-closing');
            window.setTimeout(() => {
                modal.classList.remove('is-closing');
                modal.setAttribute('aria-hidden', 'true');
                document.body.classList.remove('chat-image-viewer-open');
                document.body.style.overflow = previousBodyOverflow;
                currentScale = 1;
                currentX = 0;
                currentY = 0;
                isDragging = false;
                updateTransform();
                modalImage.removeAttribute('src');
                modalImage.style.removeProperty('width');
                modalImage.style.removeProperty('height');
                viewerImages = [];
            }, 160);
        }

        function updateTransform() {
            modalImage.style.transform = `translate(${currentX}px, ${currentY}px) scale(${currentScale})`;
            if (viewerScale) viewerScale.textContent = `${Math.round(currentScale * 100)}%`;
            if (viewerZoomOut) viewerZoomOut.disabled = currentScale <= 1;
            if (viewerZoomIn) viewerZoomIn.disabled = currentScale >= 4;
            modalImage.style.cursor = currentScale > 1 ? (isDragging ? 'grabbing' : 'grab') : 'zoom-in';
        }

        function setScale(nextScale) {
            currentScale = Math.min(4, Math.max(1, nextScale));
            if (currentScale === 1) {
                currentX = 0;
                currentY = 0;
            }
            updateTransform();
        }

        zoomContainer.addEventListener('wheel', function (e) {
            if (!modal.classList.contains('active')) return;
            e.preventDefault();
            e.stopPropagation();
            setScale(currentScale + (e.deltaY < 0 ? 0.2 : -0.2));
        }, {
            passive: false
        });

        modalImage.addEventListener('pointerdown', function (e) {
            if (currentScale <= 1) return;
            isDragging = true;
            startX = e.clientX - currentX;
            startY = e.clientY - currentY;
            modalImage.setPointerCapture?.(e.pointerId);
            updateTransform();
        });

        modalImage.addEventListener('pointermove', function (e) {
            if (!isDragging) return;
            currentX = e.clientX - startX;
            currentY = e.clientY - startY;
            updateTransform();
        });

        const stopDragging = () => {
            isDragging = false;
            updateTransform();
        };
        modalImage.addEventListener('pointerup', stopDragging);
        modalImage.addEventListener('pointercancel', stopDragging);

        modalImage.addEventListener('dblclick', function () {
            resetZoom();
        });

        modalImage.addEventListener('load', () => {
            window.requestAnimationFrame(() => {
                fitToScreen();
                zoomContainer.classList.remove('is-loading', 'is-error');
                zoomContainer.classList.add('is-ready');
            });
        });

        modalImage.addEventListener('error', () => {
            zoomContainer.classList.remove('is-loading', 'is-ready');
            zoomContainer.classList.add('is-error');
        });

        syncConversationImages();

        closeBtn.addEventListener('click', closeZoomModal);
        viewerPrev.addEventListener('click', () => renderViewerImage(viewerIndex - 1));
        viewerNext.addEventListener('click', () => renderViewerImage(viewerIndex + 1));
        viewerZoomOut.addEventListener('click', () => setScale(currentScale - 0.25));
        viewerZoomIn.addEventListener('click', () => setScale(currentScale + 0.25));
        viewerReset.addEventListener('click', resetZoom);

        window.addEventListener('resize', () => {
            if (modal.classList.contains('active')) fitToScreen();
        });

        modal.addEventListener('click', (e) => {
            if (e.target === modal) closeZoomModal();
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && modal.classList.contains('active')) {
                closeZoomModal();
            }
            if (e.key === 'ArrowLeft' && modal.classList.contains('active') && viewerImages.length > 1) {
                renderViewerImage(viewerIndex - 1);
            }
            if (e.key === 'ArrowRight' && modal.classList.contains('active') && viewerImages.length > 1) {
                renderViewerImage(viewerIndex + 1);
            }
        });

        if (typeof Livewire !== 'undefined') {
            Livewire.hook('morph.updated', ({ el }) => {
                const chatRoot = document.getElementById('chat-root');
                if (!chatRoot || (el && el !== chatRoot && !chatRoot.contains(el))) return;

                scheduleConversationLifecycleSync();
            });
        }

        Livewire.on('reset-message-input', () => {
            const textarea = document.getElementById('message-input-textarea');
            if (textarea) {
                textarea.value = '';
                textarea.style.height = 'auto';
                textarea.focus();
                autoResizeTextarea();
            }
        });

        Livewire.on('app-dialog', (data) => {
            AppDialog.notice({
                icon: data[0].type || 'info',
                title: data[0].title || '',
                text: data[0].text || '',
                timer: 2500,
            });
        });
    }, { once: true });

    function confirmChangeStatusOfUser(id, status) {
        let title = "";
        let message = "";
        if (status === "banned") {
            title = "Xác nhận mở khóa tài khoản?"
            message = "Bạn có chắc muốn mở khóa tài khoản người dùng này?"
        } else {
            title = "Xác nhận khóa tài khoản?"
            message = "Bạn có chắc muốn khóa tài khoản người dùng này?"
        }
        AppDialog.confirm({
            title: title,
            text: message,
            icon: "warning",
            buttons: true,
            dangerMode: true,
        })
            .then((isConfirmed) => {
                if (isConfirmed) {
                    Livewire.dispatch('change-status-user', {
                        id: id
                    });
                }
            });
    }

    // Delete Modal Handlers
    let deleteMessagesModal = null;
    let deleteConversationModal = null;
    let deleteSuccessModal = null;

    // Hiển thị modal xóa tin nhắn
    function confirmDeleteMessages() {
        deleteMessagesModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('deleteMessagesModal'));
        deleteMessagesModal.show();
    }

    // Hiển thị modal xóa hội thoại
    function confirmDeleteConversation() {
        deleteConversationModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('deleteConversationModal'));
        deleteConversationModal.show();
    }


    // Delegate clicks because React mounts this markup after DOMContentLoaded
    // and Livewire/SPA navigation can replace the confirmation buttons.
    document.addEventListener('click', function (event) {
        const chatRoot = document.getElementById('chat-root');
        if (!chatRoot || !chatRoot.contains(event.target)) return;

        // Xử lý xóa tin nhắn
        const confirmDeleteMessagesBtn = event.target.closest('#confirmDeleteMessagesBtn');
        if (confirmDeleteMessagesBtn && !confirmDeleteMessagesBtn.disabled) {
            // Disable button và show loading
            confirmDeleteMessagesBtn.disabled = true;
            confirmDeleteMessagesBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Đang xóa...';

            // Get Livewire component
            // Call Livewire method deleteAllMessages
            Promise.resolve().then(() => {
                const component = window.Livewire.find(chatRoot.getAttribute('wire:id'));
                return component.call('deleteAllMessages');
            }).then(deleted => {
                // Close delete modal
                deleteMessagesModal.hide();

                confirmDeleteMessagesBtn.disabled = false;
                confirmDeleteMessagesBtn.innerHTML = '<i class="fas fa-eraser me-2"></i>Xóa tin nhắn';
                if (deleted !== true) return;

                setTimeout(() => {
                    if (document.getElementById('chat-root') !== chatRoot) return;
                    document.getElementById('deleteSuccessMessage').textContent = 'Tất cả tin nhắn đã được xóa.';
                    deleteSuccessModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('deleteSuccessModal'));
                    deleteSuccessModal.show();
                }, 300);
            }).catch(error => {
                console.error('Error deleting messages:', error);
                confirmDeleteMessagesBtn.disabled = false;
                confirmDeleteMessagesBtn.innerHTML = '<i class="fas fa-eraser me-2"></i>Xóa tin nhắn';
                deleteMessagesModal.hide();
                alert('Có lỗi xảy ra khi xóa tin nhắn!');
            });
        }

        // Xử lý xóa hội thoại
        const confirmDeleteConversationBtn = event.target.closest('#confirmDeleteConversationBtn');
        if (confirmDeleteConversationBtn && !confirmDeleteConversationBtn.disabled) {
            // Disable button và show loading
            confirmDeleteConversationBtn.disabled = true;
            confirmDeleteConversationBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Đang xóa...';

            // Get Livewire component
            // Call Livewire method deleteConversation
            Promise.resolve().then(() => {
                const component = window.Livewire.find(chatRoot.getAttribute('wire:id'));
                return component.call('deleteConversation');
            }).then(deleted => {
                if (deleted !== true) throw new Error('Conversation deletion was rejected');
                // Close delete modal
                deleteConversationModal.hide();

                // Show success modal
                setTimeout(() => {
                    if (document.getElementById('chat-root') !== chatRoot) return;
                    document.getElementById('deleteSuccessMessage').textContent = 'Hội thoại đã được xóa thành công';
                    deleteSuccessModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('deleteSuccessModal'));
                    deleteSuccessModal.show();

                    // Reset button
                    confirmDeleteConversationBtn.disabled = false;
                    confirmDeleteConversationBtn.innerHTML = '<i class="fas fa-trash-alt me-2"></i>Xóa hội thoại';
                }, 300);
            }).catch(error => {
                console.error('Error deleting conversation:', error);
                confirmDeleteConversationBtn.disabled = false;
                confirmDeleteConversationBtn.innerHTML = '<i class="fas fa-trash-alt me-2"></i>Xóa hội thoại';
                deleteConversationModal.hide();
                alert('Có lỗi xảy ra khi xóa hội thoại!');
            });
        }
    });


    window.confirmDeleteSingleMessage = function(messageId) {
        AppDialog.confirm({
            title: 'Xóa tin nhắn?',
            text: "Bạn có chắc chắn muốn xóa tin nhắn này không?",
            icon: 'warning',
            dangerMode: true,
            confirmText: 'Đồng ý xóa',
            cancelText: 'Hủy'
        }).then((result) => {
            if (result) {
                Livewire.dispatch('delete-single-message', { messageId: messageId });
            }
        })
    }
</script>
</div>

@push('scripts')
    @vite('resources/js/admin/chat.js')
    @vite('resources/js/admin/chat/chat-panel.js')
@endpush
