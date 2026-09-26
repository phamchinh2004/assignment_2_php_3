<?php

namespace App\Livewire\Admin;

use App\Events\MessageSent;
use App\Events\MessageRead;
use App\Events\MessageUpdated;
use App\Events\MessageDeleted;
use App\Events\ConversationCleared;
use App\Events\ConversationAssigned;
use App\Events\UserLocked;
use Livewire\Component;
use App\Models\User;
use App\Models\Conversation;
use App\Models\ConversationNotificationMute;
use App\Models\ChatQuickMessage;
use App\Models\Message;
use App\Services\AuthorizationService;
use App\Services\ChatReadService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\WithFileUploads;


class ChatComponent extends Component
{
    use WithFileUploads;
    public $image; // Hình ảnh được chọn
    public $imagePreviewUrl; // URL preview ảnh
    public $selectedConversationId = null;
    #[Url(as: 'conversation', history: true)]
    public ?string $conversationPublicId = null;
    public $selectedStaffId = null;
    public $messageText = '';
    public $messages = [];
    public $conversations = [];
    public $staffUsers = [];
    public $adminUsers = [];
    public $expandedStaff = [];
    public ?int $conversationMenuId = null;
    public ?int $dispatchConversationId = null;
    public $messagesPerPage = 20; // Tăng số tin nhắn mỗi lần tải
    public $currentPage = 1;
    public $hasMoreMessages = true;
    public $isLoading = false; // Loading state cho load more button
    public $searchTerm = '';
    public $maxMessageLength = 1000;
    public $staffUsersUpdateKey = 0; // Key để force re-render
    public array $quickMessages = [];
    public array $customQuickMessageKeys = [];
    public ?string $editingQuickMessageKey = null;
    public string $editingQuickMessageText = '';
    public bool $addingQuickMessage = false;
    public string $newQuickMessageText = '';

    public $editingMessageId = null;
    public $editingMessageText = '';
    protected $listeners = [
        'message-received' => 'messageReceived'
    ];

    private function canManageAllChats(): bool
    {
        return app(AuthorizationService::class)->can(
            Auth::user(),
            config('authorization.capabilities.chats_view_all')
        );
    }

    private function isOwner(): bool
    {
        return app(AuthorizationService::class)->canDeleteChatMessages(Auth::user());
    }

    private function canViewConversation(Conversation $conversation): bool
    {
        return app(AuthorizationService::class)->canViewConversation(Auth::user(), $conversation);
    }

    private function canDispatchConversation(Conversation $conversation): bool
    {
        return app(AuthorizationService::class)->canDispatchConversation(Auth::user(), $conversation);
    }

    public function toggleConversationMenu(int $conversationId): void
    {
        $conversation = Conversation::with('staff:id,role')->find($conversationId);
        abort_unless($conversation && $this->canDispatchConversation($conversation), 403);

        $this->conversationMenuId = $this->conversationMenuId === $conversationId
            ? null
            : $conversationId;
    }

    public function openDispatchDialog(int $conversationId): void
    {
        $conversation = Conversation::with('staff:id,role')->find($conversationId);
        abort_unless($conversation && $this->canDispatchConversation($conversation), 403);

        $this->conversationMenuId = null;
        $this->dispatchConversationId = $conversationId;
    }

    public function closeDispatchDialog(): void
    {
        $this->dispatchConversationId = null;
    }

    public function getDispatchCandidatesProperty()
    {
        if (!$this->dispatchConversationId || !in_array(Auth::user()->role, [User::ROLE_ADMIN, User::ROLE_OWNER], true)) {
            return collect();
        }

        $roles = Auth::user()->role === User::ROLE_OWNER
            ? [User::ROLE_STAFF, User::ROLE_ADMIN]
            : [User::ROLE_STAFF];

        return User::query()
            ->whereIn('role', $roles)
            ->where('status', 'activated')
            ->orderBy('full_name')
            ->get(['id', 'full_name', 'role', 'status']);
    }

    public function dispatchConversationTo(int $operatorId): void
    {
        $conversation = Conversation::with('staff:id,role')->find($this->dispatchConversationId);
        abort_unless($conversation && $this->canDispatchConversation($conversation), 403);

        $target = $this->dispatchCandidates->firstWhere('id', $operatorId);
        abort_unless($target && app(AuthorizationService::class)->canReceiveDispatchedConversation(Auth::user(), $target), 403);

        if ((int) $conversation->staff_id === $operatorId) {
            $this->closeDispatchDialog();
            return;
        }

        if (Conversation::query()
            ->where('user_id', $conversation->user_id)
            ->where('staff_id', $operatorId)
            ->whereKeyNot($conversation->id)
            ->exists()) {
            $this->dispatch('app-dialog', [
                'type' => 'warning',
                'title' => 'Không thể điều phối',
                'text' => 'Người nhận đã có hội thoại riêng với khách hàng này.',
            ]);
            return;
        }

        $previousOperatorId = (int) $conversation->staff_id;
        $updated = Conversation::query()
            ->whereKey($conversation->id)
            ->where('staff_id', $previousOperatorId)
            ->update(['staff_id' => $operatorId]);

        if (!$updated) {
            $this->closeDispatchDialog();
            $this->refreshChatState();
            $this->dispatch('app-dialog', [
                'type' => 'warning',
                'title' => 'Hội thoại đã thay đổi',
                'text' => 'Vui lòng tải lại và thử điều phối lần nữa.',
            ]);
            return;
        }

        $this->closeDispatchDialog();
        if (!in_array($operatorId, $this->expandedStaff, true)) {
            $this->expandedStaff[] = $operatorId;
        }

        $this->refreshChatState();
        event(new ConversationAssigned($conversation->id, $previousOperatorId, $operatorId));
        $this->dispatch('app-dialog', [
            'type' => 'success',
            'title' => 'Điều phối thành công',
            'text' => 'Đã chuyển hội thoại cho ' . $target->full_name . '.',
        ]);
    }

    public function getSelectedConversationNotificationMuteProperty(): ?ConversationNotificationMute
    {
        if (!$this->selectedConversationId || !Auth::id()) {
            return null;
        }

        return ConversationNotificationMute::query()
            ->where('user_id', Auth::id())
            ->where('conversation_id', $this->selectedConversationId)
            ->active()
            ->first();
    }

    public function muteSelectedConversation(string $duration): void
    {
        $this->muteConversation((int) $this->selectedConversationId, $duration);
    }

    public function muteConversationFromNotification(int $conversationId, string $duration): void
    {
        $this->muteConversation($conversationId, $duration);
    }

    private function muteConversation(int $conversationId, string $duration): void
    {
        $conversation = Conversation::with('staff:id,role')->find($conversationId);
        abort_unless($conversation && $this->canViewConversation($conversation), 403);

        $mutedUntil = match ($duration) {
            '15m' => now()->addMinutes(15),
            '1h' => now()->addHour(),
            '8h' => now()->addHours(8),
            'forever' => null,
            default => null,
        };

        abort_unless(in_array($duration, ['15m', '1h', '8h', 'forever'], true), 422);

        ConversationNotificationMute::updateOrCreate(
            [
                'user_id' => Auth::id(),
                'conversation_id' => $conversation->id,
            ],
            ['muted_until' => $mutedUntil]
        );

        $this->dispatch('conversation-notification-mute-updated', [
            'conversationId' => $conversation->id,
            'muted' => true,
            'mutedUntil' => $mutedUntil?->toIso8601String(),
        ]);
    }

    public function unmuteSelectedConversation(): void
    {
        $conversation = Conversation::with('staff:id,role')->find($this->selectedConversationId);
        abort_unless($conversation && $this->canViewConversation($conversation), 403);

        ConversationNotificationMute::query()
            ->where('user_id', Auth::id())
            ->where('conversation_id', $conversation->id)
            ->delete();

        $this->dispatch('conversation-notification-mute-updated', [
            'conversationId' => $conversation->id,
            'muted' => false,
            'mutedUntil' => null,
        ]);
    }

    public function mount()
    {
        $this->loadQuickMessages();
        $this->loadConversations();
        if ($this->canManageAllChats()) {
            $this->loadStaffUsersAlternative();
        }

        if ($this->conversationPublicId) {
            $this->openConversationFromPublicId($this->conversationPublicId);
        }
    }

    private function quickMessageDefaults(): array
    {
        return collect(config('chat.admin_quick_messages', []))
            ->mapWithKeys(fn ($message, $key) => [$key => (string) ($message['content'] ?? '')])
            ->all();
    }

    private function ensureQuickMessageAccess(): void
    {
        abort_unless(
            Auth::user() && in_array(Auth::user()->role, User::MANAGEMENT_ROLES, true),
            403
        );
    }

    private function loadQuickMessages(): void
    {
        $this->ensureQuickMessageAccess();

        $defaults = $this->quickMessageDefaults();
        $records = ChatQuickMessage::query()
            ->where('user_id', Auth::id())
            ->orderBy('id')
            ->get(['message_key', 'content', 'is_deleted']);

        $messages = $defaults;
        $customKeys = [];

        foreach ($records as $record) {
            if ($record->is_deleted) {
                unset($messages[$record->message_key]);
                continue;
            }

            $messages[$record->message_key] = $record->content;
            if (!array_key_exists($record->message_key, $defaults)) {
                $customKeys[] = $record->message_key;
            }
        }

        $this->quickMessages = $messages;
        $this->customQuickMessageKeys = array_values(array_unique($customKeys));
    }

    private function quickMessageKeyBelongsToCurrentUser(string $key): bool
    {
        if (array_key_exists($key, $this->quickMessageDefaults())) {
            return true;
        }

        return ChatQuickMessage::query()
            ->where('user_id', Auth::id())
            ->where('message_key', $key)
            ->where('is_deleted', false)
            ->exists();
    }

    public function startEditingQuickMessage(string $key): void
    {
        $this->ensureQuickMessageAccess();
        abort_unless($this->quickMessageKeyBelongsToCurrentUser($key), 404);
        abort_unless(array_key_exists($key, $this->quickMessages), 404);

        $this->resetErrorBag('editingQuickMessageText');
        $this->addingQuickMessage = false;
        $this->newQuickMessageText = '';
        $this->editingQuickMessageKey = $key;
        $this->editingQuickMessageText = $this->quickMessages[$key];
    }

    public function saveQuickMessage(?string $content = null): void
    {
        $this->ensureQuickMessageAccess();
        abort_unless(
            $this->editingQuickMessageKey
                && $this->quickMessageKeyBelongsToCurrentUser($this->editingQuickMessageKey),
            404
        );

        if ($content !== null) {
            $this->editingQuickMessageText = $content;
        }

        $this->validate([
            'editingQuickMessageText' => ['required', 'string', 'max:2000'],
        ], [
            'editingQuickMessageText.required' => 'Tin nhắn nhanh không được để trống.',
            'editingQuickMessageText.max' => 'Tin nhắn nhanh không được vượt quá 2000 ký tự.',
        ]);

        $messageKey = $this->editingQuickMessageKey;
        $messageContent = trim($this->editingQuickMessageText);

        ChatQuickMessage::updateOrCreate(
            [
                'user_id' => Auth::id(),
                'message_key' => $messageKey,
            ],
            [
                'content' => $messageContent,
                'is_deleted' => false,
            ]
        );

        $this->quickMessages[$messageKey] = $messageContent;
        $this->editingQuickMessageKey = null;
        $this->editingQuickMessageText = '';
        $this->resetErrorBag('editingQuickMessageText');
    }

    public function startAddingQuickMessage(): void
    {
        $this->ensureQuickMessageAccess();
        $this->editingQuickMessageKey = null;
        $this->editingQuickMessageText = '';
        $this->resetErrorBag(['editingQuickMessageText', 'newQuickMessageText']);
        $this->addingQuickMessage = true;
        $this->newQuickMessageText = '';
    }

    public function saveNewQuickMessage(?string $content = null): void
    {
        $this->ensureQuickMessageAccess();

        if ($content !== null) {
            $this->newQuickMessageText = $content;
        }

        $this->validate([
            'newQuickMessageText' => ['required', 'string', 'max:2000'],
        ], [
            'newQuickMessageText.required' => 'Tin nhắn nhanh không được để trống.',
            'newQuickMessageText.max' => 'Tin nhắn nhanh không được vượt quá 2000 ký tự.',
        ]);

        $message = ChatQuickMessage::create([
            'user_id' => Auth::id(),
            'message_key' => 'custom_' . Str::uuid(),
            'content' => trim($this->newQuickMessageText),
            'is_deleted' => false,
        ]);

        $this->quickMessages[$message->message_key] = $message->content;
        $this->customQuickMessageKeys[] = $message->message_key;
        $this->addingQuickMessage = false;
        $this->newQuickMessageText = '';
        $this->resetErrorBag('newQuickMessageText');
    }

    public function cancelQuickMessageAdd(): void
    {
        $this->addingQuickMessage = false;
        $this->newQuickMessageText = '';
        $this->resetErrorBag('newQuickMessageText');
    }

    public function deleteQuickMessage(string $key): void
    {
        $this->ensureQuickMessageAccess();
        abort_unless($this->quickMessageKeyBelongsToCurrentUser($key), 404);

        $defaults = $this->quickMessageDefaults();
        if (array_key_exists($key, $defaults)) {
            ChatQuickMessage::updateOrCreate(
                [
                    'user_id' => Auth::id(),
                    'message_key' => $key,
                ],
                [
                    'content' => $this->quickMessages[$key] ?? $defaults[$key],
                    'is_deleted' => true,
                ]
            );
        } else {
            ChatQuickMessage::query()
                ->where('user_id', Auth::id())
                ->where('message_key', $key)
                ->delete();
        }

        if ($this->editingQuickMessageKey === $key) {
            $this->cancelQuickMessageEdit();
        }

        $this->loadQuickMessages();
    }

    public function cancelQuickMessageEdit(): void
    {
        $this->editingQuickMessageKey = null;
        $this->editingQuickMessageText = '';
        $this->resetErrorBag('editingQuickMessageText');
    }

    public function updatedConversationPublicId(?string $publicId): void
    {
        if (!$publicId) {
            $this->clearConversationSelection();
            return;
        }

        $this->openConversationFromPublicId($publicId);
    }

    private function openConversationFromPublicId(string $publicId): void
    {
        abort_unless(Str::isUuid($publicId), 404);

        $conversation = Conversation::with('staff:id,role')
            ->where('public_id', $publicId)
            ->first();

        abort_unless($conversation && $this->canViewConversation($conversation), 404);

        if ((int) $this->selectedConversationId === (int) $conversation->id) {
            return;
        }

        $this->openConversationFromNotification($conversation->id, null, null);
    }

    private function clearConversationSelection(): void
    {
        $this->dispatch('leave-conversation-channel');
        $this->selectedConversationId = null;
        $this->messages = [];
        $this->messageText = '';
        $this->currentPage = 1;
        $this->hasMoreMessages = true;
        $this->isLoading = false;
        $this->editingMessageId = null;
        $this->editingMessageText = '';
    }
    public function refreshChatState()
    {
        $this->loadConversations();

        if ($this->canManageAllChats()) {
            $this->loadStaffUsersAlternative();
        }

        if ($this->selectedConversationId) {
            $selected = Conversation::with('staff:id,role')->find($this->selectedConversationId);
            if (!$selected || !$this->canViewConversation($selected)) {
                $this->clearConversationSelection();
                $this->conversationPublicId = null;
            }
        }
    }
    #[Computed]
    public function selectedConversation()
    {
        if (!$this->selectedConversationId) {
            return null;
        }

        // Livewire can hydrate a conversation from an earlier request. Recheck the
        // current assignment before exposing messages or accepting a reply.
        $current = Conversation::with('staff:id,role')->find($this->selectedConversationId);
        if (!$current || !$this->canViewConversation($current)) {
            return null;
        }

        $cached = $this->conversations->firstWhere('id', $this->selectedConversationId);
        if ($cached && (int) $cached->staff_id === (int) $current->staff_id) {
            return $cached;
        }

        return $current->load(['user', 'staff', 'messages']);
    }

    public function loadConversations()
    {
        $user = Auth::user();

        // Main query
        $query = Conversation::query()
            ->with([
                'user',
                'staff:id,full_name,username,role',
                'messages' => function ($query) {
                    $query->select('id', 'conversation_id', 'sender_id', 'message', 'type', 'kind', 'created_at')->latest()->limit(1);
                }
            ])
            ->withCount([
                'messages as unread_count' => function ($query) use ($user) {
                    $query->unreadFor($user->id);
                }
            ])
            ->orderByDesc('updated_at');

        $authorization = app(AuthorizationService::class);

        if ($authorization->isSuperuser($user)) {
            $query->where(function ($conversationQuery) use ($user) {
                $conversationQuery->where('staff_id', $user->id)
                    ->orWhereHas('staff', function ($staffQuery) {
                        $staffQuery->whereIn('role', [User::ROLE_STAFF, User::ROLE_ADMIN]);
                    });
            });
        } elseif ($user->role === User::ROLE_ADMIN && $this->canManageAllChats()) {
            $query->where(function ($conversationQuery) use ($user) {
                $conversationQuery->where('staff_id', $user->id)
                    ->orWhereHas('staff', function ($staffQuery) {
                        $staffQuery->where('role', User::ROLE_STAFF);
                    });
            });
        } else {
            $query->where('staff_id', $user->id);
        }

        if ($this->searchTerm) {
            $query->whereHas('user', function ($q) {
                $q->where('full_name', 'like', '%' . $this->searchTerm . '%')
                    ->orWhere('username', 'like', '%' . $this->searchTerm . '%');
            });
        }

        $this->conversations = $query->get();
    }
    public function updatedSearchTerm()
    {
        $this->loadConversations();
        if ($this->canManageAllChats()) {
            $this->loadStaffUsersAlternative();
        }
    }

    // Alternative method nếu bạn muốn dùng updated_at của conversation

    public function loadStaffUsersAlternative()
    {
        $authorization = app(AuthorizationService::class);
        $visibleRoles = $authorization->visibleTeamChatRoles(Auth::user());

        if (empty($visibleRoles)) {
            $this->staffUsers = [];
            $this->adminUsers = [];
            return;
        }

        $currentUserId = Auth::id();

        $staffData = User::whereIn('role', $visibleRoles)
            ->select('id', 'full_name', 'role') // Optimize fields
            ->orderBy('id', 'asc')
            ->get();

        // Tạo array mới hoàn toàn
        $staffArray = [];
        $adminArray = [];

        // Lấy tất cả thông tin các staff member bằng IN clause trước để giảm query N+1
        $staffIds = $staffData->pluck('id');
        $allMembers = User::where('role', User::ROLE_MEMBER)
            ->where(function ($query) use ($staffIds) {
                $query->whereIn('referrer_id', $staffIds)
                    ->orWhereHas('memberConversations', function ($conversationQuery) use ($staffIds) {
                        $conversationQuery->whereIn('staff_id', $staffIds);
                    });
            })
            ->withCount('memberConversations')
            // Lấy kèm hộp thoại và message mới nhất
            ->with([
                'memberConversations' => function ($q) use ($currentUserId, $staffIds) {
                    $q->whereIn('staff_id', $staffIds)
                        ->orderBy('updated_at', 'desc')
                        ->with([
                            'messages' => function ($qm) {
                                $qm->select('id', 'conversation_id', 'sender_id', 'message', 'type', 'kind', 'created_at', 'is_read')
                                    ->orderBy('created_at', 'desc')
                                    ->limit(1);
                            }
                        ])
                        ->withCount([
                            'messages as unread_count' => function ($qu) use ($currentUserId) {
                                $qu->unreadFor($currentUserId);
                            }
                        ]);
                }
            ])
            ->get();

        foreach ($staffData as $staff) {
            $users = $allMembers->filter(function ($member) use ($staff) {
                $hasAssignedConversation = $member->memberConversations->contains('staff_id', $staff->id);

                return $hasAssignedConversation || (
                    (int) $member->referrer_id === (int) $staff->id
                    && (int) $member->member_conversations_count === 0
                );
            });
            $usersArray = [];

            foreach ($users as $user) {
                $latestConv = $user->memberConversations->firstWhere('staff_id', $staff->id);

                $userData = [
                    'id' => $user->id,
                    'full_name' => $user->full_name,
                    'username' => $user->username,
                    'avatar' => $user->avatar,
                    'last_seen' => $user->last_seen,
                    'referrer_id' => $user->referrer_id,
                    'latest_conversation' => null,
                    'conv_updated_at_timestamp' => 0,
                    '_user_model' => $user, // Lưu model để gọi methods
                ];

                if ($latestConv) {
                    $userData['latest_conversation'] = [
                        'id' => $latestConv->id,
                        'updated_at' => $latestConv->updated_at,
                        'unread_count' => $latestConv->unread_count,
                        'messages' => $latestConv->messages->toArray(),
                    ];
                    $userData['conv_updated_at_timestamp'] = $latestConv->updated_at->timestamp;
                }

                $usersArray[] = $userData;
            }

            // Sắp xếp users theo timestamp
            usort($usersArray, function ($a, $b) {
                return $b['conv_updated_at_timestamp'] - $a['conv_updated_at_timestamp'];
            });

            $operatorData = [
                'id' => $staff->id,
                'full_name' => $staff->full_name,
                'invited_users' => $usersArray,
                'unread_count' => array_sum(array_map(
                    fn ($user) => (int) ($user['latest_conversation']['unread_count'] ?? 0),
                    $usersArray
                )),
            ];

            if ($staff->role === User::ROLE_ADMIN) {
                $adminArray[] = $operatorData;
            } else {
                $staffArray[] = $operatorData;
            }
        }

        $this->staffUsers = $staffArray;
        $this->adminUsers = $adminArray;
        $this->staffUsersUpdateKey++;
    }




    public function selectConversation($conversationId)
    {
        $conversation = Conversation::with('staff:id,role')->find($conversationId);
        abort_unless($conversation && $this->canViewConversation($conversation), 403);

        $this->conversationPublicId = $conversation->public_id;

        logger('🎯 selectConversation called', [
            'conversationId' => $conversationId,
            'previousConversationId' => $this->selectedConversationId
        ]);

        // Chọn lại cùng conversation vẫn phải reload dữ liệu tin nhắn.
        if ($this->selectedConversationId == $conversationId) {
            logger('🔄 Conversation already selected, reloading messages');
            $this->messages = [];
            $this->currentPage = 1;
            $this->hasMoreMessages = true;
            $this->isLoading = false;
            $this->loadMessages();
            $this->markMessagesAsRead($conversationId);
            $this->updateConversationUnreadCount($conversationId);
            $this->dispatch('scroll-to-bottom');
            return;
        }

        // Reset state trước khi chọn conversation mới
        $this->messages = [];
        $this->selectedConversationId = $conversationId;
        $this->currentPage = 1;
        $this->hasMoreMessages = true;
        $this->isLoading = false;

        $this->loadMessages();
        logger('📚 Loaded messages', ['count' => count($this->messages)]);

        $this->markMessagesAsRead($conversationId);

        // Chỉ cập nhật unread count cho conversation này, không reload toàn bộ sidebar
        // Sidebar sẽ tự cập nhật qua WebSocket events khi có tin nhắn mới
        $this->updateConversationUnreadCount($conversationId);

        logger('📡 Dispatching join-conversation-channel', ['conversationId' => $conversationId]);
        $this->dispatch('join-conversation-channel', conversationId: $conversationId);
        $this->dispatch('conversation-selected');
    }

    /**
     * Cập nhật unread count cho một conversation cụ thể
     */
    private function updateConversationUnreadCount($conversationId)
    {
        // Tìm conversation trong danh sách hiện tại và set unread_count = 0
        foreach ($this->conversations as $conv) {
            if ($conv->id == $conversationId) {
                $conv->unread_count = 0;
                break;
            }
        }

        if ($this->canManageAllChats()) {
            $this->staffUsers = $this->clearOperatorUnreadCount($this->staffUsers, $conversationId);
            $this->adminUsers = $this->clearOperatorUnreadCount($this->adminUsers, $conversationId);
        }
    }

    private function clearOperatorUnreadCount(array $operators, $conversationId): array
    {
        foreach ($operators as &$operator) {
            if (!isset($operator['invited_users']) || !is_array($operator['invited_users'])) {
                continue;
            }

            foreach ($operator['invited_users'] as &$user) {
                if (
                    isset($user['latest_conversation'])
                    && $user['latest_conversation']['id'] == $conversationId
                ) {
                    $user['latest_conversation']['unread_count'] = 0;
                    break;
                }
            }
            unset($user);

            $operator['unread_count'] = array_sum(array_map(
                fn ($user) => (int) ($user['latest_conversation']['unread_count'] ?? 0),
                $operator['invited_users']
            ));
        }
        unset($operator);

        return $operators;
    }

    /**
     * Đánh dấu tất cả tin nhắn chưa đọc trong conversation là đã đọc
     */
    private function markMessagesAsRead($conversationId)
    {
        $conversation = Conversation::with('staff:id,role')->find($conversationId);
        if (!$conversation || !$this->canViewConversation($conversation)) {
            return;
        }

        $updatedCount = app(ChatReadService::class)->markConversationRead($conversation, Auth::id());
        $this->refreshReadReceipts();

        if ($updatedCount > 0) {
            // Gửi 1 broadcast duy nhất cho toàn bộ cuộc hội thoại
            broadcast(new \App\Events\ConversationRead($conversationId, Auth::id()));
        }
    }

    #[On('delete-all-messages')]
    public function deleteAllMessages()
    {
        if (!$this->isOwner()) {
            return;
        }

        // $this->dispatch('app-dialog', [
        //     'type' => 'error',
        //     'title' => 'Lỗi',
        //     'text' => 'Không thể xóa tin nhắn. Vui lòng thử lại.'
        // ]);
        if (!$this->selectedConversation) {
            $this->dispatch('app-dialog', [
                'type' => 'error',
                'title' => 'Không tìm thấy đoạn chat',
                'text' => 'Vui lòng chọn một cuộc trò chuyện trước.'
            ]);
            return;
        }

        try {
            $conversationId = $this->selectedConversation->id;
            $this->selectedConversation->messages()->delete();
            broadcast(new ConversationCleared($conversationId));
            $this->messages = [];

            $this->loadConversations();
            if ($this->canManageAllChats()) {
                $this->loadStaffUsersAlternative();
            }
            $this->dispatch('scroll-to-bottom');

            $this->dispatch('app-dialog', [
                'type' => 'success',
                'title' => 'Xóa thành công',
                'text' => 'Tất cả tin nhắn đã được xóa.'
            ]);
        } catch (\Throwable $e) {
            logger('Xóa tin nhắn lỗi:', ['err' => $e->getMessage()]);
            $this->dispatch('app-dialog', [
                'type' => 'error',
                'title' => 'Lỗi',
                'text' => 'Không thể xóa tin nhắn. Vui lòng thử lại.'
            ]);
        }
    }

    public function deleteConversation()
    {
        if (!$this->isOwner()) {
            return false;
        }

        if (!$this->selectedConversation) {
            return false;
        }

        try {
            // Lưu conversation để xóa
            $conversationToDelete = $this->selectedConversation;
            $conversationId = $conversationToDelete->id;

            // Reset state trước khi xóa
            $this->dispatch('leave-conversation-channel');
            $this->messages = [];
            $this->selectedConversationId = null;
            $this->conversationPublicId = null;
            $this->messageText = '';

            // Xóa tất cả messages trong conversation
            $conversationToDelete->messages()->delete();
            broadcast(new ConversationCleared($conversationId));

            // Xóa luôn bản ghi conversation
            $conversationToDelete->delete();

            // Reload danh sách
            $this->loadConversations();
            if ($this->canManageAllChats()) {
                $this->loadStaffUsersAlternative();
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('Error deleting conversation: ' . $e->getMessage());
            return false;
        }
    }

    #[On('change-status-user')]
    public function changeStatusUser($id)
    {
        if (!$this->selectedConversation) {
            $this->dispatch('app-dialog', [
                'type' => 'error',
                'title' => 'Không tìm thấy đoạn chat',
                'text' => 'Vui lòng chọn một cuộc trò chuyện trước.'
            ]);
            return;
        }
        $getUser = User::find($id);
        if (!$getUser) {
            $this->dispatch('app-dialog', [
                'type' => 'error',
                'title' => 'Lỗi',
                'text' => 'Không tìm thấy người dùng.'
            ]);
            return;
        }
        if ($getUser->status === "activated") {
            $message = "Khóa tài khoản người dùng thành công!";
            $getUser->status = "banned";
            event(new UserLocked($getUser->id));
            $this->dispatch('app-dialog', [
                'type' => 'success',
                'title' => 'Đã khóa!',
                'text' => $message
            ]);
        } else {
            $getUser->status = "activated";
            $message = "Mở khóa tài khoản người dùng thành công!";
            $this->dispatch('app-dialog', [
                'type' => 'success',
                'title' => 'Đã mở khóa',
                'text' => $message
            ]);
        }
        $getUser->save();
        $this->loadConversations();
        if ($this->canManageAllChats()) {
            $this->loadStaffUsersAlternative();
        }
        $this->loadMessages();
    }
    private function loadMessages($page = 1)
    {
        if (!$this->selectedConversation) {
            return;
        }

        $conversation = $this->selectedConversation;

        // Đếm tổng số tin nhắn
        $totalMessages = $conversation->messages()->count();

        // Load messages với phân trang, sắp xếp từ mới nhất
        $messages = $conversation->messages()
            ->with('sender:id,full_name,role')
            ->select('id', 'message', 'type', 'kind', 'image_path', 'reference_type', 'reference_id', 'reference_payload', 'sender_id', 'conversation_id', 'is_read', 'created_at')
            ->orderBy('created_at', 'desc')
            ->skip(($page - 1) * $this->messagesPerPage)
            ->take($this->messagesPerPage)
            ->get();

        $readStatuses = app(ChatReadService::class)->sentReadStatuses($messages, $conversation);

        // Log để debug
        Log::info('Loading messages', [
            'page' => $page,
            'total_messages' => $totalMessages,
            'loaded_count' => $messages->count(),
            'skip' => ($page - 1) * $this->messagesPerPage,
            'take' => $this->messagesPerPage
        ]);

        $messagesArray = $messages->values() // Giữ nguyên thứ tự mới nhất (Mới -> Cũ) cho flex column-reverse
            ->map(function ($message) use ($readStatuses) {
                return [
                    'id' => $message->id,
                    'message' => $message->message,
                    'image_path' => $message->image_path,
                    'type' => $message->type,
                    'kind' => $message->kind ?: $message->type,
                    'reference_type' => $message->reference_type,
                    'reference_id' => $message->reference_id,
                    'reference_payload' => $message->reference_payload,
                    'sender_id' => $message->sender_id,
                    'conversation_id' => $message->conversation_id,
                    'is_read' => $readStatuses[$message->id] ?? false,
                    'created_at' => $message->created_at,
                    'sender' => [
                        'id' => $message->sender->id,
                        'full_name' => $message->sender->full_name,
                        'role' => $message->sender->role,
                    ]
                ];
            })->toArray();

        if ($page === 1) {
            // Trang đầu tiên - thay thế toàn bộ messages
            $this->messages = $messagesArray;
        } else {
            // Trang tiếp theo - thêm vào cuối mảng (tin nhắn cũ hơn sẽ ở index lớn hơn)
            $this->messages = array_merge($this->messages, $messagesArray);
        }

        // Kiểm tra còn tin nhắn để load không
        $loadedSoFar = ($page * $this->messagesPerPage);
        $hasMore = $loadedSoFar < $totalMessages;

        $this->hasMoreMessages = $hasMore;

        // Dispatch event để JavaScript biết đã load xong
        $this->dispatch('messages-loaded', ['hasMore' => $hasMore, 'totalLoaded' => count($this->messages)]);
    }

    public function loadMoreMessages($page = null)
    {
        // Nếu không truyền page, tự động tăng currentPage
        if ($page === null) {
            $page = $this->currentPage + 1;
        }

        Log::info('loadMoreMessages called', ['page' => $page, 'hasMore' => $this->hasMoreMessages]);

        if (!$this->hasMoreMessages || !$this->selectedConversation) {
            Log::info('Cannot load more messages', ['hasMore' => $this->hasMoreMessages, 'hasConversation' => !!$this->selectedConversation]);
            $this->isLoading = false;
            return;
        }

        $this->isLoading = true;
        $this->currentPage = $page;
        $this->loadMessages($page);
        $this->isLoading = false;

        // Dispatch event để JavaScript biết đã load xong
        $this->dispatch('messages-loaded-complete');
    }

    public function toggleStaffExpansion($staffId)
    {
        if (in_array($staffId, $this->expandedStaff)) {
            // Bỏ staffId khỏi danh sách mở rộng
            $this->expandedStaff = array_diff($this->expandedStaff, [$staffId]);
        } else {
            // Thêm staffId vào danh sách mở rộng
            $this->expandedStaff[] = $staffId;
        }
        $this->loadStaffUsersAlternative();
    }

    public function openConversationFromNotification($conversationId, $userId, $staffId)
    {
        $currentUserId = Auth::id();
        $conversation = Conversation::with('staff:id,role')->find($conversationId);
        abort_unless($conversation && $this->canViewConversation($conversation), 403);

        $userId = $conversation->user_id;
        $staffId = $conversation->staff_id;

        logger('🔔 openConversationFromNotification called', [
            'conversationId' => $conversationId,
            'userId' => $userId,
            'staffId' => $staffId,
            'currentUserId' => $currentUserId,
            'isAdminConversation' => $staffId === $currentUserId
        ]);

        // Nếu staffId khác admin_id → Expand staff section
        if ($staffId && $staffId !== $currentUserId) {
            logger('📂 Expanding staff section', ['staffId' => $staffId]);
            // Expand staff nếu chưa expand
            if (!in_array($staffId, $this->expandedStaff)) {
                $this->expandedStaff[] = $staffId;
            }
            $this->loadStaffUsersAlternative();
        } else {
            logger('👤 Admin conversation - không cần expand staff');
        }

        // Chọn conversation
        logger('🎯 Calling selectConversation', ['conversationId' => $conversationId]);
        $this->selectConversation($conversationId);

        // Dispatch event để scroll và highlight
        $this->dispatch('scroll-to-conversation', [
            'conversationId' => $conversationId,
            'staffId' => $staffId,
            'isStaffConversation' => $staffId !== $currentUserId
        ]);
    }

    public function selectUserForChat($userId, $staffId = null)
    {
        $currentUser = Auth::user();
        $authorization = app(AuthorizationService::class);

        // Nếu là admin và không truyền staffId, tìm staff đã mời user này
        if ($this->canManageAllChats() && !$staffId) {
            $member = User::find($userId);
            if ($member && $member->referrer_id) {
                $staffId = $member->referrer_id;
            } else {
                // Nếu user không có referrer, không cho phép chat
                $this->dispatch('app-dialog', [
                    'type' => 'warning',
                    'title' => 'Không thể mở chat',
                    'text' => 'Người dùng này chưa được staff nào mời.'
                ]);
                return;
            }
        }

        $actualStaffId = $staffId ?? $currentUser->id;
        $operator = User::find($actualStaffId);

        abort_unless(
            $operator && $authorization->canViewOperatorChats($currentUser, $operator),
            403,
            'Không được phép truy cập hội thoại của tài khoản này.'
        );

        abort_unless(
            $operator->invitedUsers->contains('id', $userId)
                || Conversation::where('user_id', $userId)->where('staff_id', $actualStaffId)->exists(),
            403,
            'Không được phép truy cập người dùng này.'
        );

        // Đảm bảo accordion của nhân viên này được giữ mở
        if (!in_array($actualStaffId, $this->expandedStaff)) {
            $this->expandedStaff[] = $actualStaffId;
        }

        // Reset state trước
        $this->messages = [];

        // Admin: CHỈ TÌM conversation hiện có, KHÔNG TẠO MỚI
        if ($this->canManageAllChats()) {
            $conversation = Conversation::where('user_id', $userId)
                ->where('staff_id', $actualStaffId)
                ->first();

            if (!$conversation) {
                $this->dispatch('app-dialog', [
                    'type' => 'info',
                    'title' => 'Chưa có cuộc trò chuyện',
                    'text' => 'Người dùng này chưa nhắn tin với staff.'
                ]);
                return;
            }
        } else {
            // Staff: Được phép tạo conversation mới
            $conversation = Conversation::where('user_id', $userId)
                ->where('staff_id', $actualStaffId)
                ->first();

            if (!$conversation) {
                abort_if(
                    Conversation::where('user_id', $userId)->exists(),
                    403,
                    'Hội thoại này đã được điều phối cho người phụ trách khác.'
                );

                $conversation = Conversation::firstOrCreate(
                    ['user_id' => $userId],
                    ['staff_id' => $actualStaffId]
                );
            }

            abort_unless(
                (int) $conversation->staff_id === (int) $actualStaffId,
                403,
                'Hội thoại này đã được điều phối cho người phụ trách khác.'
            );

            // Nếu tạo mới conversation, cần reload sidebar để hiển thị
            if ($conversation->wasRecentlyCreated) {
                $this->loadConversations();
            }
        }

        $this->selectConversation($conversation->id);
    }

    public function editMessage($messageId)
    {
        $message = Message::find($messageId);
        if ($message && $message->sender_id == Auth::id() && $message->type === 'text') {
            $this->editingMessageId = $messageId;
            $this->editingMessageText = $message->message;
        }
    }

    public function cancelEdit()
    {
        $this->editingMessageId = null;
        $this->editingMessageText = '';
    }

    public function updateMessage()
    {
        if (!$this->editingMessageId)
            return;

        $message = Message::find($this->editingMessageId);
        if ($message && $message->sender_id == Auth::id()) {
            $message->update([
                'message' => trim($this->editingMessageText)
            ]);
            broadcast(new MessageUpdated($message->id));

            // Cập nhật trong mảng messages đang hiển thị
            if (is_array($this->messages)) {
                foreach ($this->messages as &$msg) {
                    if ($msg['id'] == $this->editingMessageId) {
                        $msg['message'] = trim($this->editingMessageText);
                        break;
                    }
                }
            }

            $this->cancelEdit();
        }
    }

    #[On('delete-single-message')]
    public function deleteMessage($messageId)
    {
        if (!$this->isOwner()) {
            return;
        }

        $message = Message::find($messageId);
        if ($message) {
            $conversationId = $message->conversation_id;
            $message->delete();
            broadcast(new MessageDeleted($messageId, $conversationId));

            // Xóa khỏi mảng hiển thị
            if (is_array($this->messages)) {
                $this->messages = array_values(array_filter($this->messages, function ($msg) use ($messageId) {
                    return $msg['id'] != $messageId;
                }));
            }
        }
    }

    public function sendMessage()
    {
        if (!$this->selectedConversation || (empty(trim($this->messageText)) && !$this->image))
            return;

        try {
            $imagePath = null;
            $userId = Auth::id();
            $userName = Auth::user()->full_name;
            $userRole = Auth::user()->role;

            // Nếu có ảnh
            if ($this->image) {
                $imagePath = $this->image->store('chat-images', 'public');
            }

            $message = Message::create([
                'conversation_id' => $this->selectedConversation->id,
                'sender_id' => $userId,
                'message' => trim($this->messageText),
                'image_path' => $imagePath,
                'type' => $imagePath ? 'image' : 'text',
                'kind' => $imagePath ? 'image' : 'text',
            ]);

            // Format message cho UI (không cần load sender - dùng data có sẵn)
            $messageArray = [
                'id' => $message->id,
                'message' => $message->message,
                'image_path' => $message->image_path,
                'type' => $message->type,
                'kind' => $message->kind ?: $message->type,
                'reference_type' => $message->reference_type,
                'reference_id' => $message->reference_id,
                'reference_payload' => $message->reference_payload,
                'sender_id' => $userId,
                'conversation_id' => $message->conversation_id,
                'is_read' => false,
                'created_at' => $message->created_at,
                'sender' => [
                    'id' => $userId,
                    'full_name' => $userName,
                    'role' => $userRole,
                ]
            ];

            // Thêm tin nhắn mới vào UI
            $tempMessages = $this->messages;
            array_unshift($tempMessages, $messageArray); // Đẩy tin nhắn mới nhất lên đầu (index 0)
            $this->messages = $tempMessages;

            // Reset ngay lập tức
            $this->messageText = '';
            $this->image = null;
            $this->dispatch('reset-message-input');
            $this->dispatch('scroll-to-bottom');
            $this->dispatch('refresh-conversations');

            // Dispatch broadcast job - Chỉ pass ID, không query trong constructor
            \App\Jobs\BroadcastMessageSent::dispatch($message->id);
        } catch (\Throwable $e) {
            logger('Send message failed:', ['error' => $e->getMessage()]);
            session()->flash('error', 'Không thể gửi tin nhắn. Vui lòng thử lại.');
        }
    }
    public function scrollToBottom()
    {
        $this->dispatch('scroll-to-bottom');
    }

    public function messageReceived($message)
    {
        logger('🔔 messageReceived called', [
            'message_id' => $message['id'] ?? 'unknown',
            'conversation_id' => $message['conversation_id'] ?? 'unknown',
            'selectedConversationId' => $this->selectedConversationId
        ]);

        if (
            !is_array($message) ||
            !isset($message['id'], $message['conversation_id'], $message['sender_id'])
        ) {
            logger('❌ Message không hợp lệ (Admin):', ['message' => $message]);
            return;
        }

        $conversation = Conversation::with(['user', 'staff:id,role'])->find($message['conversation_id']);
        if (!$conversation || !$this->canViewConversation($conversation)) {
            return;
        }

        // KIỂM TRA DUPLICATE: Sử dụng session để cache (tốt hơn property vì shared across requests)
        $messageId = $message['id'];
        $processedIds = session()->get('chat.processed_message_ids', []);

        if (in_array($messageId, $processedIds)) {
            logger('⏭️ Message đã được xử lý, bỏ qua:', ['message_id' => $messageId, 'cache' => $processedIds]);
            return;
        }

        // Đánh dấu message đã được xử lý
        $processedIds[] = $messageId;
        logger('✅ Message đánh dấu đã xử lý:', ['message_id' => $messageId]);

        // Giới hạn cache không quá 50 messages
        if (count($processedIds) > 50) {
            $processedIds = array_slice($processedIds, -50);
        }

        // Lưu lại vào session
        session()->put('chat.processed_message_ids', $processedIds);

        // Tin nhắn thuộc conversation đang mở
        logger('🔍 Checking if message belongs to current conversation', [
            'selectedConversationId' => $this->selectedConversationId,
            'message_conversation_id' => $message['conversation_id'],
            'matches' => (int) $this->selectedConversationId === (int) $message['conversation_id']
        ]);

        if ((int) $this->selectedConversationId === (int) $message['conversation_id']) {
            logger('✅ Tin nhắn thuộc conversation đang mở', [
                'message_id' => $message['id'],
                'current_messages_count' => count($this->messages)
            ]);

            if (is_array($this->messages)) {
                // Kiểm tra trùng ID
                $ids = array_column($this->messages, 'id');
                if (!in_array($message['id'], $ids)) {
                    logger('➕ Thêm tin nhắn mới vào backend', ['message_id' => $message['id']]);

                    // Tự động đánh dấu tin nhắn là đã đọc vì conversation đang được mở
                    $this->markSingleMessageAsRead($message['id'], $message['conversation_id']);

                    // Receipt của người gửi được tra riêng theo người nhận thực tế.
                    $message['is_read'] = false;

                    // Thêm tin nhắn mới và force Livewire detect change
                    $tempMessages = $this->messages;
                    array_unshift($tempMessages, $message); // Tin nhắn mới lên đầu
                    $this->messages = $tempMessages;
                    $this->refreshReadReceipts();

                    logger('📜 Dispatching scroll-to-bottom event', [
                        'new_messages_count' => count($this->messages)
                    ]);
                    $this->dispatch('scroll-to-bottom');
                } else {
                    logger('⚠️ Tin nhắn đã tồn tại trong backend, bỏ qua', ['message_id' => $message['id']]);
                }
            }
        } else {
            logger('📢 Tin nhắn KHÔNG phải conversation đang mở → hiển thị notification', [
                'message_id' => $message['id'],
                'conversation_id' => $message['conversation_id']
            ]);

            // Tin nhắn KHÔNG thuộc conversation đang mở - hiển thị notification có thể click
            // Kiểm tra tồn tại sender info trước khi truy cập
            $senderName = $message['sender']['full_name'] ?? 'Người dùng';
            $messagePreview = match ($message['kind'] ?? $message['type'] ?? 'text') {
                'order_reference' => 'Đã gửi đơn hàng liên quan',
                'transaction_reference' => 'Đã gửi giao dịch liên quan',
                'image' => 'Đã gửi hình ảnh',
                default => Str::limit($message['message'] ?? '', 30, '...'),
            };

            $userId = $conversation->user_id ?? null;
            $staffId = $conversation->staff_id ?? null;

            logger('💬 Dispatching chat-notification event', [
                'conversationId' => $message['conversation_id'],
                'senderName' => $senderName
            ]);

            if (!ConversationNotificationMute::isMutedFor((int) Auth::id(), (int) $message['conversation_id'])) {
                $this->dispatch('chat-notification', [
                    'conversationId' => $message['conversation_id'],
                    'userId' => $userId,
                    'staffId' => $staffId,
                    'senderName' => $senderName,
                    'message' => $messagePreview
                ]);
            }
        }

        // Cập nhật sidebar
        $this->loadConversations();
        if ($this->canManageAllChats()) {
            $this->loadStaffUsersAlternative();
        }
        $this->dispatch('refresh-conversations');
    }

    /**
     * Đánh dấu một tin nhắn cụ thể là đã đọc
     */
    public function markSingleMessageAsRead($messageId, $conversationId)
    {
        $message = Message::with('conversation.staff:id,role')->find($messageId);
        if (!$message || (int) $message->conversation_id !== (int) $conversationId) {
            return;
        }

        abort_unless(
            $message->conversation && $this->canViewConversation($message->conversation),
            403
        );

        if (app(ChatReadService::class)->markMessageRead($message->id, $message->conversation, Auth::id())) {
            broadcast(new MessageRead($message->id, $conversationId, Auth::id()));
        }
    }

    public function onConversationRead($data)
    {
        if (isset($data['conversation_id']) && (int) $data['conversation_id'] === (int) $this->selectedConversationId) {
            $this->refreshReadReceipts();
        }
    }

    /**
     * Xử lý khi nhận event MessageRead từ WebSocket
     */
    public function onMessageReadUpdate($messageId)
    {
        if ($messageId) {
            $this->refreshReadReceipts();
        }
    }

    public function refreshReadReceipts(): void
    {
        $conversation = $this->selectedConversation;
        if (!$conversation || !is_array($this->messages) || $this->messages === []) {
            return;
        }

        $ids = array_column($this->messages, 'id');
        $messages = Message::query()
            ->where('conversation_id', $conversation->id)
            ->whereIn('id', $ids)
            ->get(['id', 'sender_id']);
        $statuses = app(ChatReadService::class)->sentReadStatuses($messages, $conversation);

        $this->messages = array_map(function ($message) use ($statuses) {
            $message['is_read'] = $statuses[$message['id']] ?? false;
            return $message;
        }, $this->messages);
    }

    /**
     * Cập nhật trạng thái is_read của tin nhắn trong $this->messages array
     */
    private function updateMessageReadStatus($messageId, $isRead)
    {
        if (!is_array($this->messages)) {
            return;
        }

        $updated = false;
        $newMessages = [];

        foreach ($this->messages as $key => $message) {
            if (isset($message['id']) && $message['id'] == $messageId) {
                $message['is_read'] = $isRead;
                $updated = true;
            }
            $newMessages[] = $message;
        }

        // Force Livewire to detect the change by completely reassigning
        if ($updated) {
            $this->messages = $newMessages;
        }
    }

    public function render()
    {
        return view('livewire.admin.chat-component');
    }
}
