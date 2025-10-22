@php
    use Illuminate\Support\Facades\Storage;
@endphp

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/admin/sidebar-chat.css') }}">
@endpush

<div class="p-3 border-bottom bg-light">
    <h5 class="mb-0 text-dark fw-bold">
        @if(auth()->user()->role === 'admin')
            <i class="fas fa-user-shield me-2 text-primary"></i>Quản lý Chat
        @else
            <i class="fas fa-comments me-2 text-primary"></i>Danh sách Chat
        @endif
    </h5>
</div>
<input type="text" class="form-control mb-3" placeholder="Tìm kiếm người dùng..."
    wire:model.debounce.300ms="searchTerm" />
<div class="overflow-auto custom-scrollbar" style="height: calc(100vh - 105px);">
    @if(auth()->user()->role === 'admin')
        <!-- Giao diện Admin -->
        <div class="pt-3 pe-3 pb-5 ps-3">
            <!-- Conversations của admin -->
            <div class="mb-4">
                <h6 class="text-muted mb-3 fw-semibold">
                    <i class="fas fa-user-circle me-2"></i>Tin nhắn của tôi
                </h6>
                @foreach($conversations->where('staff_id', auth()->id()) as $conversation)
                    @php
                        $hasPenalty = $conversation->user->hasPenalizedOrders();
                        // Tính trực tiếp unread count trong view
                        $unreadCount = App\Models\Message::where('conversation_id', $conversation->id)
                            ->where('sender_id', '!=', auth()->id())
                            ->where('is_read', 0)
                            ->count();
                        $hasUnread = $unreadCount > 0;
                        $bgClass = $this->selectedConversation && $this->selectedConversation->id === $conversation->id 
                            ? 'bg-primary bg-opacity-10 border-start border-primary border-4 shadow-sm' 
                            : ($hasPenalty ? 'bg-warning bg-opacity-10 shadow-sm' : ($hasUnread ? 'bg-info bg-opacity-5 shadow-sm' : 'bg-white shadow-sm'));
                        $borderColor = $hasPenalty ? '#ffc107' : ($hasUnread ? '#0dcaf0' : '#e9ecef');
                    @endphp
                    <div class="conversation-item d-flex align-items-center p-3 rounded-3 mb-2 position-relative cursor-pointer {{ $bgClass }}"
                        style="cursor: pointer; transition: all 0.3s ease; border: 2px solid {{ $borderColor }};"
                        wire:click="selectConversation({{ $conversation->id }})">
                        <div class="avatar rounded-circle d-flex align-items-center justify-content-center text-white fw-bold me-3 position-relative"
                            style="width: 45px; height: 45px; font-size: 16px; overflow: hidden;">
                            @if($conversation->user->avatar && Storage::disk('public')->exists($conversation->user->avatar))
                                <img src="{{ asset('storage/' . $conversation->user->avatar) }}"
                                    alt="{{ $conversation->user->full_name }}"
                                    style="width: 100%; height: 100%; object-fit: cover;">
                            @else
                                <div class="bg-primary w-100 h-100 d-flex align-items-center justify-content-center">
                                    <i class="fas fa-user" style="font-size: 18px;"></i>
                                </div>
                            @endif
                            @if($conversation->messages->where('sender_id', '!=', auth()->id())->where('created_at', '>', now()->subHour())->count() > 0)
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                                    style="font-size: 8px;">
                                    <span class="visually-hidden">unread messages</span>
                                </span>
                            @endif
                        </div>
                        <div class="flex-grow-1 min-width-0">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <div class="{{ $hasUnread ? 'fw-bold' : 'fw-semibold' }} text-dark text-truncate">
                                    @if($hasPenalty)
                                        <i class="fas fa-exclamation-triangle text-warning me-1" title="Đang bị phạt"></i>
                                    @endif
                                    {{ $conversation->user->full_name . " (" . $conversation->user->username . ")"}}
                                </div>
                                @if($hasUnread)
                                    <span class="badge bg-danger rounded-pill ms-2" style="font-size: 10px;">
                                        {{ $unreadCount > 99 ? '99+' : $unreadCount }}
                                    </span>
                                @endif
                            </div>
                            <div class="{{ $hasUnread ? 'fw-bold' : '' }} text-muted small text-truncate d-flex align-items-center mb-1">
                                @if($conversation->messages->last())
                                    <i class="fas fa-comment-dots me-1" style="font-size: 10px;"></i>
                                    {{ Str::limit($conversation->messages->last()->message ?: "Hình ảnh", 30) }}
                                @else
                                    <i class="fas fa-clock me-1" style="font-size: 10px;"></i>
                                    Chưa có tin nhắn
                                @endif
                            </div>
                            @php
                                $isOnline = $conversation->user->last_seen && 
                                            $conversation->user->last_seen->diffInMinutes(now()) <= 5;
                            @endphp
                            <div style="font-size: 10px;">
                                @if($isOnline)
                                    <span class="bg-success rounded-circle me-1" style="width: 6px; height: 6px; display: inline-block;"></span>
                                    <span class="text-success fw-semibold">Đang online</span>
                                @elseif($conversation->user->last_seen)
                                    <i class="fas fa-clock me-1"></i>
                                    {{ $conversation->user->last_seen->diffForHumans() }}
                                @else
                                    <i class="fas fa-circle text-secondary me-1" style="font-size: 6px;"></i>
                                    Offline
                                @endif
                            </div>
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
                            <div class="bg-success rounded-circle d-flex align-items-center justify-content-center text-white fw-bold me-3"
                                style="width: 36px; height: 36px; font-size: 14px;">
                                {{ substr($staff->full_name, 0, 1) }}
                            </div>
                            <div class="flex-grow-1">
                                <div class="fw-semibold text-dark">{{ $staff->full_name }}</div>
                                <div class="text-muted" style="font-size: 12px;">
                                    <i class="fas fa-users me-1"></i>{{ $staff->invitedUsers->count() }} khách hàng
                                </div>
                            </div>
                            <i class="fas fa-chevron-down text-muted transition-transform {{ in_array($staff->id, $expandedStaff) ? 'rotated' : '' }}"
                                style="font-size: 12px; transition: transform 0.3s ease;"></i>
                        </div>

                        <!-- Danh sách người dùng của nhân viên (có thể thu gọn) -->
                        <div class="staff-users-list {{ in_array($staff->id, $expandedStaff) ? 'expanded' : 'collapsed' }}"
                            style="transition: all 0.3s ease;">
                            @if(in_array($staff->id, $expandedStaff))
                                <div class="ms-4 mt-2">
                                    @foreach($staff->invitedUsersOrdered as $user)
                                        @php
                                            // Tính trực tiếp unread count cho staff users
                                            $userUnreadCount = 0;
                                            if ($user->latestConversation) {
                                                $userUnreadCount = App\Models\Message::where('conversation_id', $user->latestConversation->id)
                                                    ->where('sender_id', '!=', auth()->id())
                                                    ->where('is_read', 0)
                                                    ->count();
                                            }
                                            $userHasUnread = $userUnreadCount > 0;
                                        @endphp
                                        <div class="user-item d-flex align-items-center p-2 rounded-3 mb-1 cursor-pointer {{ $userHasUnread ? 'bg-info bg-opacity-5' : '' }}"
                                            style="cursor: pointer; transition: all 0.2s ease; border: 1px solid {{ $userHasUnread ? '#0dcaf0' : 'transparent' }};"
                                            wire:click="selectUserForChat({{ $user->id }}, {{ $staff->id }})">
                                            <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold me-2"
                                                style="width: 28px; height: 28px; font-size: 12px; overflow: hidden;">
                                                @if($user->avatar && Storage::disk('public')->exists($user->avatar))
                                                    <img src="{{ asset('storage/' . $user->avatar) }}" alt="{{ $user->full_name }}"
                                                        style="width: 100%; height: 100%; object-fit: cover;">
                                                @else
                                                    <div class="bg-info w-100 h-100 d-flex align-items-center justify-content-center">
                                                        <i class="fas fa-user" style="font-size: 12px;"></i>
                                                    </div>
                                                @endif
                                            </div>
                                            <div class="flex-grow-1">
                                                <div class="d-flex align-items-center justify-content-between">
                                                    <div class="{{ $userHasUnread ? 'fw-bold' : 'fw-medium' }} text-dark small text-truncate">
                                                        {{ $user->full_name . " (" . $user->username . ")" }}
                                                    </div>
                                                    @if($userHasUnread)
                                                        <span class="badge bg-danger rounded-pill ms-1" style="font-size: 9px;">
                                                            {{ $userUnreadCount > 99 ? '99+' : $userUnreadCount }}
                                                        </span>
                                                    @endif
                                                </div>
                                                <div class="text-muted" style="font-size: 10px;">
                                                    @php
                                                        $isUserOnline = $user->last_seen && 
                                                                        $user->last_seen->diffInMinutes(now()) <= 5;
                                                    @endphp
                                                    @if($isUserOnline)
                                                        <span class="bg-success rounded-circle me-1" style="width: 6px; height: 6px; display: inline-block;"></span>
                                                        <span class="text-success fw-semibold">Online</span>
                                                    @else
                                                        <i class="fas fa-clock me-1"></i>
                                                        @if($user->last_seen)
                                                            {{ $user->last_seen->diffForHumans() }}
                                                        @else
                                                            Offline
                                                        @endif
                                                    @endif
                                                    @if($user->latestConversation && $user->latestConversation->messages && $user->latestConversation->messages->count() > 0)
                                                        - {{ Str::limit($user->latestConversation->messages->last()->message ?: "Hình ảnh", 30) }}
                                                    @else
                                                        - Chưa có tin nhắn
                                                    @endif
                                                </div>
                                            </div>
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
            @foreach($conversations->where('staff_id', auth()->id()) as $conversation)
                @php
                    $hasPenaltyStaff = $conversation->user->hasPenalizedOrders();
                    // Tính trực tiếp unread count trong view
                    $unreadCountStaff = App\Models\Message::where('conversation_id', $conversation->id)
                        ->where('sender_id', '!=', auth()->id())
                        ->where('is_read', 0)
                        ->count();
                    $hasUnreadStaff = $unreadCountStaff > 0;
                    $bgClassStaff = $this->selectedConversation && $this->selectedConversation->id === $conversation->id 
                        ? 'bg-primary bg-opacity-10 border-start border-primary border-4 shadow-sm' 
                        : ($hasPenaltyStaff ? 'bg-warning bg-opacity-10 shadow-sm' : ($hasUnreadStaff ? 'bg-info bg-opacity-5 shadow-sm' : 'bg-white shadow-sm'));
                    $borderColorStaff = $hasPenaltyStaff ? '#ffc107' : ($hasUnreadStaff ? '#0dcaf0' : '#e9ecef');
                @endphp
                <div class="conversation-item d-flex align-items-center p-3 rounded-3 mb-2 position-relative cursor-pointer {{ $bgClassStaff }}"
                    style="cursor: pointer; transition: all 0.3s ease; border: 2px solid {{ $borderColorStaff }};"
                    wire:click="selectConversation({{ $conversation->id }})">
                    <div class="avatar rounded-circle d-flex align-items-center justify-content-center text-white fw-bold me-3 position-relative"
                        style="width: 45px; height: 45px; font-size: 16px; overflow: hidden;">
                        @if($conversation->user->avatar && Storage::disk('public')->exists($conversation->user->avatar))
                            <img src="{{ asset('storage/' . $conversation->user->avatar) }}"
                                alt="{{ $conversation->user->full_name }}" style="width: 100%; height: 100%; object-fit: cover;">
                        @else
                            <div class="bg-primary w-100 h-100 d-flex align-items-center justify-content-center">
                                <i class="fas fa-user" style="font-size: 18px;"></i>
                            </div>
                        @endif
                        @if($conversation->messages->where('sender_id', '!=', auth()->id())->where('created_at', '>', now()->subHour())->count() > 0)
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                                style="font-size: 8px;">
                                <span class="visually-hidden">unread messages</span>
                            </span>
                        @endif
                    </div>
                    <div class="flex-grow-1 min-width-0">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <div class="{{ $hasUnreadStaff ? 'fw-bold' : 'fw-semibold' }} text-dark text-truncate">
                                @if($hasPenaltyStaff)
                                    <i class="fas fa-exclamation-triangle text-warning me-1" title="Đang bị phạt"></i>
                                @endif
                                {{ $conversation->user->full_name . " (" . $conversation->user->username . ")"}}
                            </div>
                            @if($hasUnreadStaff)
                                <span class="badge bg-danger rounded-pill ms-2" style="font-size: 10px;">
                                    {{ $unreadCountStaff > 99 ? '99+' : $unreadCountStaff }}
                                </span>
                            @endif
                        </div>
                        <div class="{{ $hasUnreadStaff ? 'fw-bold' : '' }} text-muted small text-truncate d-flex align-items-center mb-1">
                            @if($conversation->messages->last())
                                <i class="fas fa-comment-dots me-1" style="font-size: 10px;"></i>
                                {{ Str::limit($conversation->messages->last()->message ?: "Hình ảnh", 30) }}
                            @else
                                <i class="fas fa-clock me-1" style="font-size: 10px;"></i>
                                Chưa có tin nhắn
                            @endif
                        </div>
                        @php
                            $isOnlineStaff = $conversation->user->last_seen && 
                                        $conversation->user->last_seen->diffInMinutes(now()) <= 5;
                        @endphp
                        <div style="font-size: 10px;">
                            @if($isOnlineStaff)
                                <span class="bg-success rounded-circle me-1" style="width: 6px; height: 6px; display: inline-block;"></span>
                                <span class="text-success fw-semibold">Đang online</span>
                            @elseif($conversation->user->last_seen)
                                <i class="fas fa-clock me-1"></i>
                                {{ $conversation->user->last_seen->diffForHumans() }}
                            @else
                                <i class="fas fa-circle text-secondary me-1" style="font-size: 6px;"></i>
                                Offline
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>