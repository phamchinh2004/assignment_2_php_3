@php
    use Illuminate\Support\Facades\Storage;

    // Keep distinct keys and element IDs for the desktop and mobile sidebars.
    $keyPrefix = ($isMobile ?? false) ? 'mobile-' : 'desktop-';
    $authorization = app(\App\Services\AuthorizationService::class);
    $visibleTeamRoles = $authorization->visibleTeamChatRoles(auth()->user());
    $canManageTeamChats = !empty($visibleTeamRoles);
    $operatorSections = [];

    if (in_array(\App\Models\User::ROLE_STAFF, $visibleTeamRoles, true)) {
        $operatorSections[] = [
            'key' => 'staff',
            'title' => 'Danh sách nhân viên',
            'empty' => 'Chưa có nhân viên trong danh sách',
            'users' => $staffUsers,
        ];
    }

    if (in_array(\App\Models\User::ROLE_ADMIN, $visibleTeamRoles, true)) {
        $operatorSections[] = [
            'key' => 'admin',
            'title' => 'Danh sách admin',
            'empty' => 'Chưa có admin trong danh sách',
            'users' => $adminUsers,
        ];
    }

    $ownConversations = $conversations->where('staff_id', auth()->id());
@endphp

@push('css')
    @vite('resources/css/admin/sidebar-chat.css')
@endpush

<div class="chat-sidebar" wire:poll.visible.15s="refreshChatState">
    <div class="chat-sidebar-heading">
        <div class="chat-sidebar-heading-icon" aria-hidden="true"><i class="fas fa-comments"></i></div>
        <div>
            <h2 class="chat-sidebar-title">Hộp thư</h2>
            <p class="chat-sidebar-subtitle">{{ $canManageTeamChats ? 'Quản lý hội thoại khách hàng' : 'Kết nối với khách hàng của bạn' }}</p>
        </div>
    </div>

    <div class="chat-sidebar-search">
        <label class="visually-hidden" for="{{ $keyPrefix }}chat-search">Tìm kiếm người dùng</label>
        <div class="chat-sidebar-search-field">
            <i class="fas fa-search chat-search-icon" aria-hidden="true"></i>
            <input id="{{ $keyPrefix }}chat-search" type="search" class="form-control sidebar-search-input"
                placeholder="Tìm tên hoặc tài khoản..." autocomplete="off" x-ref="inboxSearch" wire:model.live.debounce.300ms="searchTerm" />
            <span class="chat-search-loading" wire:loading.delay wire:target="searchTerm" role="status">
                <i class="fas fa-circle-notch fa-spin" aria-hidden="true"></i>
                <span class="visually-hidden">Đang tìm kiếm...</span>
            </span>
        </div>
    </div>

    <div class="chat-sidebar-list custom-scrollbar">
        <section class="chat-sidebar-section" aria-labelledby="{{ $keyPrefix }}my-chats-title">
            <h3 class="sidebar-section-title" id="{{ $keyPrefix }}my-chats-title">
                <span>{{ $canManageTeamChats ? 'Tin nhắn của tôi' : 'Khách hàng của tôi' }}</span>
                <span class="sidebar-section-count">{{ $ownConversations->count() }}</span>
            </h3>

            @forelse($ownConversations as $conversation)
                @php
                    $hasPenalty = $conversation->user->hasPenalizedOrders();
                    $unreadCount = (int) $conversation->unread_count;
                    $hasUnread = $unreadCount > 0;
                    $isSelected = $this->selectedConversationId !== null && (int) $this->selectedConversationId === (int) $conversation->id;
                    $isOnline = $conversation->user->last_seen && $conversation->user->last_seen->diffInMinutes(now()) <= 5;
                    $lastMessage = $conversation->messages->last();
                @endphp
                <div class="conversation-row" wire:key="{{ $keyPrefix }}{{ $canManageTeamChats ? 'manager' : 'staff' }}-conversation-{{ $conversation->id }}">
                <button type="button"
                    class="conversation-item {{ $isSelected ? 'is-selected active bg-primary' : '' }} {{ $hasUnread ? 'has-unread' : '' }} {{ $hasPenalty ? 'has-penalty' : '' }}"
                    aria-current="{{ $isSelected ? 'true' : 'false' }}" x-on:click="mobileListOpen = false; contextOpen = wide"
                    wire:click="selectConversation({{ $conversation->id }})">
                    @include('livewire.admin.partials.conversation-summary', [
                        'contact' => $conversation->user, 'previewMessage' => $lastMessage,
                        'unread' => $unreadCount, 'awaitingReply' => $conversation->awaiting_reply,
                        'penalized' => $hasPenalty,
                    ])
                </button>
                </div>
            @empty
                <div class="sidebar-empty-state {{ $canManageTeamChats ? 'sidebar-empty-state-compact' : '' }}">
                    <span class="sidebar-empty-icon" aria-hidden="true"><i class="{{ trim($searchTerm ?? '') !== '' ? 'fas fa-search' : 'far fa-comment-dots' }}"></i></span>
                    <p>{{ trim($searchTerm ?? '') !== '' ? 'Không tìm thấy hội thoại' : 'Chưa có hội thoại' }}</p>
                    <span>{{ trim($searchTerm ?? '') !== '' ? 'Thử tìm bằng tên hoặc tài khoản khác.' : 'Tin nhắn khách hàng sẽ xuất hiện tại đây.' }}</span>
                </div>
            @endforelse
        </section>

        @foreach($operatorSections as $operatorSection)
            <section class="chat-sidebar-section" aria-labelledby="{{ $keyPrefix }}{{ $operatorSection['key'] }}-chats-title">
                <h3 class="sidebar-section-title" id="{{ $keyPrefix }}{{ $operatorSection['key'] }}-chats-title">
                    <span>{{ $operatorSection['title'] }}</span>
                    <span class="sidebar-section-count">{{ count($operatorSection['users']) }}</span>
                </h3>
                @forelse($operatorSection['users'] as $staff)
                    @php
                        $isExpanded = in_array($staff['id'], $expandedStaff);
                        $staffUnreadCount = (int) ($staff['unread_count'] ?? 0);
                    @endphp
                    <div wire:key="{{ $keyPrefix }}{{ $operatorSection['key'] }}-section-{{ $staff['id'] }}" class="sidebar-staff-group">
                        <button type="button" class="staff-header {{ $isExpanded ? 'is-expanded' : '' }} {{ $staffUnreadCount > 0 ? 'has-unread' : '' }}"
                            aria-expanded="{{ $isExpanded ? 'true' : 'false' }}" aria-controls="{{ $keyPrefix }}{{ $operatorSection['key'] }}-users-{{ $staff['id'] }}"
                            wire:click="toggleStaffExpansion({{ $staff['id'] }})"
                            wire:loading.attr="disabled"
                            wire:target="toggleStaffExpansion({{ $staff['id'] }})">
                            <span class="staff-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($staff['full_name'], 0, 1)) }}</span>
                            <span class="staff-details">
                                <span class="staff-name" title="{{ $staff['full_name'] }}">{{ $staff['full_name'] }}</span>
                                <span class="staff-customer-count">{{ count($staff['invited_users']) }} khách hàng</span>
                            </span>
                            @if($staffUnreadCount > 0)
                                <span class="staff-unread" aria-label="{{ $staffUnreadCount }} tin nhắn mới"
                                    title="{{ $staffUnreadCount }} tin nhắn mới">
                                    {{ $staffUnreadCount > 99 ? '99+' : $staffUnreadCount }}
                                </span>
                            @endif
                            <span class="staff-inline-loading"
                                wire:loading.flex
                                wire:target="toggleStaffExpansion({{ $staff['id'] }})"
                                role="status" aria-label="Đang tải danh sách khách hàng">
                                <i class="fas fa-circle-notch fa-spin" aria-hidden="true"></i>
                                <span class="visually-hidden">Đang tải danh sách khách hàng...</span>
                            </span>
                            <i class="fas fa-chevron-down staff-chevron {{ $isExpanded ? 'rotated' : '' }}"
                                wire:loading.remove
                                wire:target="toggleStaffExpansion({{ $staff['id'] }})"
                                aria-hidden="true"></i>
                        </button>

                        <div id="{{ $keyPrefix }}{{ $operatorSection['key'] }}-users-{{ $staff['id'] }}" class="staff-users-list {{ $isExpanded ? 'expanded' : 'collapsed' }}">
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
                                    <div class="conversation-row" wire:key="{{ $keyPrefix }}{{ $operatorSection['key'] }}-{{ $staff['id'] }}-user-{{ $user['id'] }}">
                                    <button type="button"
                                        class="conversation-item {{ $isSelected ? 'is-selected active bg-primary' : '' }} {{ $userHasUnread ? 'has-unread' : '' }} {{ $userHasPenalty ? 'has-penalty' : '' }}"
                                        aria-current="{{ $isSelected ? 'true' : 'false' }}" x-on:click="mobileListOpen = false; contextOpen = wide"
                                        wire:click="selectUserForChat({{ $user['id'] }}, {{ $staff['id'] }})">
                                        @include('livewire.admin.partials.conversation-summary', [
                                            'contact' => $user, 'previewMessage' => $lastMsg,
                                            'unread' => $userUnreadCount, 'awaitingReply' => $user['latest_conversation']['awaiting_reply'] ?? false,
                                            'penalized' => $userHasPenalty,
                                        ])
                                    </button>
                                    </div>
                                @empty
                                    <p class="staff-empty-state">Chưa có khách hàng trong danh sách.</p>
                                @endforelse
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="sidebar-empty-state sidebar-empty-state-compact">
                        <p>{{ $operatorSection['empty'] }}</p>
                        @if(trim($searchTerm ?? '') !== '')<span>Thử tìm bằng tên hoặc tài khoản khác.</span>@endif
                    </div>
                @endforelse
            </section>
        @endforeach
    </div>
    <div class="chat-sidebar-footer"><i class="fas fa-shield-halved" aria-hidden="true"></i> Hội thoại trong phạm vi quản lý của bạn</div>
</div>
