<div class="floating-chat-container" wire:id="{{ $this->getId() }}" id="chat-root"
    x-data="{ isOpen: @entangle('showBox'), isLoading: false }">
    <!-- Floating Chat Button -->
    <button class="floating-chat-button" 
            @click="isLoading = true; $wire.call('toggleBox').then(() => { setTimeout(() => isLoading = false, 300) })"
            x-show="!isOpen" 
            type="button"
            :disabled="isLoading">
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
    <div class="floating-chat-window" x-show="isOpen" x-transition:enter="chat-enter" x-transition:leave="chat-leave"
        style="display: none;" wire:init="scrollToBottom" id="box_arround">

        <!-- Header với gradient -->
        <div class="p-3 d-flex justify-content-between align-items-center"
            style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
            <div class="d-flex align-items-center">
                <div class="position-relative">
                    <img src="https://ui-avatars.com/api/?name=Support&background=ffffff&color=667eea&size=32&rounded=true&bold=true"
                        alt="Support" class="rounded-circle" width="32" height="32">
                    <span class="position-absolute bottom-0 end-0 bg-success rounded-circle"
                        style="width: 10px; height: 10px; border: 2px solid white;"></span>
                </div>
                <div class="ms-2">
                    <div class="fw-bold" style="font-size: 14px;">{{__('home.HoTroKhachHang')}}</div>
                    <div class="text-start" style="font-size: 11px; opacity: 0.9;">{{__('home.DangTrucTuyen')}}</div>
                </div>
            </div>
            <button @click="isLoading = true; $wire.call('closeBox').then(() => { setTimeout(() => isLoading = false, 200) })"
                    class="btn-close-chat" 
                    type="button"
                    :disabled="isLoading"
                    x-data="{ isLoading: false }">
                <i class="fa-solid fa-times" x-show="!isLoading"></i>
                <i class="fa fa-spinner fa-spin" x-show="isLoading" style="display: none;"></i>
            </button>
        </div>

        <!-- Danh sách tin nhắn -->
        <div class="p-3 position-relative" style="height: 300px; overflow-y: auto; overflow-x: hidden; background: #f8f9fa;"
            id="chat-messages-container">
            <!-- Loading indicator cho load more -->
            @if($isLoading)
                <div class="text-center py-2" id="loading-indicator">
                    <div class="spinner-border spinner-border-sm text-primary" role="status">
                        <span class="visually-hidden">{{__('home.DangTai')}}</span>
                    </div>
                    <small class="text-muted ms-2">{{__('home.DangTaiTinNhanCu')}}</small>
                </div>
            @endif

            <!-- Nút load more messages -->
            @if($hasMoreMessages && !$isLoading)
                <div class="text-center py-2">
                    <button onclick="loadMoreMessagesManual()" class="btn btn-sm btn-outline-primary rounded-pill">
                        <i class="fa fa-chevron-up me-1"></i>
                        {{__('home.TaiTinNhanCuHon')}}
                    </button>
                </div>
            @endif

            @if($chatMessages->count() == 0)
                <div class="text-center text-muted py-3">
                    <i class="fa fa-comments fa-2x mb-2"></i>
                    <div>{{__('home.ChaoBanChungToiCoTheGiupGiChoBan')}}</div>
                </div>
            @endif

            @foreach ($chatMessages as $msg)
                @php
                    $isCurrentUser = (is_array($msg) ? $msg['sender_id'] : $msg->sender_id) === auth()->id();
                    $message = is_array($msg) ? $msg['message'] : $msg->message;
                    $type = is_array($msg) ? $msg['type'] : $msg->type;
                    $imagePath = is_array($msg) ? $msg['image_path'] : $msg->image_path;
                    $createdAt = is_array($msg) ? $msg['created_at'] : $msg->created_at;
                    $messageId = is_array($msg) ? $msg['id'] : $msg->id;
                    $isRead = is_array($msg) ? ($msg['is_read'] ?? false) : ($msg->is_read ?? false);
                    $senderName = is_array($msg)
                        ? ($msg['sender']['full_name'] ?? 'User')
                        : ($msg->sender->full_name ?? 'User');
                @endphp

                @if($isCurrentUser)
                    <!-- Tin nhắn của user -->
                    <div class="d-flex justify-content-end mb-3" wire:key="msg-{{ is_array($msg) ? $msg['id'] : $msg->id }}" style="min-width: 0;">
                        <div class="d-flex align-items-end" style="max-width: 90%; min-width: 0;">
                            <div class="me-2" style="display: flex; flex-direction: column; align-items: flex-end; min-width: 0; max-width: 100%;">
                                <div class="message-bubble text-start" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; font-size: 13px; line-height: 1.4; word-wrap: break-word; overflow-wrap: break-word; word-break: break-word; white-space: pre-line; display: inline-block; padding: 6px 12px; margin: 0; {{ $type === 'text' ? 'width: fit-content; max-width: 100%;' : 'width: 200px; max-width: 200px;' }} {{ $type === 'text' ? 'border-radius: 16px;' : 'border-radius: 15px;' }}">@if($type === 'image')<img src="{{ Storage::url($imagePath) }}" alt="Sent image" class="img-fluid rounded" style="width: 100%; max-width: 200px; max-height: 200px; cursor: pointer;" onclick="openImageModal(this.src)">@else{{ trim($message) }}@endif</div>
                                <div class="text-end mt-1 d-flex align-items-center justify-content-end gap-1" style="font-size: 10px; color: #6c757d;">
                                    <span>{{ \Carbon\Carbon::parse($createdAt)->setTimezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i') }}</span>
                                    <span data-message-id="{{ $messageId }}" data-seen-status="{{ $isRead ? 'true' : 'false' }}">
                                        @if($isRead)
                                            <i class="fas fa-check-double text-info" style="font-size: 10px;" title="Đã xem"></i>
                                        @else
                                            <i class="fas fa-check" style="font-size: 10px; color: #6c757d;" title="Đã gửi"></i>
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
                    <div class="d-flex justify-content-start mb-3" wire:key="msg-{{ is_array($msg) ? $msg['id'] : $msg->id }}" style="min-width: 0;">
                        <div class="d-flex align-items-end" style="max-width: 90%; min-width: 0;">
                            <img src="https://ui-avatars.com/api/?name=Support&background=28a745&color=ffffff&size=28&rounded=true&bold=true"
                                alt="Support" class="rounded-circle flex-shrink-0" width="28" height="28">
                            <div class="ms-2" style="display: flex; flex-direction: column; align-items: flex-start; min-width: 0; max-width: 100%;">
                                <div class="message-bubble rounded-4 position-relative member-message text-start" style="transition: all 0.2s ease; border: 1px solid #e9ecef; display: inline-block; background: white; font-size: 13px; line-height: 1.4; padding: 6px 12px; margin: 0; {{ $type === 'text' ? 'width: fit-content; max-width: 100%;' : 'width: 200px; max-width: 200px;' }} word-wrap: break-word; overflow-wrap: break-word; word-break: break-word; white-space: pre-line;">@if($type === 'image')<img src="{{ Storage::url($imagePath) }}" alt="Received image" class="img-fluid rounded" style="width: 100%; max-width: 200px; max-height: 200px; cursor: pointer;" onclick="openImageModal(this.src)">@else{{ trim($message) }}@endif</div>
                                <div class="mt-1 ps-2" style="font-size: 10px; color: #6c757d;text-align:left;">
                                    {{__('home.HoTro') . \Carbon\Carbon::parse($createdAt)->setTimezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i') }}
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            @endforeach
        </div>

        <!-- Gợi ý tin nhắn nhanh (Quick Replies) -->
        @if($showQuickReplies && count($quickReplySuggestions) > 0)
            <div class="px-3 py-2" style="background: #f8f9fa; border-top: 1px solid #e9ecef;">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <small class="text-muted fw-bold" style="font-size: 11px;">
                        <i class="fa fa-lightbulb me-1"></i> Gợi ý tin nhắn nhanh
                    </small>
                    <button wire:click="hideQuickReplies" class="btn btn-link btn-sm p-0 text-muted" 
                            style="font-size: 11px; text-decoration: none;">
                        <i class="fa fa-times"></i>
                    </button>
                </div>
                <div class="d-flex flex-wrap gap-2" x-data="{ loadingIndex: null }">
                    @foreach($quickReplySuggestions as $index => $suggestion)
                        <button wire:click="useQuickReply('{{ addslashes($suggestion) }}')" 
                                @click="loadingIndex = {{ $index }}"
                                class="btn btn-sm quick-reply-btn"
                                style="background: white; 
                                       border: 1px solid #dee2e6; 
                                       border-radius: 16px; 
                                       padding: 6px 12px;
                                       font-size: 12px;
                                       color: #495057;
                                       transition: all 0.2s ease;
                                       white-space: nowrap;
                                       position: relative;
                                       min-width: fit-content;"
                                x-bind:disabled="loadingIndex !== null"
                                x-bind:style="loadingIndex !== null && loadingIndex !== {{ $index }} ? 'opacity: 0.5; cursor: not-allowed;' : ''"
                                onmouseover="if (!this.disabled) {
                                                this.style.background='linear-gradient(135deg, #667eea 0%, #764ba2 100%)'; 
                                                this.style.color='white'; 
                                                this.style.borderColor='transparent';
                                             }"
                                onmouseout="if (!this.disabled) {
                                                this.style.background='white'; 
                                                this.style.color='#495057'; 
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

        <!-- Nút toggle Quick Replies - Thiết kế gọn -->
        @if(config('chat.quick_replies.enabled', true) && count($quickReplySuggestions) > 0)
            <div class="d-flex justify-content-center align-items-center" 
                 style="background: white; border-top: 1px solid #e9ecef; padding: 4px 0; min-height: 28px;"
                 x-data="{ toggleLoading: false }">
                <button type="button" 
                        @click="toggleLoading = true; $wire.call('toggleQuickReplies').then(() => { setTimeout(() => toggleLoading = false, 200) })"
                        class="btn btn-link p-0 m-0"
                        style="color: {{ $showQuickReplies ? '#667eea' : '#adb5bd' }};
                               font-size: 16px;
                               line-height: 1;
                               transition: all 0.2s ease;
                               text-decoration: none;"
                        :disabled="toggleLoading"
                        onmouseover="if (!this.disabled) { this.style.color='#667eea'; this.style.transform='scale(1.1)'; }"
                        onmouseout="if (!this.disabled) { this.style.color='{{ $showQuickReplies ? '#667eea' : '#adb5bd' }}'; this.style.transform='scale(1)'; }"
                        title="{{ $showQuickReplies ? '▼ Ẩn gợi ý tin nhắn' : '▲ Hiển thị gợi ý tin nhắn' }}">
                    <i class="fa fa-chevron-{{ $showQuickReplies ? 'down' : 'up' }}" x-show="!toggleLoading"></i>
                    <i class="fa fa-spinner fa-spin" x-show="toggleLoading" style="display: none;"></i>
                </button>
            </div>
        @endif

        <!-- Form nhập với design hiện đại -->
        <form class="p-3" style="background: white; border-top: 1px solid #e9ecef;" x-data="{ 
                  formSending: false,
                  hasImage: @entangle('selectedImage')
              }" x-on:submit.prevent="
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
                        <button type="button" wire:click="removeImage" class="btn btn-sm btn-outline-danger">
                            <i class="fa fa-times"></i>
                        </button>
                    </div>
                    <div class="d-flex justify-content-center align-items-center">
                        <img src="{{ $selectedImage->temporaryUrl() }}" alt="Preview" class="img-fluid rounded"
                            style="max-width: 150px; max-height: 150px;">
                    </div>
                </div>
            @endif

            <div class="d-flex align-items-end" style="background: #f8f9fa; border-radius: 20px; padding: 8px 16px;">
                <!-- Nút chọn ảnh -->
                <label for="image-upload" class="btn btn-link p-0 me-2"
                    style="color: #667eea; font-size: 18px; cursor: pointer; flex-shrink: 0;">
                    <i class="fa fa-image"></i>
                </label>
                <input type="file" wire:model="selectedImage" id="image-upload" accept="image/*" style="display: none;">

                <textarea wire:model="newMessage" class="form-control border-0 bg-transparent"
                    placeholder="{{__('home.NhapTinNhanCuaBan')}}" id="chat-input-field" autocomplete="off"
                    maxlength="{{ $maxMessageLength }}" rows="1"
                    style="font-size: 13px; resize: none; overflow-y: hidden; max-height: 100px; padding: 5px 0; line-height: 1.5;"
                    x-on:input="
                        $el.style.height = 'auto';
                        $el.style.height = Math.min($el.scrollHeight, 100) + 'px';
                    "
                    x-on:keydown.enter.prevent="
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

                <button type="submit" class="btn btn-link p-0 ms-2" style="color: #667eea; font-size: 18px; flex-shrink: 0;"
                    x-bind:disabled="formSending">
                    <i class="fa fa-paper-plane" x-show="!formSending"></i>
                    <i class="fa fa-spinner fa-spin" x-show="formSending" style="display: none;"></i>
                </button>
            </div>

            <!-- Hiển thị số ký tự còn lại và lỗi -->
            <div class="d-flex justify-content-between align-items-center mt-2">
                <div style="font-size: 11px; color: #6c757d;">
                    Enter: Gửi tin nhắn | Shift+Enter: Xuống dòng
                </div>
                <div style="font-size: 11px;"
                    class="{{ $this->getRemainingCharacters() < 20 ? 'text-warning' : 'text-muted' }}">
                    {{ $this->getRemainingCharacters() }}/{{ $maxMessageLength }}
                </div>
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

    <!-- Modal để xem ảnh phóng to -->
    <div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="imageModalLabel">Xem ảnh</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="modalImage" src="" alt="Full size image" class="img-fluid">
                </div>
            </div>
        </div>
    </div>
</div>


<script>
    // Function to open image modal
    function openImageModal(imageSrc) {
        document.getElementById('modalImage').src = imageSrc;
        var imageModal = new bootstrap.Modal(document.getElementById('imageModal'));
        imageModal.show();
    }

    document.addEventListener('livewire:initialized', () => {
        let conversationId = @json($conversation->id ?? null);
        let currentUserId = @json(auth()->id());
        let isLoadingMore = false;
        let previousScrollHeight = 0;
        let previousScrollTop = 0;

        function scrollToBottom(behavior = 'smooth') {
            const container = document.getElementById('chat-messages-container');
            if (container) {
                setTimeout(() => {
                    container.scrollTo({
                        top: container.scrollHeight,
                        behavior: behavior
                    });
                }, 100);
            }
        }

        // Hàm load tin nhắn cũ hơn khi bấm nút
        window.loadMoreMessagesManual = function () {
            if (!isLoadingMore) {
                const container = document.getElementById('chat-messages-container');
                if (container) {
                    isLoadingMore = true;
                    previousScrollHeight = container.scrollHeight;
                    previousScrollTop = container.scrollTop;

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
            scrollToBottom();
        });

        Livewire.on('scroll-to-bottom', () => {
            scrollToBottom();
        });

        Livewire.on('messages-loaded', () => {
            const container = document.getElementById('chat-messages-container');
            if (container) {
                // Đợi DOM cập nhật hoàn toàn
                setTimeout(() => {
                    const newScrollHeight = container.scrollHeight;
                    const scrollDiff = newScrollHeight - previousScrollHeight;
                    // Đặt vị trí scroll = vị trí cũ + phần tin nhắn mới thêm vào
                    container.scrollTop = previousScrollTop + scrollDiff;
                }, 50);
            }
            isLoadingMore = false;
        });

        // Listen for WebSocket messages
        if (conversationId && window.Echo) {
            window.Echo.private(`chat.conversation.${conversationId}`)
                .listen('.MessageSent', (e) => {
                    // console.log('New message at User:', e.message);
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
                    // console.log('Message read:', e);
                    
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
                .error((error) => {
                    console.error('Echo error:', error);
                });
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