<?php

namespace App\Livewire\User;

use App\Events\MessageSent;
use App\Events\MessageRead;
use App\Events\UserJoinChat;
use App\Events\UserSentMessage;
use App\Jobs\SendChatNotificationEmail;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Services\ChatReferenceService;
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

    /**
     * Đếm số tin nhắn chưa đọc
     */
    public function loadUnreadCount()
    {
        $this->unreadCount = Message::where('conversation_id', $this->conversation->id)
            ->where('sender_id', '!=', Auth::id())
            ->where('is_read', false)
            ->count();
    }

    public function loadLatestMessages()
    {
        $messages = Message::where('conversation_id', $this->conversation->id)
            ->with('sender:id,full_name,role')
            ->select('id', 'message', 'type', 'kind', 'image_path', 'reference_type', 'reference_id', 'reference_payload', 'sender_id', 'conversation_id', 'is_read', 'created_at')
            ->orderBy('created_at', 'desc')
            ->limit($this->messagesPerLoad)
            ->get()
            ->values()
            ->map(function ($message) {
                return $this->formatMessage($message);
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
            ->get()
            ->values()
            ->map(function ($message) {
                return $this->formatMessage($message);
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

    private function formatMessage($message)
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
            'is_read' => $message->is_read ?? false,
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

        $isFirstCustomerMessage = Message::where('conversation_id', $this->conversation->id)->count() === 0;
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
        $this->checkAndSendEmailNotification($notificationText);
        $this->sendAutoReplyIfNeeded($isFirstCustomerMessage);
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
        $isFirstCustomerMessage = Message::where('conversation_id', $conversationId)->count() === 0;

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
        $this->checkAndSendEmailNotification($template_message_for_notification);

        // Kiểm tra và gửi tin nhắn chào tự động nếu là tin nhắn đầu tiên hoặc đã quá thời gian chờ
        $this->sendAutoReplyIfNeeded($isFirstCustomerMessage);
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
                    // Cập nhật message trong UI để hiển thị is_read = true
                    $message['is_read'] = true;
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
        $message = Message::find($messageId);
        if ($message && !$message->is_read) {
            $message->update(['is_read' => true]);

            // Cập nhật trong $this->chatMessages collection để UI hiển thị đúng
            $this->updateMessageReadStatus($messageId, true);

            // Broadcast event để người gửi biết tin nhắn đã được đọc
            broadcast(new MessageRead($message->id, $this->conversation->id));
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

        // Cập nhật tất cả tin nhắn chưa đọc sang đã đọc bằng 1 câu lệnh SQL duy nhất
        $updatedCount = Message::where('conversation_id', $this->conversation->id)
            ->where('is_read', false)
            ->where('sender_id', '!=', Auth::id())
            ->update(['is_read' => true]);

        if ($updatedCount > 0) {
            // Cập nhật trạng thái in-memory collection nếu cần
            if ($this->chatMessages instanceof Collection) {
                $this->chatMessages = $this->chatMessages->map(function($msg) {
                    if (is_array($msg)) {
                        if ($msg['sender_id'] != Auth::id()) {
                            $msg['is_read'] = true;
                        }
                    } else if (isset($msg->sender_id) && $msg->sender_id != Auth::id()) {
                        $msg->is_read = true;
                    }
                    return $msg;
                });
            }

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
            $this->updateMessageReadStatus($messageId, true);
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
        if (isset($data['conversation_id']) && $data['conversation_id'] == $this->conversation->id) {
            // User thấy admin đã xem tin nhắn -> đánh dấu tất cả sang đã xem
            if ($this->chatMessages instanceof Collection) {
                $this->chatMessages = $this->chatMessages->map(function ($msg) {
                    if (is_array($msg)) {
                        if ($msg['sender_id'] == Auth::id()) {
                            $msg['is_read'] = true;
                        }
                    } else if (isset($msg->sender_id) && $msg->sender_id == Auth::id()) {
                        $msg->is_read = true;
                    }
                    return $msg;
                });
            }
        }
    }

    public function onMessageUpdated($data)
    {
        $message = $data['message'] ?? null;
        if (!$message || (int) ($message['conversation_id'] ?? 0) !== (int) $this->conversation->id) {
            return;
        }

        $this->chatMessages = collect($this->chatMessages)->map(function ($currentMessage) use ($message) {
            return (int) ($currentMessage['id'] ?? 0) === (int) $message['id']
                ? $message
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
     * Gửi tin nhắn chào tự động nếu đã lâu không có tin nhắn từ staff
     */
    protected function sendAutoReplyIfNeeded($isFirstCustomerMessage = false)
    {
        try {
            if (!config('chat.auto_reply.enabled', true)) {
                return;
            }

            $timeoutHours = (float) config('chat.auto_reply.timeout_hours', 1);
            $repeatAfterHours = (float) config('chat.auto_reply.repeat_after_hours', 1);
            $escalationAfterMinutes = (int) config('chat.auto_reply.escalation_after_minutes', 5);
            $currentLocale = app()->getLocale();
            $defaultLocale = config('chat.auto_reply.default_language', 'vi');
            $messages = config('chat.auto_reply.messages', []);
            $autoReplyMessage = $messages[$currentLocale] ?? $messages[$defaultLocale] ?? $messages['vi'] ?? null;

            if (!$autoReplyMessage) {
                return;
            }

            $lastStaffMessage = Message::where('conversation_id', $this->conversation->id)
                ->where('sender_id', '!=', Auth::id())
                ->whereHas('sender', function ($query) {
                    $query->whereIn('role', ['admin', 'staff']);
                })
                ->select('id', 'created_at', 'sender_id')
                ->orderBy('created_at', 'desc')
                ->first();

            $lastAutoReply = Message::where('conversation_id', $this->conversation->id)
                ->where('sender_id', $this->conversation->staff_id)
                ->whereIn('message', array_values($messages))
                ->select('id', 'created_at', 'message')
                ->orderBy('created_at', 'desc')
                ->first();

            $shouldSendAutoReply = false;

            if ($isFirstCustomerMessage) {
                $shouldSendAutoReply = true;
            } elseif ($lastAutoReply && $lastAutoReply->created_at->diffInHours(now()) >= $repeatAfterHours && (!$lastStaffMessage || $lastStaffMessage->created_at->diffInHours(now()) >= $timeoutHours)) {
                $shouldSendAutoReply = true;
            } elseif (!$lastStaffMessage && (!$lastAutoReply || $lastAutoReply->created_at->diffInHours(now()) >= $repeatAfterHours)) {
                $shouldSendAutoReply = true;
            }

            if (!$shouldSendAutoReply) {
                return;
            }

            $staffId = $this->conversation->staff_id;
            $manager = User::find($staffId);
            $managerIsOnline = $manager?->isOnline() ?? false;
            $onlineManagerDelayMinutes = max(0, (int) config('chat.auto_reply.online_manager_delay_minutes', 1));
            $triggerCustomerMessageId = Message::where('conversation_id', $this->conversation->id)
                ->where('sender_id', Auth::id())
                ->orderByDesc('id')
                ->value('id');

            if (!$triggerCustomerMessageId) {
                return;
            }

            $pendingAutoReply = \App\Jobs\SendAutoReplyMessage::dispatch(
                $this->conversation->id,
                $staffId,
                $autoReplyMessage,
                Auth::id(),
                $currentLocale,
                $lastStaffMessage ? $lastStaffMessage->created_at->diffInHours(now()) : null,
                $triggerCustomerMessageId
            );

            if ($managerIsOnline && $onlineManagerDelayMinutes > 0) {
                $pendingAutoReply->delay(now()->addMinutes($onlineManagerDelayMinutes));
            }

            $recipientEmails = \App\Services\ChatAutoReplyService::getEscalationRecipients(Auth::user());
            \App\Jobs\NotifyAutoReplyEscalation::dispatch(
                $this->conversation->id,
                Auth::id(),
                $autoReplyMessage,
                $recipientEmails,
                $escalationAfterMinutes
            )->delay(now()->addMinutes($escalationAfterMinutes));

            Log::info('Đã đưa tin nhắn chào tự động vào queue', [
                'conversation_id' => $this->conversation->id,
                'user_id' => Auth::id(),
                'staff_id' => $staffId,
                'locale' => $currentLocale,
                'hours_since_last_message' => $lastStaffMessage ? $lastStaffMessage->created_at->diffInHours(now()) : null,
                'is_first_customer_message' => $isFirstCustomerMessage,
                'manager_is_online' => $managerIsOnline,
                'auto_reply_delay_minutes' => $managerIsOnline ? $onlineManagerDelayMinutes : 0,
                'trigger_customer_message_id' => $triggerCustomerMessageId,
            ]);

        } catch (\Exception $e) {
            Log::error('Lỗi gửi tin nhắn chào tự động: ' . $e->getMessage(), [
                'conversation_id' => $this->conversation->id ?? null,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Kiểm tra và gửi email thông báo nếu admin/staff offline
     */
    protected function checkAndSendEmailNotification($messageContent)
    {
        try {
            $currentUser = Auth::user();
            $emailsSent = [];
            $recipients = collect([]);

            // 1. Lấy nhân viên mời (referrer) nếu có
            $referrer = null;
            if ($currentUser->referrer_id) {
                $referrer = User::find($currentUser->referrer_id);
            }

            // 2. Lấy admin (người có quyền cao nhất)
            $admin = User::where('role', 'admin')->first();

            // LOGIC GỬI EMAIL:
            // - Nếu user CÓ referrer (được nhân viên mời):
            //   + Gửi cho referrer nếu offline
            //   + Gửi cho admin nếu offline (và admin khác referrer)
            // - Nếu user KHÔNG CÓ referrer:
            //   + Chỉ gửi cho admin nếu offline

            if ($referrer) {
                // User được mời bởi nhân viên

                // Gửi cho referrer nếu offline
                if (!$referrer->isOnline()) {
                    $recipients->push($referrer);
                    $emailsSent[] = "Nhân viên: {$referrer->full_name} ({$referrer->email})";
                }

                // Gửi cho admin nếu offline và admin khác referrer
                if ($admin && !$admin->isOnline() && $admin->id !== $referrer->id) {
                    $recipients->push($admin);
                    $emailsSent[] = "Admin: {$admin->full_name} ({$admin->email})";
                }

            } else {
                // User đăng ký không có ai mời → chỉ gửi cho admin

                if ($admin && !$admin->isOnline()) {
                    $recipients->push($admin);
                    $emailsSent[] = "Admin: {$admin->full_name} ({$admin->email})";
                }
            }

            // Nếu không có ai offline, không gửi email
            if ($recipients->isEmpty()) {
                Log::info('Tất cả staff/admin đang online, không cần gửi email', [
                    'user_id' => Auth::id(),
                    'conversation_id' => $this->conversation->id,
                    'has_referrer' => $referrer ? true : false
                ]);
                return;
            }

            // Dispatch email jobs vào queue (gửi bất đồng bộ để không block UI)
            foreach ($recipients as $recipient) {
                SendChatNotificationEmail::dispatch(
                    $currentUser,
                    $messageContent,
                    $this->conversation->id,
                    $recipient->email
                );
            }

            Log::info('Đã đưa email thông báo chat vào queue', [
                'user_id' => Auth::id(),
                'user_name' => $currentUser->full_name,
                'conversation_id' => $this->conversation->id,
                'has_referrer' => $referrer ? true : false,
                'recipients' => $emailsSent,
                'total_emails' => $recipients->count()
            ]);

        } catch (\Exception $e) {
            // Log lỗi nhưng không làm gián đoạn việc gửi tin nhắn
            Log::error('Lỗi gửi email thông báo chat: ' . $e->getMessage(), [
                'conversation_id' => $this->conversation->id ?? null,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    public function render()
    {
        return view('livewire.user.chat-component');
    }
}
