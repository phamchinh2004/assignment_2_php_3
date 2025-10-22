<?php

namespace App\Livewire\Admin;

use App\Events\MessageSent;
use App\Events\MessageRead;
use App\Events\UserLocked;
use Livewire\Component;
use App\Models\User;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\WithFileUploads;
use Str;


class ChatComponent extends Component
{
    use WithFileUploads;
    public $image; // Hình ảnh được chọn
    public $imagePreviewUrl; // URL preview ảnh
    public $selectedConversationId = null;
    public $selectedStaffId = null;
    public $messageText = '';
    public $messages = [];
    public $conversations = [];
    public $staffUsers = [];
    public $expandedStaff = [];
    public $messagesPerPage = 20; // Tăng số tin nhắn mỗi lần tải
    public $currentPage = 1;
    public $hasMoreMessages = true;
    public $searchTerm = '';
    public $maxMessageLength = 1000;
    protected $listeners = ['message-received' => 'messageReceived'];

    public function mount()
    {
        $this->loadConversations();
        if (Auth::user()->role === 'admin') {
            $this->loadStaffUsersAlternative();
        }
    }
    public function refreshChatState()
    {
        $this->loadConversations();

        if (Auth::user()->role === 'admin') {
            $this->loadStaffUsersAlternative();
        }
    }
    public function getSelectedConversationProperty()
    {
        return $this->conversations->firstWhere('id', $this->selectedConversationId);
    }

    public function loadConversations()
    {
        $user = Auth::user();

        // Main query
        $query = Conversation::query()
            ->with([
                'user',
                'staff',
                'messages' => function ($query) {
                    $query->latest()->limit(1);
                }
            ])
            ->orderByDesc('updated_at');

        if ($user->role === 'staff') {
            $query->where('staff_id', $user->id);
        }

        if ($this->searchTerm) {
            $query->whereHas('user', function ($q) {
                $q->where('full_name', 'like', '%' . $this->searchTerm . '%')
                    ->orWhere('username', 'like', '%' . $this->searchTerm . '%');
            });
        }

        $conversations = $query->get();
        
        // Tính unread_count cho mỗi conversation
        foreach ($conversations as $conv) {
            $unreadCount = Message::where('conversation_id', $conv->id)
                ->where('sender_id', '!=', $user->id)
                ->where('is_read', 0)
                ->count();
            $conv->unread_count = $unreadCount;
        }
        
        $this->conversations = $conversations;
    }
    public function updatedSearchTerm()
    {
        $this->loadConversations();
        if (Auth::user()->role === 'admin') {
            $this->loadStaffUsersAlternative();
        }
    }

    // Alternative method nếu bạn muốn dùng updated_at của conversation

    public function loadStaffUsersAlternative()
    {
        $currentUserId = Auth::id();
        
        $staffData = User::where('role', 'staff')
            ->with([
                'invitedUsers' => function ($query) {
                    $query->where('role', 'member')
                        ->with(['latestConversation.messages']);
                }
            ])
            ->get();

        // Tạo collection mới để đảm bảo reference được cập nhật đúng
        $this->staffUsers = collect();

        foreach ($staffData as $staff) {
            // Tính unread_count cho mỗi user
            foreach ($staff->invitedUsers as $user) {
                if ($user->latestConversation) {
                    $unreadCount = Message::where('conversation_id', $user->latestConversation->id)
                        ->where('sender_id', '!=', $currentUserId)
                        ->where('is_read', 0)
                        ->count();
                    $user->latestConversation->unread_count = $unreadCount;
                }
            }
            
            // Sắp xếp invitedUsers
            $sortedUsers = $staff->invitedUsers
                ->sortByDesc(function ($user) {
                    if ($user->latestConversation) {
                        return $user->latestConversation->updated_at;
                    }
                    return '1900-01-01 00:00:00';
                })
                ->values();

            // Gán lại sorted users cho staff
            $staff->setRelation('invitedUsers', $sortedUsers);

            // Thêm vào collection mới
            $this->staffUsers->push($staff);
        }
    }




    public function selectConversation($conversationId)
    {
        $this->selectedConversationId = $conversationId;
        $this->currentPage = 1;
        $this->hasMoreMessages = true;

        $this->loadMessages();
        $this->markMessagesAsRead($conversationId);
        
        // Reload conversations để cập nhật unread_count
        $this->loadConversations();
        if (Auth::user()->role === 'admin') {
            $this->loadStaffUsersAlternative();
        }
        
        $this->dispatch('join-conversation-channel', conversationId: $conversationId);
        $this->dispatch('conversation-selected');
    }

    /**
     * Đánh dấu tất cả tin nhắn chưa đọc trong conversation là đã đọc
     */
    private function markMessagesAsRead($conversationId)
    {
        $conversation = Conversation::find($conversationId);
        if (!$conversation) {
            return;
        }

        // Lấy tất cả tin nhắn chưa đọc không phải của mình
        $unreadMessages = $conversation->messages()
            ->where('is_read', false)
            ->where('sender_id', '!=', Auth::id())
            ->get();

        foreach ($unreadMessages as $message) {
            $message->update(['is_read' => true]);
            
            // Cập nhật trong $this->messages array để UI hiển thị đúng
            $this->updateMessageReadStatus($message->id, true);
            
            // Broadcast event để người gửi biết tin nhắn đã được đọc
            broadcast(new MessageRead($message, $conversationId))->toOthers();
        }
    }

    #[On('delete-all-messages')]
    public function deleteAllMessages()
    {
        // $this->dispatch('swal', [
        //     'type' => 'error',
        //     'title' => 'Lỗi',
        //     'text' => 'Không thể xóa tin nhắn. Vui lòng thử lại.'
        // ]);
        if (!$this->selectedConversation) {
            $this->dispatch('swal', [
                'type' => 'error',
                'title' => 'Không tìm thấy đoạn chat',
                'text' => 'Vui lòng chọn một cuộc trò chuyện trước.'
            ]);
            return;
        }

        try {
            $this->selectedConversation->messages()->delete();
            $this->messages = [];

            $this->loadConversations();
            if (Auth::user()->role === 'admin') {
                $this->loadStaffUsersAlternative();
            }
            $this->dispatch('scroll-to-bottom');

            $this->dispatch('swal', [
                'type' => 'success',
                'title' => 'Xóa thành công',
                'text' => 'Tất cả tin nhắn đã được xóa.'
            ]);
        } catch (\Throwable $e) {
            logger('Xóa tin nhắn lỗi:', ['err' => $e->getMessage()]);
            $this->dispatch('swal', [
                'type' => 'error',
                'title' => 'Lỗi',
                'text' => 'Không thể xóa tin nhắn. Vui lòng thử lại.'
            ]);
        }
    }
    #[On('change-status-user')]
    public function changeStatusUser($id)
    {
        if (!$this->selectedConversation) {
            $this->dispatch('swal', [
                'type' => 'error',
                'title' => 'Không tìm thấy đoạn chat',
                'text' => 'Vui lòng chọn một cuộc trò chuyện trước.'
            ]);
            return;
        }
        $getUser = User::find($id);
        if (!$getUser) {
            $this->dispatch('swal', [
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
            $this->dispatch('swal', [
                'type' => 'success',
                'title' => 'Đã khóa!',
                'text' => $message
            ]);
        } else {
            $getUser->status = "activated";
            $message = "Mở khóa tài khoản người dùng thành công!";
            $this->dispatch('swal', [
                'type' => 'success',
                'title' => 'Đã mở khóa',
                'text' => $message
            ]);
        }
        $getUser->save();
        $this->loadConversations();
        if (Auth::user()->role === 'admin') {
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
            ->with('sender')
            ->orderBy('created_at', 'desc')
            ->skip(($page - 1) * $this->messagesPerPage)
            ->take($this->messagesPerPage)
            ->get();

        // Log để debug
        \Log::info('Loading messages', [
            'page' => $page,
            'total_messages' => $totalMessages,
            'loaded_count' => $messages->count(),
            'skip' => ($page - 1) * $this->messagesPerPage,
            'take' => $this->messagesPerPage
        ]);

        $messagesArray = $messages->reverse() // Đảo ngược để hiển thị đúng thứ tự (cũ -> mới)
            ->map(function ($message) {
                return [
                    'id' => $message->id,
                    'message' => $message->message,
                    'image_path' => $message->image_path,
                    'type' => $message->type,
                    'sender_id' => $message->sender_id,
                    'conversation_id' => $message->conversation_id,
                    'is_read' => $message->is_read,
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
            // Trang tiếp theo - thêm vào đầu mảng (tin nhắn cũ hơn)
            $this->messages = array_merge($messagesArray, $this->messages);
        }

        // Kiểm tra còn tin nhắn để load không
        $loadedSoFar = ($page * $this->messagesPerPage);
        $hasMore = $loadedSoFar < $totalMessages;

        $this->hasMoreMessages = $hasMore;

        if (!$hasMore) {
            $this->dispatch('no-more-messages');
        }

        // Dispatch event để JavaScript biết đã load xong
        $this->dispatch('messages-loaded', ['hasMore' => $hasMore, 'totalLoaded' => count($this->messages)]);
    }

    public function loadMoreMessages($page)
    {
        \Log::info('loadMoreMessages called', ['page' => $page, 'hasMore' => $this->hasMoreMessages]);

        if (!$this->hasMoreMessages || !$this->selectedConversation) {
            \Log::info('Cannot load more messages', ['hasMore' => $this->hasMoreMessages, 'hasConversation' => !!$this->selectedConversation]);
            return;
        }

        $this->currentPage = $page;
        $this->loadMessages($page);
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

    public function selectUserForChat($userId, $staffId = null)
    {
        if ($staffId && !User::find($staffId)?->invitedUsers->contains('id', $userId)) {
            abort(403, 'Không được phép truy cập người dùng này.');
        }

        $user = Auth::user();
        $actualStaffId = $staffId ?? $user->id;

        // Kiểm tra quyền truy cập
        if ($user->role === 'staff' && $actualStaffId !== $user->id) {
            return;
        }

        // Tìm hoặc tạo conversation
        $conversation = Conversation::firstOrCreate([
            'user_id' => $userId,
            'staff_id' => $actualStaffId
        ]);

        $this->selectConversation($conversation->id);
    }

    public function sendMessage()
    {
        if (!$this->selectedConversation || (empty(trim($this->messageText)) && !$this->image)) return;

        try {
            $imagePath = null;

            // Nếu có ảnh
            if ($this->image) {
                $imagePath = $this->image->store('chat-images', 'public');
            }

            $message = Message::create([
                'conversation_id' => $this->selectedConversation->id,
                'sender_id' => Auth::id(),
                'message' => trim($this->messageText),
                'image_path' => $imagePath,
                'type' => $imagePath ? 'image' : 'text'
            ]);
            $message->load('sender');
            $this->selectedConversation->touch();

            $messageArray = [
                'id' => $message->id,
                'message' => $message->message,
                'image_path' => $message->image_path,
                'type' => $message->type,
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

            // Thêm tin nhắn mới và force Livewire detect change bằng cách reassign
            $tempMessages = $this->messages;
            $tempMessages[] = $messageArray;
            $this->messages = $tempMessages;
            
            broadcast(new MessageSent($message))->toOthers();

            // Reset
            $this->messageText = '';
            $this->image = null;
            $this->dispatch('reset-message-input');
            $this->dispatch('scroll-to-bottom');
            $this->loadConversations();
            if (Auth::user()->role === 'admin') {
                $this->loadStaffUsersAlternative();
            }
            $this->dispatch('refresh-conversations');
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
        if (
            !is_array($message) ||
            !isset($message['id'], $message['conversation_id'], $message['sender_id'])
        ) {
            logger('Message không hợp lệ (Admin):', ['message' => $message]);
            return;
        }

        if (
            (int) $this->selectedConversationId === (int) $message['conversation_id'] &&
            (int) $message['sender_id'] !== Auth::id()
        ) {
            // Nếu $this->messages là mảng thường
            if (is_array($this->messages)) {
                // Kiểm tra trùng ID
                $ids = array_column($this->messages, 'id');
                if (!in_array($message['id'], $ids)) {
                    // Tự động đánh dấu tin nhắn là đã đọc vì conversation đang được mở
                    $this->markSingleMessageAsRead($message['id'], $message['conversation_id']);
                    
                    // Cập nhật message trong UI để hiển thị is_read = true
                    $message['is_read'] = true;
                    
                    // Thêm tin nhắn mới và force Livewire detect change
                    $tempMessages = $this->messages;
                    $tempMessages[] = $message;
                    $this->messages = $tempMessages;
                    
                    $this->dispatch('scroll-to-bottom');
                }
            }
        } else {
            $this->dispatch('swal', [
                'type' => 'warning',
                'title' => $message['sender']['full_name'],
                'text' => $message['type'] === "text" ? Str::limit($message['message'], 30, '...') : "Đã gửi hình ảnh"
            ]);
        }

        $this->loadConversations();
        if (Auth::user()->role === 'admin') {
            $this->loadStaffUsersAlternative();
        }
        $this->dispatch('refresh-conversations');
    }

    /**
     * Đánh dấu một tin nhắn cụ thể là đã đọc
     */
    private function markSingleMessageAsRead($messageId, $conversationId)
    {
        $message = Message::find($messageId);
        if ($message && !$message->is_read) {
            $message->update(['is_read' => true]);
            
            // Cập nhật trong $this->messages array để UI hiển thị đúng
            $this->updateMessageReadStatus($messageId, true);
            
            // Broadcast event để người gửi biết tin nhắn đã được đọc
            broadcast(new MessageRead($message, $conversationId))->toOthers();
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
