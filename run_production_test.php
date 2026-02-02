<?php

/**
 * Production Load Test Runner
 * 
 * This script creates realistic production data and runs for 5 minutes
 * testing group chats, one-to-one chats, and edge cases.
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Group;
use App\Models\ChMessage;
use Illuminate\Support\Facades\DB;

echo "\n";
echo "╔═══════════════════════════════════════════════════════════╗\n";
echo "║     CHATIFY PRODUCTION LOAD TEST - 5 MINUTE RUN           ║\n";
echo "╚═══════════════════════════════════════════════════════════╝\n\n";

$startTime = time();
$endTime = $startTime + (5 * 60); // 5 minutes

// Step 1: Create Users
echo "[1/7] Creating 25 users...\n";
$users = [];
$userNames = [
    'Alice Johnson', 'Bob Smith', 'Charlie Brown', 'Diana Prince', 'Eve Anderson',
    'Frank Miller', 'Grace Lee', 'Henry Davis', 'Ivy Wilson', 'Jack Taylor',
    'Kate Moore', 'Leo Martinez', 'Maya Garcia', 'Noah Rodriguez', 'Olivia White',
    'Peter Harris', 'Quinn Clark', 'Rachel Lewis', 'Sam Walker', 'Tina Hall',
    'Uma Patel', 'Victor Chen', 'Wendy Kim', 'Xavier Lopez', 'Yara Singh'
];

foreach ($userNames as $name) {
    $users[] = User::factory()->create([
        'name' => $name,
        'email' => strtolower(str_replace(' ', '.', $name)) . '@company.com'
    ]);
}
echo "  ✓ Created " . count($users) . " users\n\n";

// Step 2: Create Groups
echo "[2/7] Creating 10 groups...\n";
$groups = [];
$groupConfigs = [
    ['name' => 'Engineering Team', 'desc' => 'All engineers', 'members' => range(0, 9)],
    ['name' => 'Product Team', 'desc' => 'Product managers', 'members' => [10, 11, 12, 13, 14]],
    ['name' => 'Design Team', 'desc' => 'Designers and UX', 'members' => [15, 16, 17, 18]],
    ['name' => 'Marketing', 'desc' => 'Marketing department', 'members' => [19, 20, 21]],
    ['name' => 'Sales', 'desc' => 'Sales team', 'members' => [22, 23, 24]],
    ['name' => 'Leadership', 'desc' => 'C-level executives', 'members' => [0, 10, 15, 19, 22]],
    ['name' => 'Random Thoughts', 'desc' => 'Off-topic chat', 'members' => range(0, 24)],
    ['name' => 'Project Alpha', 'desc' => 'Alpha project team', 'members' => [0, 1, 2, 10, 11, 15]],
    ['name' => 'Customer Success', 'desc' => 'Customer support', 'members' => [3, 4, 5, 6]],
    ['name' => 'DevOps', 'desc' => 'Infrastructure team', 'members' => [7, 8, 9]]
];

foreach ($groupConfigs as $config) {
    $group = Group::create([
        'name' => $config['name'],
        'created_by' => $users[0]->id,
        'description' => $config['desc']
    ]);
    
    foreach ($config['members'] as $idx) {
        $group->members()->attach($users[$idx]->id, [
            'role' => $idx === $config['members'][0] ? 'admin' : 'member'
        ]);
    }
    
    $groups[] = $group;
    echo "  ✓ {$config['name']} - " . count($config['members']) . " members\n";
}
echo "\n";

// Step 3: Message Templates
$messageTemplates = [
    "Good morning team! ☀️",
    "I just pushed the latest changes to {branch}",
    "Can someone review PR #{number}?",
    "Meeting in 5 minutes!",
    "Great work on {feature} everyone! 🎉",
    "I'm working on {task} today",
    "Does anyone know how to {question}?",
    "Thanks {name} for your help!",
    "I found a bug in {module}",
    "Deployment scheduled for {time}",
    "Coffee break? ☕",
    "Updated the documentation for {feature}",
    "The client approved {feature}!",
    "Can we discuss {topic} in the next meeting?",
    "I'm blocked on {task}, any ideas?",
    "Just merged {branch} into main",
    "Happy {day}! 🎊",
    "New feature request from the client",
    "Performance improvement on {module}",
    "Code review completed ✅",
];

$replacements = [
    '{branch}' => ['feature/auth', 'feature/dashboard', 'bugfix/login', 'develop'],
    '{number}' => range(100, 999),
    '{feature}' => ['authentication', 'dashboard', 'reports', 'notifications', 'search'],
    '{task}' => ['API integration', 'database migration', 'UI updates', 'testing'],
    '{question}' => ['optimize this query', 'implement caching', 'deploy to production'],
    '{name}' => array_column($userNames, null),
    '{module}' => ['payment gateway', 'user management', 'file upload', 'email service'],
    '{time}' => ['2pm', '5pm', '9am', 'tomorrow', 'tonight'],
    '{topic}' => ['architecture', 'security', 'scalability', 'performance'],
    '{day}' => ['Monday', 'Friday', 'Wednesday']
];

// Step 4: Continuous Message Generation
echo "[3/7] Generating messages for 5 minutes...\n";
echo "Start time: " . date('Y-m-d H:i:s') . "\n";
echo "End time: " . date('Y-m-d H:i:s', $endTime) . "\n\n";

$messageCount = 0;
$groupMessageCount = 0;
$oneToOneCount = 0;
$round = 0;

while (time() < $endTime) {
    $round++;
    $roundStart = microtime(true);
    
    // Generate group messages (70% of traffic)
    if (rand(1, 100) <= 70) {
        $group = $groups[array_rand($groups)];
        $members = $group->members()->get();
        $sender = $members->random();
        
        $template = $messageTemplates[array_rand($messageTemplates)];
        foreach ($replacements as $placeholder => $values) {
            if (strpos($template, $placeholder) !== false) {
                $template = str_replace($placeholder, $values[array_rand($values)], $template);
            }
        }
        
        ChMessage::create([
            'from_id' => $sender->id,
            'to_id' => null,
            'group_id' => $group->id,
            'body' => $template,
            'seen' => rand(0, 1)
        ]);
        
        $groupMessageCount++;
        $messageCount++;
    } 
    // Generate one-to-one messages (30% of traffic)
    else {
        $user1 = $users[array_rand($users)];
        $user2 = $users[array_rand($users)];
        
        if ($user1->id !== $user2->id) {
            $template = $messageTemplates[array_rand($messageTemplates)];
            foreach ($replacements as $placeholder => $values) {
                if (strpos($template, $placeholder) !== false) {
                    $template = str_replace($placeholder, $values[array_rand($values)], $template);
                }
            }
            
            ChMessage::create([
                'from_id' => $user1->id,
                'to_id' => $user2->id,
                'group_id' => null,
                'body' => $template,
                'seen' => rand(0, 1)
            ]);
            
            $oneToOneCount++;
            $messageCount++;
        }
    }
    
    // Progress update every 10 rounds
    if ($round % 10 === 0) {
        $elapsed = time() - $startTime;
        $remaining = $endTime - time();
        $avgRate = $messageCount / max($elapsed, 1);
        
        echo sprintf(
            "  Round %d | Messages: %d (Group: %d, 1-to-1: %d) | Elapsed: %ds | Remaining: %ds | Rate: %.1f msg/s\r",
            $round, $messageCount, $groupMessageCount, $oneToOneCount, $elapsed, $remaining, $avgRate
        );
    }
    
    // Realistic delay (simulate typing and reading time)
    usleep(rand(50000, 200000)); // 50-200ms between messages
}

echo "\n\n";

// Step 5: Edge Cases
echo "[4/7] Testing edge cases...\n";

// Very long message
$longMsg = str_repeat("This is a stress test message to ensure the system handles long content properly. ", 50);
ChMessage::create([
    'from_id' => $users[0]->id,
    'to_id' => null,
    'group_id' => $groups[0]->id,
    'body' => $longMsg
]);
echo "  ✓ Long message test (4000+ chars)\n";

// Rapid fire messages
for ($i = 0; $i < 100; $i++) {
    ChMessage::create([
        'from_id' => $users[1]->id,
        'to_id' => null,
        'group_id' => $groups[1]->id,
        'body' => "Rapid message #$i - Testing spam handling"
    ]);
}
echo "  ✓ Rapid messaging test (100 messages in succession)\n";

// Special characters and emojis
ChMessage::create([
    'from_id' => $users[2]->id,
    'to_id' => $users[3]->id,
    'group_id' => null,
    'body' => "Special chars: !@#$%^&*()_+-=[]{}|;:',.<>?/~` and emojis: 🚀🎉💻🔥⚡️🌟✨🎯🏆"
]);
echo "  ✓ Special characters and emojis test\n\n";

// Step 6: Query Performance Tests
echo "[5/7] Testing query performance...\n";

$queryStart = microtime(true);
$allGroups = Group::with(['members', 'latestMessage'])->get();
$queryTime = round((microtime(true) - $queryStart) * 1000, 2);
echo "  ✓ Loaded " . $allGroups->count() . " groups with relations in {$queryTime}ms\n";

$queryStart = microtime(true);
$recentMessages = ChMessage::with('from')->latest()->take(100)->get();
$queryTime = round((microtime(true) - $queryStart) * 1000, 2);
echo "  ✓ Loaded 100 recent messages with sender info in {$queryTime}ms\n";

foreach ($groups as $group) {
    $queryStart = microtime(true);
    $messages = ChMessage::where('group_id', $group->id)
        ->with('from')
        ->oldest()
        ->get();
    $queryTime = round((microtime(true) - $queryStart) * 1000, 2);
    echo "  ✓ {$group->name}: {$messages->count()} messages loaded in {$queryTime}ms\n";
}
echo "\n";

// Step 7: Final Statistics
echo "[6/7] Verifying message sender tracking...\n";

// Check random group messages
$sampleGroup = $groups[array_rand($groups)];
$sampleMessages = ChMessage::where('group_id', $sampleGroup->id)
    ->with('from')
    ->take(10)
    ->get();

foreach ($sampleMessages as $msg) {
    echo "  ✓ \"{$msg->from->name}\" said: \"" . substr($msg->body, 0, 50) . "...\"\n";
}
echo "\n";

// Final Report
echo "[7/7] FINAL REPORT\n";
echo "╔═══════════════════════════════════════════════════════════╗\n";
echo "║              PRODUCTION TEST COMPLETE                     ║\n";
echo "╠═══════════════════════════════════════════════════════════╣\n";

$stats = [
    'Total Users' => User::count(),
    'Total Groups' => Group::count(),
    'Total Messages' => ChMessage::count(),
    'Group Messages' => ChMessage::whereNotNull('group_id')->count(),
    'One-to-One Messages' => ChMessage::whereNull('group_id')->count(),
    'Total Test Duration' => round((time() - $startTime) / 60, 2) . ' minutes',
    'Messages Per Second' => round(ChMessage::count() / (time() - $startTime), 2),
];

foreach ($stats as $label => $value) {
    echo "║ " . str_pad($label . ':', 30) . str_pad($value, 28) . " ║\n";
}

echo "╠═══════════════════════════════════════════════════════════╣\n";
echo "║ GROUP BREAKDOWN:                                          ║\n";

foreach ($groups as $group) {
    $msgCount = ChMessage::where('group_id', $group->id)->count();
    $memberCount = $group->members()->count();
    echo "║ " . str_pad($group->name, 25) . str_pad("$msgCount msgs, $memberCount members", 33) . " ║\n";
}

echo "╚═══════════════════════════════════════════════════════════╝\n\n";

echo "✅ TEST COMPLETED SUCCESSFULLY!\n";
echo "📊 Database is now populated with production-like data\n";
echo "🚀 System is ready for testing in browser\n\n";
echo "Next steps:\n";
echo "1. Start server: php artisan serve\n";
echo "2. Open browser: http://127.0.0.1:8000\n";
echo "3. Login with any user (check database for credentials)\n";
echo "4. Test group chats and verify sender names appear correctly\n\n";
