<div class="d-flex" id="chat-root" style="height: 100vh; background-color: #f8f9fa;" wire:poll.5s="loadConversations" wire:id="{{ $this->getId() }}">
    <!-- Sidebar trái -->
    <div class="bg-white border-end" style="width: 350px;">
        <div class="p-3 border-bottom">
            <h5 class="mb-0 text-dark">
                @if(auth()->user()->role === 'admin')
                Quản lý Chat
                @else
                Danh sách Chat
                @endif
            </h5>
        </div>

        <div class="overflow-auto" style="height: calc(100vh - 80px);">
            @if(auth()->user()->role === 'admin')
            <!-- Giao diện Admin -->
            <div class="p-3">
                <!-- Conversations của admin -->
                <div class="mb-4">
                    <h6 class="text-muted mb-3">Tin nhắn của tôi</h6>
                    @foreach($conversations->where('staff_id', auth()->id()) as $conversation)
                    <div class="d-flex align-items-center p-2 rounded mb-2 position-relative cursor-pointer {{ $this->selectedConversation && $this->selectedConversation->id === $conversation->id ? 'bg-primary bg-opacity-10 border-start border-primary border-3' : '' }}"
                        style="cursor: pointer;"
                        wire:click="selectConversation({{ $conversation->id }})"
                        onmouseover="this.style.backgroundColor='#f8f9fa'"
                        onmouseout="this.style.backgroundColor='{{ $this->selectedConversation && $this->selectedConversation->id === $conversation->id ? 'rgba(13, 110, 253, 0.1)' : 'transparent' }}'">
                        <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center text-white fw-bold me-3" style="width: 40px; height: 40px; font-size: 14px;">
                            {{ substr($conversation->user->full_name, 0, 1) }}
                        </div>
                        <div class="flex-grow-1 min-width-0">
                            <div class="fw-semibold text-dark text-truncate">{{ $conversation->user->full_name ."(".$conversation->user->username.")"}}</div>
                            <div class="text-muted small text-truncate">
                                @if($conversation->messages->last())
                                {{ $conversation->messages->last()->message }}
                                @endif
                            </div>
                        </div>
                        @if($conversation->messages->where('sender_id', '!=', auth()->id())->where('created_at', '>', now()->subHour())->count() > 0)
                        <div class="bg-danger rounded-circle" style="width: 8px; height: 8px;"></div>
                        @endif
                    </div>
                    @endforeach
                </div>

                <!-- Danh sách nhân viên và người dùng của họ -->
                <div>
                    <h6 class="text-muted mb-3">Nhân viên và khách hàng</h6>
                    @foreach($staffUsers as $staff)
                    <div class="mb-3">
                        <!-- Header nhân viên -->
                        <div class="d-flex align-items-center p-2 bg-light rounded cursor-pointer"
                            style="cursor: pointer;"
                            wire:click="toggleStaffExpansion({{ $staff->id }})">
                            <div class="bg-success rounded-circle d-flex align-items-center justify-content-center text-white fw-bold me-3" style="width: 32px; height: 32px; font-size: 12px;">
                                {{ substr($staff->full_name, 0, 1) }}
                            </div>
                            <div class="flex-grow-1">
                                <div class="fw-semibold text-dark">{{ $staff->full_name }}</div>
                                <div class="text-muted" style="font-size: 11px;">{{ $staff->invitedUsers->count() }} khách hàng</div>
                            </div>
                            <i class="fas fa-chevron-down text-muted {{ in_array($staff->id, $expandedStaff) ? 'rotate-180' : '' }}" style="font-size: 12px; transition: transform 0.3s;"></i>
                        </div>

                        <!-- Danh sách người dùng của nhân viên (có thể thu gọn) -->
                        @if(in_array($staff->id, $expandedStaff))
                        <div class="ms-4 mt-2">
                            @foreach($staff->invitedUsers as $user)
                            <div class="d-flex align-items-center p-2 rounded mb-1 cursor-pointer"
                                style="cursor: pointer;"
                                wire:click="selectUserForChat({{ $user->id }}, {{ $staff->id }})"
                                onmouseover="this.style.backgroundColor='#f8f9fa'"
                                onmouseout="this.style.backgroundColor='transparent'">
                                <div class="bg-info rounded-circle d-flex align-items-center justify-content-center text-white fw-bold me-2" style="width: 24px; height: 24px; font-size: 10px;">
                                    {{ substr($user->full_name, 0, 1) }}
                                </div>
                                <div class="text-dark small">{{ $user->full_name ." (".$user->username.")" }}</div>
                            </div>
                            @endforeach
                        </div>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>
            @else
            <!-- Giao diện Staff -->
            <div class="p-3">
                <h6 class="text-muted mb-3">Khách hàng của tôi</h6>
                @foreach(auth()->user()->invitedUsers()->where('role', 'user')->get() as $user)
                @php
                $conversation = $conversations->where('user_id', $user->id)->first();
                @endphp
                <div class="d-flex align-items-center p-2 rounded mb-2 cursor-pointer {{ $this->selectedConversation && $conversation && $this->selectedConversation->id === $conversation->id ? 'bg-primary bg-opacity-10 border-start border-primary border-3' : '' }}"
                    style="cursor: pointer;"
                    wire:click="selectUserForChat({{ $user->id }})"
                    onmouseover="this.style.backgroundColor='#f8f9fa'"
                    onmouseout="this.style.backgroundColor='{{ $this->selectedConversation && $conversation && $this->selectedConversation->id === $conversation->id ? 'rgba(13, 110, 253, 0.1)' : 'transparent' }}'">
                    <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center text-white fw-bold me-3" style="width: 40px; height: 40px; font-size: 14px;">
                        {{ substr($user->full_name, 0, 1) }}
                    </div>
                    <div class="flex-grow-1 min-width-0">
                        <div class="fw-semibold text-dark text-truncate">{{ $user->full_name }}</div>
                        <div class="text-muted small text-truncate">
                            @if($conversation && $conversation->messages->last())
                            {{ $conversation->messages->last()->message }}
                            @else
                            Chưa có tin nhắn
                            @endif
                        </div>
                    </div>
                    @if($conversation && $conversation->messages->where('sender_id', $user->id)->where('created_at', '>', now()->subHour())->count() > 0)
                    <div class="bg-danger rounded-circle" style="width: 8px; height: 8px;"></div>
                    @endif
                </div>
                @endforeach
            </div>
            @endif
        </div>
    </div>

    <!-- Khu vực chat chính -->
    <div class="flex-grow-1 d-flex flex-column">
        @if($this->selectedConversation)
        <!-- Header chat -->
        <div class="bg-white border-bottom p-3">
            <div class="d-flex align-items-center">
                <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center text-white fw-bold me-3" style="width: 40px; height: 40px; font-size: 14px;">
                    {{ substr($this->selectedConversation->user->full_name, 0, 1) }}
                </div>
                <div>
                    <div class="fw-semibold text-dark">{{ $this->selectedConversation->user->full_name }}</div>
                    <div class="text-muted small">
                        @if(auth()->user()->role === 'admin')
                        Được quản lý bởi: {{ $this->selectedConversation->staff->full_name }}
                        @else
                        Trực tuyến
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Khu vực tin nhắn -->
        <div class="flex-grow-1 overflow-auto p-3" id="messages-container" style="background-color: #f8f9fa;">
            @foreach($messages as $message)
            <div class="d-flex mb-3 {{ $message['sender_id'] == auth()->id() ? 'justify-content-end' : 'justify-content-start' }}" wire:key="message-{{ $message['id'] ?? $index }}">
                <div class="rounded-3 px-3 py-2 {{ $message['sender_id'] == auth()->id() ? 'bg-primary text-white' : 'bg-white text-dark border' }}" style="max-width: 70%;">
                    <div class="mb-1">{{ $message['message'] }}</div>
                    <div class="small {{ $message['sender_id'] == auth()->id() ? 'text-white-50 text-right' : 'text-muted' }}" style="font-size: 11px;">
                        {{ \Carbon\Carbon::parse($message['created_at'])->format('H:i') }}
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        <!-- Input tin nhắn -->
        <div class="bg-white border-top p-3">
            <form wire:submit.prevent="sendMessage" class="d-flex align-items-center">
                <input type="text"
                    wire:model.live="messageText"
                    placeholder="Nhập tin nhắn..."
                    class="form-control me-2"
                    wire:keydown.enter.prevent="sendMessage">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </form>
        </div>
        @else
        <!-- Trạng thái chưa chọn conversation -->
        <div class="flex-grow-1 d-flex align-items-center justify-content-center" style="background-color: #f8f9fa;">
            <div class="text-center">
                <div class="bg-secondary rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                    <i class="fas fa-comments fa-2x text-white"></i>
                </div>
                <h5 class="text-dark mb-2">Chọn một cuộc trò chuyện</h5>
                <p class="text-muted">Chọn một người dùng từ danh sách bên trái để bắt đầu chat</p>
            </div>
        </div>
        @endif
    </div>
</div>

<script>
    let currentChannel = null;

    document.addEventListener('livewire:initialized', () => {
        // Scroll to bottom
        Livewire.on('scroll-to-bottom', () => {
            const container = document.getElementById('messages-container');
            if (container) {
                setTimeout(() => {
                    container.scrollTop = container.scrollHeight;
                }, 100);
            }
        });

        // Join conversation channel
        Livewire.on('join-conversation-channel', (data) => {
            console.log('Joining channel for conversation:', data.conversationId);

            // Leave previous channel if exists
            if (currentChannel) {
                window.Echo.leave(currentChannel);
                console.log('Left previous channel:', currentChannel);
            }

            // Join new channel
            currentChannel = `chat.conversation.${data.conversationId}`;
            console.log('Joining new channel:', currentChannel);

            window.Echo.private(currentChannel)
                .listen('.MessageSent', (e) => {
                    console.log('Received message:', e);
                    const root = document.getElementById('chat-root');
                    const component = Livewire.find(root.getAttribute('wire:id'));
                    component.dispatch('message-received', e);
                })
                .error((error) => {
                    console.error('Echo error:', error);
                });
        });
    });

    // Auto-focus on message input
    // document.addEventListener('DOMContentLoaded', function() {
    //     const messageInput = document.querySelector('input[wire\\:model="messageText"]');
    //     if (messageInput) {
    //         messageInput.addEventListener('keydown', function(e) {
    //             if (e.key === 'Enter' && !e.shiftKey) {
    //                 e.preventDefault();
    //                 @this.sendMessage();
    //             }
    //         });
    //     }
    // });
</script>