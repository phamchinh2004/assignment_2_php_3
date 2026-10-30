<?php

namespace Tests\Feature;

use App\Jobs\NotifyAutoReplyEscalation;
use App\Mail\ChatEscalationMail;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Services\ChatAutoReplyService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

class ChatAutoReplyEscalationTest extends TestCase
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
        DB::purge('sqlite');

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('email')->nullable();
            $table->string('role');
            $table->unsignedBigInteger('referrer_id')->nullable();
        });

        Schema::create('user_manager_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('manager_setting_id');
            $table->boolean('is_active')->default(true);
        });

        Schema::create('manager_settings', function (Blueprint $table) {
            $table->id();
            $table->string('manager_code');
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
            $table->boolean('is_read')->default(false);
            $table->timestamps();
        });

        DB::table('users')->insert([
            ['id' => 1, 'email' => 'customer@example.com', 'role' => User::ROLE_MEMBER, 'referrer_id' => null],
            ['id' => 2, 'email' => 'staff@example.com', 'role' => User::ROLE_STAFF, 'referrer_id' => 4],
            ['id' => 4, 'email' => 'admin@example.com', 'role' => User::ROLE_ADMIN, 'referrer_id' => null],
            ['id' => 5, 'email' => 'owner1@example.com', 'role' => User::ROLE_OWNER, 'referrer_id' => null],
            ['id' => 6, 'email' => 'owner2@example.com', 'role' => User::ROLE_OWNER, 'referrer_id' => null],
        ]);

        DB::table('conversations')->insert([
            ['id' => 1, 'public_id' => 'conv-1', 'user_id' => 1, 'staff_id' => 2, 'status' => 'open', 'last_escalated_reply_id' => null, 'last_escalated_at' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function test_escalation_recipients_include_assigned_staff_and_all_owners(): void
    {
        $staff = User::findOrFail(2);
        $recipients = ChatAutoReplyService::getEscalationRecipients($staff);

        $this->assertContains('staff@example.com', $recipients);
        $this->assertContains('owner1@example.com', $recipients);
        $this->assertContains('owner2@example.com', $recipients);
    }

    public function test_customer_sending_multiple_messages_triggers_only_one_escalation_email(): void
    {
        Mail::fake();

        // Khách gửi tin nhắn 1
        $msg1 = Message::create([
            'id' => 10,
            'conversation_id' => 1,
            'sender_id' => 1,
            'message' => 'Xin chào shop',
            'kind' => 'text',
        ]);

        // Khách gửi tin nhắn 2
        $msg2 = Message::create([
            'id' => 11,
            'conversation_id' => 1,
            'sender_id' => 1,
            'message' => 'Có ai online không?',
            'kind' => 'text',
        ]);

        $recipients = ['staff@example.com', 'owner1@example.com'];

        // Job 1 xử lý
        $job1 = new NotifyAutoReplyEscalation(1, 1, 'Tin nhắn tự động', $recipients, 5, $msg1->id, true);
        $job1->handle();

        Mail::assertSent(ChatEscalationMail::class, 2); // 1 mail cho staff, 1 mail cho owner

        $conversation = Conversation::find(1);
        $this->assertEquals(0, $conversation->last_escalated_reply_id);
        $this->assertNotNull($conversation->last_escalated_at);

        // Job 2 cho tin nhắn thứ 2 trong cùng đợt chờ xử lý
        $job2 = new NotifyAutoReplyEscalation(1, 1, 'Tin nhắn tự động', $recipients, 5, $msg2->id, true);
        $job2->handle();

        // Không gửi thêm bất kỳ mail nào
        Mail::assertSent(ChatEscalationMail::class, 2);
    }

    public function test_wait_resets_when_staff_replies_and_allows_new_escalation_on_next_unanswered_turn(): void
    {
        Mail::fake();

        // Đợt 1: Khách gửi tin nhắn
        $msg1 = Message::create([
            'id' => 10,
            'conversation_id' => 1,
            'sender_id' => 1,
            'message' => 'Lần 1',
            'kind' => 'text',
        ]);

        $recipients = ['staff@example.com'];

        (new NotifyAutoReplyEscalation(1, 1, 'Tin tự động', $recipients, 5, $msg1->id, true))->handle();
        Mail::assertSent(ChatEscalationMail::class, 1);

        // Nhân viên trả lời khách hàng
        $staffReply = Message::create([
            'id' => 20,
            'conversation_id' => 1,
            'sender_id' => 2,
            'message' => 'Dạ shop đây ạ',
            'kind' => 'text',
        ]);

        // Đợt 2: Khách nhắn tiếp tin mới
        $msg2 = Message::create([
            'id' => 21,
            'conversation_id' => 1,
            'sender_id' => 1,
            'message' => 'Hàng em giao chưa?',
            'kind' => 'text',
        ]);

        // Job đợt mới xử lý
        (new NotifyAutoReplyEscalation(1, 1, 'Tin tự động', $recipients, 5, $msg2->id, true))->handle();

        // Đã gửi mail mới cho đợt 2!
        Mail::assertSent(ChatEscalationMail::class, 2);

        $conversation = Conversation::find(1);
        $this->assertEquals($staffReply->id, $conversation->last_escalated_reply_id);

        // Khách gửi tiếp tin nhắn thứ 2 của đợt 2:
        $msg3 = Message::create([
            'id' => 22,
            'conversation_id' => 1,
            'sender_id' => 1,
            'message' => 'Alo shop ơi?',
            'kind' => 'text',
        ]);

        (new NotifyAutoReplyEscalation(1, 1, 'Tin tự động', $recipients, 5, $msg3->id, true))->handle();

        // Vẫn chỉ là 2 mail, không gửi thêm cho msg3
        Mail::assertSent(ChatEscalationMail::class, 2);
    }

    public function test_escalation_is_cancelled_if_staff_already_replied_before_escalation_job_runs(): void
    {
        Mail::fake();

        // Khách gửi tin
        $msg1 = Message::create([
            'id' => 30,
            'conversation_id' => 1,
            'sender_id' => 1,
            'message' => 'Alo?',
            'kind' => 'text',
        ]);

        // Nhân viên trả lời ngay lập tức (trước khi job delay 5 phút chạy)
        Message::create([
            'id' => 31,
            'conversation_id' => 1,
            'sender_id' => 2,
            'message' => 'Chào bạn!',
            'kind' => 'text',
        ]);

        // Sau 5 phút job chạy
        (new NotifyAutoReplyEscalation(1, 1, 'Tin tự động', ['staff@example.com'], 5, $msg1->id, true))->handle();

        // Không có mail nào bị gửi
        Mail::assertNothingSent();
    }

    public function test_escalation_mail_renders_properly_with_detailed_content(): void
    {
        Message::create([
            'id' => 40,
            'conversation_id' => 1,
            'sender_id' => 1,
            'message' => 'Shop ơi tôi cần hỗ trợ nạp tiền ngay',
            'kind' => 'text',
        ]);

        $mail = new ChatEscalationMail(1, 1, 'Tin tự động mẫu', 5);

        $html = $mail->render();

        $this->assertStringContainsString('YÊU CẦU PHẢN HỒI TIN NHẮN', $html);
        $this->assertStringContainsString('Shop ơi tôi cần hỗ trợ nạp tiền ngay', $html);
        $this->assertStringContainsString('staff@example.com', $html);
        $this->assertStringContainsString('Tin tự động mẫu', $html);
        $this->assertStringContainsString('5 phút', $html);
    }
}
