<?php

namespace App\Livewire\User;

use App\Events\MessageSent;
use App\Events\UserJoinChat;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
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
    public $maxMessageLength = 200;

    protected $listeners = ['message-received' => 'messageReceived', 'toggleChatBox'];

    protected $rules = [
        'newMessage' => 'nullable|string|max:200',
        'selectedImage' => 'nullable|image|max:5120', // 5MB
    ];

    protected function messages()
    {
        return [
            'newMessage.max' => __('livewire.TinNhanKhongDuocVuotQua200KyTu'),
            'selectedImage.image' => __('livewire.ChiChapNhanTepHinhAnh'),
            'selectedImage.max' => __('livewire.HinhAnhKhongDuocVuotQua5MB'),
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
        event(new UserJoinChat($this->conversation->id));
        // $this->dispatch('join-conversation-channel', conversationId: $this->conversation->id);
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

        $messages = [];

        // Gửi ảnh trước nếu có
        if ($this->selectedImage) {
            $imagePath = $this->selectedImage->store('chat-images', 'public');

            $imageMessage = Message::create([
                'conversation_id' => $this->conversation->id,
                'sender_id' => Auth::user()->id,
                'message' => null,
                'type' => 'image',
                'image_path' => $imagePath,
            ]);

            $imageMessage->load('sender');
            $messages[] = $this->formatMessage($imageMessage);

            // Broadcast image message
            broadcast(new MessageSent($imageMessage))->toOthers();
        }

        // Gửi tin nhắn sau nếu có
        if ($this->newMessage && trim($this->newMessage) !== '') {
            $textMessage = Message::create([
                'conversation_id' => $this->conversation->id,
                'sender_id' => Auth::user()->id,
                'message' => trim($this->newMessage),
                'type' => 'text',
                'image_path' => null,
            ]);

            $textMessage->load('sender');
            $messages[] = $this->formatMessage($textMessage);

            // Broadcast text message
            broadcast(new MessageSent($textMessage))->toOthers();
        }

        // Reset form
        $this->newMessage = '';
        $this->selectedImage = null;
        $this->resetErrorBag();
        $this->dispatch('reset-message-input');

        // Add messages to collection
        if (!$this->chatMessages instanceof Collection) {
            $this->chatMessages = collect($this->chatMessages);
        }

        foreach ($messages as $message) {
            $this->chatMessages->push($message);
        }

        // Dispatch events
        $this->dispatch('message-sent');
        $this->dispatch('scroll-to-bottom');
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
