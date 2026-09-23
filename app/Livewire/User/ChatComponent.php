<?php

namespace App\Livewire\User;

use App\Events\MessageSent;
use App\Events\MessageRead;
use App\Events\UserJoinChat;
use App\Events\UserSentMessage;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Services\ChatReferenceService;
use App\Services\ChatReadService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;

class ChatComponent extends Component
{
    use WithFileUploads;

    public $newMessage = '';
    public $selectedImage;
    public $chatMessages;
    public $showBox = false;
    public $conversation;
    public $currentChannel = null;
    public $messagesPerLoad = 10;
    public $offset = 0;
    public $hasMoreMessages = true;
    public $isLoading = false;
    public $maxMessageLength = null;
    public $unreadCount = 0;
    public $showQuickReplies = false;
    public $quickReplySuggestions = [];
    public $quickReplyLoading = false;
    public $showReferencePicker = false;
    public $referenceTab = 'order';

    protected $listeners = [
        'message-received' => 'messageReceived',
        'toggleChatBox'
    ];

    protected $rules = [
        'newMessage' => 'nullable|string',
        'selectedImage' => 'nullable|image', // Không giới hạn
    ];

    protected function messages()
    {
        return [
            'newMessage.max' => __('livewire.TinNhanKhongDuocVuotQua500KyTu'),
            'selectedImage.image' => __('livewire.ChiChapNhanTepHinhAnh'),
        ];
    }

    public function mount()
    {
        $referrerId = Auth::user()->referrer_id;
        $adminId = User::where('role', 'admin')->value('id');
        $this->conversation = Conversation::firstOrCreate(
            ['user_id' => Auth::user()->id],
            ['staff_id' => $referrerId ?: $adminId]
        );

        $this->loadLatestMessages();
        $this->loadUnreadCount();
        $this->loadQuickReplies();
        $this->dispatch('join-conversation-channel', conversationId: $this->conversation->id);
    }

    public function hydrate(): void
    {
        // The assigned operator may change while this Livewire component is open.
        $this->conversation = Conversation::where('user_id', Auth::id())->first();
        if (!$this->chatMessages) {
            return;
        }

        $messages = collect($this->chatMessages);
        $messageIds = $messages
            ->map(fn ($message) => is_array($message) ? ($message['id'] ?? null) : ($message->id ?? null))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->values();

        if ($messageIds->isEmpty()) {
            return;
        }

        $persistedMessages = Message::where('conversation_id', $this->conversation->id)
            ->whereIn('id', $messageIds)
            ->get(['id', 'sender_id']);
        $readStatuses = app(ChatReadService::class)->sentReadStatuses($persistedMessages, $this->conversation);

        $this->chatMessages = $messages->map(function ($message) use ($readStatuses) {
            $messageId = is_array($message) ? ($message['id'] ?? null) : ($message->id ?? null);

            if (!$messageId || !array_key_exists($messageId, $readStatuses)) {
                return $message;
            }

            if (is_array($message)) {
                $message['is_read'] = $readStatuses[$messageId];
            } else {
                $message->is_read = $readStatuses[$messageId];
            }

            return $message;
        });
    }

    /**
     * Đếm số tin nhắn chưa đọc
     */
    public function loadUnreadCount()
    {
        $this->unreadCount = Message::where('conversation_id', $this->conversation->id)
            ->unreadFor(Auth::id())
            ->count();
    }

    public function loadLatestMessages()
    {
        $messages = Message::where('conversation_id', $this->conversation->id)
            ->with('sender:id,full_name,role')
            ->select('id', 'message', 'type', 'kind', 'image_path', 'reference_type', 'reference_id', 'reference_payload', 'sender_id', 'conversation_id', 'is_read', 'created_at')
            ->orderBy('created_at', 'desc')
            ->limit($this->messagesPerLoad)
            ->get();
        $readStatuses = app(ChatReadService::class)->sentReadStatuses($messages, $this->conversation);
        $messages = $messages->values()
            ->map(function ($message) use ($readStatuses) {
                return $this->formatMessage($message, $readStatuses[$message->id] ?? false);
            });

        $this->chatMessages = collect($messages);
        $this->offset = $messages->count();

        $totalMessages = Message::where('conversation_id', $this->conversation->id)->count();
        $this->hasMoreMessages = $totalMessages > $this->offset;
    }

    public function loadMoreMessages()
    {
        if (!$this->hasMoreMessages || $this->isLoading) {
            return;
        }

        $this->isLoading = true;

        $olderMessages = Message::where('conversation_id', $this->conversation->id)
            ->with('sender:id,full_name,role')
            ->select('id', 'message', 'type', 'kind', 'image_path', 'reference_type', 'reference_id', 'reference_payload', 'sender_id', 'conversation_id', 'is_read', 'created_at')
            ->orderBy('created_at', 'desc')
            ->offset($this->offset)
            ->limit($this->messagesPerLoad)
            ->get();
        $readStatuses = app(ChatReadService::class)->sentReadStatuses($olderMessages, $this->conversation);
        $olderMessages = $olderMessages->values()
            ->map(function ($message) use ($readStatuses) {
                return $this->formatMessage($message, $readStatuses[$message->id] ?? false);
            });

        if ($olderMessages->count() > 0) {
            $this->chatMessages = $this->chatMessages->concat($olderMessages); // Append older messages to end of collection
            $this->offset += $olderMessages->count();

            $totalMessages = Message::where('conversation_id', $this->conversation->id)->count();
            $this->hasMoreMessages = $totalMessages > $this->offset;
        } else {
            $this->hasMoreMessages = false;
        }

        $this->isLoading = false;
        $this->dispatch('messages-loaded');
    }

    private function formatMessage($message, bool $isRead = false)
    {
        return [
            'id' => $message->id,
            'message' => $message->message,
            'type' => $message->type,
            'kind' => $message->kind ?: $message->type,
            'image_path' => $message->image_path,
            'reference_type' => $message->reference_type,
            'reference_id' => $message->reference_id,
            'reference_payload' => $message->reference_payload,
            'sender_id' => $message->sender_id,
            'conversation_id' => $message->conversation_id,
            'is_read' => $isRead,
            'created_at' => $message->created_at,
            'sender' => [
                'id' => $message->sender->id,
                'full_name' => $message->sender->full_name,
                'role' => $message->sender->role,
            ]
        ];
    }

    public function updatedSelectedImage()
    {
        $this->validate([
            'selectedImage' => 'image'
        ]);
    }

    public function removeImage()
    {
        $this->selectedImage = null;
        $this->resetErrorBag('selectedImage');
    }

    public function openReferencePicker(string $tab = 'order'): void
    {
        $this->referenceTab = in_array($tab, ['order', 'transaction'], true) ? $tab : 'order';
        $this->showReferencePicker = true;
    }

    public function closeReferencePicker(): void
    {
        $this->showReferencePicker = false;
    }

    public function selectReferenceTab(string $tab): void
    {
        if (in_array($tab, ['order', 'transaction'], true)) {
            $this->referenceTab = $tab;
        }
    }

    public function getReferenceItemsProperty(): array
    {
        $references = app(ChatReferenceService::class);

        return $this->referenceTab === 'transaction'
            ? $references->recentTransactions(Auth::id())
            : $references->recentOrders(Auth::id());
    }

    public function sendOrderReference(int $orderId): void
    {
        $payload = app(ChatReferenceService::class)->orderForUser(Auth::id(), $orderId);
        $this->sendReference('order_reference', 'frozen_order', $orderId, $payload);
    }

    public function sendTransactionReference(string $source, int $transactionId): void
    {
        $payload = app(ChatReferenceService::class)->transactionForUser(Auth::id(), $source, $transactionId);
        $this->sendReference('transaction_reference', $source, $transactionId, $payload);
    }

    private function sendReference(string $kind, string $referenceType, int $referenceId, array $payload): void
    {
        abort_unless((int) $this->conversation->user_id === (int) Auth::id(), 403);

        $message = Message::create([
            'conversation_id' => $this->conversation->id,
            'sender_id' => Auth::id(),
            'message' => null,
            'type' => 'text',
            'kind' => $kind,
            'image_path' => null,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'reference_payload' => $payload,
        ]);

        $message->setRelation('sender', Auth::user());
        $this->chatMessages = collect($this->chatMessages)->prepend($this->formatMessage($message));
        $this->conversation->touch();
        $this->showReferencePicker = false;
        $this->showQuickReplies = false;

        \App\Jobs\BroadcastMessageSent::dispatch($message->id);
        $this->dispatch('message-sent');
        $this->dispatch('scroll-to-bottom');
        $notificationText = $kind === 'order_reference'
            ? 'Đã gửi đơn hàng liên quan'
            : 'Đã gửi giao dịch liên quan';
        event(new UserSentMessage(Auth::user()->full_name, $notificationText, Auth::id()));
        $this->sendAutoReplyIfNeeded();
    }

    public function sendMessage()
    {
        // Kiểm tra có tin nhắn hoặc ảnh không
        if (!$this->selectedImage && (!$this->newMessage || trim($this->newMessage) === '')) {
            $this->addError('newMessage', __('livewire.VuiLongNhapTinNhanHoacChonAnh'));
            return;
        }

        $this->validate();

        // Lưu data (frontend đã reset input rồi, nhưng vẫn cần lấy giá trị)
        $messageText = trim($this->newMessage);
        $imageFile = $this->selectedImage;
        $conversationId = $this->conversation->id;
        $userId = Auth::id();
        $userName = Auth::user()->full_name;

        $messages = [];
        $template_message_for_notification = "";

        // Prepare collection
        if (!$this->chatMessages instanceof Collection) {
            $this->chatMessages = collect($this->chatMessages);
        }

        // XỬ LÝ ẢNH (nếu có) - Insert DB đồng bộ
        if ($imageFile) {
            $imagePath = $imageFile->store('chat-images', 'public');

            $imageMessage = Message::create([
                'conversation_id' => $conversationId,
                'sender_id' => $userId,
                'message' => null,
                'type' => 'image',
                'kind' => 'image',
                'image_path' => $imagePath,
            ]);

            $messages[] = [
                'id' => $imageMessage->id,
                'message' => $imageMessage->message,
                'type' => $imageMessage->type,
                'kind' => 'image',
                'image_path' => $imageMessage->image_path,
                'reference_type' => null,
                'reference_id' => null,
                'reference_payload' => null,
                'sender_id' => $userId,
                'conversation_id' => $conversationId,
                'is_read' => false,
                'created_at' => $imageMessage->created_at,
                'sender' => [
                    'id' => $userId,
                    'full_name' => $userName,
                    'role' => 'user',
                ]
            ];
            $template_message_for_notification = "Đã gửi hình ảnh";

            // Broadcast qua job (không block response)
            \App\Jobs\BroadcastMessageSent::dispatch($imageMessage->id);
        }

        // XỬ LÝ TEXT (nếu có) - Insert DB đồng bộ
        if ($messageText !== '') {
            $textMessage = Message::create([
                'conversation_id' => $conversationId,
                'sender_id' => $userId,
                'message' => $messageText,
                'type' => 'text',
                'kind' => 'text',
                'image_path' => null,
            ]);

            $messages[] = [
                'id' => $textMessage->id,
                'message' => $textMessage->message,
                'type' => $textMessage->type,
                'kind' => 'text',
                'image_path' => $textMessage->image_path,
                'reference_type' => null,
                'reference_id' => null,
                'reference_payload' => null,
                'sender_id' => $userId,
                'conversation_id' => $conversationId,
                'is_read' => false,
                'created_at' => $textMessage->created_at,
                'sender' => [
                    'id' => $userId,
                    'full_name' => $userName,
                    'role' => 'user',
                ]
            ];
            $template_message_for_notification = Str::limit($messageText, 30, '...');

            // Broadcast qua job (không block response)
            \App\Jobs\BroadcastMessageSent::dispatch($textMessage->id);
        }

        // Update conversation
        $this->conversation->touch();

        // Add messages vào UI
        foreach ($messages as $message) {
            $this->chatMessages = $this->chatMessages->prepend($message); // Tin nhắn mới lên đầu (đáy visual)
        }

        // Reset Livewire state (đồng bộ với frontend)
        $this->newMessage = '';
        $this->selectedImage = null;
        $this->resetErrorBag();

        // Dispatch UI events
        $this->dispatch('message-sent');
        $this->dispatch('scroll-to-bottom');

        // Event và Email notification (đã dùng Queue, không block)
        $user = Auth::user();
        event(new UserSentMessage($userName, $template_message_for_notification, $user->id));
        // Apply the manager online/offline auto-reply flow.
        $this->sendAutoReplyIfNeeded();
    }

    public function messageReceived($message)
    {
        logger('User nhận được message:', ['message' => $message]);

        if (
            !is_array($message) ||
            !isset($message['id'], $message['conversation_id'], $message['sender_id'])
        ) {
            logger('Message không hợp lệ:', ['message' => $message]);
            return;
        }

        if (
            (int) $message['conversation_id'] === (int) $this->conversation->id &&
            (int) $message['sender_id'] != Auth::id()
        ) {
            if (!$this->chatMessages instanceof Collection) {
                $this->chatMessages = collect($this->chatMessages);
            }

            if (!$this->chatMessages->contains('id', $message['id'])) {
                // Nếu chat box đang mở, tự động đánh dấu tin nhắn là đã đọc
                if ($this->showBox) {
                    $this->markMessageAsRead($message['id']);
                    $message['is_read'] = false;
                } else {
                    // Nếu chat box đóng, tăng số tin nhắn chưa đọc
                    $this->unreadCount++;
                }

                $this->chatMessages = $this->chatMessages->prepend($message); // Prepend for index 0 (newest at bottom)
                $this->dispatch('scroll-to-bottom');
            }
        }
    }

    /**
     * Đánh dấu một tin nhắn cụ thể là đã đọc
     */
    private function markMessageAsRead($messageId)
    {
        if ((int) $this->conversation->user_id !== (int) Auth::id()) {
            return;
        }

        if (app(ChatReadService::class)->markMessageRead($messageId, $this->conversation, Auth::id())) {
            broadcast(new MessageRead($messageId, $this->conversation->id, Auth::id()));
        }
    }

    public function closeBox()
    {
        $this->showBox = false;
    }

    public function toggleBox($isOpen = null)
    {
        if ($isOpen !== null) {
            $this->showBox = $isOpen;
        } else {
            $this->showBox = !$this->showBox;
        }

        if ($this->showBox) {
            $user = Auth::user();
            // Broadcast trực tiếp không qua queue để tránh lỗi socket ID
            broadcast(new UserJoinChat($user->username, $user->full_name, $user->id));
            // Đánh dấu tin nhắn đã đọc khi mở chat box
            $this->markMessagesAsRead();
            // Reset unread count về 0
            $this->unreadCount = 0;
            $this->dispatch('conversation-opened');
        }
    }

    /**
     * Đánh dấu tất cả tin nhắn chưa đọc là đã đọc
     */
    private function markMessagesAsRead()
    {
        if (!$this->conversation) {
            return;
        }

        abort_unless((int) $this->conversation->user_id === (int) Auth::id(), 403);
        $updatedCount = app(ChatReadService::class)->markConversationRead($this->conversation, Auth::id());

        if ($updatedCount > 0) {
            // Gửi duy nhất 1 broadcast thay vì loop gửi N broadcast
            broadcast(new \App\Events\ConversationRead($this->conversation->id, Auth::id()));
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

    /**
     * Cập nhật trạng thái is_read của tin nhắn trong $this->chatMessages collection
     */
    private function updateMessageReadStatus($messageId, $isRead)
    {
        if (!$this->chatMessages instanceof \Illuminate\Support\Collection) {
            $this->chatMessages = collect($this->chatMessages);
        }

        // Convert to array để modify, sau đó convert lại thành collection
        $messages = $this->chatMessages->toArray();

        foreach ($messages as &$message) {
            if (isset($message['id']) && $message['id'] == $messageId) {
                $message['is_read'] = $isRead;
                break;
            }
        }
        unset($message); // Break reference

        $this->chatMessages = collect($messages);
    }

    public function onConversationRead($data)
    {
        if (isset($data['conversation_id']) && (int) $data['conversation_id'] === (int) $this->conversation->id) {
            $this->refreshReadReceipts();
        }
    }

    public function refreshReadReceipts(): void
    {
        if (!$this->conversation || (int) $this->conversation->user_id !== (int) Auth::id()) {
            return;
        }

        $this->conversation->refresh();
        $messages = collect($this->chatMessages);
        $ids = $messages->map(fn ($message) => $message['id'] ?? null)->filter()->all();
        if ($ids === []) {
            return;
        }

        $persistedMessages = Message::where('conversation_id', $this->conversation->id)
            ->whereIn('id', $ids)
            ->get(['id', 'sender_id']);
        $statuses = app(ChatReadService::class)->sentReadStatuses($persistedMessages, $this->conversation);
        $this->chatMessages = $messages->map(function ($message) use ($statuses) {
            $message['is_read'] = $statuses[$message['id']] ?? false;
            return $message;
        });
    }

    public function onMessageUpdated($data)
    {
        $message = $data['message'] ?? null;
        if (!$message || (int) ($message['conversation_id'] ?? 0) !== (int) $this->conversation->id) {
            return;
        }

        $this->chatMessages = collect($this->chatMessages)->map(function ($currentMessage) use ($message) {
            return (int) ($currentMessage['id'] ?? 0) === (int) $message['id']
                ? array_merge($currentMessage, $message, ['is_read' => $currentMessage['is_read'] ?? false])
                : $currentMessage;
        });
    }

    public function onMessageDeleted($data)
    {
        if ((int) ($data['conversation_id'] ?? 0) !== (int) $this->conversation->id) {
            return;
        }

        $this->chatMessages = collect($this->chatMessages)
            ->reject(fn ($message) => (int) ($message['id'] ?? 0) === (int) ($data['message_id'] ?? 0))
            ->values();
    }

    public function onConversationCleared($data)
    {
        if ((int) ($data['conversation_id'] ?? 0) !== (int) $this->conversation->id) {
            return;
        }

        $this->chatMessages = collect();
        $this->offset = 0;
        $this->hasMoreMessages = false;
        $this->unreadCount = 0;
    }

    public function scrollToBottom()
    {
        $this->dispatch('scroll-to-bottom');
    }

    public function getRemainingCharacters()
    {
        // Không giới hạn ký tự nên luôn trả về null
        return null;
    }

    /**
     * Load danh sách gợi ý tin nhắn nhanh
     */
    public function loadQuickReplies()
    {
        if (!config('chat.quick_replies.enabled', true)) {
            $this->showQuickReplies = false;
            return;
        }

        // Lấy ngôn ngữ hiện tại
        $currentLocale = app()->getLocale();
        $allSuggestions = config('chat.quick_replies.suggestions', []);

        // Lấy gợi ý theo ngôn ngữ
        $this->quickReplySuggestions = $allSuggestions[$currentLocale] ?? $allSuggestions['vi'] ?? [];

        // Nếu được gọi từ mount (chưa click manual), kiểm tra điều kiện auto-show
        if (is_null($this->showQuickReplies) || $this->showQuickReplies === false) {
            $this->showQuickReplies = $this->shouldShowQuickReplies();
        }
    }

    /**
     * Kiểm tra có nên hiển thị gợi ý tin nhắn không
     */
    protected function shouldShowQuickReplies()
    {
        if (!$this->conversation) return false;

        $config = config('chat.quick_replies.show_when', []);

        // Lấy tin nhắn cuối của user để check cả 2 điều kiện trong 1 query
        $lastUserMessage = Message::where('conversation_id', $this->conversation->id)
            ->where('sender_id', Auth::id())
            ->select('id', 'created_at')
            ->latest()
            ->first();

        // 1. Nếu chat trống (chưa có tin nhắn từ user)
        if (!$lastUserMessage) {
            return $config['chat_empty'] ?? true;
        }

        // 2. Nếu đã lâu không nhắn (X giờ)
        $afterHours = $config['after_hours'] ?? 2;
        if ($lastUserMessage->created_at->diffInHours(now()) >= $afterHours) {
            return true;
        }

        return false;
    }

    /**
     * Sử dụng tin nhắn gợi ý nhanh
     */
    public function useQuickReply($message)
    {
        try {
            // Bật loading spinner
            $this->quickReplyLoading = true;

            // Xóa emoji và khoảng trắng thừa nếu cần
            $this->newMessage = trim($message);

            // Tự động gửi tin nhắn
            $this->sendMessage();

            // Ẩn gợi ý sau khi gửi thành công
            $this->showQuickReplies = false;

        } catch (\Exception $e) {
            Log::error('Lỗi khi sử dụng quick reply: ' . $e->getMessage());
            $this->addError('quickReply', 'Có lỗi xảy ra, vui lòng thử lại.');
        } finally {
            // Tắt loading spinner
            $this->quickReplyLoading = false;
        }
    }

    /**
     * Đóng/ẩn gợi ý tin nhắn
     */
    public function hideQuickReplies()
    {
        $this->showQuickReplies = false;
    }

    /**
     * Toggle (mở/đóng) gợi ý tin nhắn
     */
    public function toggleQuickReplies()
    {
        $this->showQuickReplies = !$this->showQuickReplies;

        if ($this->showQuickReplies && empty($this->quickReplySuggestions)) {
            $this->loadQuickReplies();
        }
    }

    /**
     * Handle auto-reply and escalation email from the manager assigned to the conversation.
     */
    protected function sendAutoReplyIfNeeded(): void
    {
        try {
            if (!config('chat.auto_reply.enabled', true)) {
                return;
            }

            $currentLocale = app()->getLocale();
            $defaultLocale = config('chat.auto_reply.default_language', 'vi');
            $messages = config('chat.auto_reply.messages', []);
            $autoReplyMessage = $messages[$currentLocale] ?? $messages[$defaultLocale] ?? $messages['vi'] ?? null;

            if (!$autoReplyMessage) {
                return;
            }

            $staffId = (int) $this->conversation->staff_id;
            $manager = $staffId > 0 ? User::find($staffId) : null;

            if (!$manager) {
                Log::warning('Cannot process chat auto-reply because the conversation has no valid manager', [
                    'conversation_id' => $this->conversation->id,
                    'user_id' => Auth::id(),
                    'staff_id' => $this->conversation->staff_id,
                ]);
                return;
            }

            $triggerCustomerMessageId = Message::where('conversation_id', $this->conversation->id)
                ->where('sender_id', Auth::id())
                ->orderByDesc('id')
                ->value('id');

            if (!$triggerCustomerMessageId) {
                return;
            }

            $managerIsOnline = $manager->isOnline();
            $onlineManagerDelayMinutes = max(0, (int) config('chat.auto_reply.online_manager_delay_minutes', 1));
            $escalationAfterMinutes = max(0, (int) config('chat.auto_reply.escalation_after_minutes', 5));

            $pendingAutoReply = \App\Jobs\SendAutoReplyMessage::dispatch(
                $this->conversation->id,
                $manager->id,
                $autoReplyMessage,
                Auth::id(),
                $currentLocale,
                null,
                $triggerCustomerMessageId
            );

            if ($managerIsOnline && $onlineManagerDelayMinutes > 0) {
                $pendingAutoReply->delay(now()->addMinutes($onlineManagerDelayMinutes));
            }

            $recipientEmails = \App\Services\ChatAutoReplyService::getEscalationRecipients($manager);
            $emailAfterMinutes = $managerIsOnline ? $escalationAfterMinutes : 0;
            $pendingEscalation = \App\Jobs\NotifyAutoReplyEscalation::dispatch(
                $this->conversation->id,
                Auth::id(),
                $autoReplyMessage,
                $recipientEmails,
                $emailAfterMinutes,
                $triggerCustomerMessageId,
                $managerIsOnline
            );

            if ($managerIsOnline && $escalationAfterMinutes > 0) {
                $pendingEscalation->delay(now()->addMinutes($escalationAfterMinutes));
            }

            Log::info('Queued chat auto-reply and escalation email flow', [
                'conversation_id' => $this->conversation->id,
                'user_id' => Auth::id(),
                'staff_id' => $manager->id,
                'manager_role' => $manager->role,
                'manager_is_online' => $managerIsOnline,
                'auto_reply_delay_minutes' => $managerIsOnline ? $onlineManagerDelayMinutes : 0,
                'email_delay_minutes' => $emailAfterMinutes,
                'trigger_customer_message_id' => $triggerCustomerMessageId,
                'recipient_count' => count($recipientEmails),
            ]);
        } catch (\Exception $e) {
            Log::error('Chat auto-reply flow failed: ' . $e->getMessage(), [
                'conversation_id' => $this->conversation->id ?? null,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    public function render()
    {
        return view('livewire.user.chat-component');
    }
}
