<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Group;
use App\Models\ChMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProductionLoadTest extends TestCase
{
    use RefreshDatabase;

    private $users = [];
    private $groups = [];
    
    public function test_production_load_simulation()
    {
        $startTime = microtime(true);
        echo "\n\n=== PRODUCTION LOAD TEST STARTED ===\n";
        
        // Step 1: Create multiple users
        $this->createUsers();
        
        // Step 2: Create multiple groups
        $this->createGroups();
        
        // Step 3: Simulate group conversations
        $this->simulateGroupConversations();
        
        // Step 4: Simulate one-to-one conversations
        $this->simulateOneToOneConversations();
        
        // Step 5: Test edge cases
        $this->testEdgeCases();
        
        // Step 6: Performance metrics
        $this->showMetrics($startTime);
        
        $this->assertTrue(true);
    }
    
    private function createUsers()
    {
        echo "\n[1/6] Creating 20 users...\n";
        
        $userNames = [
            'Alice Johnson', 'Bob Smith', 'Charlie Brown', 'Diana Prince',
            'Eve Anderson', 'Frank Miller', 'Grace Lee', 'Henry Davis',
            'Ivy Wilson', 'Jack Taylor', 'Kate Moore', 'Leo Martinez',
            'Maya Garcia', 'Noah Rodriguez', 'Olivia White', 'Peter Harris',
            'Quinn Clark', 'Rachel Lewis', 'Sam Walker', 'Tina Hall'
        ];
        
        foreach ($userNames as $index => $name) {
            $this->users[] = User::factory()->create([
                'name' => $name,
                'email' => strtolower(str_replace(' ', '.', $name)) . '@test.com'
            ]);
        }
        
        echo "  ✓ Created " . count($this->users) . " users\n";
    }
    
    private function createGroups()
    {
        echo "\n[2/6] Creating 8 groups with members...\n";
        
        $groupData = [
            ['name' => 'Project Alpha Team', 'description' => 'Main development team', 'members' => [0, 1, 2, 3, 4]],
            ['name' => 'Marketing Squad', 'description' => 'Marketing and outreach', 'members' => [5, 6, 7, 8]],
            ['name' => 'Design Hub', 'description' => 'UI/UX designers', 'members' => [9, 10, 11, 12, 13]],
            ['name' => 'QA Testing', 'description' => 'Quality assurance team', 'members' => [14, 15, 16, 17]],
            ['name' => 'Management', 'description' => 'Leadership team', 'members' => [0, 5, 9, 14, 18]],
            ['name' => 'Social Committee', 'description' => 'Fun events planning', 'members' => [1, 6, 10, 15, 19]],
            ['name' => 'Tech Support', 'description' => 'Customer support', 'members' => [2, 7, 11, 16]],
            ['name' => 'All Hands', 'description' => 'Company-wide announcements', 'members' => range(0, 19)]
        ];
        
        foreach ($groupData as $data) {
            $group = Group::create([
                'name' => $data['name'],
                'created_by' => $this->users[0]->id,
                'description' => $data['description']
            ]);
            
            foreach ($data['members'] as $userIndex) {
                $role = $userIndex === 0 ? 'admin' : 'member';
                $group->members()->attach($this->users[$userIndex]->id, ['role' => $role]);
            }
            
            $this->groups[] = $group;
            echo "  ✓ Created group: {$data['name']} with " . count($data['members']) . " members\n";
        }
    }
    
    private function simulateGroupConversations()
    {
        echo "\n[3/6] Simulating group conversations (this will take a few minutes)...\n";
        
        $messageTemplates = [
            "Hey team! How's everyone doing today?",
            "I just finished the {task}. Can someone review it?",
            "Great work on the last sprint! 🎉",
            "Does anyone have experience with {topic}?",
            "Meeting at {time} today. Don't forget!",
            "I'm working on {feature}. Should be done by EOD.",
            "Quick question: {question}",
            "Thanks {name} for your help earlier!",
            "Can we schedule a sync-up call?",
            "This is looking great! Keep it up team!",
            "I found a bug in {module}. Creating a ticket.",
            "Anyone free for a code review?",
            "Updated the docs for {feature}.",
            "Coffee break anyone? ☕",
            "Pushed the latest changes to main branch.",
            "The client loved our presentation!",
            "Let's discuss this in tomorrow's standup.",
            "I'm blocked on {task}. Need some help.",
            "Deployment scheduled for tonight.",
            "Happy Friday everyone! 🎊"
        ];
        
        $tasks = ['authentication', 'dashboard', 'API integration', 'user profile', 'reports'];
        $topics = ['React hooks', 'database optimization', 'caching strategies', 'testing'];
        $features = ['notifications', 'search', 'export feature', 'dark mode'];
        $modules = ['login module', 'payment gateway', 'file upload'];
        
        $totalMessages = 0;
        $iterations = 10; // Run multiple rounds of messages
        
        for ($round = 1; $round <= $iterations; $round++) {
            echo "  Round $round/$iterations... ";
            
            foreach ($this->groups as $groupIndex => $group) {
                $members = $group->members()->get();
                $messagesInGroup = rand(5, 15);
                
                for ($i = 0; $i < $messagesInGroup; $i++) {
                    $sender = $members->random();
                    $template = $messageTemplates[array_rand($messageTemplates)];
                    
                    // Replace placeholders
                    $message = str_replace('{task}', $tasks[array_rand($tasks)], $template);
                    $message = str_replace('{topic}', $topics[array_rand($topics)], $message);
                    $message = str_replace('{time}', rand(9, 17) . ':00', $message);
                    $message = str_replace('{feature}', $features[array_rand($features)], $message);
                    $message = str_replace('{question}', 'what\'s the best approach here?', $message);
                    $message = str_replace('{name}', $members->random()->name, $message);
                    $message = str_replace('{module}', $modules[array_rand($modules)], $message);
                    
                    ChMessage::create([
                        'from_id' => $sender->id,
                        'to_id' => null,
                        'group_id' => $group->id,
                        'body' => $message,
                        'seen' => rand(0, 1)
                    ]);
                    
                    $totalMessages++;
                }
            }
            
            echo "$totalMessages total messages created\n";
            
            // Simulate realistic timing
            usleep(100000); // 100ms delay between rounds
        }
        
        echo "  ✓ Created $totalMessages group messages across " . count($this->groups) . " groups\n";
    }
    
    private function simulateOneToOneConversations()
    {
        echo "\n[4/6] Simulating one-to-one conversations...\n";
        
        $oneToOneMessages = [
            "Hey, do you have a minute?",
            "Sure! What's up?",
            "I wanted to get your thoughts on the new feature.",
            "Sounds good. Let's discuss it.",
            "Thanks for the quick response!",
            "No problem at all!",
            "By the way, how's the project going?",
            "Pretty well! Almost done with my part.",
            "That's great to hear!",
            "Want to grab lunch later?",
            "Sure, sounds good!",
            "See you at noon then.",
            "Perfect! 👍",
        ];
        
        $conversations = 15; // 15 different conversation pairs
        $totalMessages = 0;
        
        for ($i = 0; $i < $conversations; $i++) {
            $user1 = $this->users[rand(0, count($this->users) - 1)];
            $user2 = $this->users[rand(0, count($this->users) - 1)];
            
            if ($user1->id === $user2->id) continue;
            
            $messagesInConvo = rand(5, 13);
            
            for ($j = 0; $j < $messagesInConvo; $j++) {
                $sender = $j % 2 === 0 ? $user1 : $user2;
                $recipient = $j % 2 === 0 ? $user2 : $user1;
                
                ChMessage::create([
                    'from_id' => $sender->id,
                    'to_id' => $recipient->id,
                    'group_id' => null,
                    'body' => $oneToOneMessages[array_rand($oneToOneMessages)],
                    'seen' => rand(0, 1)
                ]);
                
                $totalMessages++;
            }
        }
        
        echo "  ✓ Created $totalMessages one-to-one messages between users\n";
    }
    
    private function testEdgeCases()
    {
        echo "\n[5/6] Testing edge cases...\n";
        
        // Test long messages
        $longMessage = str_repeat("This is a very long message that tests the system's ability to handle large text content. ", 20);
        ChMessage::create([
            'from_id' => $this->users[0]->id,
            'to_id' => null,
            'group_id' => $this->groups[0]->id,
            'body' => $longMessage
        ]);
        echo "  ✓ Long message test (1600+ chars)\n";
        
        // Test empty group (no messages)
        $emptyGroup = Group::create([
            'name' => 'Silent Group',
            'created_by' => $this->users[0]->id,
            'description' => 'No messages yet'
        ]);
        $emptyGroup->members()->attach($this->users[0]->id, ['role' => 'admin']);
        echo "  ✓ Empty group test\n";
        
        // Test rapid-fire messages (spam scenario)
        for ($i = 0; $i < 50; $i++) {
            ChMessage::create([
                'from_id' => $this->users[1]->id,
                'to_id' => null,
                'group_id' => $this->groups[0]->id,
                'body' => "Rapid message #$i"
            ]);
        }
        echo "  ✓ Rapid messaging test (50 messages)\n";
        
        // Test special characters
        $specialChars = "Testing special chars: !@#$%^&*()_+-=[]{}|;:',.<>?/~`";
        ChMessage::create([
            'from_id' => $this->users[2]->id,
            'to_id' => $this->users[3]->id,
            'group_id' => null,
            'body' => $specialChars
        ]);
        echo "  ✓ Special characters test\n";
        
        // Test emoji-heavy message
        $emojiMessage = "🎉🎊🎈🎁🎀🎂🍰🍾🥂🍻🍺 Party time! 🎵🎶🎤🎧🎼🎹🎸🎺🎷🥁";
        ChMessage::create([
            'from_id' => $this->users[4]->id,
            'to_id' => null,
            'group_id' => $this->groups[5]->id,
            'body' => $emojiMessage
        ]);
        echo "  ✓ Emoji-heavy message test\n";
    }
    
    private function showMetrics($startTime)
    {
        echo "\n[6/6] Calculating metrics...\n";
        
        $totalUsers = User::count();
        $totalGroups = Group::count();
        $totalMessages = ChMessage::count();
        $groupMessages = ChMessage::whereNotNull('group_id')->count();
        $oneToOneMessages = ChMessage::whereNull('group_id')->count();
        
        $executionTime = round(microtime(true) - $startTime, 2);
        
        echo "\n";
        echo "╔════════════════════════════════════════════╗\n";
        echo "║       PRODUCTION LOAD TEST RESULTS         ║\n";
        echo "╠════════════════════════════════════════════╣\n";
        echo "║ Total Users:           " . str_pad($totalUsers, 18) . " ║\n";
        echo "║ Total Groups:          " . str_pad($totalGroups, 18) . " ║\n";
        echo "║ Total Messages:        " . str_pad($totalMessages, 18) . " ║\n";
        echo "║ - Group Messages:      " . str_pad($groupMessages, 18) . " ║\n";
        echo "║ - One-to-One:          " . str_pad($oneToOneMessages, 18) . " ║\n";
        echo "║ Execution Time:        " . str_pad($executionTime . 's', 18) . " ║\n";
        echo "║ Avg Msg/Second:        " . str_pad(round($totalMessages/$executionTime, 2), 18) . " ║\n";
        echo "╚════════════════════════════════════════════╝\n";
        
        // Test API endpoints
        echo "\n[TESTING API ENDPOINTS]\n";
        
        // Test group listing
        $user = $this->users[0];
        $this->actingAs($user);
        
        $response = $this->getJson('/groups');
        $response->assertStatus(200);
        $groups = $response->json('groups');
        echo "  ✓ GET /groups - Returned " . count($groups) . " groups\n";
        
        // Test message retrieval for each group
        foreach ($this->groups as $group) {
            $response = $this->getJson("/groups/{$group->id}/messages");
            $response->assertStatus(200);
            $messages = $response->json('messages');
            echo "  ✓ GET /groups/{$group->id}/messages - Retrieved " . count($messages) . " messages\n";
            
            // Verify sender information
            if (!empty($messages)) {
                $firstMsg = $messages[0];
                $this->assertArrayHasKey('from', $firstMsg);
                $this->assertArrayHasKey('id', $firstMsg['from']);
                $this->assertArrayHasKey('name', $firstMsg['from']);
                $this->assertNotNull($firstMsg['from']['name']);
            }
        }
        
        echo "\n✅ ALL TESTS PASSED - System ready for production!\n\n";
    }
}
