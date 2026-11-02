<?php

namespace Tests\Feature;

use App\Livewire\Admin\ChatComponent;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;

class AdminChatUnreadJumpTest extends TestCase
{
    public function createApplication(): Application
    {
        $app = require __DIR__ . '/../../bootstrap/app.php';
        $app->make(Kernel::class)->bootstrap();

        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        config()->set('broadcasting.default', 'null');
        DB::purge('sqlite');

        Message::unguard();
        Event::fake([\App\Events\ConversationRead::class]);

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('full_name')->nullable();
            $table->string('username')->nullable();
            $table->string('email')->nullable();
            $table->string('role');
            $table->string('avatar')->nullable();
            $table->string('status')->default('activated');
            $table->unsignedBigInteger('referrer_id')->nullable();
            $table->timestamp('last_seen')->nullable();
            $table->timestamps();
        });

        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->string('public_id')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('staff_id');
            $table->string('status')->default('open');
            $table->unsignedBigInteger('last_escalated_reply_id')->nullable();
            $table->timestamp('last_escalated_at')->nullable();
            $table->timestamps();
        });

        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('conversation_id');
            $table->unsignedBigInteger('sender_id');
            $table->text('message')->nullable();
            $table->string('type')->default('text');
            $table->string('kind')->nullable();
            $table->string('image_path')->nullable();
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('reference_payload')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamps();
        });

        Schema::create('message_reads', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('message_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamp('read_at')->nullable();
        });

        Schema::create('chat_quick_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('message_key')->nullable();
            $table->text('content')->nullable();
            $table->boolean('is_deleted')->default(false);
            $table->timestamps();
        });

        Schema::create('frozen_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->boolean('is_frozen')->default(false);
            $table->decimal('penalty_amount', 16, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('conversation_notification_mutes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('conversation_id');
            $table->timestamp('muted_until')->nullable();
            $table->timestamps();
        });

        Schema::create('wallet_balance_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->double('value')->default(0);
            $table->double('initial_balance')->default(0);
            $table->string('type')->default('deposit');
            $table->string('status')->default('completed');
            $table->timestamps();
        });

        Schema::create('transaction_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->double('amount')->default(0);
            $table->string('type')->default('deposit');
            $table->timestamps();
        });

        DB::table('users')->insert([
            ['id' => 1, 'full_name' => 'Customer', 'username' => 'cust', 'email' => 'customer@example.com', 'role' => User::ROLE_MEMBER, 'referrer_id' => 2, 'status' => 'activated'],
            ['id' => 2, 'full_name' => 'Staff 1', 'username' => 'staff1', 'email' => 'staff@example.com', 'role' => User::ROLE_STAFF, 'referrer_id' => 5, 'status' => 'activated'],
            ['id' => 5, 'full_name' => 'Owner', 'username' => 'owner', 'email' => 'owner@example.com', 'role' => User::ROLE_OWNER, 'referrer_id' => null, 'status' => 'activated'],
        ]);

        DB::table('conversations')->insert([
            ['id' => 1, 'public_id' => '11111111-1111-1111-1111-111111111111', 'user_id' => 1, 'staff_id' => 2, 'status' => 'open', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function test_owner_opening_conversation_detects_first_unread_message_of_customer(): void
    {
        $owner = User::find(5);
        $this->actingAs($owner);

        // Khách hàng gửi 5 tin nhắn chưa đọc
        for ($i = 1; $i <= 5; $i++) {
            Message::create([
                'id' => 100 + $i,
                'conversation_id' => 1,
                'sender_id' => 1,
                'message' => "Tin nhắn $i",
                'type' => 'text',
                'created_at' => now()->subMinutes(10 - $i),
            ]);
        }

        Livewire::test(ChatComponent::class)
            ->call('selectConversation', 1)
            ->assertSet('firstUnreadMessageId', 101)
            ->assertSet('currentChatUnreadCount', 5);
    }

    public function test_jump_to_first_unread_message_dispatches_scroll_event(): void
    {
        $owner = User::find(5);
        $this->actingAs($owner);

        Message::create([
            'id' => 201,
            'conversation_id' => 1,
            'sender_id' => 1,
            'message' => "Tin nhắn chưa đọc",
            'type' => 'text',
            'created_at' => now()->subMinutes(5),
        ]);

        Livewire::test(ChatComponent::class)
            ->call('selectConversation', 1)
            ->assertSet('firstUnreadMessageId', 201)
            ->call('jumpToFirstUnreadMessage')
            ->assertDispatched('scroll-to-unread');
    }

    public function test_conversation_without_unread_messages_has_null_first_unread(): void
    {
        $owner = User::find(5);
        $this->actingAs($owner);

        Message::create([
            'id' => 301,
            'conversation_id' => 1,
            'sender_id' => 1,
            'message' => "Tin nhắn đã đọc",
            'type' => 'text',
            'created_at' => now()->subMinutes(15),
        ]);

        // Đã đọc bởi owner và staff
        DB::table('message_reads')->insert([
            ['message_id' => 301, 'user_id' => 5, 'read_at' => now()],
            ['message_id' => 301, 'user_id' => 2, 'read_at' => now()],
        ]);

        Livewire::test(ChatComponent::class)
            ->call('selectConversation', 1)
            ->assertSet('firstUnreadMessageId', null)
            ->assertSet('currentChatUnreadCount', 0);
    }
}
