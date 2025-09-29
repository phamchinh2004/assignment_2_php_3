<div class="d-flex flex-column flex-md-row" id="chat-root" style="height: 100vh; background-color: #f8f9fa;" wire:poll.10s="refreshChatState" wire:id="{{ $this->getId() }}">
    <!-- Sidebar trái -->
    <!-- SIDEBAR DẠNG OFFCANVAS (mobile) -->
    <div class="offcanvas offcanvas-start d-md-none" tabindex="-1" id="mobileSidebar" aria-labelledby="mobileSidebarLabel">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title" id="mobileSidebarLabel">Danh sách Chat</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body p-0">
            <div class="bg-white border-end shadow-sm" style="width: 100%;">
                @include('livewire.admin.sidebar-chat')
            </div>
        </div>
    </div>

    <!-- SIDEBAR CỐ ĐỊNH (desktop) -->
    <div class="bg-white border-end shadow-sm d-none d-md-block" style="width: 350px;">
        @include('livewire.admin.sidebar-chat')
    </div>

    <!-- Khu vực chat chính -->
    <div class="flex-grow-1 d-flex flex-column" style="transition: all 0.3s ease;height:100vh">
        <button class="btn btn-outline-primary d-md-none mb-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar">
            <i class="fas fa-bars me-1"></i> Mở danh sách Chat
        </button>
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
        <div class="flex-grow-1 overflow-auto p-3 custom-scrollbar" wire:init="scrollToBottom" id="messages-container" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); scroll-behavior: smooth;">
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
                    <div class="mb-2 panzoom-parent">
                        <img src="{{ asset('storage/' . $message['image_path']) }}"
                            alt="Ảnh"
                            class="img-fluid rounded zoomable-image"
                            style="max-height: 200px; cursor: pointer;">
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
    <!-- Modal Zoom -->
    <div class="zoom-modal" id="zoomModal">
        <button class="zoom-close" id="closeModal">&times;</button>
        <div class="zoom-container" id="zoomContainer">
            <img src="" alt="Zoomed image" class="zoom-modal-image" id="zoomModalImage">
        </div>
        <div class="zoom-hint">
            🖱️ Cuộn chuột để zoom | 🖐️ Kéo để di chuyển | 🖱️ Double-click để reset
        </div>
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
                    // console.log('New message at Admin:', e.message);

                    playNotificationSound(1, 3, 500);
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
        const modal = document.getElementById('zoomModal');
        const modalImage = document.getElementById('zoomModalImage');
        const closeBtn = document.getElementById('closeModal');
        const zoomContainer = document.getElementById('zoomContainer');
        let currentScale = 1;
        let currentX = 0;
        let currentY = 0;
        let isDragging = false;
        let startX, startY;

        function openZoomModal(imageSrc) {
            modalImage.src = imageSrc;
            modal.classList.add('active');
            document.body.classList.add('modal-open');
            document.body.style.overflow = 'hidden';

            // Reset về giữa
            currentScale = 1;
            currentX = 0;
            currentY = 0;
            updateTransform();

            console.log('✅ Modal opened');
        }

        function closeZoomModal() {
            modal.classList.remove('active');
            document.body.classList.remove('modal-open');
            document.body.style.overflow = '';

            // Reset
            currentScale = 1;
            currentX = 0;
            currentY = 0;
            updateTransform();
        }

        function updateTransform() {
            modalImage.style.transform = `translate(${currentX}px, ${currentY}px) scale(${currentScale})`;
        }

        // Zoom bằng scroll
        zoomContainer.addEventListener('wheel', function(e) {
            e.preventDefault();
            e.stopPropagation();

            const rect = modalImage.getBoundingClientRect();
            const containerRect = zoomContainer.getBoundingClientRect();

            // Vị trí chuột trong container
            const mouseX = e.clientX - containerRect.left;
            const mouseY = e.clientY - containerRect.top;

            // Vị trí chuột trong ảnh
            const imgX = (mouseX - rect.left) / currentScale;
            const imgY = (mouseY - rect.top) / currentScale;

            // Tính scale mới
            const delta = e.deltaY > 0 ? 0.9 : 1.1;
            const newScale = Math.min(Math.max(0.5, currentScale * delta), 10);

            // Tính offset mới để zoom vào điểm chuột
            const scaleDiff = newScale - currentScale;
            currentX -= imgX * scaleDiff;
            currentY -= imgY * scaleDiff;
            currentScale = newScale;

            updateTransform();
        }, {
            passive: false
        });

        // Kéo thả ảnh
        modalImage.addEventListener('mousedown', function(e) {
            if (currentScale > 1) {
                isDragging = true;
                startX = e.clientX - currentX;
                startY = e.clientY - currentY;
                modalImage.style.cursor = 'grabbing';
            }
        });

        document.addEventListener('mousemove', function(e) {
            if (isDragging) {
                currentX = e.clientX - startX;
                currentY = e.clientY - startY;
                updateTransform();
            }
        });

        document.addEventListener('mouseup', function() {
            isDragging = false;
            modalImage.style.cursor = 'grab';
        });

        // Double click để reset
        modalImage.addEventListener('dblclick', function() {
            currentScale = 1;
            currentX = 0;
            currentY = 0;
            modalImage.style.transition = 'transform 0.3s ease';
            updateTransform();

            setTimeout(() => {
                modalImage.style.transition = '';
            }, 300);
        });

        function attachImageClickEvents() {
            document.querySelectorAll('.zoomable-image').forEach(img => {
                const newImg = img.cloneNode(true);
                img.parentNode.replaceChild(newImg, img);

                newImg.onclick = function() {
                    openZoomModal(this.src);
                };
            });
            console.log('✅ Click events attached');
        }

        attachImageClickEvents();

        closeBtn.addEventListener('click', closeZoomModal);

        modal.addEventListener('click', (e) => {
            if (e.target === modal) closeZoomModal();
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && modal.classList.contains('active')) {
                closeZoomModal();
            }
        });

        if (typeof Livewire !== 'undefined') {
            Livewire.hook('morph.updated', () => {
                setTimeout(attachImageClickEvents, 100);
            });
        }


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