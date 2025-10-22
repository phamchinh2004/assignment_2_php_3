@php
    use Illuminate\Support\Facades\Storage;
@endphp

@push('css')
    @vite('resources/css/admin/chat.css')
@endpush

<div class="d-flex flex-column flex-md-row" id="chat-root" style="height: 100vh; background-color: #f8f9fa;" wire:id="{{ $this->getId() }}">
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
                <div class="position-relative me-3" style="width: 45px; height: 45px;">
                    <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold" style="width: 100%; height: 100%; font-size: 16px; overflow: hidden;">
                        @if($this->selectedConversation->user->avatar && Storage::disk('public')->exists($this->selectedConversation->user->avatar))
                            <img src="{{ asset('storage/' . $this->selectedConversation->user->avatar) }}"
                                alt="{{ $this->selectedConversation->user->full_name }}"
                                style="width: 100%; height: 100%; object-fit: cover;">
                        @else
                            <div class="bg-primary w-100 h-100 d-flex align-items-center justify-content-center rounded-circle">
                                {{ substr($this->selectedConversation->user->full_name, 0, 1) }}
                            </div>
                        @endif
                    </div>
                    @php
                        $isOnline = $this->selectedConversation->user->last_seen && 
                                    $this->selectedConversation->user->last_seen->diffInMinutes(now()) <= 5;
                    @endphp
                    <span class="position-absolute bottom-0 end-0 {{ $isOnline ? 'bg-success' : 'bg-secondary' }} border border-2 border-white rounded-circle" 
                          style="width: 12px; height: 12px;" 
                          title="{{ $isOnline ? 'Đang hoạt động' : ($this->selectedConversation->user->last_seen ? 'Hoạt động ' . $this->selectedConversation->user->last_seen->diffForHumans() : 'Chưa từng online') }}"></span>
                </div>
                <div class="flex-grow-1">
                    <div class="fw-semibold text-dark mb-1">
                        @if($this->selectedConversation->user->hasPenalizedOrders())
                            <i class="fas fa-exclamation-triangle text-warning me-1" title="Người dùng đang bị phạt"></i>
                        @endif
                        {{ $this->selectedConversation->user->full_name }}
                    </div>
                    <div class="text-muted small d-flex align-items-center">
                        @if(auth()->user()->role === 'admin' || auth()->user()->role === 'staff')
                            {{ $isOnline ? 'Đang hoạt động' : ($this->selectedConversation->user->last_seen ? 'Hoạt động ' . $this->selectedConversation->user->last_seen->diffForHumans() : 'Chưa từng online') }} | 
                            Được quản lý bởi: {{ $this->selectedConversation->staff->full_name }}
                        @else
                            {{ $isOnline ? 'Đang hoạt động' : ($this->selectedConversation->user->last_seen ? 'Hoạt động ' . $this->selectedConversation->user->last_seen->diffForHumans() : 'Chưa từng online') }}
                        @endif
                    </div>
                    @if($this->selectedConversation->user->hasPenalizedOrders())
                        @php
                            $penaltyInfo = $this->selectedConversation->user->penalty_info;
                            $usdToVnd = 26342; // Tỷ giá USD/VND hiện tại
                        @endphp
                        <div class="alert alert-warning mb-0 mt-2 py-1 px-2" style="font-size: 11px; border-left: 3px solid #ffc107;" x-data="{ penaltyOpen: true }">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <div class="fw-bold" style="font-size: 12px;">
                                    <i class="fas fa-exclamation-triangle text-warning me-1"></i>
                                    Đang bị phạt ({{ $penaltyInfo['frozen_orders_count'] }} đơn)
                                </div>
                                <button class="btn btn-sm p-0 text-warning" type="button" @click="penaltyOpen = !penaltyOpen" style="border: none; background: none;">
                                    <i class="fas" :class="penaltyOpen ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                                </button>
                            </div>
                            <div x-show="penaltyOpen" x-transition>
                            <div class="d-flex flex-wrap gap-2">
                                <div>
                                    <span class="text-muted">💰 Phạt (30%):</span> <strong class="text-danger">{{ number_format($penaltyInfo['total_penalty'] * $usdToVnd, 0, ',', '.') }}₫</strong>
                                    <span class="text-muted" style="font-size: 9px;">(~${{ number_format($penaltyInfo['total_penalty'], 2) }})</span>
                                </div>
                                <div>
                                    <span class="text-muted">🛒 ĐH:</span> <strong>{{ number_format($penaltyInfo['total_frozen_value'] * $usdToVnd, 0, ',', '.') }}₫</strong>
                                    <span class="text-muted" style="font-size: 9px;">(~${{ number_format($penaltyInfo['total_frozen_value'], 2) }})</span>
                                </div>
                                <div>
                                    <span class="text-muted">💳 Dư:</span> <strong>{{ number_format($penaltyInfo['current_balance'] * $usdToVnd, 0, ',', '.') }}₫</strong>
                                    <span class="text-muted" style="font-size: 9px;">(~${{ number_format($penaltyInfo['current_balance'], 2) }})</span>
                                </div>
                                @if($penaltyInfo['required_deposit'] > 0)
                                    <div>
                                        <span class="text-danger">⬆️ Nạp:</span> <strong class="text-danger">{{ number_format($penaltyInfo['required_deposit'] * $usdToVnd, 0, ',', '.') }}₫</strong>
                                        <span class="text-danger" style="font-size: 9px;">(~${{ number_format($penaltyInfo['required_deposit'], 2) }})</span>
                                    </div>
                                @else
                                    <div class="text-success fw-bold">
                                        <i class="fas fa-check-circle"></i> Đủ tiền
                                    </div>
                                @endif
                            </div>
                            <div class="text-muted mt-1" style="font-size: 9px;">
                                <i class="fas fa-info-circle"></i> 1 USD = {{ number_format($usdToVnd, 0, ',', '.') }}₫ | Được thưởng 10% khi hoàn thành
                            </div>
                            <hr class="my-2">
                            <div class="mb-1" style="font-size: 10px;">
                                <strong><i class="fas fa-bolt me-1"></i>Tin nhắn nhanh:</strong>
                            </div>
                            <div class="d-flex flex-column gap-1">
                                @php
                                    $quickMessage2 = "Tài khoản của bạn đang có " . $penaltyInfo['frozen_orders_count'] . " đơn hàng bị phạt với tổng giá trị " . number_format($penaltyInfo['total_frozen_value'] * $usdToVnd, 0, ',', '.') . " VND và tiền phạt " . number_format($penaltyInfo['total_penalty'] * $usdToVnd, 0, ',', '.') . " VND (30%). Vui lòng xử lý để tiếp tục.";
                                @endphp
                                
                                @if($penaltyInfo['required_deposit'] > 0)
                                    @php
                                        $quickMessage1 = "Bạn cần nạp thêm " . number_format($penaltyInfo['required_deposit'] * $usdToVnd, 0, ',', '.') . " VND (số dư: " . number_format($penaltyInfo['current_balance'] * $usdToVnd, 0, ',', '.') . " - [đơn hàng: " . number_format($penaltyInfo['total_frozen_value'] * $usdToVnd, 0, ',', '.') . " + tiền phạt: " . number_format($penaltyInfo['total_penalty'] * $usdToVnd, 0, ',', '.') . "]) để xử lý đơn hàng. Hoàn thành đơn hàng sẽ được hệ thống thưởng 10%.";
                                    @endphp
                                    <button type="button" class="btn btn-outline-primary btn-sm text-start" style="font-size: 9px; white-space: normal;" onclick="copyQuickMessage('{{ addslashes($quickMessage1) }}')" title="Click để sao chép">
                                        📋 {{ Str::limit($quickMessage1, 60) }}
                                    </button>
                                @else
                                    @php
                                        $quickMessage4 = "Số dư của bạn đủ để xử lý đơn hàng bị phạt. Vui lòng hoàn thành các đơn hàng để được hệ thống thưởng 10%.";
                                    @endphp
                                    <button type="button" class="btn btn-outline-success btn-sm text-start" style="font-size: 9px; white-space: normal;" onclick="copyQuickMessage('{{ addslashes($quickMessage4) }}')" title="Click để sao chép">
                                        📋 {{ Str::limit($quickMessage4, 60) }}
                                    </button>
                                @endif
                                
                                <button type="button" class="btn btn-outline-primary btn-sm text-start" style="font-size: 9px; white-space: normal;" onclick="copyQuickMessage('{{ addslashes($quickMessage2) }}')" title="Click để sao chép">
                                    📋 {{ Str::limit($quickMessage2, 60) }}
                                </button>
                            </div>
                            </div>
                        </div>
                    @endif
                    
                    {{-- Tin nhắn nhanh cho người có đơn hàng đặc biệt --}}
                    @if($this->selectedConversation->user->hasSpecialOrders())
                        @php
                            $specialInfo = $this->selectedConversation->user->special_orders_info;
                            $usdToVnd = 26342;
                        @endphp
                        
                        @if(!$this->selectedConversation->user->hasPenalizedOrders())
                            {{-- Người có đơn đặc biệt nhưng không bị phạt --}}
                            <div class="alert alert-success mb-0 mt-2 py-1 px-2" style="font-size: 11px; border-left: 3px solid #198754;" x-data="{ specialOpen: true }">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <div style="font-size: 10px;">
                                        <strong><i class="fas fa-gift me-1"></i>Đơn hàng đặc biệt ({{ $specialInfo['orders_count'] }} đơn)</strong>
                                    </div>
                                    <button class="btn btn-sm p-0 text-success" type="button" @click="specialOpen = !specialOpen" style="border: none; background: none;">
                                        <i class="fas" :class="specialOpen ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                                    </button>
                                </div>
                                <div x-show="specialOpen" x-transition class="d-flex flex-column gap-1">
                                    @php
                                        $quickMessageSpecial1 = "Sau khi kiểm tra tài khoản của bạn, xin chúc mừng bạn khi tham gia chương trình sự kiện cặp đôi đã quay trúng đơn thương may mắn của sự kiện. Bạn sẽ được hệ thống thưởng 10% khi hoàn thành phân phối.";
                                        
                                        if ($specialInfo['required_deposit'] > 0) {
                                            $quickMessageSpecial2 = "Bạn cần nạp thêm " . number_format($specialInfo['required_deposit'] * $usdToVnd, 0, ',', '.') . " VND (số dư: " . number_format($specialInfo['current_balance'] * $usdToVnd, 0, ',', '.') . " - đơn hàng đặc biệt: " . number_format($specialInfo['total_value'] * $usdToVnd, 0, ',', '.') . ") để xử lý đơn hàng. Hoàn thành sẽ được hệ thống thưởng 10%.";
                                        }
                                    @endphp
                                    
                                    <button type="button" class="btn btn-outline-success btn-sm text-start" style="font-size: 9px; white-space: normal;" onclick='copyQuickMessage(`{{ str_replace('`', '\`', $quickMessageSpecial1) }}`)' title="Click để sao chép">
                                        🎉 Chúc mừng trúng đơn may mắn
                                    </button>
                                    
                                    @if($specialInfo['required_deposit'] > 0)
                                        <button type="button" class="btn btn-outline-primary btn-sm text-start" style="font-size: 9px; white-space: normal;" onclick="copyQuickMessage('{{ addslashes($quickMessageSpecial2) }}')" title="Click để sao chép">
                                            📋 {{ Str::limit($quickMessageSpecial2, 60) }}
                                        </button>
                                    @endif
                                </div>
                                </div>
                            </div>
                        @endif
                        
                        {{-- Tin nhắn chung cho người có đơn đặc biệt --}}
                        <div class="alert alert-info mb-0 mt-2 py-1 px-2" style="font-size: 11px; border-left: 3px solid #0dcaf0;" x-data="{ quickMsgOpen: true }">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <div style="font-size: 10px;">
                                    <strong><i class="fas fa-bolt me-1"></i>Tin nhắn nhanh:</strong>
                                </div>
                                <button class="btn btn-sm p-0 text-info" type="button" @click="quickMsgOpen = !quickMsgOpen" style="border: none; background: none;">
                                    <i class="fas" :class="quickMsgOpen ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                                </button>
                            </div>
                            <div x-show="quickMsgOpen" x-transition class="d-flex flex-column gap-1">
                                @php
                                    $quickMessage5 = "VIB : 071679952" . PHP_EOL . "PHAM VAN HIEU";
                                    $quickMessage6 = "Sau khi giao dịch thành công, bạn vui lòng cung cấp hình ảnh để xác minh. Hiệu lực trong vòng 30 phút tính từ lúc cung cấp tài khoản ngân hàng. Xin Cảm Ơn!";
                                @endphp
                                
                                <button type="button" class="btn btn-outline-info btn-sm text-start" style="font-size: 9px; white-space: normal;" onclick='copyQuickMessage(`{{ str_replace('`', '\`', $quickMessage5) }}`)' title="Click để sao chép">
                                    🏦 Thông tin tài khoản ngân hàng
                                </button>
                                
                                <button type="button" class="btn btn-outline-warning btn-sm text-start" style="font-size: 9px; white-space: normal;" onclick='copyQuickMessage(`{{ str_replace('`', '\`', $quickMessage6) }}`)' title="Click để sao chép">
                                    ⏱️ Hướng dẫn xác minh giao dịch
                                </button>
                            </div>
                        </div>
                    @else
                        {{-- Tin nhắn chung cho người không có đơn đặc biệt --}}
                        <div class="alert alert-secondary mb-0 mt-2 py-1 px-2" style="font-size: 11px; border-left: 3px solid #6c757d;" x-data="{ generalMsgOpen: true }">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <div style="font-size: 10px;">
                                    <strong><i class="fas fa-comments me-1"></i>Tin nhắn nhanh:</strong>
                                </div>
                                <button class="btn btn-sm p-0 text-secondary" type="button" @click="generalMsgOpen = !generalMsgOpen" style="border: none; background: none;">
                                    <i class="fas" :class="generalMsgOpen ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                                </button>
                            </div>
                            <div x-show="generalMsgOpen" x-transition class="d-flex flex-column gap-1">
                                @php
                                    $quickMessageGeneral1 = "Xin chào, tôi có thể giúp gì cho bạn?";
                                    $quickMessageGeneral2 = "Chào bạn! Nếu bạn có bất kỳ thắc mắc nào, vui lòng cho tôi biết.";
                                    $quickMessageGeneral3 = "Cảm ơn bạn đã liên hệ. Tôi sẽ hỗ trợ bạn ngay bây giờ.";
                                @endphp
                                
                                <button type="button" class="btn btn-outline-secondary btn-sm text-start" style="font-size: 9px; white-space: normal;" onclick='copyQuickMessage(`{{ $quickMessageGeneral1 }}`)' title="Click để sao chép">
                                    👋 {{ $quickMessageGeneral1 }}
                                </button>
                                
                                <button type="button" class="btn btn-outline-secondary btn-sm text-start" style="font-size: 9px; white-space: normal;" onclick='copyQuickMessage(`{{ $quickMessageGeneral2 }}`)' title="Click để sao chép">
                                    💬 {{ $quickMessageGeneral2 }}
                                </button>
                                
                                <button type="button" class="btn btn-outline-secondary btn-sm text-start" style="font-size: 9px; white-space: normal;" onclick='copyQuickMessage(`{{ $quickMessageGeneral3 }}`)' title="Click để sao chép">
                                    🙏 {{ $quickMessageGeneral3 }}
                                </button>
                            </div>
                        </div>
                    @endif
                </div>
                <div class="dropdown ms-3">
                    <button class="btn btn-outline-secondary btn-sm" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Thao tác">
                        <i class="fas fa-bars"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        @if($this->selectedConversation->user->status==="activated")
                            <li>
                                <a class="dropdown-item text-danger" href="javascript:void(0)" onclick="confirmChangeStatusOfUser({{ $this->selectedConversation->user->id }},'{{ $this->selectedConversation->user->status }}')">
                                    <i class="fas fa-lock me-2"></i>Khóa tài khoản
                                </a>
                            </li>
                        @elseif($this->selectedConversation->user->status==="banned")
                            <li>
                                <a class="dropdown-item text-success" href="javascript:void(0)" onclick="confirmChangeStatusOfUser({{ $this->selectedConversation->user->id }},'{{ $this->selectedConversation->user->status }}')">
                                    <i class="fas fa-lock-open me-2"></i>Mở khóa tài khoản
                                </a>
                            </li>
                        @endif
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item text-danger" href="javascript:void(0)" onclick="confirmDeleteMessages()">
                                <i class="fas fa-trash me-2"></i>Xóa tin nhắn
                            </a>
                        </li>
                    </ul>
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
            @if (empty($messages))
            <div class="flex-grow-1 d-flex align-items-center justify-content-center" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);">
                <div class="text-center">
                    <div class="bg-primary bg-opacity-10 rounded-circle mx-auto mb-4 d-flex align-items-center justify-content-center" style="width: 100px; height: 100px; animation: pulse 2s infinite;">
                        <i class="fas fa-comments fa-3x text-primary"></i>
                    </div>
                    <h4 class="text-dark mb-3 fw-bold">Chưa có tin nhắn nào!</h4>
                    <p class="text-muted mb-0">Nhắn tin ngay bây giờ...</p>
                </div>
            </div>
            @else

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
                    <div style="white-space: pre-line; word-wrap: break-word; margin: 0;">{{ trim($message['message']) }}</div>
                    @endif

                    <!-- Thời gian và trạng thái -->
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="small {{ $isCurrentUser ? 'text-white-50' : 'opacity-75' }}" style="font-size: 10px;">
                            {{ \Carbon\Carbon::parse($message['created_at'])->setTimezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i') }}
                        </div>
                        @if($isCurrentUser)
                        <div class="ms-2" data-message-id="{{ $message['id'] }}" data-seen-status="{{ $message['is_read'] ? 'true' : 'false' }}">
                            @if($message['is_read'] ?? false)
                                <i class="fas fa-check-double text-info" style="font-size: 10px;" title="Đã xem"></i>
                            @else
                                <i class="fas fa-check text-white-50" style="font-size: 10px;" title="Đã gửi"></i>
                            @endif
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

            @endif
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
            <form wire:submit.prevent="sendMessage" class="d-flex align-items-end">
                <input type="file" wire:model="image" accept="image/*" class="d-none" id="upload-image-admin">
                <label for="upload-image-admin" class="btn btn-outline-secondary btn-sm rounded-circle d-flex align-items-center justify-content-center m-0 me-2" style="width: 40px; height: 40px; flex-shrink: 0;" title="Gửi ảnh">
                    <i class="fas fa-image"></i>
                </label>
                <div class="flex-grow-1 position-relative">
                    <textarea
                        id="message-input-textarea"
                        wire:model="messageText"
                        placeholder="Nhập tin nhắn... (Shift+Enter để xuống dòng)"
                        class="form-control rounded-3 pe-5"
                        rows="1"
                        style="border: 2px solid #e9ecef; transition: all 0.3s ease; resize: none; overflow-y: hidden; max-height: 150px; padding: 10px 40px 10px 15px; line-height: 1.5;"></textarea>
                    <button type="button" class="btn btn-link position-absolute end-0 top-50 translate-middle-y" style="z-index: 10;">
                        <i class="fas fa-smile text-muted"></i>
                    </button>
                </div>
                <button type="submit" class="btn btn-primary rounded-circle ms-2 d-flex align-items-center justify-content-center" style="width: 45px; height: 45px; flex-shrink: 0; transition: all 0.3s ease;">
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


        // Listen MessageSent event trên staff channel để update sidebar khi có tin nhắn mới
        // (Notification sound và popup đã được xử lý ở master.blade.php)
        @if(auth()->user()->role === 'admin' || auth()->user()->role === 'staff')
            const staffChannel = `staff.{{ auth()->id() }}`;
            window.Echo.private(staffChannel)
                .listen('.MessageSent', (e) => {
                    console.log('New message on staff channel:', e.message);
                    
                    const root = document.getElementById('chat-root');
                    const component = Livewire.find(root.getAttribute('wire:id'));
                    
                    // Nếu tin nhắn không phải từ conversation đang mở, reload sidebar
                    const currentConvId = component.get('selectedConversationId');
                    if (currentConvId != e.message.conversation_id) {
                        // Reload conversations và staff users để cập nhật sidebar
                        component.call('loadConversations');
                        @if(auth()->user()->role === 'admin')
                            component.call('loadStaffUsersAlternative');
                        @endif
                    }
                })
                .listen('.UserJoinChat', (e) => {
                    // Update sidebar khi user join chat
                    const root = document.getElementById('chat-root');
                    const component = Livewire.find(root.getAttribute('wire:id'));
                    component.call('loadConversations');
                    @if(auth()->user()->role === 'admin')
                        component.call('loadStaffUsersAlternative');
                    @endif
                })
                .error((error) => {
                    console.error('Staff Echo error:', error);
                });
        @endif

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

                    // Âm thanh đã được phát bởi global listener trong master.blade.php
                    // Chỉ cần dispatch event để update UI
                    const root = document.getElementById('chat-root');
                    const component = Livewire.find(root.getAttribute('wire:id'));
                    component.dispatch('message-received', e);
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
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                const form = e.target.closest('form');
                if (form) {
                    // Trigger submit
                    const root = document.getElementById('chat-root');
                    const component = Livewire.find(root.getAttribute('wire:id'));
                    component.call('sendMessage').then(() => {
                        // Reset textarea sau khi gửi
                        e.target.value = '';
                        e.target.style.height = 'auto';
                        autoResizeTextarea();
                    });
                }
            }
        }

        // Attach event listeners
        function attachTextareaEvents() {
            const textarea = document.getElementById('message-input-textarea');
            if (textarea) {
                // Remove old listeners by cloning
                const newTextarea = textarea.cloneNode(true);
                textarea.parentNode.replaceChild(newTextarea, textarea);
                
                // Add new listeners
                newTextarea.addEventListener('keydown', handleTextareaKeydown);
                newTextarea.addEventListener('input', autoResizeTextarea);
            }
        }

        // Initialize textarea events
        attachTextareaEvents();

        // ===== Xử lý zoom ảnh =====
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
                setTimeout(() => {
                    attachImageClickEvents();
                    attachTextareaEvents();
                }, 100);
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

@push('scripts')
    @vite('resources/js/admin/chat.js')
@endpush