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
    public $messagesPerLoad = 5;
    public $offset = 0;
    public $hasMoreMessages = true;
    public $isLoading = false;
    public $maxMessageLength = 500;
    public $unreadCount = 0;
    public $showQuickReplies = false;
    public $quickReplySuggestions = [];
    public $quickReplyLoading = false;

    protected $listeners = [
        'message-received' => 'messageReceived',
        'toggleChatBox'
    ];

    protected $rules = [
        'newMessage' => 'nullable|string|max:500',
        'selectedImage' => 'nullable|image', // 5MB
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
        $admin = User::where('role', 'admin')->first();
        $this->conversation = Conversation::firstOrCreate(
            ['user_id' => Auth::user()->id],
            ['staff_id' => $referrerId ?: $admin->id]
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
            ->with('sender')
            ->orderBy('created_at', 'desc')
            ->limit($this->messagesPerLoad)
            ->get()
            ->reverse()
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
            ->with('sender')
            ->orderBy('created_at', 'desc')
            ->offset($this->offset)
            ->limit($this->messagesPerLoad)
            ->get()
            ->reverse()
            ->map(function ($message) {
                return $this->formatMessage($message);
            });

        if ($olderMessages->count() > 0) {
            $this->chatMessages = $olderMessages->concat($this->chatMessages);
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
            'image_path' => $message->image_path,
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
            'selectedImage' => 'image|max:5120'
        ]);
    }

    public function removeImage()
    {
        $this->selectedImage = null;
        $this->resetErrorBag('selectedImage');
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
                'image_path' => $imagePath,
            ]);

            $messages[] = [
                'id' => $imageMessage->id,
                'message' => $imageMessage->message,
                'type' => $imageMessage->type,
                'image_path' => $imageMessage->image_path,
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
                'image_path' => null,
            ]);
            
            $messages[] = [
                'id' => $textMessage->id,
                'message' => $textMessage->message,
                'type' => $textMessage->type,
                'image_path' => $textMessage->image_path,
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
            $this->chatMessages = $this->chatMessages->push($message);
        }
        
        // Reset Livewire state (đồng bộ với frontend)
        $this->newMessage = '';
        $this->selectedImage = null;
        $this->resetErrorBag();

        // Dispatch UI events
        $this->dispatch('message-sent');
        $this->dispatch('scroll-to-bottom');
        
        // Event và Email notification (đã dùng Queue, không block)
        $user = Auth::user()->load('conversation');
        event(new UserSentMessage($userName, $template_message_for_notification, $user->id));
        $this->checkAndSendEmailNotification($template_message_for_notification);
        
        // Kiểm tra và gửi tin nhắn chào tự động nếu đã lâu không nhắn
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
            (int) $message['sender_id'] !== Auth::id()
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
                
                $this->chatMessages = $this->chatMessages->push($message);
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
            broadcast(new MessageRead($message->id, $this->conversation->id))->toOthers();
        }
    }

    public function closeBox()
    {
        $this->showBox = false;
    }

    public function toggleChatBox()
    {
        $this->toggleBox();
    }

    public function toggleBox()
    {
        $this->showBox = !$this->showBox;
        if ($this->showBox) {
            $user = Auth::user()->load('conversation');
            event(new UserJoinChat($user->username, $user->full_name, $user->id));
            // Đánh dấu tin nhắn đã đọc khi mở chat box
            $this->markMessagesAsRead();
            // Reset unread count về 0
            $this->unreadCount = 0;
        }
        if ($this->showBox) {
            $this->dispatch('scroll-to-bottom');
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

        // Lấy tất cả tin nhắn chưa đọc không phải của mình
        $unreadMessages = $this->conversation->messages()
            ->where('is_read', false)
            ->where('sender_id', '!=', Auth::id())
            ->get();

        foreach ($unreadMessages as $message) {
            $message->update(['is_read' => true]);
            
            // Cập nhật trong $this->chatMessages collection để UI hiển thị đúng
            $this->updateMessageReadStatus($message->id, true);
            
            // Broadcast event để người gửi biết tin nhắn đã được đọc
            broadcast(new MessageRead($message->id, $this->conversation->id))->toOthers();
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

    public function scrollToBottom()
    {
        $this->dispatch('scroll-to-bottom');
    }

    public function getRemainingCharacters()
    {
        return $this->maxMessageLength - strlen($this->newMessage);
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
        
        // Kiểm tra có nên hiển thị gợi ý không
        $this->showQuickReplies = $this->shouldShowQuickReplies();
    }

    /**
     * Kiểm tra có nên hiển thị gợi ý tin nhắn không
     */
    protected function shouldShowQuickReplies()
    {
        $config = config('chat.quick_replies.show_when', []);
        
        // Nếu chat trống (chưa có tin nhắn từ user)
        if ($config['chat_empty'] ?? true) {
            $userMessageCount = Message::where('conversation_id', $this->conversation->id)
                ->where('sender_id', Auth::id())
                ->count();
            
            if ($userMessageCount === 0) {
                return true;
            }
        }
        
        // Nếu đã lâu không nhắn (X giờ)
        $afterHours = $config['after_hours'] ?? 2;
        $lastUserMessage = Message::where('conversation_id', $this->conversation->id)
            ->where('sender_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->first();
        
        if ($lastUserMessage) {
            $hoursSinceLastMessage = $lastUserMessage->created_at->diffInHours(now());
            if ($hoursSinceLastMessage >= $afterHours) {
                return true;
            }
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
        if (!config('chat.quick_replies.enabled', true)) {
            return;
        }

        if ($this->showQuickReplies) {
            // Đang mở → đóng lại
            $this->showQuickReplies = false;
        } else {
            // Đang đóng → mở ra và load lại gợi ý
            $this->loadQuickReplies();
            // Luôn hiển thị khi user click vào nút
            $this->showQuickReplies = true;
        }
    }

    /**
     * Gửi tin nhắn chào tự động nếu đã lâu không có tin nhắn từ staff
     */
    protected function sendAutoReplyIfNeeded()
    {
        try {
            // Kiểm tra tính năng có được bật không
            if (!config('chat.auto_reply.enabled', true)) {
                return;
            }

            // Thời gian timeout (giờ) từ config
            $timeoutHours = config('chat.auto_reply.timeout_hours', 1);
            
            // Lấy tin nhắn gần nhất từ staff trong conversation này
            $lastStaffMessage = Message::where('conversation_id', $this->conversation->id)
                ->where('sender_id', '!=', Auth::id())
                ->whereHas('sender', function ($query) {
                    $query->whereIn('role', ['admin', 'staff']);
                })
                ->orderBy('created_at', 'desc')
                ->first();
            
            // Kiểm tra có cần gửi auto-reply không
            $shouldSendAutoReply = false;
            
            if (!$lastStaffMessage) {
                // Chưa có tin nhắn nào từ staff → gửi auto-reply
                $shouldSendAutoReply = true;
            } else {
                // Kiểm tra thời gian tin nhắn cuối từ staff
                $hoursSinceLastMessage = $lastStaffMessage->created_at->diffInHours(now());
                
                if ($hoursSinceLastMessage >= $timeoutHours) {
                    $shouldSendAutoReply = true;
                }
            }
            
            // Gửi tin nhắn chào tự động nếu cần
            if ($shouldSendAutoReply) {
                // Lấy ngôn ngữ hiện tại của user (từ session hoặc config)
                $currentLocale = app()->getLocale();
                $defaultLocale = config('chat.auto_reply.default_language', 'vi');
                
                // Lấy nội dung tin nhắn theo ngôn ngữ
                $messages = config('chat.auto_reply.messages', []);
                $autoReplyMessage = $messages[$currentLocale] ?? $messages[$defaultLocale] ?? $messages['vi'];
                
                // Lấy staff_id từ conversation để làm người gửi
                $staffId = $this->conversation->staff_id;
                
                // Dispatch job để GỬI TIN NHẮN TỰ ĐỘNG SAU 3 GIÂY
                // Delay để đảm bảo tin nhắn user được INSERT VÀ BROADCAST trước
                \App\Jobs\SendAutoReplyMessage::dispatch(
                    $this->conversation->id,
                    $staffId,
                    $autoReplyMessage,
                    Auth::id(),
                    $currentLocale,
                    $lastStaffMessage ? $lastStaffMessage->created_at->diffInHours(now()) : null
                )->delay(now()->addSeconds(3));
                
                Log::info('Đã đưa tin nhắn chào tự động vào queue', [
                    'conversation_id' => $this->conversation->id,
                    'user_id' => Auth::id(),
                    'staff_id' => $staffId,
                    'locale' => $currentLocale,
                    'hours_since_last_message' => $lastStaffMessage ? $lastStaffMessage->created_at->diffInHours(now()) : null,
                ]);
            }
            
        } catch (\Exception $e) {
            // Log lỗi nhưng không làm gián đoạn việc gửi tin nhắn
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
