<?php

namespace Tests\Feature;

use App\Events\ConversationRead;
use App\Events\MessageRead;
use App\Livewire\Admin\ChatComponent;
use App\Livewire\User\ChatComponent as UserChatComponent;
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
use Livewire\Livewire;
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
            $table->string('avatar')->nullable();
            $table->string('role');
            $table->string('status');
            $table->unsignedBigInteger('referrer_id')->nullable();
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
            $table->string('manager_name')->nullable();
            $table->unsignedBigInteger('parent_manager_setting_id')->nullable();
            $table->timestamps();
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
            $table->unsignedBigInteger('user_id')->nullable();
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
        Schema::create('bug_reports', function (Blueprint $table) {
            $table->id();
            $table->string('status');
        });
        Schema::create('chat_quick_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('message_key', 64);
            $table->text('content');
            $table->boolean('is_deleted')->default(false);
            $table->timestamps();
        });
        Schema::create('frozen_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->boolean('is_frozen')->default(true);
            $table->decimal('penalty_amount', 10, 2)->nullable();
        });
        Schema::create('conversation_notification_mutes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('conversation_id');
            $table->timestamp('muted_until')->nullable();
            $table->timestamps();
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

    public function test_user_sent_message_is_immediately_rendered_after_send(): void
    {
        config()->set('chat.quick_replies.enabled', false);
        config()->set('chat.auto_reply.enabled', false);
        Event::fake();
        $this->actingAs(User::findOrFail(1));

        $component = Livewire::test(UserChatComponent::class)
            ->set('newMessage', 'Tin nhắn vừa gửi')
            ->call('sendMessage');

        $component->assertSet('newMessage', '')
            ->assertSee('Tin nhắn vừa gửi');

        $this->assertDatabaseHas('messages', [
            'conversation_id' => 1,
            'sender_id' => 1,
            'message' => 'Tin nhắn vừa gửi',
        ]);
        $this->assertSame('Tin nhắn vừa gửi', collect($component->get('chatMessages'))->first()['message']);
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

    public function test_notification_totals_include_items_beyond_the_preview_and_are_scoped_to_the_account(): void
    {
        $staff = User::findOrFail(2);
        $method = new \ReflectionMethod(AdminHeaderService::class, 'notifications');
        $header = app(AdminHeaderService::class);
        $empty = $method->invoke($header, $staff, 1);
        $this->assertSame(0, $empty['total_count']);
        $this->assertSame(0, $empty['unread_count']);
        $this->assertSame([], $empty['items']);

        foreach ([null, now(), null] as $readAt) {
            $staff->notifications()->create([
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'type' => 'test', 'data' => ['title' => 'Notice'], 'read_at' => $readAt,
            ]);
        }
        User::findOrFail(4)->notifications()->create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'type' => 'test', 'data' => [], 'read_at' => null,
        ]);

        DB::enableQueryLog();
        try {
            $state = $method->invoke($header, $staff, 1);
            $countQueries = collect(DB::getQueryLog())->filter(
                fn ($query) => str_contains(strtolower($query['query']), 'count(')
            );
        } finally {
            DB::disableQueryLog();
            DB::flushQueryLog();
        }
        $this->assertSame(3, $state['total_count']);
        $this->assertSame(2, $state['unread_count']);
        $this->assertCount(1, $state['items']);
        $this->assertCount(1, $countQueries);
        $this->assertStringNotContainsString('order by', strtolower($countQueries->first()['query']));
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

    public function test_header_customer_avatar_uses_the_stored_image_or_default_and_keeps_chat_visibility(): void
    {
        $method = new \ReflectionMethod(AdminHeaderService::class, 'messages');
        $header = app(AdminHeaderService::class);
        $staff = User::findOrFail(2);
        $state = $method->invoke($header, $staff, 6);
        $this->assertSame(asset('images/default-avatar-gray.svg'), $state['conversations'][0]['participant_avatar_url']);
        DB::table('users')->where('id', 1)->update(['avatar' => 'uploads/avatars/chat.jpg']);
        $state = $method->invoke($header, $staff, 6);
        $this->assertSame(asset('storage/uploads/avatars/chat.jpg'), $state['conversations'][0]['participant_avatar_url']);
        $this->assertSame([], $method->invoke($header, User::findOrFail(5), 6)['conversations']);
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
        DB::table('users')->where('id', 1)->update(['avatar' => 'avatars/customer.png']);
        Event::fake([ConversationRead::class, MessageRead::class]);
        $this->actingAs(User::findOrFail(2));
        $component = $this->chatComponent();
        $component->conversations = collect([$this->conversation]);

        $component->selectConversation(1);

        $this->assertSame('avatars/customer.png', $component->messages[0]['sender']['avatar']);
        $this->assertSame('avatars/customer.png', (new \App\Events\MessageSent(10))->broadcastWith()['message']['sender']['avatar']);

        $this->assertTrue($component->messages[0]['is_read']);
        $this->assertDatabaseHas('message_reads', ['message_id' => 10, 'user_id' => 2]);
    }

    public function test_selecting_another_conversation_preserves_other_sidebar_badges(): void
    {
        Event::fake([ConversationRead::class, MessageRead::class]);
        DB::table('users')->insert([
            'id' => 20,
            'full_name' => 'Second customer',
            'username' => 'second_customer',
            'role' => User::ROLE_MEMBER,
            'status' => 'activated',
        ]);
        DB::table('conversations')->insert([
            'id' => 2,
            'user_id' => 20,
            'staff_id' => 2,
            'public_id' => '22222222-2222-4222-8222-222222222222',
            'created_at' => now(),
            'updated_at' => now()->addSecond(),
        ]);
        DB::table('messages')->insert([
            'id' => 20,
            'conversation_id' => 2,
            'sender_id' => 20,
            'message' => 'Second conversation',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs(User::findOrFail(2));
        $component = Livewire::test(ChatComponent::class);

        $before = collect($component->get('conversations'))->firstWhere('id', 1);
        $this->assertSame(1, (int) $before->unread_count);
        $this->assertTrue((bool) $before->awaiting_reply);

        $component->call('selectConversation', 2);

        $databaseState = Conversation::withInboxStateFor(2)->findOrFail(1);
        $this->assertSame(1, (int) $databaseState->unread_count);
        $this->assertTrue((bool) $databaseState->awaiting_reply);

        $untouched = collect($component->get('conversations'))->firstWhere('id', 1);
        $this->assertSame(1, (int) $untouched->unread_count);
        $this->assertTrue((bool) $untouched->awaiting_reply);
        $this->assertStringContainsString('conversation-awaiting', $component->html());
        $this->assertStringContainsString('conversation-unread', $component->html());
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

    public function test_admin_chat_lists_header_and_dispatch_only_include_managed_and_shared_staff(): void
    {
        DB::table('users')->insert([
            'id' => 6, 'full_name' => 'Other admin', 'username' => 'other_admin',
            'role' => User::ROLE_ADMIN, 'status' => 'activated',
        ]);
        DB::table('users')->where('id', 1)->update(['referrer_id' => 2]);
        DB::table('users')->where('id', 2)->update(['referrer_id' => 3]);
        DB::table('users')->where('id', 5)->update(['referrer_id' => 6]);
        $permissionId = DB::table('manager_settings')->insertGetId(['manager_code' => config('authorization.capabilities.chats_view_all')]);
        foreach ([3, 6] as $adminId) {
            DB::table('user_manager_settings')->insert([
                'user_id' => $adminId, 'manager_setting_id' => $permissionId, 'is_active' => true,
            ]);
        }
        $headerMessages = new \ReflectionMethod(AdminHeaderService::class, 'messages');
        $authorization = app(\App\Services\AuthorizationService::class);
        $this->actingAs(User::findOrFail(3));
        $component = $this->chatComponent();
        $component->loadStaffUsersAlternative();
        $component->loadConversations();
        $this->assertSame([2], array_column($component->staffUsers, 'id'));
        $this->assertSame([1], $component->conversations->pluck('id')->all());
        $component->dispatchConversationId = 1;
        $this->assertSame([2], $component->getDispatchCandidatesProperty()->pluck('id')->all());
        $this->assertFalse($authorization->canReceiveDispatchedConversation(auth()->user(), User::findOrFail(5)));
        $recipients = app(\App\Services\ManagementRecipientResolver::class);
        $this->assertSame([2, 3, 4], $recipients->forUser(User::findOrFail(1))->pluck('id')->all());
        $this->assertSame(
            ['private-chat.conversation.1', 'private-staff.2', 'private-staff.3', 'private-staff.4'],
            array_map(fn ($channel) => $channel->name, (new \App\Events\MessageSent(10))->broadcastOn())
        );

        $this->actingAs(User::findOrFail(6));
        $component = $this->chatComponent();
        $component->loadStaffUsersAlternative();
        $component->loadConversations();
        $this->assertSame([5], array_column($component->staffUsers, 'id'));
        $this->assertCount(0, $component->conversations);
        $this->assertSame([], $headerMessages->invoke(app(AdminHeaderService::class), auth()->user(), 6)['conversations']);
        try {
            $component->selectConversation(1);
            $this->fail('Other admin team conversations must be forbidden.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }

        DB::table('users')->where('id', 2)->update(['referrer_id' => null]);
        $component->loadStaffUsersAlternative();
        $component->loadConversations();
        $this->assertSame([2, 5], array_column($component->staffUsers, 'id'));
        $this->assertSame([1], $component->conversations->pluck('id')->all());
        $this->assertTrue($authorization->canViewConversation(auth()->user(), Conversation::findOrFail(1)));
        $this->assertCount(1, $headerMessages->invoke(app(AdminHeaderService::class), auth()->user(), 6)['conversations']);
        $this->assertSame([2, 3, 6, 4], $recipients->forUser(User::findOrFail(1))->pluck('id')->all());

        DB::table('user_manager_settings')->where('user_id', 6)->update(['is_active' => false]);
        $this->actingAs(User::findOrFail(6));
        $component->loadStaffUsersAlternative();
        $component->loadConversations();
        $this->assertSame([], $component->staffUsers);
        $this->assertCount(0, $component->conversations);
        $this->assertFalse($authorization->canViewConversation(auth()->user(), Conversation::findOrFail(1)));
        $this->assertSame([2, 3, 4], $recipients->forUser(User::findOrFail(1))->pluck('id')->all());
    }

    public function test_inbox_pending_badge_and_three_personal_unread_counts_are_independent(): void
    {
        foreach ([11, 12] as $id) {
            DB::table('messages')->insert(['id' => $id, 'conversation_id' => 1, 'sender_id' => 1, 'message' => 'New message']);
        }
        $state = fn ($viewer) => Conversation::withInboxStateFor($viewer)->findOrFail(1);
        foreach ([2, 3, 4] as $viewer) {
            $this->assertSame(3, $state($viewer)->unread_count);
            $this->assertTrue((bool) $state($viewer)->awaiting_reply);
        }
        $reads = app(ChatReadService::class);
        $reads->markConversationRead($this->conversation, 4);
        $this->assertSame(0, $state(4)->unread_count);
        $this->assertTrue((bool) $state(4)->awaiting_reply);
        $this->assertSame(3, $state(2)->unread_count);
        $this->assertSame(3, $state(3)->unread_count);
        $reads->markConversationRead($this->conversation, 2);
        foreach ([2, 3, 4] as $viewer) $this->assertFalse((bool) $state($viewer)->awaiting_reply);
        $this->assertSame(3, $state(3)->unread_count);
        $this->assertSame(0, $state(2)->unread_count);

        // Reassignment follows the new operator's receipt, without rewriting history.
        DB::table('conversations')->where('id', 1)->update(['staff_id' => 5]);
        $this->assertTrue((bool) $state(5)->awaiting_reply);
        $this->assertSame(3, $state(5)->unread_count);
    }

    public function test_chat_context_requires_permission_and_conversation_scope(): void
    {
        $service = app(\App\Services\ChatContextService::class);
        DB::table('messages')->where('id', 10)->update(['image_path' => 'chat/visible.png']);
        $context = $service->forConversation(User::findOrFail(2), $this->conversation);
        $this->assertCount(1, $context['images']);
        $this->assertFalse($context['can_view_context']);
        $this->assertSame([], $context['orders']);
        $this->assertSame([], $context['transactions']);

        $permission = DB::table('manager_settings')->insertGetId(['manager_code' => 'chats.view-context']);
        foreach ([2, 5] as $id) DB::table('user_manager_settings')->insert(['user_id' => $id, 'manager_setting_id' => $permission, 'is_active' => true]);
        $this->mock(\App\Services\ChatReferenceService::class, function ($mock) {
            $mock->shouldReceive('recentOrders')->once()->with(1, 8)->andReturn([['id' => 91]]);
            $mock->shouldReceive('recentTransactions')->once()->with(1, 8)->andReturn([['id' => 92]]);
        });
        $context = $service->forConversation(User::findOrFail(2), $this->conversation);
        $this->assertTrue($context['can_view_context']);
        $this->assertSame([['id' => 91]], $context['orders']);
        $this->assertSame([['id' => 92]], $context['transactions']);
        foreach ([1, 5] as $actor) {
            try {
                $service->forConversation(User::findOrFail($actor), $this->conversation);
                $this->fail('Customer or unrelated staff must not access admin context.');
            } catch (HttpException $exception) {
                $this->assertSame(403, $exception->getStatusCode());
            }
        }
        DB::table('user_manager_settings')->where('user_id', 2)->update(['is_active' => false]);
        $this->assertFalse($service->forConversation(User::findOrFail(2), $this->conversation)['can_view_context']);
    }

    public function test_typing_is_scoped_throttled_and_carries_authenticated_identity(): void
    {
        \Illuminate\Support\Facades\Cache::setDefaultDriver('array');
        Event::fake([\App\Events\ChatTyping::class]);
        $service = app(\App\Services\ChatTypingService::class);
        $actor = User::findOrFail(2);
        $service->update($actor, 1, true);
        $service->update($actor, 1, true);
        $service->update($actor, 1, false);
        $service->update(User::findOrFail(1), 1, true);
        Event::assertDispatchedTimes(\App\Events\ChatTyping::class, 3);
        Event::assertDispatched(\App\Events\ChatTyping::class, fn ($event) => $event->userId === 2 && $event->name === 'Assigned staff' && $event->broadcastOn()[0]->name === 'private-chat.conversation.1');
        $this->expectException(HttpException::class);
        $service->update(User::findOrFail(5), 1, true);
    }

    public function test_typing_endpoint_is_independent_from_livewire_chat_state_and_keeps_conversation_scope(): void
    {
        \Illuminate\Support\Facades\Cache::setDefaultDriver('array');
        Event::fake([\App\Events\ChatTyping::class]);
        $this->withoutMiddleware(\App\Http\Middleware\UpdateLastSeen::class);

        $this->actingAs(User::findOrFail(1))
            ->postJson(route('chat.typing'), ['conversation_id' => 1, 'typing' => true])
            ->assertNoContent();

        Event::assertDispatched(\App\Events\ChatTyping::class, fn ($event) => $event->userId === 1 && $event->typing === true);

        $this->actingAs(User::findOrFail(5))
            ->postJson(route('chat.typing'), ['conversation_id' => 1, 'typing' => true])
            ->assertForbidden();
    }

    public function test_context_permission_migration_repairs_group_without_changing_grants(): void
    {
        $permission = DB::table('manager_settings')->insertGetId(['manager_code' => 'chats.view-context']);
        foreach ([2 => false, 3 => true] as $id => $active) DB::table('user_manager_settings')->insert(['user_id' => $id, 'manager_setting_id' => $permission, 'is_active' => $active]);
        $before = DB::table('user_manager_settings')->orderBy('id')->get()->toArray();
        $migration = require __DIR__.'/../../database/migrations/2026_10_04_000002_add_chat_context_permission.php';
        $migration->up();
        $migration->up();
        $group = DB::table('manager_settings')->where('manager_code', 'permission-group.chats')->sole();
        $this->assertNull($group->parent_manager_setting_id);
        $this->assertSame($group->id, DB::table('manager_settings')->find($permission)->parent_manager_setting_id);
        $this->assertEquals($before, DB::table('user_manager_settings')->orderBy('id')->get()->toArray());
        $this->assertSame(1, DB::table('manager_settings')->where('manager_code', 'chats.view-context')->count());
    }

    public function test_owner_clearing_messages_keeps_conversation_without_backend_success_notice(): void
    {
        Event::fake([\App\Events\ConversationCleared::class]);
        $this->actingAs(User::findOrFail(4));
        $component = $this->chatComponent();
        $component->conversations = collect([$this->conversation]);
        $component->selectedConversationId = 1;
        $component->messages = [['id' => 10, 'message' => 'Please help']];

        $this->assertTrue($component->deleteAllMessages());

        $this->assertDatabaseHas('conversations', ['id' => 1]);
        $this->assertDatabaseMissing('messages', ['conversation_id' => 1]);
        $this->assertSame(1, $component->selectedConversationId);
        $this->assertSame([], $component->messages);
        $notices = collect(\Livewire\store($component)->get('dispatched'))
            ->map(fn ($event) => $event->serialize())
            ->where('name', 'app-dialog')->values();
        $this->assertCount(0, $notices);
    }

    public function test_clearing_without_selection_returns_failure_and_preserves_error_notice(): void
    {
        $this->actingAs(User::findOrFail(4));
        $component = $this->chatComponent();

        $this->assertFalse($component->deleteAllMessages());
        $this->assertDatabaseHas('messages', ['id' => 10]);
        $notices = collect(\Livewire\store($component)->get('dispatched'))
            ->map(fn ($event) => $event->serialize())
            ->where('name', 'app-dialog')->values();
        $this->assertCount(1, $notices);
        $this->assertSame('error', $notices[0]['params'][0]['type']);
    }

    public function test_owner_can_delete_selected_conversation_and_its_messages(): void
    {
        Event::fake([\App\Events\ConversationCleared::class]);
        $this->actingAs(User::findOrFail(4));
        $component = $this->chatComponent();
        $component->conversations = collect([$this->conversation]);
        $component->selectedConversationId = 1;
        $component->messages = [['id' => 10, 'message' => 'Please help']];

        $this->assertTrue($component->deleteConversation());
        $this->assertDatabaseMissing('conversations', ['id' => 1]);
        $this->assertDatabaseMissing('messages', ['conversation_id' => 1]);
        $this->assertNull($component->selectedConversationId);
        $this->assertSame([], $component->messages);
        Event::assertDispatched(\App\Events\ConversationCleared::class);
    }

    public function test_admin_and_staff_cannot_delete_conversations(): void
    {
        Event::fake([\App\Events\ConversationCleared::class]);
        foreach ([2, 3, 5] as $viewerId) {
            $this->actingAs(User::findOrFail($viewerId));
            $component = $this->chatComponent();
            $component->conversations = collect([$this->conversation]);
            $component->selectedConversationId = 1;

            $this->assertFalse($component->deleteConversation());
            $this->assertFalse($component->deleteAllMessages());
            $this->assertDatabaseHas('conversations', ['id' => 1]);
            $this->assertDatabaseHas('messages', ['id' => 10]);
            $this->assertSame(1, $component->selectedConversationId);
            $this->assertEmpty(\Livewire\store($component)->get('dispatched', []));
        }
        Event::assertNotDispatched(\App\Events\ConversationCleared::class);
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
