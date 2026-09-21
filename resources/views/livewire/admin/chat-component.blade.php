@php
    use Illuminate\Support\Facades\Storage;
@endphp

@push('css')
    @vite('resources/css/admin/chat.css')
@endpush

<div class="chat-workspace d-flex flex-column flex-lg-row" id="chat-root">
    <!-- Sidebar trái -->
    <!-- SIDEBAR DẠNG OFFCANVAS (mobile) -->
    <div class="offcanvas offcanvas-start d-lg-none" tabindex="-1" id="mobileSidebar"
        aria-labelledby="mobileSidebarLabel">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title" id="mobileSidebarLabel">Hộp thư hỗ trợ</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Đóng danh sách hội thoại"></button>
        </div>
        <div class="offcanvas-body p-0">
            @include('livewire.admin.sidebar-chat', ['isMobile' => true])
        </div>
    </div>

    <!-- SIDEBAR CỐ ĐỊNH (desktop) -->
    <div class="chat-sidebar-shell d-none d-lg-block">
        @include('livewire.admin.sidebar-chat', ['isMobile' => false])
    </div>

    <!-- Khu vực chat chính -->
    <div class="chat-main flex-grow-1 d-flex flex-column position-relative">
        <!-- Loading Spinner Overlay: Tự động hiện khi chọn cuộc hội thoại -->
        <div id="chat-loading-spinner"
            wire:loading.delay
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

        <div class="chat-mobile-nav d-lg-none">
            <button class="btn chat-mobile-toggle" type="button" data-bs-toggle="offcanvas"
                data-bs-target="#mobileSidebar" aria-controls="mobileSidebar">
                <i class="fas fa-bars me-2" aria-hidden="true"></i> Hộp thư hỗ trợ
            </button>
        </div>
        @if($this->selectedConversation)
            <!-- Header chat -->
            <div wire:key="chat-header-{{ $this->selectedConversation->id }}"
                class="chat-header"
                x-data="{ contextOpen: false, penaltyOpen: true, highValueOrderOpen: true, quickMsgOpen: true, generalMsgOpen: true }">
                <div class="chat-identity-row d-flex align-items-center">
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
                        <span
                            class="position-absolute bottom-0 end-0 {{ $isOnline ? 'bg-success' : 'bg-secondary' }} border border-2 border-white rounded-circle"
                            style="width: 12px; height: 12px;"
                            title="{{ $isOnline ? 'Đang hoạt động' : ($this->selectedConversation->user->last_seen ? 'Hoạt động ' . $this->selectedConversation->user->last_seen->diffForHumans() : 'Chưa từng online') }}"></span>
                    </div>
                    <div class="chat-contact-info flex-grow-1">
                        <div class="chat-contact-heading d-flex flex-wrap align-items-center gap-2">
                            <h2 class="chat-contact-name mb-0">
                                @if($this->selectedConversation->user->hasPenalizedOrders())
                                    <i class="fas fa-exclamation-triangle text-warning me-1" title="Người dùng đang bị phạt"></i>
                                @endif
                                {{ $this->selectedConversation->user->full_name }}
                            </h2>

                            <span class="chat-header-chip">
                                <span class="rounded-circle me-1 {{ $isOnline ? 'bg-success' : 'bg-secondary' }}" style="width: 7px; height: 7px; display: inline-block;"></span>
                                {{ $isOnline ? 'Đang hoạt động' : ($this->selectedConversation->user->last_seen ? 'Hoạt động ' . $this->selectedConversation->user->last_seen->diffForHumans() : 'Chưa từng online') }}
                            </span>

                        </div>
                        <div class="chat-contact-meta d-flex flex-wrap align-items-center gap-2">
                            <span class="chat-contact-username">{{ $this->selectedConversation->user->username }}</span>
                            @if(auth()->user()->role === 'admin' || auth()->user()->role === 'staff')
                                <span class="chat-header-chip chip-staff">
                                    <i class="fas fa-user-shield me-1"></i>QL: {{ $this->selectedConversation->staff->full_name }}
                                </span>
                            @endif

                            <span class="chat-header-chip chip-location">
                                <i class="fas fa-location-dot me-1 text-primary"></i>
                                @if($this->selectedConversation->user->location_country_code)
                                    <span class="me-1">{{ country_flag($this->selectedConversation->user->location_country_code) }}</span>
                                @endif
                                {{ $this->selectedConversation->user->location_city ?: 'Chưa xác định TP' }}
                                @if($this->selectedConversation->user->location_country)
                                    , {{ $this->selectedConversation->user->location_country }}
                                @endif
                                @if($this->selectedConversation->user->location_permission !== 'granted')
                                    <span class="text-warning ms-1" style="font-size: 10px;">(Chưa cấp quyền)</span>
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
                    </div>
                    <div class="dropdown chat-header-actions">
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
                            @if(auth()->user()->role === 'admin')
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
                <div class="chat-context-toolbar">
                    <button type="button" class="chat-context-toggle" @click="contextOpen = !contextOpen"
                        :aria-expanded="contextOpen" aria-controls="chat-context-panel">
                        <i class="fas fa-bolt" aria-hidden="true"></i>
                        <span>Thông tin &amp; trả lời nhanh</span>
                        <i class="fas fa-chevron-down chat-context-chevron" :class="{ 'is-open': contextOpen }" aria-hidden="true"></i>
                    </button>
                    <span class="chat-context-hint d-none d-lg-inline">Chọn mẫu để sao chép nội dung</span>
                </div>
                <div id="chat-context-panel" class="chat-context-panel custom-scrollbar" x-show="contextOpen" x-cloak>
                    @if($this->selectedConversation->user->hasPenalizedOrders())
                            @php
                                $penaltyInfo = $this->selectedConversation->user->penalty_info;
                                $usdToVnd = 26342; // Tỷ giá USD/VND hiện tại
                            @endphp
                            <div class="alert alert-warning mb-0 mt-2 py-1 px-2"
                                style="font-size: 11px; border-left: 3px solid #ffc107;">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <div class="fw-bold" style="font-size: 12px;">
                                        <i class="fas fa-exclamation-triangle text-warning me-1"></i>
                                        Đang bị phạt ({{ $penaltyInfo['frozen_orders_count'] }} đơn)
                                    </div>
                                    <button class="btn btn-sm p-0 text-warning" type="button"
                                        @click="penaltyOpen = !penaltyOpen" :aria-expanded="penaltyOpen" aria-label="Chi tiết đơn hàng bị phạt" style="border: none; background: none;">
                                        <i class="fas" :class="penaltyOpen ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                                    </button>
                                </div>
                                <div x-show="penaltyOpen" x-transition>
                                    <div class="d-flex flex-wrap gap-2">
                                        <div>
                                            <span class="text-muted">💰 Phạt (30%):</span> <strong
                                                class="text-danger">{{ number_format($penaltyInfo['total_penalty'] * $usdToVnd, 0, ',', '.') }}₫</strong>
                                            <span class="text-muted"
                                                style="font-size: 9px;">(~${{ number_format($penaltyInfo['total_penalty'], 2) }})</span>
                                        </div>
                                        <div>
                                            <span class="text-muted">🛒 ĐH:</span>
                                            <strong>{{ number_format($penaltyInfo['total_frozen_value'] * $usdToVnd, 0, ',', '.') }}₫</strong>
                                            <span class="text-muted"
                                                style="font-size: 9px;">(~${{ number_format($penaltyInfo['total_frozen_value'], 2) }})</span>
                                        </div>
                                        <div>
                                            <span class="text-muted">💳 Dư:</span>
                                            <strong>{{ number_format($penaltyInfo['current_balance'] * $usdToVnd, 0, ',', '.') }}₫</strong>
                                            <span class="text-muted"
                                                style="font-size: 9px;">(~${{ number_format($penaltyInfo['current_balance'], 2) }})</span>
                                        </div>
                                        @if($penaltyInfo['required_deposit'] > 0)
                                            <div>
                                                <span class="text-danger">⬆️ Nạp:</span> <strong
                                                    class="text-danger">{{ number_format($penaltyInfo['required_deposit'] * $usdToVnd, 0, ',', '.') }}₫</strong>
                                                <span class="text-danger"
                                                    style="font-size: 9px;">(~${{ number_format($penaltyInfo['required_deposit'], 2) }})</span>
                                            </div>
                                        @else
                                            <div class="text-success fw-bold">
                                                <i class="fas fa-check-circle"></i> Đủ tiền
                                            </div>
                                        @endif
                                    </div>
                                    <div class="text-muted mt-1" style="font-size: 9px;">
                                        <i class="fas fa-info-circle"></i> 1 USD = {{ number_format($usdToVnd, 0, ',', '.') }}₫
                                        | Được thưởng 10% khi hoàn thành
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
                                            <button type="button" class="quick-msg-btn text-start"
                                                onclick='copyQuickMessage(`{{ str_replace('`', '\`', $quickMessage1) }}`)'
                                                title="Click để sao chép">
                                                💰 {{ Str::limit("Cần nạp {$penaltyRequiredVND}₫", 60) }}
                                            </button>
                                        @else
                                            @php
                                                $quickMessage4 = "Số dư của bạn đủ để xử lý đơn hàng bị phạt. Vui lòng hoàn thành các đơn hàng để được hệ thống thưởng 10%.";
                                            @endphp
                                            <button type="button" class="quick-msg-btn text-start"
                                                onclick="copyQuickMessage('{{ addslashes($quickMessage4) }}')"
                                                title="Click để sao chép">
                                                📋 {{ Str::limit($quickMessage4, 60) }}
                                            </button>
                                        @endif

                                        <button type="button" class="quick-msg-btn text-start"
                                            onclick="copyQuickMessage('{{ addslashes($quickMessage2) }}')"
                                            title="Click để sao chép">
                                            📋 {{ Str::limit($quickMessage2, 60) }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- Tin nhắn nhanh cho người có đơn hàng giá trị cao --}}
                        @if($this->selectedConversation->user->hasHighValueOrders())
                            @php
                                $highValueOrderInfo = $this->selectedConversation->user->high_value_orders_info;
                                $usdToVnd = 26342;
                            @endphp

                            @if(!$this->selectedConversation->user->hasPenalizedOrders())
                                {{-- Người có đơn hàng giá trị cao nhưng không bị phạt --}}
                                <div class="alert alert-success mb-0 mt-2 py-1 px-2"
                                    style="font-size: 11px; border-left: 3px solid #198754;">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <div style="font-size: 10px;">
                                            <strong><i class="fas fa-gift me-1"></i>Đơn hàng giá trị cao
                                                ({{ $highValueOrderInfo['orders_count'] }} đơn)</strong>
                                        </div>
                                        <button class="btn btn-sm p-0 text-success" type="button"
                                            @click="highValueOrderOpen = !highValueOrderOpen" :aria-expanded="highValueOrderOpen" aria-label="Chi tiết đơn hàng giá trị cao" style="border: none; background: none;">
                                            <i class="fas" :class="highValueOrderOpen ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                                        </button>
                                    </div>
                                    <div x-show="highValueOrderOpen" x-transition class="flex-column gap-1" style="display: flex;">
                                        @php
                                            $quickMessageHvo1 = "Sau khi kiểm tra tài khoản của bạn, xin chúc mừng bạn khi tham gia chương trình sự kiện đại lễ 30/4 - 1/5 đã quay trúng đơn thương may mắn của sự kiện. Bạn sẽ được hệ thống thưởng 10% khi hoàn thành phân phối.";

                                            if ($highValueOrderInfo['required_deposit'] > 0) {
                                                $quickMessageHvo2 = "- Bạn cần nạp thêm " . number_format($highValueOrderInfo['required_deposit'] * $usdToVnd, 0, ',', '.') . " VND (số dư: " . number_format($highValueOrderInfo['current_balance'] * $usdToVnd, 0, ',', '.') . " - đơn hàng giá trị cao: " . number_format($highValueOrderInfo['total_value'] * $usdToVnd, 0, ',', '.') . ") để xử lý đơn hàng. Hoàn thành sẽ được hệ thống thưởng 10%.";
                                            }
                                        @endphp

                                        <button type="button" class="quick-msg-btn text-start"
                                            onclick='copyQuickMessage(`{{ str_replace('`', '\`', $quickMessageHvo1) }}`)'
                                            title="Click để sao chép">
                                            🎉 Chúc mừng trúng đơn may mắn
                                        </button>

                                        @if($highValueOrderInfo['required_deposit'] > 0)
                                            @php
                                                $requiredDepositVND = number_format($highValueOrderInfo['required_deposit'] * $usdToVnd, 0, ',', '.');
                                                $requiredDepositUSD = number_format($highValueOrderInfo['required_deposit'], 2);
                                                $currentBalanceVND = number_format($highValueOrderInfo['current_balance'] * $usdToVnd, 0, ',', '.');
                                                $currentBalanceUSD = number_format($highValueOrderInfo['current_balance'], 2);
                                                $totalValueVND = number_format($highValueOrderInfo['total_value'] * $usdToVnd, 0, ',', '.');
                                                $totalValueUSD = number_format($highValueOrderInfo['total_value'], 2);

                                                $quickMessageHvo3 = "- Bạn cần nạp thêm {$requiredDepositVND}₫ (\${$requiredDepositUSD})\n" .
                                                    "- Số dư: {$currentBalanceVND}₫ (\${$currentBalanceUSD})\n" .
                                                    "- Đơn hàng: {$totalValueVND}₫ (\${$totalValueUSD})\n" .
                                                    "{$totalValueVND}-{$currentBalanceVND}={$requiredDepositVND} (VND)\n" .
                                                    "để xử lý đơn hàng. Hoàn thành đơn hàng sẽ được hệ thống thưởng 10%.";
                                            @endphp

                                            <button type="button" class="quick-msg-btn text-start"
                                                onclick='copyQuickMessage(`{{ str_replace('`', '\`', $quickMessageHvo3) }}`)'
                                                title="Click để sao chép">
                                                💰 {{ Str::limit("Cần nạp {$requiredDepositVND}₫", 60) }}
                                            </button>

                                            <button type="button" class="quick-msg-btn text-start"
                                                onclick="copyQuickMessage('{{ addslashes($quickMessageHvo2) }}')"
                                                title="Click để sao chép">
                                                📋 {{ Str::limit($quickMessageHvo2, 60) }}
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            @endif

                            {{-- Tin nhắn chung cho người có đơn hàng giá trị cao --}}
                            <div class="alert alert-info mb-0 mt-2 py-1 px-2"
                                style="font-size: 11px; border-left: 3px solid #0dcaf0;">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <div style="font-size: 10px;">
                                        <strong><i class="fas fa-bolt me-1"></i>Tin nhắn nhanh:</strong>
                                    </div>
                                    <button class="btn btn-sm p-0 text-info" type="button" @click="quickMsgOpen = !quickMsgOpen" :aria-expanded="quickMsgOpen" aria-label="Mẫu trả lời nhanh"
                                        style="border: none; background: none;">
                                        <i class="fas" :class="quickMsgOpen ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                                    </button>
                                </div>
                                <div x-show="quickMsgOpen" x-transition class="flex-column gap-1" style="display: flex;">
                                    @php
                                        $quickMessage5 = "VIB : 0987654321" . PHP_EOL . "PHAM VAN A";
                                        $quickMessage6 = "Sau khi giao dịch thành công, bạn vui lòng cung cấp hình ảnh để xác minh. Hiệu lực trong vòng 30 phút tính từ lúc cung cấp tài khoản ngân hàng. Xin Cảm Ơn!";
                                    @endphp

                                    <button type="button" class="quick-msg-btn text-start"
                                        onclick='copyQuickMessage(`{{ str_replace('`', '\`', $quickMessage5) }}`)'
                                        title="Click để sao chép">
                                        🏦 Thông tin tài khoản ngân hàng
                                    </button>

                                    <button type="button" class="quick-msg-btn text-start"
                                        onclick='copyQuickMessage(`{{ str_replace('`', '\`', $quickMessage6) }}`)'
                                        title="Click để sao chép">
                                        ⏱️ Hướng dẫn xác minh giao dịch
                                    </button>
                                </div>
                            </div>
                        @else
                            {{-- Tin nhắn chung cho người không có đơn hàng giá trị cao --}}
                            <div class="alert alert-secondary mb-0 mt-2 py-1 px-2"
                                style="font-size: 11px; border-left: 3px solid #6c757d;">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <div style="font-size: 10px;">
                                        <strong><i class="fas fa-comments me-1"></i>Tin nhắn nhanh:</strong>
                                    </div>
                                    <button class="btn btn-sm p-0 text-secondary" type="button"
                                        @click="generalMsgOpen = !generalMsgOpen" :aria-expanded="generalMsgOpen" aria-label="Mẫu trả lời nhanh" style="border: none; background: none;">
                                        <i class="fas" :class="generalMsgOpen ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                                    </button>
                                </div>
                                <div x-show="generalMsgOpen" x-transition class="flex-column gap-1" style="display: flex;">
                                    @php
                                        $quickMessageGeneral1 = "👋 Xin chào, tôi có thể giúp gì cho bạn?";
                                        $quickMessageGeneral2 = "💬 Chào bạn! Nếu bạn có bất kỳ thắc mắc nào, vui lòng cho tôi biết.";
                                        $quickMessageGeneral3 = "🙏 Cảm ơn bạn đã liên hệ. Tôi sẽ hỗ trợ bạn ngay bây giờ.";
                                    @endphp

                                    <button type="button" class="quick-msg-btn text-start"
                                        onclick='copyQuickMessage(`{{ $quickMessageGeneral1 }}`)' title="Click để sao chép">
                                        {{ $quickMessageGeneral1 }}
                                    </button>

                                    <button type="button" class="quick-msg-btn text-start"
                                        onclick='copyQuickMessage(`{{ $quickMessageGeneral2 }}`)' title="Click để sao chép">
                                        {{ $quickMessageGeneral2 }}
                                    </button>

                                    <button type="button" class="quick-msg-btn text-start"
                                        onclick='copyQuickMessage(`{{ $quickMessageGeneral3 }}`)' title="Click để sao chép">
                                        {{ $quickMessageGeneral3 }}
                                    </button>
                                </div>
                            </div>
                        @endif
                </div>
            </div>

            <!-- Khu vực tin nhắn -->
            <div wire:key="messages-container-{{ $this->selectedConversation->id }}"
                class="chat-message-list flex-grow-1 overflow-auto custom-scrollbar position-relative" id="messages-container"
                aria-label="Tin nhắn trong hội thoại">

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

                            <div class="message-item d-flex {{ $containerClass }}"
                                wire:key="message-{{ $message['id'] ?? $index }}">
                                <div class="position-relative {{ $isReferenceMessage ? 'chat-structured-message admin-chat-structured-message' : ($isImageMessage ? 'admin-chat-image-message ' . ($isCurrentUser ? 'is-sent' : 'is-received') : 'message-bubble ' . $bubbleClass) }}">

                                    <!-- Hiển thị tên người gửi và role (chỉ với tin nhắn của người khác) -->
                                    @if(!$isCurrentUser)
                                        <div class="d-flex align-items-center justify-content-between mb-1">
                                            <small class="fw-bold opacity-90">
                                                {{ $message['sender']['full_name'] ?? 'Unknown User' }}
                                            </small>
                                            <span class="role-badge ms-2">
                                                @if($senderRole === 'admin') Quản trị viên
                                                @elseif($senderRole === 'staff') Nhân viên
                                                @else Khách hàng
                                                @endif
                                            </span>
                                        </div>
                                    @endif

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
                                    @if(($isCurrentUser && ($message['type'] ?? 'text') === 'text') || auth()->user()->role === 'admin')
                                    <div class="message-actions d-flex" aria-label="Thao tác tin nhắn">
                                        @if($isCurrentUser && ($message['type'] ?? 'text') === 'text')
                                            <button type="button" class="btn btn-link btn-sm text-muted" title="Sửa tin nhắn" aria-label="Sửa tin nhắn"
                                                    wire:click="editMessage({{ $message['id'] }})">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        @endif
                                        @if(auth()->user()->role === 'admin')
                                            <button type="button" class="btn btn-link btn-sm text-danger" title="Xóa tin nhắn" aria-label="Xóa tin nhắn"
                                                    onclick="confirmDeleteSingleMessage({{ $message['id'] }})">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        @endif
                                    </div>
                                    @endif

                                    <!-- Thời gian và trạng thái -->
                                    <div class="chat-message-meta d-flex align-items-center justify-content-end gap-2">
                                        <time datetime="{{ \Carbon\Carbon::parse($message['created_at'])->toIso8601String() }}">
                                            {{ \Carbon\Carbon::parse($message['created_at'])->setTimezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i') }}
                                        </time>
                                        @if($isCurrentUser)
                                            <div class="ms-2" data-message-id="{{ $message['id'] }}"
                                                data-seen-status="{{ $message['is_read'] ? 'true' : 'false' }}">
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
                class="message-input position-relative">

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
                <form wire:submit.prevent="{{ $editingMessageId ? 'updateMessage' : 'sendMessage' }}" class="chat-composer d-flex align-items-end gap-2">
                    @if(!$editingMessageId)
                        <input type="file" wire:model="image" accept="image/*" class="visually-hidden" id="upload-image-admin" aria-label="Chọn ảnh để gửi">
                        <label for="upload-image-admin"
                            class="btn chat-attachment-button d-flex align-items-center justify-content-center m-0 position-relative"
                            title="Đính kèm ảnh">
                            <i class="fas fa-image" style="font-size: 16px;" wire:loading.remove wire:target="image"></i>
                            <span class="spinner-border spinner-border-sm text-primary" wire:loading wire:target="image"></span>
                        </label>
                    @endif
                    <div class="flex-grow-1 position-relative">
                        <textarea id="message-input-textarea" wire:model="{{ $editingMessageId ? 'editingMessageText' : 'messageText' }}"
                            placeholder="{{ $editingMessageId ? 'Sửa nội dung tin nhắn...' : 'Viết tin nhắn cho khách hàng...' }}" class="form-control"
                            aria-label="{{ $editingMessageId ? 'Nội dung tin nhắn cần sửa' : 'Nội dung tin nhắn' }}"
                            aria-describedby="chat-composer-hint"
                            rows="1"
                            ></textarea>
                    </div>
                    <button type="submit"
                        class="btn chat-send-button {{ $editingMessageId ? 'btn-success' : 'send-btn-gradient' }} d-flex align-items-center justify-content-center position-relative"
                        title="{{ $editingMessageId ? 'Cập nhật' : 'Gửi tin nhắn' }}"
                        aria-label="{{ $editingMessageId ? 'Cập nhật tin nhắn' : 'Gửi tin nhắn' }}">
                        <i class="fas {{ $editingMessageId ? 'fa-check' : 'fa-paper-plane' }}" style="font-size: 15px;" wire:loading.remove wire:target="{{ $editingMessageId ? 'updateMessage' : 'sendMessage' }}"></i>
                        <span class="spinner-border spinner-border-sm text-white" wire:loading wire:target="{{ $editingMessageId ? 'updateMessage' : 'sendMessage' }}"></span>
                    </button>
                </form>
                <div class="chat-composer-hint" id="chat-composer-hint">
                    <span><i class="far fa-comment-dots me-1" aria-hidden="true"></i>{{ $editingMessageId ? 'Chỉnh sửa nội dung và nhấn nút cập nhật' : 'Enter để gửi · Shift + Enter để xuống dòng' }}</span>
                </div>
                @error('image') <div class="chat-input-error" role="alert">{{ $message }}</div> @enderror
                @error('messageText') <div class="chat-input-error" role="alert">{{ $message }}</div> @enderror
                @error('editingMessageText') <div class="chat-input-error" role="alert">{{ $message }}</div> @enderror
            </div>
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
                    <button type="button" class="btn chat-empty-action d-lg-none" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar" aria-controls="mobileSidebar">
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

    // Loading Spinner is now handled natively by Livewire wire:loading on #chat-loading-spinner

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
                    // Click để mở conversation với logic expand staff - wire:loading will now handle this
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

        // Listen MessageSent event trên staff channel để update sidebar khi có tin nhắn mới
        @if(auth()->user()->role === 'admin' || auth()->user()->role === 'staff')
            const staffChannel = `staff.{{ auth()->id() }}`;
            const currentUserId = {{ auth()->id() }};

            whenEchoReady(() => window.Echo.private(staffChannel)
                .listen('.MessageSent', (e) => {
                    const root = document.getElementById('chat-root');
                    const component = Livewire.find(root.getAttribute('wire:id'));

                    // Lấy selectedConversationId từ component
                    const selectedConversationId = component.get('selectedConversationId');

                    // Nếu tin nhắn thuộc conversation đang focus
                    // → KHÔNG làm gì cả, để conversation channel xử lý toàn bộ
                    // → (thêm tin nhắn, scroll, đánh dấu đã đọc, update sidebar)
                    if (selectedConversationId && selectedConversationId == e.message.conversation_id) {
                        // KHÔNG reload sidebar để tránh mất focus
                        // Conversation channel sẽ xử lý tất cả (kể cả tin nhắn của mình chưa có trong UI)
                        return;
                    }

                    // Tin nhắn KHÔNG thuộc conversation đang focus
                    // Bỏ qua tin nhắn của chính mình (đã được thêm qua sendMessage và broadcast qua conversation channel)
                    if (e.message.sender_id === currentUserId) {
                        // Chỉ reload sidebar, không hiển thị notification
                        component.call('loadConversations');
                        @if(auth()->user()->role === 'admin')
                            component.call('loadStaffUsersAlternative');
                        @endif
                                    return;
                    }

                    // Tin nhắn của người khác và conversation khác
                    // → Reload sidebar và gọi messageReceived() để hiển thị notification
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

                    // Chỉ giải quyết tin nhắn của khách đến (tin nhắn gửi đi đã được hàm sendMessage xử lý thẳng)
                    if (message.sender_id !== currentUserId) {
                        playNotificationSound();

                        const root = document.getElementById('chat-root');
                        const component = Livewire.find(root.getAttribute('wire:id'));

                        // Đẩy cho Backend Livewire xử lý (update Array, mark as read, scroll, update Sidebar)
                        component.call('messageReceived', message);
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

                    // Cập nhật tất cả icon seen cho các tin nhắn của admin (sender_id != user_id trong event)
                    // Hoặc đơn giản là query all messages của mình (bên phải) và mark as seen
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
                .error((error) => {
                    console.error('Echo error:', error);
                }));
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

    document.addEventListener('DOMContentLoaded', function () {
        const boundTextareas = new WeakSet();

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
            return Array.from(document.querySelectorAll('#messages-container .zoomable-image'))
                .map(img => img.currentSrc || img.src)
                .filter(src => src && !seen.has(src) && seen.add(src));
        }

        function setThumbnailState(image, state) {
            const frame = image.closest('.admin-chat-image-frame');
            if (!frame) return;

            frame.classList.remove('is-loaded', 'is-error');
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
        }

        function scheduleConversationLifecycleSync() {
            if (lifecycleSyncFrame !== null) return;

            lifecycleSyncFrame = window.requestAnimationFrame(() => {
                lifecycleSyncFrame = null;
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
    document.addEventListener('DOMContentLoaded', function () {
        const chatRoot = document.getElementById('chat-root');

        // Xử lý xóa tin nhắn
        const confirmDeleteMessagesBtn = document.getElementById('confirmDeleteMessagesBtn');
        if (confirmDeleteMessagesBtn) {
            confirmDeleteMessagesBtn.addEventListener('click', function () {
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
            confirmDeleteConversationBtn.addEventListener('click', function () {
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

@push('scripts')
    @vite('resources/js/admin/chat.js')
    @vite('resources/js/admin/chat/chat-panel.js')
@endpush
