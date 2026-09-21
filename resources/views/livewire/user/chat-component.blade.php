@php
    $supportAgent = $conversation?->staff;
    $supportIsOnline = $supportAgent?->isOnline() ?? false;
@endphp

<div id="chat-root">
    <div class="floating-chat-container" x-data="{
        isOpen: @entangle('showBox'),
        isLoading: false,
        showQuick: @entangle('showQuickReplies'),
        showReferencePicker: @entangle('showReferencePicker'),
        dragging: false,
        moved: false,
        offsetX: 0,
        offsetY: 0,
        startDrag(event) {
            if (event.button !== undefined && event.button !== 0) return;
            const rect = $el.getBoundingClientRect();
            this.dragging = true;
            this.moved = false;
            this.offsetX = event.clientX - rect.left;
            this.offsetY = event.clientY - rect.top;
            $el.classList.add('is-dragging');
            event.currentTarget.setPointerCapture?.(event.pointerId);
        },
        drag(event) {
            if (!this.dragging) return;
            this.moved = true;
            const maxLeft = window.innerWidth - $el.offsetWidth;
            const maxTop = window.innerHeight - $el.offsetHeight;
            $el.style.setProperty('left', `${Math.max(0, Math.min(event.clientX - this.offsetX, maxLeft))}px`, 'important');
            $el.style.setProperty('top', `${Math.max(0, Math.min(event.clientY - this.offsetY, maxTop))}px`, 'important');
            $el.style.setProperty('right', 'auto', 'important');
            $el.style.setProperty('bottom', 'auto', 'important');
        },
        endDrag() {
            if (!this.dragging) return;
            this.dragging = false;
            $el.classList.remove('is-dragging');
            const rect = $el.getBoundingClientRect();
            const edgeInset = 16;
            const left = rect.left + rect.width / 2 < window.innerWidth / 2
                ? edgeInset
                : window.innerWidth - rect.width - edgeInset;
            const top = Math.max(edgeInset, Math.min(rect.top, window.innerHeight - rect.height - edgeInset));
            $el.style.setProperty('left', `${left}px`, 'important');
            $el.style.setProperty('top', `${top}px`, 'important');
            $el.style.setProperty('right', 'auto', 'important');
            $el.style.setProperty('bottom', 'auto', 'important');
            localStorage.setItem('chatBubblePosition', JSON.stringify({ left, top }));
            setTimeout(() => { this.moved = false; }, 0);
        },
        suppressClick(event) {
            if (this.moved) {
                event.preventDefault();
                event.stopPropagation();
            }
        },
        restorePosition() {
            const saved = JSON.parse(localStorage.getItem('chatBubblePosition') || 'null');
            if (!saved) return;
            const edgeInset = 16;
            const maxLeft = window.innerWidth - $el.offsetWidth - edgeInset;
            const maxTop = window.innerHeight - $el.offsetHeight - edgeInset;
            $el.style.setProperty('left', `${Math.max(edgeInset, Math.min(saved.left, maxLeft))}px`, 'important');
            $el.style.setProperty('top', `${Math.max(edgeInset, Math.min(saved.top, maxTop))}px`, 'important');
            $el.style.setProperty('right', 'auto', 'important');
            $el.style.setProperty('bottom', 'auto', 'important');
        }
    }" x-init="restorePosition()" :class="{ 'is-open': isOpen }" @pointermove.window="drag($event)" @pointerup.window="endDrag()">
        <!-- Floating Chat Button -->
        <button class="floating-chat-button"
            @pointerdown="startDrag($event)"
            @click="suppressClick($event); if (!moved) { isOpen = true; $wire.call('toggleBox', true) }"
            x-show="!isOpen" type="button" aria-label="Mở hỗ trợ khách hàng">
            <span x-show="!isLoading">
                <i class="fa-solid fa-comments"></i>
                @if($unreadCount > 0)
                    <span class="chat-badge-count">{{ $unreadCount > 99 ? '99+' : $unreadCount }}</span>
                @endif
            </span>
            <span x-show="isLoading" style="display: none;">
                <i class="fa fa-spinner fa-spin"></i>
            </span>
        </button>

        <!-- Hộp thoại chat -->
        <div class="floating-chat-window" x-show="isOpen" x-transition:enter="chat-enter"
            x-transition:leave="chat-leave" style="display: none;" wire:init="scrollToBottom" id="box_arround">

            <!-- Header với gradient -->
            <div class="p-3 d-flex justify-content-between align-items-center"
                style="background: linear-gradient(135deg, #000000 0%, #000000 100%); color: white;">
                <div class="d-flex align-items-center">
                    <div class="chat-support-mark" aria-hidden="true">
                        <i class="fa-solid fa-headset"></i>
                    </div>
                    <div class="ms-2">
                        <div class="fw-bold" style="font-size: 14px;">{{__('home.HoTroKhachHang')}}</div>
                        <div class="text-start {{ $supportIsOnline ? 'is-online' : '' }}" style="font-size: 11px; opacity: 0.9;">
                            Bộ phận CSKH
                            @if($supportIsOnline)
                                · {{ __('home.DangTrucTuyen') }}
                            @endif
                        </div>
                    </div>
                </div>
                <button
                    @click="isOpen = false; $wire.call('toggleBox', false)"
                    class="btn-close-chat" type="button" aria-label="Đóng trò chuyện">
                    <i class="fa-solid fa-times"></i>
                </button>
            </div>

            <!-- Danh sách tin nhắn: Dùng column-reverse để neo tin nhắn mới nhất xuống đáy tự động -->
            <div class="p-3 position-relative"
                style="height: 300px; overflow-y: auto; overflow-x: hidden; background: #f8f9fa; display: flex; flex-direction: column-reverse;"
                id="chat-messages-container">
                @if($chatMessages->count() == 0)
                    <div class="text-center text-muted py-3">
                        <i class="fa fa-comments fa-2x mb-2"></i>
                        <div>{{__('home.ChaoBanChungToiCoTheGiupGiChoBan')}}</div>
                    </div>
                @endif

                <div class="chat-messages-content" style="display: flex; flex-direction: column-reverse; width: 100%;">
                    @foreach ($chatMessages as $msg)
                        @php
                            $isCurrentUser = (is_array($msg) ? $msg['sender_id'] : $msg->sender_id) == auth()->id();
                            $message = is_array($msg) ? $msg['message'] : $msg->message;
                            $type = is_array($msg) ? $msg['type'] : $msg->type;
                            $kind = is_array($msg) ? ($msg['kind'] ?? $type) : ($msg->kind ?: $type);
                            $isReference = str_ends_with($kind, '_reference');
                            $imagePath = is_array($msg) ? $msg['image_path'] : $msg->image_path;
                            $createdAt = is_array($msg) ? $msg['created_at'] : $msg->created_at;
                            $messageId = is_array($msg) ? $msg['id'] : $msg->id;
                            $isRead = is_array($msg) ? ($msg['is_read'] ?? false) : ($msg->is_read ?? false);
                            $senderName = is_array($msg)
                                ? ($msg['sender']['full_name'] ?? 'User')
                                : ($msg->sender->full_name ?? 'User');
                            $messageDate = \Carbon\Carbon::parse($createdAt)->setTimezone('Asia/Ho_Chi_Minh');
                            $nextMessage = $chatMessages->get($loop->index + 1);
                            $nextCreatedAt = $nextMessage
                                ? (is_array($nextMessage) ? $nextMessage['created_at'] : $nextMessage->created_at)
                                : null;
                            $showDateSeparator = !$nextCreatedAt
                                || !$messageDate->isSameDay(\Carbon\Carbon::parse($nextCreatedAt)->setTimezone('Asia/Ho_Chi_Minh'));
                        @endphp

                        @if($isCurrentUser)
                            <!-- Tin nhắn của user -->
                            <div class="d-flex justify-content-end mb-3 chat-message-row {{ $isReference ? 'has-structured-message' : '' }}"
                                wire:key="msg-{{ is_array($msg) ? $msg['id'] : $msg->id }}" style="min-width: 0;">
                                <div class="d-flex align-items-end chat-message-track {{ $isReference ? 'has-structured-message' : '' }}">
                                    <div class="me-2 chat-message-stack chat-message-stack--sent {{ $isReference ? 'chat-message-stack--structured' : '' }}">
                                        @if($isReference)
                                            <div class="chat-structured-message">
                                                <x-chat.reference-card :message="$msg" audience="user" />
                                            </div>
                                        @elseif($type === 'image' && $imagePath)
                                            <div class="chat-image-message-block chat-image-message-block--sent">
                                                <button type="button" class="chat-image-message" onclick="openImageModal(this.querySelector('img').src)" aria-label="Xem ảnh đã gửi">
                                                    <span class="chat-image-loading" aria-hidden="true"><i class="fas fa-circle-notch fa-spin"></i></span>
                                                    <img src="{{ Storage::disk('public')->url($imagePath) }}" alt="Ảnh đã gửi"
                                                        loading="lazy" decoding="async">
                                                    <span class="chat-image-error"><i class="fas fa-image"></i> Không thể tải ảnh</span>
                                                </button>
                                                @if(trim((string) $message) !== '')
                                                    <div class="message-bubble chat-image-caption text-start">{{ trim($message) }}</div>
                                                @endif
                                            </div>
                                        @else
                                            <div class="message-bubble text-start" style="display: inline-block; width: fit-content; max-width: 100%; margin: 0; border-radius: 16px;">{{ trim($message) }}</div>
                                        @endif
                                        <div class="text-end mt-1 d-flex align-items-center justify-content-end gap-1"
                                            style="font-size: 10px; color: #6c757d;">
                                            <span>{{ \Carbon\Carbon::parse($createdAt)->setTimezone('Asia/Ho_Chi_Minh')->format('H:i') }}</span>
                                            <span data-message-id="{{ $messageId }}"
                                                data-seen-status="{{ $isRead ? 'true' : 'false' }}">
                                                @if($isRead)
                                                    <i class="fas fa-check-double text-info" style="font-size: 10px;"
                                                        title="Đã xem"></i>
                                                @else
                                                    <i class="fas fa-check" style="font-size: 10px; color: #6c757d;"
                                                        title="Đã gửi"></i>
                                                @endif
                                            </span>
                                        </div>
                                    </div>
                                    <img src="https://ui-avatars.com/api/?name={{ urlencode($senderName) }}&background=667eea&color=ffffff&size=28&rounded=true"
                                        alt="You" class="rounded-circle flex-shrink-0" width="28" height="28">
                                </div>
                            </div>
                        @else
                            <!-- Tin nhắn của support -->
                            <div class="d-flex justify-content-start mb-3 chat-message-row {{ $isReference ? 'has-structured-message' : '' }}"
                                wire:key="msg-{{ is_array($msg) ? $msg['id'] : $msg->id }}" style="min-width: 0;">
                                <div class="d-flex align-items-end chat-message-track {{ $isReference ? 'has-structured-message' : '' }}">
                                    <img src="https://ui-avatars.com/api/?name=Support&background=28a745&color=ffffff&size=28&rounded=true&bold=true"
                                        alt="Support" class="rounded-circle flex-shrink-0" width="28" height="28">
                                    <div class="ms-2 chat-message-stack chat-message-stack--received {{ $isReference ? 'chat-message-stack--structured' : '' }}">
                                        @if($isReference)
                                            <div class="chat-structured-message">
                                                <x-chat.reference-card :message="$msg" audience="user" />
                                            </div>
                                        @elseif($type === 'image' && $imagePath)
                                            <div class="chat-image-message-block chat-image-message-block--received">
                                                <button type="button" class="chat-image-message" onclick="openImageModal(this.querySelector('img').src)" aria-label="Xem ảnh hỗ trợ gửi">
                                                    <span class="chat-image-loading" aria-hidden="true"><i class="fas fa-circle-notch fa-spin"></i></span>
                                                    <img src="{{ Storage::disk('public')->url($imagePath) }}" alt="Ảnh hỗ trợ gửi"
                                                        loading="lazy" decoding="async">
                                                    <span class="chat-image-error"><i class="fas fa-image"></i> Không thể tải ảnh</span>
                                                </button>
                                                @if(trim((string) $message) !== '')
                                                    <div class="message-bubble chat-image-caption member-message text-start">{{ trim($message) }}</div>
                                                @endif
                                            </div>
                                        @else
                                            <div class="message-bubble rounded-4 position-relative member-message text-start"
                                                style="transition: all 0.2s ease; display: inline-block; width: fit-content; max-width: 100%; margin: 0;">{{ trim($message) }}</div>
                                        @endif
                                        <div class="mt-1 ps-2" style="font-size: 10px; color: #6c757d;text-align:left;">
                                            {{ __('home.HoTro') }} · {{ \Carbon\Carbon::parse($createdAt)->setTimezone('Asia/Ho_Chi_Minh')->format('H:i') }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if($showDateSeparator)
                            <div class="chat-date-separator" aria-label="{{ $messageDate->format('d/m/Y') }}">
                                <span>{{ $messageDate->locale(app()->getLocale())->translatedFormat('d M Y') }}</span>
                            </div>
                        @endif
                    @endforeach

                    <!-- Nút load more messages: Đặt sau foreach để lật lên đỉnh -->
                    @if($hasMoreMessages)
                        <div class="text-center py-2 mb-2" wire:loading.remove wire:target="loadMoreMessages">
                            <button onclick="loadMoreMessagesManual()" class="btn btn-sm btn-outline-dark rounded-pill">
                                <i class="fa fa-chevron-up me-1"></i>
                                {{__('home.TaiTinNhanCuHon')}}
                            </button>
                        </div>
                        
                        <div class="text-center py-2 mb-2" wire:loading wire:target="loadMoreMessages">
                            <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                            <small class="text-muted ms-2">{{__('home.DangTaiTinNhanCu')}}</small>
                        </div>
                    @endif
                </div><!-- End reverse wrapper -->
            </div>

            <!-- Gợi ý tin nhắn nhanh (Quick Replies) -->
            @if(config('chat.quick_replies.enabled', true) && count($quickReplySuggestions) > 0)
                <style>
                    .quick-reply-btn,
                    .quick-reply-btn:disabled,
                    .quick-reply-btn span,
                    .quick-reply-btn i {
                        color: #495057 !important;
                        -webkit-text-fill-color: #495057 !important;
                    }
                </style>
                <div class="px-2 px-sm-3 py-2" style="background: #f8f9fa; border-top: 1px solid #e9ecef;"
                     x-show="showQuick" x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 transform -translate-y-2"
                     x-transition:enter-end="opacity-100 transform translate-y-0"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100 transform translate-y-0"
                     x-transition:leave-end="opacity-0 transform -translate-y-2">
                     <div class="d-flex justify-content-between align-items-center mb-2 px-1">
                        <small class="text-muted fw-bold" style="font-size: 10px; text-transform: uppercase; letter-spacing: 0.5px;">
                            <i class="fa fa-lightbulb me-1 text-warning"></i> Gợi ý nhanh
                        </small>
                        <button wire:click="hideQuickReplies" class="btn btn-link btn-sm p-0 text-muted"
                            style="font-size: 14px; text-decoration: none; line-height: 1;">
                            <i class="fa fa-times"></i>
                        </button>
                    </div>
                    <div class="d-flex flex-wrap gap-2" x-data="{ loadingIndex: null }" x-on:message-sent.window="loadingIndex = null">
                        @foreach($quickReplySuggestions as $index => $suggestion)
                            <button wire:click="useQuickReply('{{ addslashes($suggestion) }}')"
                                @click="loadingIndex = {{ $index }}" class="btn btn-sm quick-reply-btn" style="background: white; 
                                                               border: 1px solid #dee2e6; 
                                                               border-radius: 16px; 
                                                               padding: 6px 12px;
                                                               font-size: 12px;
                                                               color: #495057 !important;
                                                               transition: all 0.2s ease;
                                                               white-space: nowrap;
                                                               position: relative;
                                                               min-width: fit-content;" x-bind:disabled="loadingIndex !== null"
                                x-bind:style="loadingIndex !== null && loadingIndex !== {{ $index }} ? 'opacity: 0.5; cursor: not-allowed;' : ''"
                                onmouseover="if (!this.disabled) {
                                                                                                this.style.background='#f1f3f5';
                                                                                                this.style.borderColor='#adb5bd';
                                                                     }" onmouseout="if (!this.disabled) {
                                                                        this.style.background='white'; 
                                                                        this.style.borderColor='#dee2e6';
                                                                    }">
                                <span x-show="loadingIndex !== {{ $index }}">{{ $suggestion }}</span>
                                <span x-show="loadingIndex === {{ $index }}" style="display: none;">
                                    <i class="fa fa-spinner fa-spin"></i> Đang gửi...
                                </span>
                            </button>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Nút toggle Quick Replies - Thiết kế gọn và căn giữa -->
            @if(config('chat.quick_replies.enabled', true) && count($quickReplySuggestions) > 0)
                <div class="text-center" style="background: #f8f9fa; border-top: 1px solid #e9ecef; line-height: 1;">
                    <button type="button"
                        @click="showQuick = !showQuick"
                        class="btn btn-link p-0 m-0" style="color: {{ $showQuickReplies ? '#000000' : '#adb5bd' }};
                                           font-size: 14px;
                                           line-height: 1;
                                           padding: 2px 10px;
                                           transition: all 0.2s ease;
                                           text-decoration: none;
                                           display: inline-block;"
                        onmouseover="this.style.color='#000000'; this.style.transform='scale(1.2)';"
                        onmouseout="this.style.color='{{ $showQuickReplies ? '#000000' : '#adb5bd' }}'; this.style.transform='scale(1)';"
                        title="{{ $showQuickReplies ? '▼ Ẩn gợi ý tin nhắn' : '▲ Hiển thị gợi ý tin nhắn' }}">
                        <i class="fa fa-chevron-{{ $showQuickReplies ? 'down' : 'up' }}"></i>
                    </button>
                </div>
            @endif

            <!-- Form nhập với design hiện đại -->
            @if($showReferencePicker)
                <section class="chat-reference-picker" aria-label="Chọn nội dung liên quan">
                    <header class="chat-reference-picker-head">
                        <div><small>Đính kèm ngữ cảnh</small><strong>Chọn nội dung cần hỗ trợ</strong></div>
                        <button type="button" wire:click="closeReferencePicker" aria-label="Đóng"><i class="fas fa-xmark"></i></button>
                    </header>
                    <div class="chat-reference-tabs" role="tablist">
                        <button type="button" wire:click="selectReferenceTab('order')" class="{{ $referenceTab === 'order' ? 'active' : '' }}"><i class="fas fa-box"></i> Đơn hàng</button>
                        <button type="button" wire:click="selectReferenceTab('transaction')" class="{{ $referenceTab === 'transaction' ? 'active' : '' }}"><i class="fas fa-receipt"></i> Giao dịch</button>
                    </div>
                    <div class="chat-reference-options">
                        @forelse($this->referenceItems as $item)
                            @if($referenceTab === 'order')
                                <button type="button" class="chat-reference-option" wire:click="sendOrderReference({{ $item['id'] }})" wire:loading.attr="disabled">
                                    <span class="reference-option-icon"><i class="fas fa-box"></i></span>
                                    <span class="reference-option-copy"><strong>{{ $item['code'] }}</strong><small>{{ $item['name'] ?: 'Đơn hàng' }} · {{ \Carbon\Carbon::parse($item['created_at'])->format('d/m/Y') }}</small></span>
                                    @if($item['amount'] !== null)<b>{{ format_money($item['amount'], 5) }}$</b>@endif
                                    <i class="fas fa-arrow-right"></i>
                                </button>
                            @else
                                <button type="button" class="chat-reference-option" wire:click="sendTransactionReference('{{ $item['source'] }}', {{ $item['id'] }})" wire:loading.attr="disabled">
                                    <span class="reference-option-icon"><i class="fas fa-receipt"></i></span>
                                    <span class="reference-option-copy"><strong>{{ ['deposit'=>'Nạp tiền','withdraw'=>'Rút tiền','order'=>'Thanh toán đơn','profit'=>'Hoa hồng','penalty'=>'Tiền phạt'][$item['type']] ?? $item['type'] }}</strong><small>{{ strtoupper($item['source']) }}-{{ $item['id'] }} · {{ \Carbon\Carbon::parse($item['created_at'])->format('d/m/Y H:i') }}</small></span>
                                    <b>{{ format_money($item['amount'], 5) }}$</b>
                                    <i class="fas fa-arrow-right"></i>
                                </button>
                            @endif
                        @empty
                            <div class="chat-reference-empty"><i class="fas fa-inbox"></i><span>Chưa có dữ liệu để đính kèm.</span></div>
                        @endforelse
                    </div>
                </section>
            @endif

            <form class="p-3" style="background: white; border-top: 1px solid #e9ecef;" x-data="{ 
                  formSending: false,
                  hasImage: {{ $selectedImage ? 'true' : 'false' }},
                  attachmentMenuOpen: false
              }"
              x-on:keydown.escape.window="attachmentMenuOpen = false"
              x-on:submit.prevent="
                  if (!formSending) {
                      const input = $el.querySelector('#chat-input-field');
                      const val = input ? input.value.trim() : '';
                      
                      // Kiểm tra có tin nhắn hoặc ảnh không
                      if (!val && !hasImage) {
                          return; // Không làm gì nếu không có text và không có ảnh
                      }
                      
                      formSending = true;
                      // Clear input và reset height NGAY LẬP TỨC
                      if (input) {
                          input.value = '';
                          input.style.height = 'auto';
                      }
                      
                      // Gọi Livewire
                      $wire.set('newMessage', val);
                      $wire.call('sendMessage').finally(() => {
                          formSending = false;
                          if (input) input.focus();
                      });
                  }
              ">

                <!-- Preview ảnh đã chọn -->
                @if($selectedImage)
                    <div class="mb-3 p-2 border rounded" style="background: #f8f9fa;">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <small class="text-muted">Ảnh được chọn</small>
                            <button type="button" wire:click="removeImage" @click="hasImage = false" class="btn btn-sm btn-outline-danger">
                                <i class="fa fa-times"></i>
                            </button>
                        </div>
                        <div class="d-flex justify-content-center align-items-center">
                            <img src="{{ $selectedImage->temporaryUrl() }}" alt="Preview" class="img-fluid rounded"
                                style="max-width: 150px; max-height: 150px;">
                        </div>
                    </div>
                @endif

                <div class="d-flex align-items-center chat-composer-main"
                    style="background: #f8f9fa; border-radius: 25px; padding: 5px 15px; border: 1px solid #e9ecef;">
                    <div class="chat-attachment-anchor" @click.outside="attachmentMenuOpen = false">
                        <button type="button"
                            class="chat-attachment-toggle"
                            :class="{ 'is-open': attachmentMenuOpen }"
                            @click="attachmentMenuOpen = !attachmentMenuOpen"
                            :aria-expanded="attachmentMenuOpen.toString()"
                            aria-haspopup="menu"
                            aria-label="Mở menu đính kèm">
                            <i class="fas fa-plus" wire:loading.remove wire:target="selectedImage" aria-hidden="true"></i>
                            <i class="fas fa-spinner fa-spin" wire:loading wire:target="selectedImage" aria-hidden="true"></i>
                        </button>

                        <div class="chat-attachment-menu"
                            x-cloak
                            x-show="attachmentMenuOpen"
                            x-transition:enter="attachment-menu-enter"
                            x-transition:enter-start="attachment-menu-enter-start"
                            x-transition:enter-end="attachment-menu-enter-end"
                            x-transition:leave="attachment-menu-leave"
                            x-transition:leave-start="attachment-menu-leave-start"
                            x-transition:leave-end="attachment-menu-leave-end"
                            role="menu"
                            aria-label="Chọn nội dung đính kèm">
                            <label for="image-upload" class="chat-attachment-action" role="menuitem" tabindex="0"
                                @click="attachmentMenuOpen = false"
                                @keydown.enter.prevent="$el.click()"
                                @keydown.space.prevent="$el.click()">
                                <span class="chat-attachment-action-icon is-image"><i class="fas fa-image" aria-hidden="true"></i></span>
                                <span class="chat-attachment-action-copy"><strong>Ảnh</strong><small>Chọn từ thiết bị</small></span>
                            </label>
                            <button type="button" class="chat-attachment-action" role="menuitem"
                                wire:click="openReferencePicker('order')" @click="attachmentMenuOpen = false">
                                <span class="chat-attachment-action-icon is-order"><i class="fas fa-box" aria-hidden="true"></i></span>
                                <span class="chat-attachment-action-copy"><strong>Đơn hàng</strong><small>Đính kèm đơn liên quan</small></span>
                            </button>
                            <button type="button" class="chat-attachment-action" role="menuitem"
                                wire:click="openReferencePicker('transaction')" @click="attachmentMenuOpen = false">
                                <span class="chat-attachment-action-icon is-transaction"><i class="fas fa-receipt" aria-hidden="true"></i></span>
                                <span class="chat-attachment-action-copy"><strong>Giao dịch</strong><small>Đính kèm giao dịch</small></span>
                            </button>
                        </div>

                        <input type="file" wire:model="selectedImage" id="image-upload" accept="image/*"
                            style="display: none;" @change="hasImage = $event.target.files.length > 0; attachmentMenuOpen = false">
                    </div>

                    <textarea wire:model="newMessage" class="form-control border-0 bg-transparent flex-grow-1"
                        placeholder="{{__('home.NhapTinNhanCuaBan')}}" id="chat-input-field" autocomplete="off" rows="1"
                        title="Enter để gửi · Shift+Enter để xuống dòng"
                        style="font-size: 13px; resize: none; overflow-y: hidden; max-height: 100px; padding: 8px 0; line-height: 1.5; box-shadow: none;"
                        x-on:input="
                        $el.style.height = 'auto';
                        $el.style.height = Math.min($el.scrollHeight, 100) + 'px';
                    " x-on:keydown.enter.prevent="
                        if (!$event.shiftKey) {
                            if (!formSending && ($el.value.trim() || hasImage)) {
                                $el.closest('form').dispatchEvent(new Event('submit', { bubbles: true }));
                            }
                        } else {
                            // Shift+Enter: cho phép xuống dòng (mặc định của textarea)
                            const start = $el.selectionStart;
                            const end = $el.selectionEnd;
                            const value = $el.value;
                            $el.value = value.substring(0, start) + '\n' + value.substring(end);
                            $el.selectionStart = $el.selectionEnd = start + 1;
                            $el.dispatchEvent(new Event('input', { bubbles: true }));
                        }
                    "></textarea>

                    <button type="submit" class="btn btn-link p-0 ms-2 d-flex align-items-center justify-content-center"
                        aria-label="Gửi tin nhắn"
                        style="color: #000000; font-size: 20px; flex-shrink: 0; width: 30px; height: 30px; text-decoration: none;"
                        onmouseover="this.style.textDecoration='none'" onfocus="this.style.textDecoration='none'"
                        x-bind:disabled="formSending">
                        <i class="fa fa-paper-plane" x-show="!formSending" style="text-decoration: none;"></i>
                        <i class="fa fa-spinner fa-spin" x-show="formSending" style="display: none; text-decoration: none;"></i>
                    </button>
                </div>

                @error('newMessage')
                    <div class="text-danger mt-1" style="font-size: 11px;">
                        {{ $message }}
                    </div>
                @enderror

                @error('selectedImage')
                    <div class="text-danger mt-1" style="font-size: 11px;">
                        {{ $message }}
                    </div>
                @enderror
            </form>
        </div> <!-- End floating-chat-window -->

        <!-- Image viewer -->
        <div class="chat-image-viewer-overlay user-chat-image-viewer" id="imageModal" role="dialog" aria-modal="true" aria-labelledby="imageModalLabel" aria-hidden="true" wire:ignore>
            <div class="chat-image-viewer-shell">
                <header class="chat-image-viewer-header">
                    <div class="chat-image-viewer-title">
                        <strong id="imageModalLabel">Xem ảnh</strong>
                        <small>Ảnh trong cuộc trò chuyện</small>
                    </div>
                    <div class="chat-image-viewer-header-actions">
                        <span class="chat-image-viewer-counter" id="userImageViewerCounter" hidden></span>
                        <a class="chat-image-viewer-action is-secondary is-open-original" id="userImageViewerOpenOriginal" href="#" target="_blank" rel="noopener" aria-label="Mở ảnh gốc" title="Mở ảnh gốc">
                            <i class="fas fa-arrow-up-right-from-square" aria-hidden="true"></i>
                        </a>
                        <a class="chat-image-viewer-action is-secondary" id="userImageViewerDownload" href="#" download aria-label="Tải ảnh" title="Tải ảnh">
                            <i class="fas fa-download" aria-hidden="true"></i>
                        </a>
                        <button type="button" class="chat-image-viewer-action is-close" id="userImageViewerClose" aria-label="Đóng viewer" title="Đóng">
                            <i class="fas fa-xmark" aria-hidden="true"></i>
                        </button>
                    </div>
                </header>

                <div class="chat-image-viewer-stage is-loading" id="userImageViewerStage">
                    <span class="chat-image-viewer-loading" aria-hidden="true"><i class="fas fa-circle-notch fa-spin"></i></span>
                    <div class="chat-image-viewer-error" role="status">
                        <i class="fas fa-image" aria-hidden="true"></i>
                        <strong>Không thể tải ảnh</strong>
                        <small>Ảnh có thể đã được di chuyển hoặc không còn khả dụng.</small>
                    </div>
                    <button type="button" class="chat-image-viewer-nav is-prev" id="userImageViewerPrev" aria-label="Ảnh trước" hidden>
                        <i class="fas fa-chevron-left" aria-hidden="true"></i>
                    </button>
                    <img id="modalImage" src="" alt="Ảnh trong cuộc trò chuyện" class="chat-image-viewer-image" draggable="false">
                    <button type="button" class="chat-image-viewer-nav is-next" id="userImageViewerNext" aria-label="Ảnh tiếp theo" hidden>
                        <i class="fas fa-chevron-right" aria-hidden="true"></i>
                    </button>
                </div>

                <div class="chat-image-viewer-toolbar">
                    <div class="chat-image-viewer-controls">
                        <button type="button" class="chat-image-viewer-control" id="userImageViewerZoomOut" aria-label="Thu nhỏ" title="Thu nhỏ"><i class="fas fa-minus"></i></button>
                        <span class="chat-image-viewer-scale" id="userImageViewerScale">100%</span>
                        <button type="button" class="chat-image-viewer-control" id="userImageViewerZoomIn" aria-label="Phóng to" title="Phóng to"><i class="fas fa-plus"></i></button>
                        <button type="button" class="chat-image-viewer-control" id="userImageViewerReset" aria-label="Fit to screen" title="Fit to screen"><i class="fas fa-expand"></i></button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<script>
    const userImageViewer = (() => {
        let images = [];
        let currentIndex = 0;
        let scale = 1;
        let panX = 0;
        let panY = 0;
        let isDragging = false;
        let dragStartX = 0;
        let dragStartY = 0;
        let previousBodyOverflow = '';
        let closeTimer = null;

        const getElements = () => ({
            modal: document.getElementById('imageModal'),
            stage: document.getElementById('userImageViewerStage'),
            image: document.getElementById('modalImage'),
            counter: document.getElementById('userImageViewerCounter'),
            prev: document.getElementById('userImageViewerPrev'),
            next: document.getElementById('userImageViewerNext'),
            zoomOut: document.getElementById('userImageViewerZoomOut'),
            zoomIn: document.getElementById('userImageViewerZoomIn'),
            reset: document.getElementById('userImageViewerReset'),
            close: document.getElementById('userImageViewerClose'),
            scaleLabel: document.getElementById('userImageViewerScale'),
            openOriginal: document.getElementById('userImageViewerOpenOriginal'),
            download: document.getElementById('userImageViewerDownload'),
        });

        function collectImages() {
            const seen = new Set();
            return Array.from(document.querySelectorAll('#chat-messages-container .chat-image-message img'))
                .map(img => img.currentSrc || img.src)
                .filter(src => src && !seen.has(src) && seen.add(src));
        }

        function updateTransform() {
            const { image, scaleLabel, zoomOut, zoomIn } = getElements();
            if (!image) return;
            image.style.transform = `translate(${panX}px, ${panY}px) scale(${scale})`;
            image.style.cursor = scale > 1 ? (isDragging ? 'grabbing' : 'grab') : 'zoom-in';
            if (scaleLabel) scaleLabel.textContent = `${Math.round(scale * 100)}%`;
            if (zoomOut) zoomOut.disabled = scale <= 1;
            if (zoomIn) zoomIn.disabled = scale >= 4;
        }

        function fitToScreen() {
            const { stage, image } = getElements();
            if (!stage || !image?.naturalWidth || !image?.naturalHeight) return;

            const stageStyle = window.getComputedStyle(stage);
            const horizontalPadding = parseFloat(stageStyle.paddingLeft || 0) + parseFloat(stageStyle.paddingRight || 0);
            const verticalPadding = parseFloat(stageStyle.paddingTop || 0) + parseFloat(stageStyle.paddingBottom || 0);
            const availableWidth = Math.max(1, stage.clientWidth - horizontalPadding);
            const availableHeight = Math.max(1, stage.clientHeight - verticalPadding);
            const fitRatio = Math.min(
                1,
                availableWidth / image.naturalWidth,
                availableHeight / image.naturalHeight
            );

            image.style.width = `${image.naturalWidth * fitRatio}px`;
            image.style.height = `${image.naturalHeight * fitRatio}px`;
            scale = 1;
            panX = 0;
            panY = 0;
            isDragging = false;
            updateTransform();
        }

        function resetZoom() {
            fitToScreen();
        }

        function render(index) {
            const els = getElements();
            if (!els.image || !images.length) return;

            currentIndex = (index + images.length) % images.length;
            const src = images[currentIndex];
            scale = 1;
            panX = 0;
            panY = 0;
            isDragging = false;
            updateTransform();
            els.stage?.classList.remove('is-ready', 'is-error');
            els.stage?.classList.add('is-loading');
            els.image.src = src;
            els.openOriginal.href = src;
            els.download.href = src;
            els.counter.hidden = images.length <= 1;
            els.counter.textContent = `${currentIndex + 1} / ${images.length}`;
            els.prev.hidden = images.length <= 1;
            els.next.hidden = images.length <= 1;
        }

        function open(imageSrc) {
            const els = getElements();
            if (!els.modal || !els.image) return;
            if (closeTimer) {
                window.clearTimeout(closeTimer);
                closeTimer = null;
            }
            images = collectImages();
            if (!images.includes(imageSrc)) images.push(imageSrc);
            currentIndex = Math.max(0, images.indexOf(imageSrc));
            if (!document.body.classList.contains('chat-image-viewer-open')) {
                previousBodyOverflow = document.body.style.overflow;
            }
            els.modal.classList.remove('is-closing');
            els.modal.classList.add('active');
            els.modal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('chat-image-viewer-open');
            document.body.style.overflow = 'hidden';
            render(currentIndex);
            els.close?.focus({ preventScroll: true });
        }

        function close() {
            const els = getElements();
            if (!els.modal?.classList.contains('active')) return;

            els.modal.classList.remove('active');
            els.modal.classList.add('is-closing');
            closeTimer = window.setTimeout(() => {
                els.modal.classList.remove('is-closing');
                els.modal.setAttribute('aria-hidden', 'true');
                document.body.classList.remove('chat-image-viewer-open');
                document.body.style.overflow = previousBodyOverflow;
                scale = 1;
                panX = 0;
                panY = 0;
                isDragging = false;
                updateTransform();
                els.image.removeAttribute('src');
                els.image.style.removeProperty('width');
                els.image.style.removeProperty('height');
                els.stage.classList.remove('is-ready', 'is-error', 'is-loading');
                images = [];
                closeTimer = null;
            }, 160);
        }

        function shift(direction) {
            if (images.length > 1) render(currentIndex + direction);
        }

        function setScale(nextScale) {
            scale = Math.min(4, Math.max(1, nextScale));
            if (scale === 1) {
                panX = 0;
                panY = 0;
            }
            updateTransform();
        }

        function init() {
            const els = getElements();
            if (!els.modal || els.modal.dataset.viewerInitialized === 'true') return;
            els.modal.dataset.viewerInitialized = 'true';

            els.image.addEventListener('load', () => {
                window.requestAnimationFrame(() => {
                    fitToScreen();
                    els.stage.classList.remove('is-loading', 'is-error');
                    els.stage.classList.add('is-ready');
                });
            });
            els.image.addEventListener('error', () => {
                els.stage.classList.remove('is-loading', 'is-ready');
                els.stage.classList.add('is-error');
            });
            els.image.addEventListener('dblclick', resetZoom);
            els.image.addEventListener('pointerdown', (event) => {
                if (scale <= 1) return;
                isDragging = true;
                dragStartX = event.clientX - panX;
                dragStartY = event.clientY - panY;
                els.image.setPointerCapture?.(event.pointerId);
                updateTransform();
            });
            els.image.addEventListener('pointermove', (event) => {
                if (!isDragging) return;
                panX = event.clientX - dragStartX;
                panY = event.clientY - dragStartY;
                updateTransform();
            });
            const stopDragging = () => {
                isDragging = false;
                updateTransform();
            };
            els.image.addEventListener('pointerup', stopDragging);
            els.image.addEventListener('pointercancel', stopDragging);
            els.prev.addEventListener('click', () => shift(-1));
            els.next.addEventListener('click', () => shift(1));
            els.close.addEventListener('click', close);
            els.zoomOut.addEventListener('click', () => setScale(scale - 0.25));
            els.zoomIn.addEventListener('click', () => setScale(scale + 0.25));
            els.reset.addEventListener('click', resetZoom);
            els.stage.addEventListener('wheel', (event) => {
                if (!els.modal.classList.contains('active')) return;
                event.preventDefault();
                setScale(scale + (event.deltaY < 0 ? 0.2 : -0.2));
            }, { passive: false });

            window.addEventListener('resize', () => {
                if (els.modal.classList.contains('active')) fitToScreen();
            });

            els.modal.addEventListener('click', (event) => {
                if (event.target === els.modal) close();
            });

            if (!window.__userChatImageViewerKeyboardBound) {
                window.__userChatImageViewerKeyboardBound = true;
                document.addEventListener('keydown', (event) => {
                    const currentModal = document.getElementById('imageModal');
                    if (!currentModal?.classList.contains('active')) return;
                    if (event.key === 'Escape') close();
                    if (event.key === 'ArrowLeft') shift(-1);
                    if (event.key === 'ArrowRight') shift(1);
                });
            }
        }

        return { init, open };
    })();

    function openImageModal(imageSrc) {
        userImageViewer.init();
        userImageViewer.open(imageSrc);
    }

    document.addEventListener('livewire:initialized', () => {
        let conversationId = @json($conversation->id ?? null);
        let currentUserId = @json(auth()->id());
        let isLoadingMore = false;
        let boundScrollContainer = null;
        let observedContent = null;
        let resizeObserver = null;
        let scheduledScrollFrame = null;
        let isNearBottom = true;
        let isInitialOpenFollow = false;
        const boundThumbnailImages = new WeakSet();
        const nearBottomThreshold = 72;

        function getMessagesContainer() {
            return document.getElementById('chat-messages-container');
        }

        function isContainerNearLatest(container) {
            if (!container) return true;

            // This conversation uses column-reverse, so the visual latest edge is scrollTop = 0.
            // Browsers may report negative values while scrolling toward older messages.
            return Math.abs(container.scrollTop) <= nearBottomThreshold;
        }

        function setThumbnailState(image, state) {
            const frame = image.closest('.chat-image-message');
            if (!frame) return;

            frame.classList.remove('is-loaded', 'is-error');
            if (state === 'loaded') frame.classList.add('is-loaded');
            if (state === 'error') frame.classList.add('is-error');
        }

        function scheduleLatestScroll({ force = false, behavior = 'auto' } = {}) {
            if (scheduledScrollFrame !== null) {
                window.cancelAnimationFrame(scheduledScrollFrame);
            }

            scheduledScrollFrame = window.requestAnimationFrame(() => {
                scheduledScrollFrame = null;
                const container = getMessagesContainer();
                if (!container) return;

                if (force || isInitialOpenFollow || isNearBottom) {
                    container.scrollTo({ top: 0, behavior });
                    isNearBottom = true;
                }
            });
        }

        function syncThumbnailImage(image) {
            if (!boundThumbnailImages.has(image)) {
                boundThumbnailImages.add(image);

                image.addEventListener('load', () => {
                    setThumbnailState(image, 'loaded');
                    scheduleLatestScroll();
                });

                image.addEventListener('error', () => {
                    setThumbnailState(image, 'error');
                    scheduleLatestScroll();
                });
            }

            if (!image.complete) {
                setThumbnailState(image, 'loading');
                return;
            }

            setThumbnailState(image, image.naturalWidth > 0 ? 'loaded' : 'error');
        }

        function syncThumbnailImages(scope = document) {
            const container = scope?.id === 'chat-messages-container'
                ? scope
                : scope?.querySelector?.('#chat-messages-container');

            container?.querySelectorAll('.chat-image-message img').forEach(syncThumbnailImage);
        }

        function onMessagesScroll() {
            if (!boundScrollContainer) return;

            isNearBottom = isContainerNearLatest(boundScrollContainer);
            if (!isNearBottom) {
                isInitialOpenFollow = false;
            }
        }

        function refreshConversationLifecycle() {
            const container = getMessagesContainer();
            if (!container) return;

            if (boundScrollContainer !== container) {
                boundScrollContainer?.removeEventListener('scroll', onMessagesScroll);
                boundScrollContainer = container;
                boundScrollContainer.addEventListener('scroll', onMessagesScroll, { passive: true });
                isNearBottom = isContainerNearLatest(container);
            }

            const content = container.querySelector('.chat-messages-content');
            if (observedContent !== content) {
                resizeObserver?.disconnect();
                observedContent = content;

                if (content && 'ResizeObserver' in window) {
                    resizeObserver = new ResizeObserver(() => {
                        if (isInitialOpenFollow || isNearBottom) {
                            scheduleLatestScroll();
                        }
                    });
                    resizeObserver.observe(content);
                }
            }

            syncThumbnailImages(container);
        }

        function beginInitialOpenFollow() {
            isInitialOpenFollow = true;
            isNearBottom = true;
            refreshConversationLifecycle();
            scheduleLatestScroll({ force: true });
        }

        function followLatestIfAllowed(behavior = 'auto') {
            refreshConversationLifecycle();
            scheduleLatestScroll({ behavior });
        }

        refreshConversationLifecycle();

        if (!window.__userChatLifecycleMorphHookBound) {
            window.__userChatLifecycleMorphHookBound = true;
            Livewire.hook('morph.updated', ({ el }) => {
                const touchesUserChat = el?.id === 'chat-root'
                    || el?.closest?.('#chat-root')
                    || el?.querySelector?.('#chat-root');

                if (!touchesUserChat) return;

                window.requestAnimationFrame(() => {
                    refreshConversationLifecycle();
                    if (isInitialOpenFollow || isNearBottom) {
                        scheduleLatestScroll();
                    }
                });
            });
        }

        // Hàm load tin nhắn cũ hơn khi bấm nút
        window.loadMoreMessagesManual = function () {
            if (!isLoadingMore) {
                const container = document.getElementById('chat-messages-container');
                if (container) {
                    isLoadingMore = true;

                    const root = document.getElementById('chat-root');
                    const component = Livewire.find(root.getAttribute('wire:id'));
                    component.call('loadMoreMessages');
                }
            }
        }

        // Listen for Livewire events
        Livewire.on('message-sent', () => {
            const input = document.getElementById('chat-input-field');
            if (input) {
                input.value = '';
                input.style.height = 'auto';
                input.focus();
            }
            followLatestIfAllowed('smooth');
        });

        Livewire.on('scroll-to-bottom', () => {
            followLatestIfAllowed('smooth');
        });

        Livewire.on('conversation-opened', () => {
            beginInitialOpenFollow();
        });

        Livewire.on('messages-loaded', () => {
            // Với column-reverse, trình duyệt tự động neo vị trí scroll khi thêm phần tử vào "phần xa" (visual top)
            isLoadingMore = false;
            refreshConversationLifecycle();
        });

        // Listen for WebSocket messages after the Echo module is ready.
        function subscribeToConversationChannel() {
            if (!conversationId || !window.Echo) {
                return;
            }

            window.Echo.private(`chat.conversation.${conversationId}`)
                .listen('.MessageSent', (e) => {
                    const message = e.message;

                    // CHỈ phát âm thanh và xử lý khi NHẬN tin nhắn (không phải tin nhắn của mình)
                    if (message.sender_id !== currentUserId) {
                        playNotificationSound();
                        const root = document.getElementById('chat-root');
                        const component = Livewire.find(root.getAttribute('wire:id'));
                        component.dispatch('message-received', e);
                    } else {
                        console.log('Ignoring own message - không phát âm thanh');
                    }
                })
                .listen('.MessageRead', (e) => {

                    // Update Livewire property để giữ trạng thái khi re-render
                    const root = document.getElementById('chat-root');
                    const component = Livewire.find(root.getAttribute('wire:id'));
                    component.call('onMessageReadUpdate', e.message_id);

                    // Update icon seen cho tin nhắn trong DOM ngay lập tức
                    const messageElement = document.querySelector(`[data-message-id="${e.message_id}"]`);
                    if (messageElement) {
                        messageElement.setAttribute('data-seen-status', 'true');
                        const icon = messageElement.querySelector('i');
                        if (icon) {
                            icon.className = 'fas fa-check-double text-info';
                            icon.style.fontSize = '10px';
                            icon.title = 'Đã xem';
                        }
                    }
                })
                .listen('.ConversationRead', (e) => {
                    
                    // Update Livewire backend
                    const root = document.getElementById('chat-root');
                    const component = Livewire.find(root.getAttribute('wire:id'));
                    component.call('onConversationRead', e);

                    // Cập nhật tất cả các tin nhắn của mình (người đang ngồi trước máy) sang Đã xem
                    // Vì conversation_id đã khớp (nhờ listen đúng channel)
                    const myMessages = document.querySelectorAll('[data-seen-status="false"]');
                    myMessages.forEach(el => {
                        el.setAttribute('data-seen-status', 'true');
                        const icon = el.querySelector('i');
                        if (icon) {
                            icon.className = 'fas fa-check-double text-info';
                            icon.style.fontSize = '10px';
                            icon.title = 'Đã xem';
                        }
                    });
                })
                .listen('.MessageUpdated', (e) => {
                    const root = document.getElementById('chat-root');
                    const component = Livewire.find(root.getAttribute('wire:id'));
                    component.call('onMessageUpdated', e);
                })
                .listen('.MessageDeleted', (e) => {
                    const root = document.getElementById('chat-root');
                    const component = Livewire.find(root.getAttribute('wire:id'));
                    component.call('onMessageDeleted', e);
                })
                .listen('.ConversationCleared', (e) => {
                    const root = document.getElementById('chat-root');
                    const component = Livewire.find(root.getAttribute('wire:id'));
                    component.call('onConversationCleared', e);
                })
                .error((error) => {
                    console.error('Echo error:', error);
                });
        }

        if (window.Echo) {
            subscribeToConversationChannel();
        } else {
            window.addEventListener('echo:ready', subscribeToConversationChannel, { once: true });
        }

        // Auto-focus input when chat opens
        const input = document.getElementById('chat-input-field');
        if (input) {
            input.focus();

            // Enter key đã được xử lý bởi Alpine.js (x-on:keydown.enter)
        }

        // Không cần event listener nữa - Alpine.js đã xử lý
    });

</script>
