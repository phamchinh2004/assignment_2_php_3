<?php

namespace App\Livewire\Admin;

use App\Events\MessageSent;
use App\Events\UserLocked;
use Livewire\Component;
use App\Models\User;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;

class ChatComponent extends Component
{
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
    protected $listeners = ['message-received' => 'messageReceived'];

    public function mount()
    {
        $this->loadConversations();
        if (Auth::user()->role === 'admin') {
            $this->loadStaffUsers();
        }
    }

    public function getSelectedConversationProperty()
    {
        return $this->conversations->firstWhere('id', $this->selectedConversationId);
    }

    public function loadConversations()
    {
        $user = Auth::user();

        $query = Conversation::with([
            'user',
            'staff',
            'messages' => function ($query) {
                $query->latest()->limit(1);
            }
        ])
            ->whereHas('messages');

        if ($user->role === 'staff') {
            $query->where('staff_id', $user->id);
        }

        if ($this->searchTerm) {
            $query->whereHas('user', function ($q) {
                $q->where('full_name', 'like', '%' . $this->searchTerm . '%')
                    ->orWhere('username', 'like', '%' . $this->searchTerm . '%');
            });
        }

        $this->conversations = $query->orderBy('updated_at', 'desc')->get();
    }
    public function updatedSearchTerm()
    {
        $this->loadConversations();
    }

    public function loadStaffUsers()
    {
        // Lấy danh sách nhân viên và người dùng
        $this->staffUsers = User::where('role', 'staff')
            ->with(['invitedUsers' => function ($query) {
                $query->where('role', 'user');
            }])
            ->get();
    }

    public function selectConversation($conversationId)
    {
        $this->selectedConversationId = $conversationId;
        $this->currentPage = 1;
        $this->hasMoreMessages = true;

        $this->loadMessages();
        $this->dispatch('join-conversation-channel', conversationId: $conversationId);
        $this->dispatch('conversation-selected');
    }

    #[On('delete-all-messages')]
    public function deleteAllMessages()
    {
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
                    'sender_id' => $message->sender_id,
                    'conversation_id' => $message->conversation_id,
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
        if (empty(trim($this->messageText)) || !$this->selectedConversation) return;

        try {
            $message = Message::create([
                'conversation_id' => $this->selectedConversation->id,
                'sender_id' => Auth::id(),
                'message' => trim($this->messageText)
            ]);

            $message->load('sender');

            $this->selectedConversation->touch();

            $messageArray = [
                'id' => $message->id,
                'message' => $message->message,
                'sender_id' => $message->sender_id,
                'conversation_id' => $message->conversation_id,
                'created_at' => $message->created_at,
                'sender' => [
                    'id' => $message->sender->id,
                    'full_name' => $message->sender->full_name,
                    'role' => $message->sender->role,
                ]
            ];

            $this->messages[] = $messageArray;

            broadcast(new MessageSent($message))->toOthers();

            $this->messageText = '';
            $this->dispatch('reset-message-input');
            $this->dispatch('scroll-to-bottom');
            $this->loadConversations();
            $this->dispatch('refresh-conversations');
        } catch (\Throwable $e) {
            logger('Send message failed:', ['error' => $e->getMessage()]);
            session()->flash('error', 'Không thể gửi tin nhắn. Vui lòng thử lại.');
        }
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
                    $this->messages[] = $message;
                    $this->dispatch('scroll-to-bottom');
                }
            }
        }

        $this->loadConversations();
        $this->dispatch('refresh-conversations');
    }

    public function render()
    {
        return view('livewire.admin.chat-component');
    }
}
