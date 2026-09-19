@php
    use Illuminate\Support\Facades\Storage;

    // Keep distinct keys and element IDs for the desktop and mobile sidebars.
    $keyPrefix = ($isMobile ?? false) ? 'mobile-' : 'desktop-';
    $isAdmin = auth()->user()->role === 'admin';
    $ownConversations = $conversations->where('staff_id', auth()->id());
@endphp

@push('css')
    @vite('resources/css/admin/sidebar-chat.css')
@endpush

<div class="chat-sidebar">
    <div class="chat-sidebar-heading">
        <div class="chat-sidebar-heading-icon" aria-hidden="true"><i class="fas fa-comments"></i></div>
        <div>
            <h2 class="chat-sidebar-title">Hộp thư</h2>
            <p class="chat-sidebar-subtitle">{{ $isAdmin ? 'Quản lý hội thoại khách hàng' : 'Kết nối với khách hàng của bạn' }}</p>
        </div>
    </div>

    <div class="chat-sidebar-search">
        <label class="visually-hidden" for="{{ $keyPrefix }}chat-search">Tìm kiếm người dùng</label>
        <div class="chat-sidebar-search-field">
            <i class="fas fa-search chat-search-icon" aria-hidden="true"></i>
            <input id="{{ $keyPrefix }}chat-search" type="search" class="form-control sidebar-search-input"
                placeholder="Tìm kiếm người dùng..." autocomplete="off" wire:model.debounce.300ms="searchTerm" />
            <span class="chat-search-loading" wire:loading.delay wire:target="searchTerm" role="status">
                <i class="fas fa-circle-notch fa-spin" aria-hidden="true"></i>
                <span class="visually-hidden">Đang tìm kiếm...</span>
            </span>
        </div>
    </div>

    <div class="chat-sidebar-list custom-scrollbar">
        <section class="chat-sidebar-section" aria-labelledby="{{ $keyPrefix }}my-chats-title">
            <h3 class="sidebar-section-title" id="{{ $keyPrefix }}my-chats-title">
                <span>{{ $isAdmin ? 'Tin nhắn của tôi' : 'Khách hàng của tôi' }}</span>
                <span class="sidebar-section-count">{{ $ownConversations->count() }}</span>
            </h3>

            @forelse($ownConversations as $conversation)
                @php
                    $hasPenalty = $conversation->user->hasPenalizedOrders();
                    $unreadCount = App\Models\Message::where('conversation_id', $conversation->id)
                        ->where('sender_id', '!=', auth()->id())
                        ->where('is_read', 0)
                        ->count();
                    $hasUnread = $unreadCount > 0;
                    $isSelected = $this->selectedConversationId !== null && (int) $this->selectedConversationId === (int) $conversation->id;
                    $isOnline = $conversation->user->last_seen && $conversation->user->last_seen->diffInMinutes(now()) <= 5;
                    $lastMessage = $conversation->messages->last();
                @endphp
                <button type="button" wire:key="{{ $keyPrefix }}{{ $isAdmin ? 'admin' : 'staff' }}-conversation-{{ $conversation->id }}"
                    class="conversation-item {{ $isSelected ? 'is-selected active bg-primary' : '' }} {{ $hasUnread ? 'has-unread' : '' }} {{ $hasPenalty ? 'has-penalty' : '' }}"
                    aria-current="{{ $isSelected ? 'true' : 'false' }}" wire:click="selectConversation({{ $conversation->id }})">
                    <span class="conversation-avatar">
                        @if($conversation->user->avatar && Storage::disk('public')->exists($conversation->user->avatar))
                            <img src="{{ asset('storage/' . $conversation->user->avatar) }}" alt="" loading="lazy">
                        @else
                            <i class="fas fa-user" aria-hidden="true"></i>
                        @endif
                        <span class="conversation-presence {{ $isOnline ? 'is-online' : '' }}" aria-hidden="true"></span>
                    </span>
                    <span class="conversation-details">
                        <span class="conversation-topline">
                            <span class="conversation-name" title="{{ $conversation->user->full_name }}">{{ $conversation->user->full_name }}</span>
                            @if($hasPenalty)
                                <span class="conversation-penalty" title="Đang bị phạt" aria-label="Đang bị phạt"><i class="fas fa-exclamation-triangle" aria-hidden="true"></i></span>
                            @endif
                            @if($hasUnread)
                                <span class="conversation-unread" aria-label="{{ $unreadCount }} tin nhắn chưa đọc">{{ $unreadCount > 99 ? '99+' : $unreadCount }}</span>
                            @endif
                        </span>
                        <span class="conversation-username">{{ $conversation->user->username }}</span>
                        <span class="conversation-preview">
                            @if($lastMessage)
                                @if(!trim($lastMessage->message))<i class="far fa-image" aria-hidden="true"></i>@endif
                                {{ trim($lastMessage->message) ?: 'Hình ảnh' }}
                            @else
                                <span class="conversation-no-messages">Chưa có tin nhắn</span>
                            @endif
                        </span>
                        <span class="conversation-status {{ $isOnline ? 'is-online' : '' }}">
                            @if($isOnline)
                                Đang hoạt động
                            @elseif($conversation->user->last_seen)
                                {{ $conversation->user->last_seen->diffForHumans() }}
                            @else
                                Ngoại tuyến
                            @endif
                        </span>
                    </span>
                </button>
            @empty
                <div class="sidebar-empty-state {{ $isAdmin ? 'sidebar-empty-state-compact' : '' }}">
                    <span class="sidebar-empty-icon" aria-hidden="true"><i class="{{ trim($searchTerm ?? '') !== '' ? 'fas fa-search' : 'far fa-comment-dots' }}"></i></span>
                    <p>{{ trim($searchTerm ?? '') !== '' ? 'Không tìm thấy hội thoại' : 'Chưa có hội thoại' }}</p>
                    <span>{{ trim($searchTerm ?? '') !== '' ? 'Thử tìm bằng tên hoặc tài khoản khác.' : 'Tin nhắn khách hàng sẽ xuất hiện tại đây.' }}</span>
                </div>
            @endforelse
        </section>

        @if($isAdmin)
            <section class="chat-sidebar-section" aria-labelledby="{{ $keyPrefix }}team-chats-title">
                <h3 class="sidebar-section-title" id="{{ $keyPrefix }}team-chats-title">
                    <span>Nhân viên &amp; khách hàng</span>
                    <span class="sidebar-section-count">{{ count($staffUsers) }}</span>
                </h3>
                @forelse($staffUsers as $staff)
                    @php $isExpanded = in_array($staff['id'], $expandedStaff); @endphp
                    <div wire:key="{{ $keyPrefix }}staff-section-{{ $staff['id'] }}" class="sidebar-staff-group">
                        <button type="button" class="staff-header {{ $isExpanded ? 'is-expanded' : '' }}"
                            aria-expanded="{{ $isExpanded ? 'true' : 'false' }}" aria-controls="{{ $keyPrefix }}staff-users-{{ $staff['id'] }}"
                            wire:click="toggleStaffExpansion({{ $staff['id'] }})">
                            <span class="staff-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($staff['full_name'], 0, 1)) }}</span>
                            <span class="staff-details">
                                <span class="staff-name" title="{{ $staff['full_name'] }}">{{ $staff['full_name'] }}</span>
                                <span class="staff-customer-count">{{ count($staff['invited_users']) }} khách hàng</span>
                            </span>
                            <i class="fas fa-chevron-down staff-chevron {{ $isExpanded ? 'rotated' : '' }}" aria-hidden="true"></i>
                        </button>

                        <div id="{{ $keyPrefix }}staff-users-{{ $staff['id'] }}" class="staff-users-list {{ $isExpanded ? 'expanded' : 'collapsed' }}">
                            @if($isExpanded)
                                @forelse($staff['invited_users'] as $user)
                                    @php
                                        $userUnreadCount = $user['latest_conversation']['unread_count'] ?? 0;
                                        $userHasUnread = $userUnreadCount > 0;
                                        $userHasPenalty = isset($user['_user_model']) && $user['_user_model']->hasPenalizedOrders();
                                        $isSelected = ($this->selectedConversationId !== null &&
                                            isset($user['latest_conversation']) &&
                                            (int) $this->selectedConversationId === (int) $user['latest_conversation']['id'])
                                            || ($this->selectedConversation && (int) $this->selectedConversation->user_id === (int) $user['id'] && (int) $this->selectedConversation->staff_id === (int) $staff['id']);
                                        $isUserOnline = $user['last_seen'] && $user['last_seen']->diffInMinutes(now()) <= 5;
                                        $lastMsg = isset($user['latest_conversation']) && !empty($user['latest_conversation']['messages'])
                                            ? end($user['latest_conversation']['messages']) : null;
                                    @endphp
                                    <button type="button" wire:key="{{ $keyPrefix }}staff-{{ $staff['id'] }}-user-{{ $user['id'] }}"
                                        class="conversation-item {{ $isSelected ? 'is-selected active bg-primary' : '' }} {{ $userHasUnread ? 'has-unread' : '' }} {{ $userHasPenalty ? 'has-penalty' : '' }}"
                                        aria-current="{{ $isSelected ? 'true' : 'false' }}" wire:click="selectUserForChat({{ $user['id'] }}, {{ $staff['id'] }})">
                                        <span class="conversation-avatar">
                                            @if($user['avatar'] && Storage::disk('public')->exists($user['avatar']))
                                                <img src="{{ asset('storage/' . $user['avatar']) }}" alt="" loading="lazy">
                                            @else
                                                <i class="fas fa-user" aria-hidden="true"></i>
                                            @endif
                                            <span class="conversation-presence {{ $isUserOnline ? 'is-online' : '' }}" aria-hidden="true"></span>
                                        </span>
                                        <span class="conversation-details">
                                            <span class="conversation-topline">
                                                <span class="conversation-name" title="{{ $user['full_name'] }}">{{ $user['full_name'] }}</span>
                                                @if($userHasPenalty)
                                                    <span class="conversation-penalty" title="Đang bị phạt" aria-label="Đang bị phạt"><i class="fas fa-exclamation-triangle" aria-hidden="true"></i></span>
                                                @endif
                                                @if($userHasUnread)
                                                    <span class="conversation-unread" aria-label="{{ $userUnreadCount }} tin nhắn chưa đọc">{{ $userUnreadCount > 99 ? '99+' : $userUnreadCount }}</span>
                                                @endif
                                            </span>
                                            <span class="conversation-username">{{ $user['username'] }}</span>
                                            <span class="conversation-preview">
                                                @if($lastMsg)
                                                    @if(!trim($lastMsg['message']))<i class="far fa-image" aria-hidden="true"></i>@endif
                                                    {{ trim($lastMsg['message']) ?: 'Hình ảnh' }}
                                                @else
                                                    <span class="conversation-no-messages">Chưa có tin nhắn</span>
                                                @endif
                                            </span>
                                            <span class="conversation-status {{ $isUserOnline ? 'is-online' : '' }}">
                                                @if($isUserOnline)
                                                    Đang hoạt động
                                                @elseif($user['last_seen'])
                                                    {{ $user['last_seen']->diffForHumans() }}
                                                @else
                                                    Ngoại tuyến
                                                @endif
                                            </span>
                                        </span>
                                    </button>
                                @empty
                                    <p class="staff-empty-state">Chưa có khách hàng trong danh sách.</p>
                                @endforelse
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="sidebar-empty-state sidebar-empty-state-compact">
                        <p>Chưa có nhân viên trong danh sách</p>
                        @if(trim($searchTerm ?? '') !== '')<span>Thử tìm bằng tên hoặc tài khoản khác.</span>@endif
                    </div>
                @endforelse
            </section>
        @endif
    </div>
</div>
