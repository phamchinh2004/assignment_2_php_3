<?php

/**
 * Script test broadcasting - chạy từ command line
 * Usage: php test-broadcast.php <conversation_id> <user_id>
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Events\MessageSent;
use App\Models\Message;
use Illuminate\Support\Facades\Log;

if ($argc < 3) {
    echo "Usage: php test-broadcast.php <conversation_id> <user_id>\n";
    exit(1);
}

$conversationId = $argv[1];
$userId = $argv[2];

echo "🧪 Testing Broadcast...\n";
echo "Conversation ID: $conversationId\n";
echo "User ID: $userId\n\n";

// Tìm message mới nhất trong conversation
$message = \App\Models\Message::where('conversation_id', $conversationId)
    ->orderBy('id', 'desc')
    ->first();

if (!$message) {
    echo "❌ Không tìm thấy message trong conversation này\n";
    exit(1);
}

echo "📨 Message ID: {$message->id}\n";
echo "📝 Message: " . substr($message->message, 0, 50) . "...\n\n";

// Test broadcast
try {
    echo "📡 Broadcasting message...\n";
    broadcast(new MessageSent($message));
    echo "✅ Broadcast thành công!\n";
    
    // Kiểm tra queue
    echo "\n📋 Kiểm tra queue...\n";
    $queueSize = \Illuminate\Support\Facades\Redis::connection('default')->llen('queues:default');
    echo "Queue size: $queueSize\n";
    
} catch (\Exception $e) {
    echo "❌ Lỗi: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    exit(1);
}

echo "\n✅ Test hoàn tất!\n";

