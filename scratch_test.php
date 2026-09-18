<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$member = App\Models\User::where('role', 'member')->whereHas('conversation')->first();
if ($member) {
    echo "Member ID: " . $member->id . "\n";
    echo "conversations (staff_id) count: " . $member->conversations()->count() . "\n";
    echo "conversation (user_id): " . ($member->conversation ? $member->conversation->id : 'null') . "\n";
    echo "latestConversation: " . ($member->latestConversation ? $member->latestConversation->id : 'null') . "\n";
} else {
    echo "No member with conversation found\n";
}

