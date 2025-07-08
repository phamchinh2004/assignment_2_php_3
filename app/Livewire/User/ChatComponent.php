<?php

namespace App\Livewire\User;

use App\Events\MessageSent;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ChatComponent extends Component
{
    public $newMessage = '';
    public $chatMessages;
    public $showBox = false;
    public $conversation;
    public $currentChannel = null;
    public $messagesPerLoad = 5; // Số tin nhắn load mỗi lần
    public $offset = 0; // Offset để phân trang
    public $hasMoreMessages = true; // Còn tin nhắn để load không
    public $isLoading = false; // Trạng thái loading
    public $maxMessageLength = 200; // Giới hạn độ dài tin nhắn

    protected $listeners = ['message-received' => 'messageReceived', 'toggleChatBox'];

    protected $rules = [
        'newMessage' => 'required|string|max:200',
    ];

    protected function messages()
    {
        return [
            'newMessage.required' => __('livewire.VuiLongNhapTinNhan'),
            'newMessage.max' => __('livewire.TinNhanKhongDuocVuotQua200KyTu'),
        ];
    }

    public function mount()
    {
        $referrerId = Auth::user()->referrer_id;
        $this->conversation = Conversation::firstOrCreate(
            ['user_id' => Auth::user()->id],
            ['staff_id' => $referrerId]
        );

        // Load tin nhắn mới nhất
        $this->loadLatestMessages();

        $this->dispatch('join-conversation-channel', conversationId: $this->conversation->id);
    }

    public function loadLatestMessages()
    {
        $messages = Message::where('conversation_id', $this->conversation->id)
            ->with('sender')
            ->orderBy('created_at', 'desc')
            ->limit($this->messagesPerLoad)
            ->get()
            ->reverse() // Đảo ngược để tin nhắn mới nhất ở dưới
            ->map(function ($message) {
                return $this->formatMessage($message);
            });

        $this->chatMessages = collect($messages);
        $this->offset = $messages->count();

        // Kiểm tra còn tin nhắn cũ hơn không
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
            // Thêm tin nhắn cũ vào đầu danh sách
            $this->chatMessages = $olderMessages->concat($this->chatMessages);
            $this->offset += $olderMessages->count();

            // Kiểm tra còn tin nhắn cũ hơn không
            $totalMessages = Message::where('conversation_id', $this->conversation->id)->count();
            $this->hasMoreMessages = $totalMessages > $this->offset;
        } else {
            $this->hasMoreMessages = false;
        }

        $this->isLoading = false;

        // Dispatch event để giữ vị trí scroll
        $this->dispatch('messages-loaded');
    }

    private function formatMessage($message)
    {
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
    }

    public function sendMessage()
    {
        $this->validate();

        if (trim($this->newMessage) === '') return;

        // Kiểm tra độ dài tin nhắn
        if (strlen(trim($this->newMessage)) > $this->maxMessageLength) {
            $this->addError('newMessage', __('livewire.TinNhanKhongDuocVuotQua') . $this->maxMessageLength . __('livewire.KyTu'));
            return;
        }

        $message = Message::create([
            'conversation_id' => $this->conversation->id,
            'sender_id' => Auth::user()->id,
            'message' => trim($this->newMessage),
        ]);

        $this->newMessage = '';
        $this->resetErrorBag();
        $this->dispatch('reset-message-input');

        // Load sender relationship
        $message->load('sender');

        // Add to messages collection with consistent format
        $messageArray = $this->formatMessage($message);

        if (!$this->chatMessages instanceof Collection) {
            $this->chatMessages = collect($this->chatMessages);
        }

        $this->chatMessages->push($messageArray);

        logger('Gửi message từ user realtime: ' . $message->id);

        // Dispatch events
        $this->dispatch('message-sent');
        $this->dispatch('scroll-to-bottom');

        // Broadcast to others
        broadcast(new MessageSent($message))->toOthers();
    }

    public function messageReceived($message)
    {
        logger('User nhận được message:', ['message' => $message]);

        // Đảm bảo dữ liệu là mảng và có các key cần thiết
        if (
            !is_array($message) ||
            !isset($message['id'], $message['conversation_id'], $message['sender_id'])
        ) {
            logger('Message không hợp lệ:', ['message' => $message]);
            return;
        }

        // Chỉ xử lý nếu đúng conversation và không phải tin nhắn của chính mình
        if (
            (int) $message['conversation_id'] === (int) $this->conversation->id &&
            (int) $message['sender_id'] !== Auth::id()
        ) {
            // Chuyển về collection nếu chưa có
            if (!$this->chatMessages instanceof Collection) {
                $this->chatMessages = collect($this->chatMessages);
            }

            // Kiểm tra trùng ID
            if (!$this->chatMessages->contains('id', $message['id'])) {
                $this->chatMessages->push($message);
                $this->dispatch('scroll-to-bottom');
            }
        }
    }

    public function closeBox()
    {
        $this->showBox = false;
    }

    public function toggleBox()
    {
        $this->showBox = !$this->showBox;

        // Auto scroll when opening chat
        if ($this->showBox) {
            $this->dispatch('scroll-to-bottom');
        }
    }

    public function scrollToBottom()
    {
        $this->dispatch('scroll-to-bottom');
    }

    public function getRemainingCharacters()
    {
        return $this->maxMessageLength - strlen($this->newMessage);
    }

    public function render()
    {
        return view('livewire.user.chat-component');
    }
}
