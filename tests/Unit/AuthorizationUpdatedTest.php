<?php

namespace Tests\Unit;

use App\Events\AuthorizationUpdated;
use Illuminate\Broadcasting\PrivateChannel;
use PHPUnit\Framework\TestCase;

class AuthorizationUpdatedTest extends TestCase
{
    public function test_event_is_semantic_and_targets_the_staff_private_channel(): void
    {
        $event = new AuthorizationUpdated(42, 'version-hash');
        $channels = $event->broadcastOn();

        $this->assertSame('AuthorizationUpdated', $event->broadcastAs());
        $this->assertSame('version-hash', $event->version);
        $this->assertSame(['version' => 'version-hash'], $event->broadcastWith());
        $this->assertCount(1, $channels);
        $this->assertInstanceOf(PrivateChannel::class, $channels[0]);
        $this->assertSame('private-staff.42', $channels[0]->name);
    }
}
