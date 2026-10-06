<?php

namespace Tests\Feature;

use App\Livewire\User\ChatComponent;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ChatWaitingStateTest extends TestCase
{
    public function createApplication(): Application
    {
        $app = require __DIR__.'/../../bootstrap/app.php';
        $app->make(Kernel::class)->bootstrap();

        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');
        $this->travelTo(now()->startOfSecond());

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('role');
        });
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('staff_id');
        });
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('conversation_id');
            $table->unsignedBigInteger('sender_id');
            $table->string('kind')->nullable();
            $table->timestamps();
        });
        DB::table('users')->insert([
            ['id' => 1, 'role' => User::ROLE_MEMBER],
            ['id' => 2, 'role' => User::ROLE_STAFF],
            ['id' => 3, 'role' => User::ROLE_MEMBER],
            ['id' => 4, 'role' => User::ROLE_ADMIN],
            ['id' => 5, 'role' => User::ROLE_OWNER],
        ]);
        DB::table('conversations')->insert([
            ['id' => 1, 'user_id' => 1, 'staff_id' => 2],
            ['id' => 2, 'user_id' => 3, 'staff_id' => 2],
        ]);
        $this->actingAs(User::findOrFail(1));
    }

    private function message(int $id, int $senderId, ?string $kind = 'text', int $secondsAgo = 0, int $conversationId = 1): void
    {
        DB::table('messages')->insert([
            'id' => $id, 'conversation_id' => $conversationId, 'sender_id' => $senderId, 'kind' => $kind,
            'created_at' => now()->subSeconds($secondsAgo), 'updated_at' => now()->subSeconds($secondsAgo),
        ]);
    }

    private function state(int $conversationId = 1): array
    {
        $component = new ChatComponent;
        $component->conversation = Conversation::findOrFail($conversationId);

        return $component->render()->getData()['supportWait'];
    }

    public function test_empty_chat_has_no_wait(): void
    {
        $this->assertSame(['since' => null, 'now' => now()->getTimestampMs()], $this->state());
    }

    public function test_auto_replies_and_follow_ups_do_not_restart_the_wait(): void
    {
        $this->message(1, 1, 'text', 45);
        $this->message(2, 2, 'auto_reply', 40);
        $this->message(3, 1, 'image', 10);
        $this->assertSame(now()->subSeconds(45)->getTimestampMs(), $this->state()['since']);
    }

    public function test_staff_admin_and_owner_human_replies_end_the_wait(): void
    {
        foreach ([2, 4, 5] as $senderId) {
            DB::table('messages')->delete();
            $this->message(1, 1, 'text', 90);
            $this->message(2, $senderId, $senderId === 2 ? null : 'image', 1);
            $this->assertNull($this->state()['since']);
        }
    }

    public function test_new_request_after_human_reply_starts_at_first_new_message(): void
    {
        $this->message(1, 1, 'text', 90);
        $this->message(2, 2, 'text', 70);
        $this->message(3, 1, 'order_reference', 20);
        $this->message(4, 2, 'auto_reply', 10);
        $this->message(5, 1, 'transaction_reference', 5);
        $this->assertSame(now()->subSeconds(20)->getTimestampMs(), $this->state()['since']);
    }

    public function test_foreign_conversation_cannot_expose_a_wait_timestamp(): void
    {
        $this->message(1, 3, 'text', 90, 2);
        $this->assertNull($this->state(2)['since']);
        $this->actingAs(User::findOrFail(3));
        $this->assertSame(now()->subSeconds(90)->getTimestampMs(), $this->state(2)['since']);
    }

    public function test_a_reply_in_another_conversation_does_not_end_this_wait(): void
    {
        $this->message(1, 1, 'text', 45);
        $this->message(2, 2, 'text', 1, 2);
        $this->assertSame(now()->subSeconds(45)->getTimestampMs(), $this->state()['since']);
    }

    public function test_clearing_the_chat_clears_the_wait(): void
    {
        $this->message(1, 1, 'text', 90);
        $this->assertNotNull($this->state()['since']);
        DB::table('messages')->delete();
        $this->assertNull($this->state()['since']);
    }
}
