<?php

namespace Tests\Feature;

use App\Livewire\Admin\ChatComponent;
use App\Models\Conversation;
use App\Models\ConversationNotificationMute;
use App\Models\User;
use App\Services\AuthorizationService;
use Livewire\Attributes\Computed;
use Livewire\Livewire;
use Tests\TestCase;

class AdminChatLayoutTest extends TestCase
{
    public function test_selected_chat_renders_one_responsive_inbox_and_preserves_role_actions(): void
    {
        $this->withoutVite();
        $this->mock(AuthorizationService::class, function ($mock) {
            $mock->shouldReceive('can')->andReturn(false);
            $mock->shouldReceive('visibleTeamChatRoles')->andReturn([]);
        });

        foreach ([User::ROLE_STAFF, User::ROLE_ADMIN, User::ROLE_OWNER] as $role) {
            $this->actingAs((new User)->forceFill(['id' => 2, 'role' => $role, 'full_name' => 'Staff']));
            $component = Livewire::test(AdminChatLayoutFixture::class);
            $component->assertSee('Thông tin khách hàng')->assertSee('Trả lời nhanh')
                ->assertSee('Xin chào')->assertDontSee('Ghi chú nội bộ')->assertDontSee('Nhãn hội thoại')
                ->assertDontSee('Đánh dấu xong');
            $html = $component->html();
            $this->assertSame(1, substr_count($html, 'id="desktop-chat-search"'));
            $this->assertStringContainsString('wire:model.live.debounce.300ms="searchTerm"', $html);
            $this->assertSame(1, substr_count($html, 'id="message-input-textarea"'));
            $this->assertStringNotContainsString('id="mobileSidebar"', $html);

            $dom = new \DOMDocument;
            @$dom->loadHTML('<?xml encoding="utf-8" ?>'.$html);
            $xpath = new \DOMXPath($dom);
            // Details must be a sibling of the message pane, not consume space inside its header.
            $this->assertSame(1, $xpath->query('//*[@id="messages-container"]/../aside[@id="chat-context-panel"]')->length);
            $this->assertSame(0, $xpath->query('//*[contains(@class,"chat-header")]//*[@id="chat-context-panel"]')->length);
            $this->assertSame(1, $xpath->query('//*[contains(@class,"chat-message-avatar")]')->length);
            $this->assertSame(0, $xpath->query('//*[contains(@class,"role-badge")]')->length);
            $this->assertSame(1, $xpath->query('//*[contains(@class,"chat-quick-strip")]//button[contains(@class,"chat-quick-chip")]')->length);
            $component->assertSee('Xin chào...')->assertSee('Hình ảnh đã gửi')->assertDontSee('Giao dịch gần đây');
            $this->assertSame($role === User::ROLE_STAFF ? 0 : 1, $xpath->query('//button[@aria-label="Điều phối hội thoại"]')->length);
            $this->assertSame($role === User::ROLE_OWNER ? 1 : 0, $xpath->query('//a[@onclick="confirmDeleteConversation()"]')->length);
        }
    }

    public function test_empty_selection_has_inbox_without_customer_details_or_composer(): void
    {
        $this->withoutVite();
        $this->mock(AuthorizationService::class, function ($mock) {
            $mock->shouldReceive('can')->andReturn(false);
            $mock->shouldReceive('visibleTeamChatRoles')->andReturn([]);
        });
        $this->actingAs((new User)->forceFill(['id' => 2, 'role' => User::ROLE_STAFF]));
        Livewire::test(AdminChatLayoutFixture::class, ['selected' => false])
            ->assertSee('Hộp thư')->assertSee('Chọn một cuộc trò chuyện')
            ->assertDontSeeHtml('id="message-input-textarea"')
            ->assertDontSeeHtml('id="chat-context-panel"');
    }

    public function test_context_sections_and_message_alignment_render_for_the_current_viewer(): void
    {
        $this->withoutVite();
        $this->mock(AuthorizationService::class, function ($mock) {
            $mock->shouldReceive('can')->andReturn(false);
            $mock->shouldReceive('visibleTeamChatRoles')->andReturn([]);
        });
        foreach ([2 => 'is-own-message', 4 => 'is-other-message'] as $viewer => $expectedClass) {
            $this->actingAs((new User)->forceFill(['id' => $viewer, 'role' => $viewer === 2 ? User::ROLE_ADMIN : User::ROLE_OWNER, 'full_name' => 'Viewer']));
            $component = Livewire::test(AdminChatLayoutFixture::class, ['showContext' => true, 'senderId' => 2]);
            $component->assertSee('Đơn hàng liên quan')->assertSee('Giao dịch gần đây')->assertSee('ORDER-91')->assertSee('WALLET-92');
            $dom = new \DOMDocument;
            @$dom->loadHTML('<?xml encoding="utf-8" ?>'.$component->html());
            $xpath = new \DOMXPath($dom);
            $this->assertSame(1, $xpath->query('//*[contains(@class,"'.$expectedClass.'")]//*[contains(@class,"chat-message-meta")]')->length);
            $this->assertSame(0, $xpath->query('//*[contains(@class,"message-bubble")]//*[contains(@class,"chat-message-meta")]')->length);
        }
    }
}

/** Render the actual Blade template with in-memory data; never touch the local database. */
class AdminChatLayoutFixture extends ChatComponent
{
    public bool $showContext = false;

    public function mount($selected = true, $showContext = false, $senderId = 1)
    {
        $this->showContext = $showContext;
        $customer = (new AdminChatLayoutCustomer)->forceFill([
            'id' => 1, 'full_name' => 'Khách thử nghiệm', 'username' => 'customer',
            'status' => 'activated', 'role' => User::ROLE_MEMBER,
        ]);
        $conversation = (new Conversation)->forceFill(['id' => 1, 'user_id' => 1, 'staff_id' => 2, 'unread_count' => 0]);
        $conversation->setRelation('user', $customer)->setRelation('staff', auth()->user())
            ->setRelation('messages', collect());
        $this->conversations = collect([$conversation]);
        $this->selectedConversationId = $selected ? 1 : null;
        $this->messages = [[
            'id' => 1, 'sender_id' => $senderId, 'message' => 'Xin chào', 'type' => 'text',
            'created_at' => '2026-10-04 10:00:00', 'sender' => $customer->toArray(),
        ]];
        $this->hasMoreMessages = false;
        $this->quickMessages = ['hello' => 'Xin chào quý khách'];
    }

    #[Computed]
    public function selectedConversation()
    {
        return $this->selectedConversationId ? $this->conversations->first() : null;
    }

    public function getSelectedConversationNotificationMuteProperty(): ?ConversationNotificationMute
    {
        return null;
    }

    #[Computed]
    public function customerContext(): array
    {
        return ['images' => [], 'orders' => $this->showContext ? [['id' => 91, 'code' => 'ORDER-91', 'name' => null, 'amount' => null]] : [],
            'transactions' => $this->showContext ? [['id' => 92, 'source' => 'wallet', 'type' => 'deposit', 'amount' => 25]] : [],
            'can_view_context' => $this->showContext];
    }
}

class AdminChatLayoutCustomer extends User
{
    public function hasPenalizedOrders() { return false; }
    public function hasHighValueOrders() { return false; }
}
