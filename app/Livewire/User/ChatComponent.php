<?php

namespace App\Livewire\User;

use App\Events\MessageSent;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\Attributes\On;

class ChatComponent extends Component
{
    public $newMessage = '';
    public $messages;
    public $showBox = false;
    public $conversation;
    public $currentChannel = null;

    protected $listeners = ['message-received' => 'messageReceived', 'toggleChatBox'];

    public function mount()
    {
        $referrerId = Auth::user()->referrer_id;
        $this->conversation = Conversation::firstOrCreate(
            ['user_id' => Auth::user()->id],
            ['staff_id' => $referrerId]
        );

        // Load messages with sender relationship
        $this->messages = collect(Message::where('conversation_id', $this->conversation->id)
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
            }));

        $this->dispatch('join-conversation-channel', conversationId: $this->conversation->id);
    }

    public function sendMessage()
    {
        if (trim($this->newMessage) === '') return;

        $message = Message::create([
            'conversation_id' => $this->conversation->id,
            'sender_id' => Auth::user()->id,
            'message' => trim($this->newMessage),
        ]);

        // Load sender relationship
        $message->load('sender');

        // Add to messages collection with consistent format
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

        if (!$this->messages instanceof Collection) {
            $this->messages = collect($this->messages);
        }

        $this->messages->push($messageArray);

        // Reset message
        $this->newMessage = '';

        logger('Gửi message từ user realtime: ' . $message->id);

        // Dispatch events
        $this->dispatch('message-sent');
        $this->dispatch('scroll-to-bottom');

        // Broadcast to others
        broadcast(new MessageSent($message))->toOthers();
    }

    public function messageReceived($message)
    {
        logger('User nhận được message:',  ['message' => $message]);

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
            if (!$this->messages instanceof \Illuminate\Support\Collection) {
                $this->messages = collect($this->messages);
            }

            // Kiểm tra trùng ID
            if (!$this->messages->contains('id', $message['id'])) {
                $this->messages->push($message);
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

    public function render()
    {
        return view('livewire.user.chat-component');
    }
}
