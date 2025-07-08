<div class="footer-item" wire:id="{{ $this->getId() }}" id="chat-root">
    <!-- Nút CSKH trong footer -->
    <a wire:click="toggleBox" class="cspt text-dark text-decoration-none" style="cursor: pointer">
        <i class="fa fa-solid fa-headset"></i>
        <div class="fw-bold text-footer">{{__('layout.CSKH')}}</div>
    </a>

    <!-- Hộp thoại chat -->
    @if ($showBox)
    <div wire:init="scrollToBottom" id="box_arround">

        <!-- Header với gradient -->
        <div class="p-3 d-flex justify-content-between align-items-center" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
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
            <button wire:click="closeBox" class="btn btn-sm p-1" style="color: white; opacity: 0.8;">
                <i class="fa fa-times"></i>
            </button>
        </div>

        <!-- Danh sách tin nhắn -->
        <div class="p-3 position-relative" style="height: 300px; overflow-y: auto; background: #f8f9fa;" id="chat-messages-container">
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
                <button wire:click="loadMoreMessages" class="btn btn-sm btn-outline-primary rounded-pill">
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
                $createdAt = is_array($msg) ? $msg['created_at'] : $msg->created_at;
                $senderName = is_array($msg) 
                    ? ($msg['sender']['full_name'] ?? 'User') 
                    : ($msg->sender->full_name ?? 'User');
            @endphp

            @if($isCurrentUser)
            <!-- Tin nhắn của user -->
            <div class="d-flex justify-content-end mb-3" wire:key="msg-{{ is_array($msg) ? $msg['id'] : $msg->id }}">
                <div class="d-flex align-items-end" style="max-width: 80%;">
                    <div class="me-2">
                        <div class="px-3 py-2 rounded-pill"
                            style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; font-size: 14px; line-height: 1.4; word-wrap: break-word;">
                            {{ $message }}
                        </div>
                        <div class="text-end mt-1" style="font-size: 10px; color: #6c757d;">
                            {{ \Carbon\Carbon::parse($createdAt)->format('H:i') }}
                        </div>
                    </div>
                    <img src="https://ui-avatars.com/api/?name={{ urlencode($senderName) }}&background=667eea&color=ffffff&size=28&rounded=true"
                        alt="You" class="rounded-circle flex-shrink-0" width="28" height="28">
                </div>
            </div>
            @else
            <!-- Tin nhắn của support -->
            <div class="d-flex justify-content-start mb-3" wire:key="msg-{{ is_array($msg) ? $msg['id'] : $msg->id }}">
                <div class="d-flex align-items-end" style="max-width: 80%;">
                    <img src="https://ui-avatars.com/api/?name=Support&background=28a745&color=ffffff&size=28&rounded=true&bold=true"
                        alt="Support" class="rounded-circle flex-shrink-0" width="28" height="28">
                    <div class="ms-2">
                        <div class="px-3 py-2 rounded-pill"
                            style="background: white; color: #333; font-size: 14px; line-height: 1.4; border: 1px solid #e9ecef; word-wrap: break-word;">
                            {{ $message }}
                        </div>
                        <div class="mt-1 ps-2" style="font-size: 10px; color: #6c757d;text-align:left;">
                             {{__('home.HoTro'). \Carbon\Carbon::parse($createdAt)->format('H:i') }}
                        </div>
                    </div>
                </div>
            </div>
            @endif
            @endforeach
        </div>

        <!-- Form nhập với design hiện đại -->
        <form wire:submit.prevent="sendMessage" class="p-3" style="background: white; border-top: 1px solid #e9ecef;">
            <div class="d-flex align-items-center" style="background: #f8f9fa; border-radius: 25px; padding: 8px 16px;">
                <input type="text"
                    wire:model.live="newMessage"
                    class="form-control border-0 bg-transparent"
                    placeholder={{__('home.NhapTinNhanCuaBan')}}
                    id="chat-input-field"
                    autocomplete="off"
                    maxlength="{{ $maxMessageLength }}"
                    style="font-size: 14px;">
                <button type="submit"
                    class="btn btn-link p-0 ms-2"
                    style="color: #667eea; font-size: 18px;"
                    @if(strlen(trim($newMessage)) == 0 || strlen(trim($newMessage)) > $maxMessageLength) disabled @endif>
                    <i class="fa fa-paper-plane"></i>
                </button>
            </div>
            
            <!-- Hiển thị số ký tự còn lại và lỗi -->
            <div class="d-flex justify-content-between align-items-center mt-2">
                <div style="font-size: 11px; color: #6c757d;">
                    {{__('home.NhanEnterDeGuiTinNhan')}}
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
        </form>
    </div>
    @endif
</div>

<script>
    document.addEventListener('livewire:initialized', () => {
        let conversationId = @json($conversation->id ?? null);
        let currentUserId = @json(auth()->id());
        let isLoadingMore = false;
        let previousScrollHeight = 0;

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

        function autoResizeTextarea() {
            // Không cần thiết cho input
        }

        // Listen for Livewire events
        Livewire.on('message-sent', () => {
            const input = document.getElementById('chat-input-field');
            if (input) {
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
                // Giữ vị trí scroll sau khi load tin nhắn cũ
                const newScrollHeight = container.scrollHeight;
                const scrollDiff = newScrollHeight - previousScrollHeight;
                container.scrollTop = container.scrollTop + scrollDiff;
            }
            isLoadingMore = false;
        });

        // Xử lý scroll để load tin nhắn cũ
        const container = document.getElementById('chat-messages-container');
        if (container) {
            container.addEventListener('scroll', function() {
                // Nếu scroll lên đầu và có tin nhắn cũ hơn
                if (this.scrollTop <= 50 && !isLoadingMore) {
                    const hasMoreMessages = @json($hasMoreMessages);
                    if (hasMoreMessages) {
                        isLoadingMore = true;
                        previousScrollHeight = this.scrollHeight;
                        
                        // Gọi Livewire method để load tin nhắn cũ
                        const root = document.getElementById('chat-root');
                        const component = Livewire.find(root.getAttribute('wire:id'));
                        component.call('loadMoreMessages');
                    }
                }
            });
        }

        // Listen for WebSocket messages
        if (conversationId && window.Echo) {
            
            window.Echo.private(`chat.conversation.${conversationId}`)
                .listen('.MessageSent', (e) => {

                    const message = e.message;

                    // Only process if not from current user
                    if (message.sender_id !== currentUserId) {
                        const root = document.getElementById('chat-root');
                        const component = Livewire.find(root.getAttribute('wire:id'));
                        component.dispatch('message-received', e);
                    } else {
                        console.log('Ignoring own message');
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
            
            // Handle Enter key
            input.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    const root = document.getElementById('chat-root');
                    const component = Livewire.find(root.getAttribute('wire:id'));

                    if (this.value.trim().length > 0 && this.value.trim().length <= {{ $maxMessageLength }}) {
                        component.set('newMessage', this.value); // cập nhật thủ công
                        setTimeout(() => {
                            component.call('sendMessage');
                        }, 50); // nhỏ delay để đảm bảo set xong
                    }
                }
            });

        }

        // Handle Enter key
        document.addEventListener('DOMContentLoaded', function() {
            Livewire.on('reset-message-input', () => {
                const input = document.querySelector('input[wire\\:model\\.live="newMessage"]');
                if (input) {                    
                    input.value = '';
                    input.focus();
                }
            });
        });
    });
</script>

