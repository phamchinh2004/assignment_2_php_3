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
                        {{ Str::limit($conversation->messages->last()->message?:"Hình ảnh", 30) }}
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