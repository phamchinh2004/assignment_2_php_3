<div class="d-flex" id="chat-root" style="height: 100vh; background-color: #f8f9fa;" wire:poll.5s="loadConversations" wire:id="{{ $this->getId() }}">
    <!-- Sidebar trái -->
    <div class="bg-white border-end shadow-sm" style="width: 350px; transition: all 0.3s ease;">
        <div class="p-3 border-bottom bg-light">
            <h5 class="mb-0 text-dark fw-bold">
                @if(auth()->user()->role === 'admin')
                <i class="fas fa-user-shield me-2 text-primary"></i>Quản lý Chat
                @else
                <i class="fas fa-comments me-2 text-primary"></i>Danh sách Chat
                @endif
            </h5>
        </div>
        <input
            type="text"
            class="form-control mb-3"
            placeholder="Tìm kiếm người dùng..."
            wire:model.debounce.300ms="searchTerm" />
        <div class="overflow-auto custom-scrollbar" style="height: calc(100vh - 80px);">
            @if(auth()->user()->role === 'admin')
            <!-- Giao diện Admin -->
            <div class="p-3">
                <!-- Conversations của admin -->
                <div class="mb-4">
                    <h6 class="text-muted mb-3 fw-semibold">
                        <i class="fas fa-user-circle me-2"></i>Tin nhắn của tôi
                    </h6>
                    @foreach($conversations->where('staff_id', auth()->id()) as $conversation)
                    <div class="conversation-item d-flex align-items-center p-3 rounded-3 mb-2 position-relative cursor-pointer {{ $this->selectedConversation && $this->selectedConversation->id === $conversation->id ? 'bg-primary bg-opacity-10 border-start border-primary border-4 shadow-sm' : 'bg-white shadow-sm' }}"
                        style="cursor: pointer; transition: all 0.3s ease; border: 1px solid #e9ecef;"
                        wire:click="selectConversation({{ $conversation->id }})">
                        <div class="avatar bg-primary rounded-circle d-flex align-items-center justify-content-center text-white fw-bold me-3 position-relative" style="width: 45px; height: 45px; font-size: 16px;">
                            {{ substr($conversation->user->full_name, 0, 1) }}
                            @if($conversation->messages->where('sender_id', '!=', auth()->id())->where('created_at', '>', now()->subHour())->count() > 0)
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 8px;">
                                <span class="visually-hidden">unread messages</span>
                            </span>
                            @endif
                        </div>
                        <div class="flex-grow-1 min-width-0">
                            <div class="fw-semibold text-dark text-truncate mb-1">{{ $conversation->user->full_name ." (".$conversation->user->username.")"}}</div>
                            <div class="text-muted small text-truncate d-flex align-items-center">
                                @if($conversation->messages->last())
                                <i class="fas fa-comment-dots me-1" style="font-size: 10px;"></i>
                                {{ Str::limit($conversation->messages->last()->message, 30) }}
                                @else
                                <i class="fas fa-clock me-1" style="font-size: 10px;"></i>
                                Chưa có tin nhắn
                                @endif
                            </div>
                        </div>
                        <div class="text-center" style="font-size: 11px;">
                            @if($conversation->messages->last())
                            {{ $conversation->messages->last()->created_at->diffForHumans() }}
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>

                <!-- Danh sách nhân viên và người dùng của họ -->
                <div>
                    <h6 class="text-muted mb-3 fw-semibold">
                        <i class="fas fa-users me-2"></i>Nhân viên và khách hàng
                    </h6>
                    @foreach($staffUsers as $staff)
                    <div class="mb-3">
                        <!-- Header nhân viên -->
                        <div class="d-flex align-items-center p-3 bg-light rounded-3 cursor-pointer staff-header shadow-sm"
                            style="cursor: pointer; transition: all 0.3s ease; border: 1px solid #e9ecef;"
                            wire:click="toggleStaffExpansion({{ $staff->id }})">
                            <div class="bg-success rounded-circle d-flex align-items-center justify-content-center text-white fw-bold me-3" style="width: 36px; height: 36px; font-size: 14px;">
                                {{ substr($staff->full_name, 0, 1) }}
                            </div>
                            <div class="flex-grow-1">
                                <div class="fw-semibold text-dark">{{ $staff->full_name }}</div>
                                <div class="text-muted" style="font-size: 12px;">
                                    <i class="fas fa-users me-1"></i>{{ $staff->invitedUsers->count() }} khách hàng
                                </div>
                            </div>
                            <i class="fas fa-chevron-down text-muted transition-transform" style="font-size: 12px; transition: transform 0.3s ease; {{ in_array($staff->id, $expandedStaff) ? 'transform: rotate(180deg);' : '' }}"></i>
                        </div>

                        <!-- Danh sách người dùng của nhân viên (có thể thu gọn) -->
                        <div class="staff-users-list" style="transition: all 0.3s ease; {{ in_array($staff->id, $expandedStaff) ? 'max-height: 500px; opacity: 1;' : 'max-height: 0; opacity: 0; overflow: hidden;' }}">
                            @if(in_array($staff->id, $expandedStaff))
                            <div class="ms-4 mt-2">
                                @foreach($staff->invitedUsers as $user)
                                <div class="user-item d-flex align-items-center p-2 rounded-3 mb-1 cursor-pointer"
                                    style="cursor: pointer; transition: all 0.2s ease;"
                                    wire:click="selectUserForChat({{ $user->id }}, {{ $staff->id }})">
                                    <div class="bg-info rounded-circle d-flex align-items-center justify-content-center text-white fw-bold me-2" style="width: 28px; height: 28px; font-size: 12px;">
                                        {{ substr($user->full_name, 0, 1) }}
                                    </div>
                                    <div class="text-dark small fw-medium">{{ $user->full_name ." (".$user->username.")" }}</div>
                                </div>
                                @endforeach
                            </div>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @else
            <!-- Giao diện Staff -->
            <div class="p-3">
                <h6 class="text-muted mb-3 fw-semibold">
                    <i class="fas fa-user-friends me-2"></i>Khách hàng của tôi
                </h6>
                @foreach(auth()->user()->invitedUsers()->where('role', 'member')->get() as $user)
                @php
                $conversation = $conversations->where('user_id', $user->id)->first();
                @endphp
                <div class="conversation-item d-flex align-items-center p-3 rounded-3 mb-2 cursor-pointer {{ $this->selectedConversation && $conversation && $this->selectedConversation->id === $conversation->id ? 'bg-primary bg-opacity-10 border-start border-primary border-4 shadow-sm' : 'bg-white shadow-sm' }}"
                    style="cursor: pointer; transition: all 0.3s ease; border: 1px solid #e9ecef;"
                    wire:click="selectUserForChat({{ $user->id }})">
                    <div class="avatar bg-primary rounded-circle d-flex align-items-center justify-content-center text-white fw-bold me-3 position-relative" style="width: 45px; height: 45px; font-size: 16px;">
                        {{ substr($user->full_name, 0, 1)  }}
                        @if($conversation && $conversation->messages->where('sender_id', $user->id)->where('created_at', '>', now()->subHour())->count() > 0)
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 8px;">
                            <span class="visually-hidden">unread messages</span>
                        </span>
                        @endif
                    </div>
                    <div class="flex-grow-1 min-width-0">
                        <div class="fw-semibold text-dark text-truncate mb-1">{{ $user->full_name . " (".$user->username.")"}}</div>
                        <div class="text-muted small text-truncate d-flex align-items-center">
                            @if($conversation && $conversation->messages->last())
                            <i class="fas fa-comment-dots me-1" style="font-size: 10px;"></i>
                            {{ Str::limit($conversation->messages->last()->message, 30) }}
                            @else
                            <i class="fas fa-clock me-1" style="font-size: 10px;"></i>
                            Chưa có tin nhắn
                            @endif
                        </div>
                    </div>
                    <div class="text-center" style="font-size: 11px;">
                        @if($conversation && $conversation->messages->last())
                        {{ $conversation->messages->last()->created_at->diffForHumans() }}
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
            @endif
        </div>
    </div>

    <!-- Khu vực chat chính -->
    <div class="flex-grow-1 d-flex flex-column" style="transition: all 0.3s ease;">
        @if($this->selectedConversation)
        <!-- Header chat -->
        <div class="bg-white border-bottom p-3 shadow-sm chat-header" style="transition: all 0.3s ease;">
            <div class="d-flex align-items-center">
                <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center text-white fw-bold me-3 position-relative" style="width: 45px; height: 45px; font-size: 16px;">
                    {{ substr($this->selectedConversation->user->full_name, 0, 1) }}
                    <span class="position-absolute bottom-0 end-0 bg-success border border-2 border-white rounded-circle" style="width: 12px; height: 12px;"></span>
                </div>
                <div class="flex-grow-1">
                    <div class="fw-semibold text-dark mb-1">{{ $this->selectedConversation->user->full_name }}</div>
                    <div class="text-muted small d-flex align-items-center">
                        <!-- <i class="fas fa-circle text-success me-1" style="font-size: 8px;"></i> -->
                        @if(auth()->user()->role === 'admin' || auth()->user()->role === 'staff')
                        Được quản lý bởi: {{ $this->selectedConversation->staff->full_name }}
                        @else
                        Đang hoạt động
                        @endif
                    </div>
                </div>
                <div class="d-flex align-items-center">
                    @if($this->selectedConversation->user->status==="activated")
                    <button class="btn btn-outline-danger btn-sm me-2" onclick="confirmChangeStatusOfUser({{ $this->selectedConversation->user->id }},'{{ $this->selectedConversation->user->status }}')" title="Khóa tài khoản người dùng">
                        <i class="fas fa-lock"></i>
                    </button>
                    @elseif($this->selectedConversation->user->status==="banned")
                    <button class="btn btn-outline-success btn-sm me-2" onclick="confirmChangeStatusOfUser({{ $this->selectedConversation->user->id }},'{{ $this->selectedConversation->user->status }}')" title="Mở khóa tài khoản người dùng">
                        <i class="fas fa-lock-open"></i>
                    </button>
                    @endif
                    <button class="btn btn-outline-danger btn-sm" onclick="confirmDeleteMessages()" title="Kết thúc cuộc trò chuyện">
                        <i class="fas fa-phone-slash"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Loading indicator -->
        <div id="loading-messages" class="text-center p-2" style="display: none;">
            <div class="spinner-border spinner-border-sm text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <small class="text-muted ms-2">Đang tải tin nhắn...</small>
        </div>

        <!-- Khu vực tin nhắn -->
        <div class="flex-grow-1 overflow-auto p-3 custom-scrollbar" id="messages-container" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); scroll-behavior: smooth;">
            @foreach($messages as $index => $message)
            @php
            $isCurrentUser = $message['sender_id'] == auth()->id();
            $currentUserRole = auth()->user()->role;
            $senderRole = $message['sender']['role'] ?? 'member';

            // Xác định classes cho message
            if ($isCurrentUser) {
            $containerClass = 'justify-content-end';
            $bubbleClass = 'bg-primary text-white';
            $tailClass = 'message-tail-right';
            $tailColor = 'transparent';
            } else {
            // Tin nhắn của người khác - luôn hiển thị bên trái
            $containerClass = 'justify-content-start';
            $tailClass = 'message-tail-left';

            switch ($senderRole) {
            case 'admin':
            $bubbleClass = 'admin-message text-white';
            $tailColor = '#dc3545';
            break;
            case 'staff':
            $bubbleClass = 'staff-message text-white';
            $tailColor = '#198754';
            break;
            case 'member':
            $bubbleClass = 'member-message text-white';
            $tailColor = '#0d6efd';
            break;
            default:
            $bubbleClass = 'bg-white text-dark shadow-sm';
            $tailColor = '#ffffff';
            }
            }
            @endphp

            <div class="message-item d-flex mb-3 {{ $containerClass }}"
                wire:key="message-{{ $message['id'] ?? $index }}"
                style="animation: slideIn 0.3s ease-out;">
                <div class="message-bubble rounded-4 px-3 py-2 position-relative {{ $bubbleClass }}"
                    style="max-width: 70%; transition: all 0.2s ease; border: 1px solid {{ $isCurrentUser ? 'transparent' : '#e9ecef' }};">

                    <!-- Hiển thị tên người gửi và role (chỉ với tin nhắn của người khác) -->
                    @if(!$isCurrentUser)
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <small class="fw-bold opacity-90">
                            {{ $message['sender']['full_name'] ?? 'Unknown User' }}
                        </small>
                        <span class="role-badge ms-2 text-right" style="font-size: 9px; padding: 2px 6px; border-radius: 10px; 
                    @if($senderRole === 'admin') 
                        background-color: rgba(220, 53, 69, 0.2); color: rgb(149, 188, 247); border: 1px solid #dc3545;
                    @elseif($senderRole === 'staff')
                        background-color: rgba(25, 135, 84, 0.2); color: rgb(149, 188, 247); border: 1px solid #198754;
                    @else
                        background-color: rgba(13, 110, 253, 0.2); color:rgb(149, 188, 247); border: 1px solid #0d6efd;
                    @endif
                ">
                            @if($senderRole === 'admin') Admin
                            @elseif($senderRole === 'staff') Staff
                            @else Member
                            @endif
                        </span>
                    </div>
                    @endif

                    <!-- Nội dung tin nhắn -->
                    @if(isset($message['image_path']) && $message['image_path'])
                    <div class="mb-2">
                        <img src="{{ asset('storage/' . $message['image_path']) }}" alt="Ảnh" class="img-fluid rounded" style="max-height: 200px;">
                    </div>
                    @elseif($message['message'])
                    <div class="mb-1">{{ $message['message'] }}</div>
                    @endif

                    <!-- Thời gian và trạng thái -->
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="small {{ $isCurrentUser ? 'text-white-50' : 'opacity-75' }}" style="font-size: 10px;">
                            {{ \Carbon\Carbon::parse($message['created_at'])->format('H:i') }}
                        </div>
                        @if($isCurrentUser)
                        <div class="ms-2">
                            <i class="fas fa-check-double text-white-50" style="font-size: 10px;" title="Đã gửi"></i>
                        </div>
                        @endif
                    </div>

                    <!-- Message tail -->

                    <div class="message-tail position-absolute {{ $tailClass }}"
                        style="@if($tailClass === 'message-tail-left') 
                            left: -8px; border-right: 8px solid {{ $tailColor }};
                        @else 
                            right: -8px; border-left: 8px solid {{ $isCurrentUser ? '#0d6efd' : $tailColor }};
                        @endif
                        top: 50%; transform: translateY(-50%); 
                        border-top: 8px solid transparent; 
                        border-bottom: 8px solid transparent;">
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        <!-- Input tin nhắn -->
        <div class="bg-white border-top p-3 shadow-sm message-input" style="transition: all 0.3s ease;">
            @if ($image)
            <div class="mb-2 d-flex align-items-center">
                <div class="position-relative me-2">
                    <img src="{{ $image->temporaryUrl() }}" alt="Preview" class="rounded" style="height: 60px; object-fit: cover;">
                    <button type="button" class="btn-close position-absolute top-0 end-0 bg-white rounded-circle" style="transform: scale(0.7);"
                        wire:click="$set('image', null)" aria-label="Xóa ảnh xem trước"></button>
                </div>
            </div>
            @endif
            <form wire:submit.prevent="sendMessage" class="d-flex align-items-center">
                <input type="file" wire:model="image" accept="image/*" class="d-none" id="upload-image-admin">
                <label for="upload-image-admin" class="btn btn-outline-secondary btn-sm rounded-circle d-flex align-items-center justify-content-center m-0 me-2" style="width: 40px; height: 40px;" title="Gửi ảnh">
                    <i class="fas fa-image"></i>
                </label>
                <div class="flex-grow-1 position-relative">
                    <input type="text"
                        wire:model="messageText"
                        placeholder="Nhập tin nhắn..."
                        class="form-control rounded-pill pe-5"
                        style="border: 2px solid #e9ecef; transition: all 0.3s ease;"
                        wire:keydown.enter.prevent="sendMessage">
                    <button type="button" class="btn btn-link position-absolute end-0 top-50 translate-middle-y" style="z-index: 10;">
                        <i class="fas fa-smile text-muted"></i>
                    </button>
                </div>
                <button type="submit" class="btn btn-primary rounded-circle ms-2 d-flex align-items-center justify-content-center" style="width: 45px; height: 45px; transition: all 0.3s ease;">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </form>
        </div>
        @else
        <!-- Trạng thái chưa chọn conversation -->
        <div class="flex-grow-1 d-flex align-items-center justify-content-center" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);">
            <div class="text-center">
                <div class="bg-primary bg-opacity-10 rounded-circle mx-auto mb-4 d-flex align-items-center justify-content-center" style="width: 100px; height: 100px; animation: pulse 2s infinite;">
                    <i class="fas fa-comments fa-3x text-primary"></i>
                </div>
                <h4 class="text-dark mb-3 fw-bold">Chào mừng đến với Chat!</h4>
                <p class="text-muted mb-0">Chọn một cuộc trò chuyện từ danh sách bên trái để bắt đầu</p>
            </div>
        </div>
        @endif
    </div>
</div>

<script>
    let currentChannel = null;
    let isLoadingMessages = false;
    let hasMoreMessages = true;
    let currentPage = 1;

    document.addEventListener('livewire:initialized', () => {
        // Scroll to bottom with smooth animation
        Livewire.on('scroll-to-bottom', () => {
            const container = document.getElementById('messages-container');
            if (container) {
                setTimeout(() => {
                    container.scrollTo({
                        top: container.scrollHeight,
                        behavior: 'smooth'
                    });
                }, 100);
            }
        });

        // Scroll to bottom when new conversation selected
        Livewire.on('conversation-selected', () => {
            const container = document.getElementById('messages-container');
            if (container) {
                // Reset pagination
                currentPage = 1;
                hasMoreMessages = true;

                container.addEventListener('scroll', () => {
                    if (container.scrollTop === 0 && !isLoadingMessages && hasMoreMessages) {
                        loadMoreMessages();
                    }
                });
                setTimeout(() => {
                    container.scrollTo({
                        top: container.scrollHeight,
                        behavior: 'smooth'
                    });
                }, 200);
            }
        });

        // Load more messages when scrolling to top


        // Join conversation channel
        Livewire.on('join-conversation-channel', (data) => {

            // Leave previous channel if exists
            if (currentChannel) {
                window.Echo.leave(currentChannel);
            }

            // Join new channel
            currentChannel = `chat.conversation.${data.conversationId}`;

            window.Echo.private(currentChannel)
                .listen('.MessageSent', (e) => {
                    console.log('New message at Admin:', e.message);
                    const root = document.getElementById('chat-root');
                    const component = Livewire.find(root.getAttribute('wire:id'));
                    component.dispatch('message-received', e);
                })
                .error((error) => {
                    console.error('Echo error:', error);
                });
        });
    });

    function loadMoreMessages() {
        if (isLoadingMessages || !hasMoreMessages) return;

        isLoadingMessages = true;
        const loadingEl = document.getElementById('loading-messages');
        const container = document.getElementById('messages-container');

        // Show loading indicator
        loadingEl.style.display = 'block';

        // Get current scroll height to maintain position
        const oldScrollHeight = container.scrollHeight;

        // Call Livewire method to load more messages
        const root = document.getElementById('chat-root');
        const component = Livewire.find(root.getAttribute('wire:id'));

        component.call('loadMoreMessages', currentPage + 1)
            .then(() => {
                currentPage++;

                // Maintain scroll position
                setTimeout(() => {
                    const newScrollHeight = container.scrollHeight;
                    container.scrollTop = newScrollHeight - oldScrollHeight;

                    loadingEl.style.display = 'none';
                    isLoadingMessages = false;
                }, 200);
            })
            .catch(() => {
                loadingEl.style.display = 'none';
                isLoadingMessages = false;
            });
    }

    document.addEventListener('DOMContentLoaded', function() {
        Livewire.on('reset-message-input', () => {
            const input = document.querySelector('input[wire\\:model*="messageText"]');
            if (input) {
                input.value = '';
                input.focus();
            }
        });

        Livewire.on('no-more-messages', () => {
            hasMoreMessages = false;
        });

        Livewire.on('swal', (data) => {
            console.log(data);

            swal({
                icon: data[0].type || 'info',
                title: data[0].title || '',
                text: data[0].text || '',
                timer: 2500,
                buttons: false
            });
        });
    });

    function confirmDeleteMessages() {
        swal({
                title: "Xác nhận xóa",
                text: "Bạn có chắc muốn xóa tất cả tin nhắn trong đoạn chat này?",
                icon: "warning",
                buttons: true,
                dangerMode: true,
            })
            .then((isConfirmed) => {
                if (isConfirmed) {
                    Livewire.dispatch('delete-all-messages');
                }
            });
    }

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
        swal({
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
</script>