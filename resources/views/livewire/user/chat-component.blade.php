<div class="footer-item" wire:id="{{ $this->getId() }}" id="chat-root">
    <!-- Nút CSKH trong footer -->
    <a wire:click="toggleBox" class="cspt text-dark text-decoration-none" style="cursor: pointer">
        <i class="fa fa-solid fa-headset"></i>
        <div class="fw-bold text-footer">CSKH</div>
    </a>

    <!-- Hộp thoại chat -->
    @if ($showBox)
    <div wire:init="scrollToBottom"
        style="position: fixed; bottom: 90px; right: 20px; width: 350px; background: #fff; border: none; border-radius: 16px; z-index: 9999; box-shadow: 0 8px 32px rgba(0,0,0,0.12); overflow: hidden;">

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
                    <div class="fw-bold" style="font-size: 14px;">Hỗ trợ khách hàng</div>
                    <div class="text-start" style="font-size: 11px; opacity: 0.9;">Đang trực tuyến</div>
                </div>
            </div>
            <button wire:click="closeBox" class="btn btn-sm p-1" style="color: white; opacity: 0.8;">
                <i class="fa fa-times"></i>
            </button>
        </div>

        <!-- Danh sách tin nhắn -->
        <div class="p-3" style="height: 300px; overflow-y: auto; background: #f8f9fa;" id="chat-messages-container">
            @if($messages->count() == 0)
            <div class="text-center text-muted py-3">
                <i class="fa fa-comments fa-2x mb-2"></i>
                <div>Chào bạn! Chúng tôi có thể giúp gì cho bạn?</div>
            </div>
            @endif

            @foreach ($messages as $msg)
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
                            style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; font-size: 14px; line-height: 1.4;">
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
                            style="background: white; color: #333; font-size: 14px; line-height: 1.4; border: 1px solid #e9ecef;">
                            {{ $message }}
                        </div>
                        <div class="mt-1 ps-2" style="font-size: 10px; color: #6c757d;text-align:left;">
                            Hỗ trợ • {{ \Carbon\Carbon::parse($createdAt)->format('H:i') }}
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
                    wire:model="newMessage"
                    class="form-control border-0 bg-transparent"
                    placeholder="Nhập tin nhắn của bạn..."
                    id="chat-input-field"
                    autocomplete="off"
                    style="font-size: 14px;">
                <button type="submit"
                    class="btn btn-link p-0 ms-2"
                    style="color: #667eea; font-size: 18px;">
                    <i class="fa fa-paper-plane"></i>
                </button>
            </div>
            <div class="mt-2 text-center" style="font-size: 11px; color: #6c757d;">
                Nhấn Enter để gửi tin nhắn
            </div>
        </form>
    </div>
    @endif
</div>

<script>
    document.addEventListener('livewire:initialized', () => {
        let conversationId = @json($conversation->id ?? null);
        let currentUserId = @json(auth()->id());

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

        // Listen for Livewire events
        Livewire.on('message-sent', () => {
            document.getElementById('chat-input-field')?.focus();
            scrollToBottom();
        });

        Livewire.on('scroll-to-bottom', () => {
            scrollToBottom();
        });

        // Listen for WebSocket messages
        if (conversationId && window.Echo) {
            console.log('Setting up Echo listener for conversation:', conversationId);
            
            window.Echo.private(`chat.conversation.${conversationId}`)
                .listen('.MessageSent', (e) => {
                    console.log('Received WebSocket message:', e);

                    const message = e.message;

                    // Only process if not from current user
                    if (message.sender_id !== currentUserId) {
                        console.log('Processing received message from another user');
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
        }

        // Handle Enter key
        document.addEventListener('DOMContentLoaded', function() {
            Livewire.on('reset-message-input', () => {
                const input = document.querySelector('input[wire\\:model*="newMessage"]');
                if (input) {
                    input.value = '';
                    input.focus();
                }
            });
        });
    });
</script>