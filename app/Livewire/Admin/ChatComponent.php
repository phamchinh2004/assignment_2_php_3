<?php

namespace App\Livewire\Admin;

use App\Events\MessageSent;
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

    protected $listeners = ['message-received' => 'messageReceived',];

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

        if ($user->role === 'admin') {
            // Admin có thể thấy tất cả conversations
            $this->conversations = Conversation::with(['user', 'staff', 'messages.sender'])
                ->whereHas('messages')
                ->orderBy('updated_at', 'desc')
                ->get();
        } else {
            // Staff chỉ thấy conversations của họ
            $this->conversations = Conversation::with(['user', 'staff', 'messages.sender'])
                ->where('staff_id', $user->id)
                ->whereHas('messages')
                ->orderBy('updated_at', 'desc')
                ->get();
        }
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

        $conversation = $this->conversations->firstWhere('id', $conversationId);
        if ($conversation) {
            // Load messages with sender relationship
            $this->messages = $conversation->messages()
                ->with('sender')
                ->orderBy('created_at')
                ->get()
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

            $this->dispatch('join-conversation-channel', conversationId: $conversationId);
            $this->dispatch('scroll-to-bottom');
        }
    }

    public function selectUserForChat($userId, $staffId = null)
    {
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

    public function toggleStaffExpansion($staffId)
    {
        if (in_array($staffId, $this->expandedStaff)) {
            $this->expandedStaff = array_diff($this->expandedStaff, [$staffId]);
        } else {
            $this->expandedStaff[] = $staffId;
        }
    }

    public function sendMessage()
    {
        if (empty(trim($this->messageText)) || !$this->selectedConversation) {
            return;
        }

        $message = Message::create([
            'conversation_id' => $this->selectedConversation->id,
            'sender_id' => Auth::id(),
            'message' => trim($this->messageText)
        ]);
        $this->messageText = '';
        // Load sender relationship
        $message->load('sender');

        // Cập nhật timestamp của conversation
        $this->selectedConversation->touch();

        // Thêm message vào mảng hiện tại với format đồng nhất
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

        // Broadcast message qua Reverb
        logger('Gửi message từ admin realtime: ' . $message->id);
        broadcast(new MessageSent($message))->toOthers();


        $this->dispatch('scroll-to-bottom');
        $this->loadConversations();
    }

    public function messageReceived($message)
    {
        logger('Admin nhận được message:',  ['message' => $message]);

        // Đảm bảo dữ liệu là mảng và có các key cần thiết
        if (
            !is_array($message) ||
            !isset($message['id'], $message['conversation_id'], $message['sender_id'])
        ) {
            logger('Message không hợp lệ (Admin):', ['message' => $message]);
            return;
        }

        // Chỉ xử lý nếu đúng conversation đang xem và không phải tin nhắn của chính mình
        if (
            (int) $this->selectedConversationId === (int) $message['conversation_id'] &&
            (int) $message['sender_id'] !== Auth::id()
        ) {
            // Chuyển về collection nếu chưa phải
            if (!$this->messages instanceof \Illuminate\Support\Collection) {
                $this->messages = collect($this->messages);
            }

            // Kiểm tra trùng ID
            if (!$this->messages->contains('id', $message['id'])) {
                $this->messages->push($message);
                $this->dispatch('scroll-to-bottom');
            }
        }

        // Reload danh sách hội thoại (có thể để cập nhật thứ tự, tin mới...)
        $this->loadConversations();
    }


    public function render()
    {
        return view('livewire.admin.chat-component');
    }
}
