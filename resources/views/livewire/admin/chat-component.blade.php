@php
    use Illuminate\Support\Facades\Storage;
@endphp

@push('css')
    @vite('resources/css/admin/chat.css')
    <style>
        [x-cloak] { display: none !important; }
        
        @keyframes spinner-border {
            to { transform: rotate(360deg); }
        }
        
        @keyframes pulse {
            0%, 100% { 
                transform: scale(1);
                box-shadow: 0 0 0 0 rgba(102, 126, 234, 0.7);
            }
            50% { 
                transform: scale(1.05);
                box-shadow: 0 0 20px 10px rgba(102, 126, 234, 0.3);
            }
        }
        
        .spinner-border {
            display: inline-block;
            width: 3rem;
            height: 3rem;
            vertical-align: -0.125em;
            border: 0.25em solid currentColor;
            border-right-color: transparent;
            border-radius: 50%;
            animation: spinner-border 0.75s linear infinite;
        }
    </style>
@endpush

<div class="d-flex flex-column flex-md-row" id="chat-root" style="height: 100vh; background-color: #f8f9fa;">
    <!-- Sidebar trái -->
    <!-- SIDEBAR DẠNG OFFCANVAS (mobile) -->
    <div class="offcanvas offcanvas-start d-md-none" tabindex="-1" id="mobileSidebar" aria-labelledby="mobileSidebarLabel">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title" id="mobileSidebarLabel">Danh sách Chat</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body p-0">
            @include('livewire.admin.sidebar-chat', ['isMobile' => true])
        </div>
    </div>

    <!-- SIDEBAR CỐ ĐỊNH (desktop) -->
    <div class="bg-white border-end shadow-sm d-none d-md-block" style="width: 350px; min-width: 350px; max-width: 350px; flex-shrink: 0;">
        @include('livewire.admin.sidebar-chat', ['isMobile' => false])
    </div>

    <!-- Khu vực chat chính -->
    <div class="flex-grow-1 d-flex flex-column position-relative" style="transition: all 0.3s ease; height: 100vh; min-width: 0; overflow: hidden;">
        <!-- Loading Spinner Overlay -->
        <div id="chat-loading-spinner"
             style="display: none; z-index: 1000; top: 0; left: 0; opacity: 0; transition: opacity 0.2s ease; pointer-events: none;"
             class="position-absolute w-100 h-100 d-flex align-items-center justify-content-center bg-white bg-opacity-75">
            <div class="text-center">
                <div class="spinner-border text-primary mb-3" role="status">
                    <span class="visually-hidden">Đang tải...</span>
                </div>
                <p class="text-muted fw-semibold">Đang tải hội thoại...</p>
            </div>
        </div>
        
        <button class="btn btn-outline-primary d-md-none mb-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar">
            <i class="fas fa-bars me-1"></i> Mở danh sách Chat
        </button>
        @if($this->selectedConversation)
        <!-- Header chat -->
        <div wire:key="chat-header-{{ $this->selectedConversation->id }}" class="bg-white border-bottom p-3 shadow-sm chat-header" style="transition: all 0.3s ease;" x-data="{ penaltyOpen: true, specialOpen: true, quickMsgOpen: true, generalMsgOpen: true }">
            <div class="d-flex align-items-center">
                <div class="position-relative me-3" style="width: 45px; height: 45px;">
                    @if($this->selectedConversation->user->avatar && Storage::disk('public')->exists($this->selectedConversation->user->avatar))
                        <img src="{{ asset('storage/' . $this->selectedConversation->user->avatar) }}"
                            alt="{{ $this->selectedConversation->user->full_name }}"
                            class="rounded-circle"
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
                        <div class="alert alert-warning mb-0 mt-2 py-1 px-2" style="font-size: 11px; border-left: 3px solid #ffc107;">
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
                                        $penaltyRequiredVND = number_format($penaltyInfo['required_deposit'] * $usdToVnd, 0, ',', '.');
                                        $penaltyRequiredUSD = number_format($penaltyInfo['required_deposit'], 2);
                                        $penaltyBalanceVND = number_format($penaltyInfo['current_balance'] * $usdToVnd, 0, ',', '.');
                                        $penaltyBalanceUSD = number_format($penaltyInfo['current_balance'], 2);
                                        $penaltyFrozenVND = number_format($penaltyInfo['total_frozen_value'] * $usdToVnd, 0, ',', '.');
                                        $penaltyFrozenUSD = number_format($penaltyInfo['total_frozen_value'], 2);
                                        $penaltyAmountVND = number_format($penaltyInfo['total_penalty'] * $usdToVnd, 0, ',', '.');
                                        $penaltyAmountUSD = number_format($penaltyInfo['total_penalty'], 2);
                                        $penaltyTotalVND = number_format(($penaltyInfo['total_frozen_value'] + $penaltyInfo['total_penalty']) * $usdToVnd, 0, ',', '.');
                                        
                                        $quickMessage1 = "- Bạn cần nạp thêm {$penaltyRequiredVND}₫ (\${$penaltyRequiredUSD})\n" .
                                                        "- Số dư: {$penaltyBalanceVND}₫ (\${$penaltyBalanceUSD})\n" .
                                                        "- Đơn hàng: {$penaltyFrozenVND}₫ (\${$penaltyFrozenUSD})\n" .
                                                        "- Tiền phạt (30%): {$penaltyAmountVND}₫ (\${$penaltyAmountUSD})\n" .
                                                        "({$penaltyFrozenVND}+{$penaltyAmountVND})-{$penaltyBalanceVND}={$penaltyRequiredVND} (VND)\n" .
                                                        "để xử lý đơn hàng. Hoàn thành đơn hàng sẽ được hệ thống thưởng 10%.";
                                    @endphp
                                    <button type="button" class="btn btn-outline-primary btn-sm text-start" style="font-size: 9px; white-space: normal;" onclick='copyQuickMessage(`{{ str_replace('`', '\`', $quickMessage1) }}`)' title="Click để sao chép">
                                        💰 {{ Str::limit("Cần nạp {$penaltyRequiredVND}₫", 60) }}
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
                            <div class="alert alert-success mb-0 mt-2 py-1 px-2" style="font-size: 11px; border-left: 3px solid #198754;">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <div style="font-size: 10px;">
                                        <strong><i class="fas fa-gift me-1"></i>Đơn hàng đặc biệt ({{ $specialInfo['orders_count'] }} đơn)</strong>
                                    </div>
                                    <button class="btn btn-sm p-0 text-success" type="button" @click="specialOpen = !specialOpen" style="border: none; background: none;">
                                        <i class="fas" :class="specialOpen ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                                    </button>
                                </div>
                                <div x-show="specialOpen" x-transition class="flex-column gap-1" style="display: flex;">
                                    @php
                                        $quickMessageSpecial1 = "Sau khi kiểm tra tài khoản của bạn, xin chúc mừng bạn khi tham gia chương trình sự kiện cặp đôi đã quay trúng đơn thương may mắn của sự kiện. Bạn sẽ được hệ thống thưởng 10% khi hoàn thành phân phối.";
                                        
                                        if ($specialInfo['required_deposit'] > 0) {
                                            $quickMessageSpecial2 = "- Bạn cần nạp thêm " . number_format($specialInfo['required_deposit'] * $usdToVnd, 0, ',', '.') . " VND (số dư: " . number_format($specialInfo['current_balance'] * $usdToVnd, 0, ',', '.') . " - đơn hàng đặc biệt: " . number_format($specialInfo['total_value'] * $usdToVnd, 0, ',', '.') . ") để xử lý đơn hàng. Hoàn thành sẽ được hệ thống thưởng 10%.";
                                        }
                                    @endphp
                                    
                                    <button type="button" class="btn btn-outline-success btn-sm text-start" style="font-size: 9px; white-space: normal;" onclick='copyQuickMessage(`{{ str_replace('`', '\`', $quickMessageSpecial1) }}`)' title="Click để sao chép">
                                        🎉 Chúc mừng trúng đơn may mắn
                                    </button>
                                    
                                    @if($specialInfo['required_deposit'] > 0)
                                        @php
                                            $requiredDepositVND = number_format($specialInfo['required_deposit'] * $usdToVnd, 0, ',', '.');
                                            $requiredDepositUSD = number_format($specialInfo['required_deposit'], 2);
                                            $currentBalanceVND = number_format($specialInfo['current_balance'] * $usdToVnd, 0, ',', '.');
                                            $currentBalanceUSD = number_format($specialInfo['current_balance'], 2);
                                            $totalValueVND = number_format($specialInfo['total_value'] * $usdToVnd, 0, ',', '.');
                                            $totalValueUSD = number_format($specialInfo['total_value'], 2);
                                            
                                            $quickMessageSpecial3 = "- Bạn cần nạp thêm {$requiredDepositVND}₫ (\${$requiredDepositUSD})\n" .
                                                                   "- Số dư: {$currentBalanceVND}₫ (\${$currentBalanceUSD})\n" .
                                                                   "- Đơn hàng: {$totalValueVND}₫ (\${$totalValueUSD})\n" .
                                                                   "{$totalValueVND}-{$currentBalanceVND}={$requiredDepositVND} (VND)\n" .
                                                                   "để xử lý đơn hàng. Hoàn thành đơn hàng sẽ được hệ thống thưởng 10%.";
                                        @endphp
                                        
                                        <button type="button" class="btn btn-outline-primary btn-sm text-start" style="font-size: 9px; white-space: normal;" onclick='copyQuickMessage(`{{ str_replace('`', '\`', $quickMessageSpecial3) }}`)' title="Click để sao chép">
                                            💰 {{ Str::limit("Cần nạp {$requiredDepositVND}₫", 60) }}
                                        </button>
                                        
                                        <button type="button" class="btn btn-outline-primary btn-sm text-start" style="font-size: 9px; white-space: normal;" onclick="copyQuickMessage('{{ addslashes($quickMessageSpecial2) }}')" title="Click để sao chép">
                                            📋 {{ Str::limit($quickMessageSpecial2, 60) }}
                                        </button>
                                    @endif
                                </div>
                            </div>
                        @endif
                        
                        {{-- Tin nhắn chung cho người có đơn đặc biệt --}}
                        <div class="alert alert-info mb-0 mt-2 py-1 px-2" style="font-size: 11px; border-left: 3px solid #0dcaf0;">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <div style="font-size: 10px;">
                                    <strong><i class="fas fa-bolt me-1"></i>Tin nhắn nhanh:</strong>
                                </div>
                                <button class="btn btn-sm p-0 text-info" type="button" @click="quickMsgOpen = !quickMsgOpen" style="border: none; background: none;">
                                    <i class="fas" :class="quickMsgOpen ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                                </button>
                            </div>
                            <div x-show="quickMsgOpen" x-transition class="flex-column gap-1" style="display: flex;">
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
                        <div class="alert alert-secondary mb-0 mt-2 py-1 px-2" style="font-size: 11px; border-left: 3px solid #6c757d;">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <div style="font-size: 10px;">
                                    <strong><i class="fas fa-comments me-1"></i>Tin nhắn nhanh:</strong>
                                </div>
                                <button class="btn btn-sm p-0 text-secondary" type="button" @click="generalMsgOpen = !generalMsgOpen" style="border: none; background: none;">
                                    <i class="fas" :class="generalMsgOpen ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                                </button>
                            </div>
                            <div x-show="generalMsgOpen" x-transition class="flex-column gap-1" style="display: flex;">
                                @php
                                    $quickMessageGeneral1 = "👋 Xin chào, tôi có thể giúp gì cho bạn?";
                                    $quickMessageGeneral2 = "💬 Chào bạn! Nếu bạn có bất kỳ thắc mắc nào, vui lòng cho tôi biết.";
                                    $quickMessageGeneral3 = "🙏 Cảm ơn bạn đã liên hệ. Tôi sẽ hỗ trợ bạn ngay bây giờ.";
                                @endphp
                                
                                <button type="button" class="btn btn-outline-secondary btn-sm text-start" style="font-size: 9px; white-space: normal;" onclick='copyQuickMessage(`{{ $quickMessageGeneral1 }}`)' title="Click để sao chép">
                                    {{ $quickMessageGeneral1 }}
                                </button>
                                
                                <button type="button" class="btn btn-outline-secondary btn-sm text-start" style="font-size: 9px; white-space: normal;" onclick='copyQuickMessage(`{{ $quickMessageGeneral2 }}`)' title="Click để sao chép">
                                    {{ $quickMessageGeneral2 }}
                                </button>
                                
                                <button type="button" class="btn btn-outline-secondary btn-sm text-start" style="font-size: 9px; white-space: normal;" onclick='copyQuickMessage(`{{ $quickMessageGeneral3 }}`)' title="Click để sao chép">
                                    {{ $quickMessageGeneral3 }}
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
                        <li>
                            <a class="dropdown-item text-primary" href="{{ route('user.index') }}#user-{{ $this->selectedConversation->user->id }}">
                                <i class="fas fa-user-cog me-2"></i>Quản lý tài khoản
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item text-warning" href="{{ route('user.frozen.order.interface', $this->selectedConversation->user->id) }}">
                                <i class="fas fa-snowflake me-2"></i>Đóng băng đơn hàng
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
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
                            <a class="dropdown-item text-warning" href="javascript:void(0)" onclick="confirmDeleteMessages()">
                                <i class="fas fa-eraser me-2"></i>Xóa tin nhắn
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item text-danger" href="javascript:void(0)" onclick="confirmDeleteConversation()">
                                <i class="fas fa-trash-alt me-2"></i>Xóa hội thoại
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
        <div wire:key="messages-container-{{ $this->selectedConversation->id }}" class="flex-grow-1 overflow-auto p-3 custom-scrollbar position-relative" id="messages-container" style="background: linear-gradient(135deg,rgb(255, 255, 255) 0%,rgb(255, 255, 255) 100%); display: flex; flex-direction: column;">
            
            <!-- Wrapper để đẩy messages xuống dưới khi ít tin nhắn -->
            
            @if (empty($messages))
            <div class="w-100 h-100 d-flex justify-content-center align-items-center">
                <div class="flex-grow-1 d-flex align-items-center justify-content-center" style="background: linear-gradient(135deg,rgb(255, 255, 255) 0%,rgb(255, 255, 255) 100%);">
                    <div class="text-center">
                        <div class="bg-primary bg-opacity-10 rounded-circle mx-auto mb-4 d-flex align-items-center justify-content-center" style="width: 100px; height: 100px; animation: pulse 2s infinite;">
                            <i class="fas fa-comments fa-3x text-primary"></i>
                        </div>
                        <h4 class="text-dark mb-3 fw-bold">Chưa có tin nhắn nào!</h4>
                        <p class="text-muted mb-0">Nhắn tin ngay bây giờ...</p>
                    </div>
                </div>
            </div>
            @else
            <div style="margin-top: auto; display: flex; flex-direction: column;">
            
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
            $bubbleClass = 'member-message text-dark';
            $tailColor = '#f0f0f0';
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
                    style="max-width: 95%; transition: all 0.2s ease; border: 1px solid {{ $isCurrentUser ? 'transparent' : '#e9ecef' }}; overflow-wrap: break-word; word-break: break-word;">

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
                        background-color: rgba(0, 0, 0, 0.2); color:rgb(255, 255, 255); border: 1px solidrgb(255, 255, 255);
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
                    <div style="white-space: pre-line; word-wrap: break-word; overflow-wrap: break-word; word-break: break-word; margin: 0; max-width: 100%;">{{ trim($message['message']) }}</div>
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

            </div><!-- End wrapper -->
            @endif
            
        </div>

        <!-- Input tin nhắn -->
        <div wire:key="message-input-{{ $this->selectedConversation->id }}" class="bg-white border-top p-3 shadow-sm message-input position-relative" style="transition: all 0.3s ease;">
            
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
                <button type="submit" 
                    class="btn btn-primary rounded-circle ms-2 d-flex align-items-center justify-content-center" 
                    style="width: 45px; height: 45px; flex-shrink: 0; transition: all 0.3s ease;">
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
                    <p class="text-muted mb-4">Hội thoại đã được xóa thành công</p>
                    <button type="button" class="btn btn-success-gradient px-4" data-bs-dismiss="modal">
                        <i class="fas fa-check me-2"></i>Đồng ý
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    let currentChannel = null;
    let isLoadingMessages = false;
    let hasMoreMessages = true;
    let currentPage = 1;

    // Notification Sound Function
    function playNotificationSound() {
        try {
            const audio = new Audio('/audio/notification.mp3');
            audio.volume = 0.5;
            audio.play().catch(error => {
                console.log('Không thể phát âm thanh thông báo:', error);
            });
        } catch (error) {
            console.log('Lỗi khi phát âm thanh:', error);
        }
    }

    // Loading Spinner Functions
    function showChatLoadingSpinner() {
        const spinner = document.getElementById('chat-loading-spinner');
        if (spinner) {
            spinner.style.display = 'flex';
            spinner.style.pointerEvents = 'auto';
            setTimeout(() => {
                spinner.style.opacity = '1';
            }, 10);
        }
    }

    function hideChatLoadingSpinner() {
        const spinner = document.getElementById('chat-loading-spinner');
        if (spinner) {
            spinner.style.opacity = '0';
            spinner.style.pointerEvents = 'none';
            setTimeout(() => {
                spinner.style.display = 'none';
            }, 200);
        }
    }

    document.addEventListener('livewire:initialized', () => {
        // Kiểm tra nếu đã khởi tạo rồi thì bỏ qua
        if (window.chatComponentInitialized) {
            console.log('⚠️ Chat component đã được khởi tạo rồi, bỏ qua');
            return;
        }
        window.chatComponentInitialized = true;
        console.log('✅ Khởi tạo chat component');

        // Hàm scroll to bottom (với force để đảm bảo scroll được thực hiện)
        function scrollToBottom(behavior = 'smooth', force = false) {
            const container = document.getElementById('messages-container');
            if (container) {
                const doScroll = () => {
                    const scrollHeight = container.scrollHeight;
                    const clientHeight = container.clientHeight;
                    const maxScroll = scrollHeight - clientHeight;
                    
                    console.log('📜 Scrolling to bottom:', {
                        scrollHeight: scrollHeight,
                        clientHeight: clientHeight,
                        maxScroll: maxScroll,
                        currentScroll: container.scrollTop,
                        behavior: behavior
                    });
                    
                    container.scrollTo({
                        top: scrollHeight,
                        behavior: behavior
                    });
                };
                
                // Scroll ngay lập tức
                doScroll();
                
                // Scroll lại sau 100ms để đảm bảo DOM đã render xong
                if (force) {
                    setTimeout(doScroll, 100);
                    setTimeout(doScroll, 300);
                }
            } else {
                console.error('❌ Không tìm thấy messages-container');
            }
        }

        // Hide spinner when conversation loaded
        Livewire.on('conversation-selected', () => {
            // Reset pagination
            currentPage = 1;
            hasMoreMessages = true;
            isLoadingMessages = false;
            
            // Đợi messages render xong, scroll xuống bottom, rồi tắt spinner
            setTimeout(() => {
                const container = document.getElementById('messages-container');
                if (container) {
                    // Scroll ngay xuống dưới (instant, không smooth)
                    container.scrollTop = container.scrollHeight;
                }
                
                // Tắt spinner ngay sau khi scroll
                hideChatLoadingSpinner();
            }, 300);
        });

        // Listen to scroll-to-bottom event
        Livewire.on('scroll-to-bottom', () => {
            console.log('🎯 Received scroll-to-bottom event from backend');
            scrollToBottom('smooth', true);
        });

        // Listen to chat notification event (clickable notification)
        Livewire.on('chat-notification', (data) => {
            console.log('🔔 Frontend received chat-notification event:', data);
            
            const eventData = Array.isArray(data) ? data[0] : data;
            const { conversationId, userId, staffId, senderName, message } = eventData;
            
            console.log('📱 Hiển thị toastr cho conversation:', conversationId);
            
            // Phát âm thanh thông báo
            console.log('🔊 Phát âm thanh thông báo');
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
                onclick: function() {
                    // Click để mở conversation với logic expand staff
                    showChatLoadingSpinner();
                    component.call('openConversationFromNotification', conversationId, userId, staffId);
                }
            };
            
            // Sử dụng toastr.success() để có nền màu xanh success
            toastr.success(message, '💬 ' + senderName);
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

        // Load more messages when scrolling to top
        // Single scroll listener - sử dụng event delegation
        document.addEventListener('scroll', (e) => {
            const container = document.getElementById('messages-container');
            if (e.target === container && container) {
                if (container.scrollTop === 0 && !isLoadingMessages && hasMoreMessages) {
                    loadMoreMessages();
                }
            }
        }, true);

        // Listen MessageSent event trên staff channel để update sidebar khi có tin nhắn mới
        @if(auth()->user()->role === 'admin' || auth()->user()->role === 'staff')
            const staffChannel = `staff.{{ auth()->id() }}`;
            const currentUserId = {{ auth()->id() }};
            
            window.Echo.private(staffChannel)
                .listen('.MessageSent', (e) => {
                    console.log('👤 Staff channel - New message:', e.message.id);
                    
                    // Bỏ qua tin nhắn của chính mình
                    if (e.message.sender_id === currentUserId) {
                        return;
                    }
                    
                    const root = document.getElementById('chat-root');
                    const component = Livewire.find(root.getAttribute('wire:id'));
                    
                    // Lấy selectedConversationId từ component
                    const selectedConversationId = component.get('selectedConversationId');
                    
                    console.log('🔍 Staff channel checking:', {
                        messageConversationId: e.message.conversation_id,
                        selectedConversationId: selectedConversationId,
                        isCurrentConversation: selectedConversationId && selectedConversationId == e.message.conversation_id
                    });
                    
                    // Nếu tin nhắn thuộc conversation đang focus
                    // → KHÔNG làm gì cả, để conversation channel xử lý toàn bộ
                    // → (thêm tin nhắn, scroll, đánh dấu đã đọc, update sidebar)
                    if (selectedConversationId && selectedConversationId == e.message.conversation_id) {
                        console.log('⏭️ Conversation đang focus, bỏ qua staff channel - để conversation channel xử lý');
                        // KHÔNG reload sidebar để tránh mất focus
                        // Conversation channel sẽ xử lý tất cả
                        return;
                    }
                    
                    // Nếu tin nhắn KHÔNG thuộc conversation đang focus
                    // → Reload sidebar và gọi messageReceived() để hiển thị notification
                    console.log('📢 Conversation khác, gọi messageReceived()');
                    component.call('loadConversations');
                    @if(auth()->user()->role === 'admin')
                        component.call('loadStaffUsersAlternative');
                    @endif
                    component.call('messageReceived', e.message);
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
            const newChannel = `chat.conversation.${data.conversationId}`;
            
            console.log('🔄 Joining conversation channel:', newChannel);

            // Leave previous channel if exists
            if (currentChannel) {
                console.log('⬅️ Leaving previous channel:', currentChannel);
                window.Echo.leave(currentChannel);
            }

            // Update current channel
            currentChannel = newChannel;

            console.log('✅ Successfully joined conversation channel:', currentChannel);
            
            window.Echo.private(currentChannel)
                .listen('.MessageSent', (e) => {
                    console.log('📨 📨 📨 CONVERSATION CHANNEL - New message:', e.message.id, 'on channel:', currentChannel);

                    // Conversation channel CHỈ xử lý UI cho tin nhắn trong conversation đang mở
                    // KHÔNG gọi messageReceived() để tránh duplicate với staff channel
                    
                    const message = e.message;
                    const currentUserId = {{ auth()->id() }};
                    
                    // Bỏ qua tin nhắn của chính mình (đã được thêm trong sendMessage)
                    if (message.sender_id === currentUserId) {
                        console.log('⏭️ Bỏ qua tin nhắn của chính mình');
                        return;
                    }
                    
                    console.log('🔊 Phát âm thanh thông báo cho admin');
                    // Phát âm thanh thông báo khi nhận tin nhắn
                    playNotificationSound();
                    
                    const root = document.getElementById('chat-root');
                    const component = Livewire.find(root.getAttribute('wire:id'));
                    
                    // Thêm tin nhắn trực tiếp vào messages array trong Livewire
                    let currentMessages = component.get('messages');
                    
                    // Convert Proxy/Object thành array nếu cần
                    if (currentMessages && typeof currentMessages === 'object' && !Array.isArray(currentMessages)) {
                        console.log('🔄 Converting Proxy/Object to Array');
                        // Convert object/proxy thành array bằng Object.values()
                        currentMessages = Object.values(currentMessages);
                    }
                    
                    // Validate currentMessages là array
                    if (!Array.isArray(currentMessages)) {
                        console.error('❌ currentMessages vẫn không phải array sau convert:', currentMessages);
                        currentMessages = [];
                    }
                    
                    console.log('📝 Current messages count:', currentMessages.length);
                    
                    // Kiểm tra xem message đã tồn tại chưa
                    const messageIds = currentMessages.map(m => m.id);
                    if (!messageIds.includes(message.id)) {
                        console.log('✅ Thêm tin nhắn mới (ID:', message.id, ') và scroll xuống');
                        
                        // Tự động đánh dấu đã đọc vì đang mở conversation
                        message.is_read = true;
                        
                        // Thêm message vào array
                        currentMessages.push(message);
                        component.set('messages', currentMessages);
                        
                        console.log('📝 New messages count:', currentMessages.length);
                        
                        // Đợi Livewire render xong DOM trước khi scroll
                        // Sử dụng requestAnimationFrame để đảm bảo DOM đã được update
                        console.log('🎨 Đang chờ Livewire render xong...');
                        
                        // Scroll nhiều lần với delay để đảm bảo DOM render xong
                        requestAnimationFrame(() => {
                            console.log('🎨 Frame 1: Bắt đầu scroll');
                            scrollToBottom('smooth', true);
                        });
                        
                        setTimeout(() => {
                            console.log('🎨 Delay 100ms: Scroll lại');
                            scrollToBottom('smooth', true);
                        }, 100);
                        
                        setTimeout(() => {
                            console.log('🎨 Delay 300ms: Scroll cuối cùng');
                            scrollToBottom('smooth', true);
                        }, 300);
                        
                        // Gọi backend để đánh dấu đã đọc
                        component.call('markSingleMessageAsRead', message.id, message.conversation_id);
                    } else {
                        console.log('⚠️ Tin nhắn đã tồn tại (ID:', message.id, '), bỏ qua');
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

    // Delete Modal Handlers
    let deleteMessagesModal = null;
    let deleteConversationModal = null;
    let deleteSuccessModal = null;

    // Hiển thị modal xóa tin nhắn
    function confirmDeleteMessages() {
        if (!deleteMessagesModal) {
            deleteMessagesModal = new bootstrap.Modal(document.getElementById('deleteMessagesModal'));
        }
        deleteMessagesModal.show();
    }

    // Hiển thị modal xóa hội thoại
    function confirmDeleteConversation() {
        if (!deleteConversationModal) {
            deleteConversationModal = new bootstrap.Modal(document.getElementById('deleteConversationModal'));
        }
        deleteConversationModal.show();
    }

    // Handle confirm delete
    document.addEventListener('DOMContentLoaded', function() {
        const chatRoot = document.getElementById('chat-root');
        
        // Xử lý xóa tin nhắn
        const confirmDeleteMessagesBtn = document.getElementById('confirmDeleteMessagesBtn');
        if (confirmDeleteMessagesBtn) {
            confirmDeleteMessagesBtn.addEventListener('click', function() {
                // Disable button và show loading
                confirmDeleteMessagesBtn.disabled = true;
                confirmDeleteMessagesBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Đang xóa...';
                
                // Get Livewire component
                const component = window.Livewire.find(chatRoot.getAttribute('wire:id'));
                
                // Call Livewire method deleteAllMessages
                component.call('deleteAllMessages').then(() => {
                    // Close delete modal
                    deleteMessagesModal.hide();
                    
                    // Show success modal
                    setTimeout(() => {
                        if (!deleteSuccessModal) {
                            deleteSuccessModal = new bootstrap.Modal(document.getElementById('deleteSuccessModal'));
                        }
                        deleteSuccessModal.show();
                        
                        // Reset button
                        confirmDeleteMessagesBtn.disabled = false;
                        confirmDeleteMessagesBtn.innerHTML = '<i class="fas fa-eraser me-2"></i>Xóa tin nhắn';
                    }, 300);
                }).catch(error => {
                    console.error('Error deleting messages:', error);
                    confirmDeleteMessagesBtn.disabled = false;
                    confirmDeleteMessagesBtn.innerHTML = '<i class="fas fa-eraser me-2"></i>Xóa tin nhắn';
                    deleteMessagesModal.hide();
                    alert('Có lỗi xảy ra khi xóa tin nhắn!');
                });
            });
        }
        
        // Xử lý xóa hội thoại
        const confirmDeleteConversationBtn = document.getElementById('confirmDeleteConversationBtn');
        if (confirmDeleteConversationBtn) {
            confirmDeleteConversationBtn.addEventListener('click', function() {
                // Disable button và show loading
                confirmDeleteConversationBtn.disabled = true;
                confirmDeleteConversationBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Đang xóa...';
                
                // Get Livewire component
                const component = window.Livewire.find(chatRoot.getAttribute('wire:id'));
                
                // Call Livewire method deleteConversation
                component.call('deleteConversation').then(() => {
                    // Close delete modal
                    deleteConversationModal.hide();
                    
                    // Show success modal
                    setTimeout(() => {
                        if (!deleteSuccessModal) {
                            deleteSuccessModal = new bootstrap.Modal(document.getElementById('deleteSuccessModal'));
                        }
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
            });
        }
    });
</script>

@push('scripts')
    @vite('resources/js/admin/chat.js')
    @vite('resources/js/admin/chat/chat-panel.js')
@endpush