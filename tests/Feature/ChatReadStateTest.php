<?php

namespace Tests\Feature;

use App\Events\ConversationRead;
use App\Events\MessageRead;
use App\Livewire\Admin\ChatComponent;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Services\AdminHeaderService;
use App\Services\ChatReadService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ChatReadStateTest extends TestCase
{
    private Conversation $conversation;

    public function createApplication(): Application
    {
        $app = require __DIR__ . '/../../bootstrap/app.php';
        $app->make(Kernel::class)->bootstrap();

        return $app;
    }

    protected function setUp(): void
    {
        if (!in_array('sqlite', \PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('Enable pdo_sqlite to run the isolated chat read-state database tests.');
        }

        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        config()->set('database.connections.sqlite.foreign_key_constraints', true);
        DB::purge('sqlite');

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->string('username');
            $table->string('role');
            $table->string('status');
            $table->timestamps();
        });
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('staff_id')->constrained('users');
            $table->uuid('public_id');
            $table->timestamps();
        });
        Schema::create('manager_settings', function (Blueprint $table) {
            $table->id();
            $table->string('manager_code');
        });
        Schema::create('user_manager_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('manager_setting_id');
            $table->boolean('is_active');
        });
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained();
            $table->foreignId('sender_id')->constrained('users');
            $table->text('message');
            $table->string('type')->default('text');
            $table->string('kind')->default('text');
            $table->string('image_path')->nullable();
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->json('reference_payload')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamps();
        });
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
        Schema::create('wallet_balance_histories', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->string('status');
        });
        Schema::create('lucky_wheel_spins', function (Blueprint $table) {
            $table->id();
            $table->string('reward_type');
            $table->string('reward_status');
        });
        Schema::create('order_reports', function (Blueprint $table) {
            $table->id();
            $table->string('status');
        });

        (require __DIR__ . '/../../database/migrations/2026_09_23_140000_create_message_reads_table.php')->up();

        DB::table('users')->insert([
            ['id' => 1, 'full_name' => 'Customer', 'username' => 'customer', 'role' => User::ROLE_MEMBER, 'status' => 'activated'],
            ['id' => 2, 'full_name' => 'Assigned staff', 'username' => 'staff', 'role' => User::ROLE_STAFF, 'status' => 'activated'],
            ['id' => 3, 'full_name' => 'Admin', 'username' => 'admin', 'role' => User::ROLE_ADMIN, 'status' => 'activated'],
            ['id' => 4, 'full_name' => 'Owner', 'username' => 'owner', 'role' => User::ROLE_OWNER, 'status' => 'activated'],
            ['id' => 5, 'full_name' => 'Replacement staff', 'username' => 'replacement', 'role' => User::ROLE_STAFF, 'status' => 'activated'],
        ]);
        DB::table('conversations')->insert([
            'id' => 1,
            'user_id' => 1,
            'staff_id' => 2,
            'public_id' => '11111111-1111-4111-8111-111111111111',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('messages')->insert([
            'id' => 10,
            'conversation_id' => 1,
            'sender_id' => 1,
            'message' => 'Please help',
            // Old shared receipts are unreliable: an observer could have set this flag.
            'is_read' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->conversation = Conversation::findOrFail(1);
    }

    public function test_owner_and_admin_reads_do_not_clear_assigned_staff_unread(): void
    {
        $reads = app(ChatReadService::class);
        $message = Message::findOrFail(10);

        $this->assertSame(1, $reads->markConversationRead($this->conversation, 4));
        $this->assertSame(1, $reads->markConversationRead($this->conversation, 3));
        $this->assertSame(0, $reads->markConversationRead($this->conversation, 4));
        $this->assertSame(1, Message::unreadFor(2)->count());
        $this->assertFalse($reads->sentReadStatuses(collect([$message]), $this->conversation)[10]);
        $this->assertTrue($message->fresh()->is_read); // The legacy flag stays untouched.

        $this->assertTrue($reads->markMessageRead(10, $this->conversation, 2));
        $this->assertFalse($reads->markMessageRead(10, $this->conversation, 2));
        $this->assertSame(0, Message::unreadFor(2)->count());
        $this->assertTrue($reads->sentReadStatuses(collect([$message]), $this->conversation)[10]);
    }

    public function test_header_badges_are_specific_to_the_signed_in_account(): void
    {
        app(ChatReadService::class)->markConversationRead($this->conversation, 4);

        $staffState = app(AdminHeaderService::class)->state(User::findOrFail(2));
        $ownerState = app(AdminHeaderService::class)->state(User::findOrFail(4));

        $this->assertSame(1, $staffState['messages']['unread_count']);
        $this->assertSame(1, $staffState['messages']['conversations'][0]['unread_count']);
        $this->assertSame(0, $ownerState['messages']['unread_count']);
        $this->assertSame(0, $ownerState['messages']['conversations'][0]['unread_count']);
    }

    public function test_header_preview_only_prefixes_the_signed_in_users_messages(): void
    {
        $header = app(AdminHeaderService::class);
        $staff = User::findOrFail(2);
        $owner = User::findOrFail(4);

        $this->assertSame('Please help', $header->state($staff)['messages']['conversations'][0]['preview']);

        DB::table('messages')->insert([
            'id' => 11,
            'conversation_id' => 1,
            'sender_id' => $staff->id,
            'message' => 'Here is an answer',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertSame('Bạn: Here is an answer', $header->state($staff)['messages']['conversations'][0]['preview']);
        $this->assertSame('Here is an answer', $header->state($owner)['messages']['conversations'][0]['preview']);

        DB::table('messages')->insert([
            'id' => 12,
            'conversation_id' => 1,
            'sender_id' => $owner->id,
            'message' => 'I will take over',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertSame('Bạn: I will take over', $header->state($owner)['messages']['conversations'][0]['preview']);
        $this->assertSame('I will take over', $header->state($staff)['messages']['conversations'][0]['preview']);
    }

    public function test_customer_receipt_requires_customer_to_read_staff_reply(): void
    {
        DB::table('messages')->insert([
            'id' => 11,
            'conversation_id' => 1,
            'sender_id' => 2,
            'message' => 'Here is an answer',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $reads = app(ChatReadService::class);
        $reply = Message::findOrFail(11);

        $reads->markConversationRead($this->conversation, 4);
        $this->assertSame(1, Message::unreadFor(1)->count());
        $this->assertFalse($reads->sentReadStatuses(collect([$reply]), $this->conversation)[11]);

        $this->assertSame(1, $reads->markConversationRead($this->conversation, 1));
        $this->assertSame(0, Message::unreadFor(1)->count());
        $this->assertTrue($reads->sentReadStatuses(collect([$reply]), $this->conversation)[11]);
        $this->assertFalse($reads->markMessageRead(11, $this->conversation, 2));
    }

    public function test_reassignment_uses_the_new_operator_read_receipt(): void
    {
        $reads = app(ChatReadService::class);
        $message = Message::findOrFail(10);
        $reads->markMessageRead(10, $this->conversation, 2);

        $this->conversation->update(['staff_id' => 5]);
        $this->assertSame(1, Message::unreadFor(5)->count());
        $this->assertFalse($reads->sentReadStatuses(collect([$message]), $this->conversation)[10]);

        $reads->markConversationRead($this->conversation, 5);
        $this->assertSame(0, Message::unreadFor(5)->count());
        $this->assertTrue($reads->sentReadStatuses(collect([$message]), $this->conversation)[10]);
    }

    public function test_opening_chat_updates_visible_receipts_immediately(): void
    {
        Event::fake([ConversationRead::class, MessageRead::class]);
        $this->actingAs(User::findOrFail(2));
        $component = $this->chatComponent();
        $component->conversations = collect([$this->conversation]);

        $component->selectConversation(1);

        $this->assertTrue($component->messages[0]['is_read']);
        $this->assertDatabaseHas('message_reads', ['message_id' => 10, 'user_id' => 2]);
    }

    public function test_new_customer_message_is_shown_as_read_by_assigned_staff(): void
    {
        Event::fake([ConversationRead::class, MessageRead::class]);
        $this->actingAs(User::findOrFail(2));
        $component = $this->chatComponent();
        $component->conversations = collect([$this->conversation]);
        $component->selectedConversationId = 1;

        $component->messageReceived(Message::with('sender')->findOrFail(10)->toArray());

        $this->assertTrue($component->messages[0]['is_read']);
        $this->assertDatabaseHas('message_reads', ['message_id' => 10, 'user_id' => 2]);
    }

    public function test_observer_opening_chat_does_not_show_customer_message_as_read_by_staff(): void
    {
        Event::fake([ConversationRead::class, MessageRead::class]);
        $this->actingAs(User::findOrFail(4));
        $component = $this->chatComponent();
        $component->conversations = collect([$this->conversation]);

        $component->selectConversation(1);

        $this->assertFalse($component->messages[0]['is_read']);
        $this->assertDatabaseMissing('message_reads', ['message_id' => 10, 'user_id' => 2]);
    }

    public function test_unassigned_staff_cannot_open_chat_or_mark_messages_read(): void
    {
        $this->actingAs(User::findOrFail(5));
        $component = $this->chatComponent();
        $component->conversations = collect();

        foreach (['selectConversation' => [1], 'markSingleMessageAsRead' => [10, 1]] as $method => $arguments) {
            try {
                $component->$method(...$arguments);
                $this->fail('Unassigned staff should be denied access.');
            } catch (HttpException $exception) {
                $this->assertSame(403, $exception->getStatusCode());
            }
        }

        $this->assertSame([], $component->messages);
        $this->assertDatabaseMissing('message_reads', ['message_id' => 10, 'user_id' => 5]);
    }

    private function chatComponent(): ChatComponent
    {
        $component = new ChatComponent;
        $component->getAttributes()
            ->whereInstanceOf(\Livewire\Attributes\Computed::class)
            ->each(fn ($attribute) => $attribute->boot());

        return $component;
    }
}
