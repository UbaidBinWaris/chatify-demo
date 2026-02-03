<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Group;
use App\Models\ChMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

class GroupChatVisibilityTest extends TestCase
{
    use RefreshDatabase;

    protected $user1;
    protected $user2;
    protected $user3;
    protected $group;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test users
        $this->user1 = User::factory()->create(['name' => 'Test User One']);
        $this->user2 = User::factory()->create(['name' => 'Test User Two']);
        $this->user3 = User::factory()->create(['name' => 'Test User Three']);

        // Create a test group with user1 as creator
        $this->group = Group::create([
            'name' => 'Test Group',
            'created_by' => $this->user1->id,
            'description' => 'Test group for visibility testing'
        ]);

        // Add members to the group
        $this->group->members()->attach($this->user1->id, ['role' => 'admin']);
        $this->group->members()->attach($this->user2->id, ['role' => 'member']);
        $this->group->members()->attach($this->user3->id, ['role' => 'member']);
    }

    /**
     * Test that group creator can send messages
     */
    public function test_group_creator_can_send_messages()
    {
        $this->actingAs($this->user1);

        $response = $this->postJson("/groups/{$this->group->id}/messages", [
            'message' => 'Hello from creator'
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => [
                         'body' => 'Hello from creator',
                         'from_id' => $this->user1->id,
                     ]
                 ]);

        $this->assertDatabaseHas('ch_messages', [
            'group_id' => $this->group->id,
            'from_id' => $this->user1->id,
            'body' => 'Hello from creator',
        ]);
    }

    /**
     * Test that regular group members can send messages
     */
    public function test_regular_members_can_send_messages()
    {
        $this->actingAs($this->user2);

        $response = $this->postJson("/groups/{$this->group->id}/messages", [
            'message' => 'Hello from regular member'
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => [
                         'body' => 'Hello from regular member',
                         'from_id' => $this->user2->id,
                     ]
                 ]);

        $this->assertDatabaseHas('ch_messages', [
            'group_id' => $this->group->id,
            'from_id' => $this->user2->id,
            'body' => 'Hello from regular member',
        ]);
    }

    /**
     * Test that all members can read messages
     */
    public function test_all_members_can_read_messages()
    {
        // User1 sends a message
        ChMessage::create([
            'from_id' => $this->user1->id,
            'to_id' => 0,
            'group_id' => $this->group->id,
            'body' => 'Message from user1'
        ]);

        // User2 sends a message
        ChMessage::create([
            'from_id' => $this->user2->id,
            'to_id' => 0,
            'group_id' => $this->group->id,
            'body' => 'Message from user2'
        ]);

        // Test user1 can read all messages
        $this->actingAs($this->user1);
        $response = $this->getJson("/groups/{$this->group->id}/messages");
        $response->assertStatus(200);
        $messages = $response->json('messages');
        $this->assertCount(2, $messages);

        // Test user2 can read all messages
        $this->actingAs($this->user2);
        $response = $this->getJson("/groups/{$this->group->id}/messages");
        $response->assertStatus(200);
        $messages = $response->json('messages');
        $this->assertCount(2, $messages);

        // Test user3 can read all messages
        $this->actingAs($this->user3);
        $response = $this->getJson("/groups/{$this->group->id}/messages");
        $response->assertStatus(200);
        $messages = $response->json('messages');
        $this->assertCount(2, $messages);
    }

    /**
     * Test that messages persist after reopening group chat
     */
    public function test_messages_persist_after_reopening_chat()
    {
        $this->actingAs($this->user1);

        // Send multiple messages
        for ($i = 1; $i <= 5; $i++) {
            ChMessage::create([
                'from_id' => $this->user1->id,
                'to_id' => 0,
                'group_id' => $this->group->id,
                'body' => "Message number $i"
            ]);
        }

        // First fetch
        $response1 = $this->getJson("/groups/{$this->group->id}/messages");
        $response1->assertStatus(200);
        $messages1 = $response1->json('messages');
        $this->assertCount(5, $messages1);

        // Second fetch (simulating reopening chat)
        $response2 = $this->getJson("/groups/{$this->group->id}/messages");
        $response2->assertStatus(200);
        $messages2 = $response2->json('messages');
        $this->assertCount(5, $messages2);

        // Verify same messages are returned
        $this->assertEquals($messages1, $messages2);
    }

    /**
     * Test that non-members cannot send messages
     */
    public function test_non_members_cannot_send_messages()
    {
        $nonMember = User::factory()->create(['name' => 'Non Member']);
        $this->actingAs($nonMember);

        $response = $this->postJson("/groups/{$this->group->id}/messages", [
            'message' => 'Should not be allowed'
        ]);

        $response->assertStatus(403)
                 ->assertJson([
                     'error' => 'You are not a member of this group'
                 ]);

        $this->assertDatabaseMissing('ch_messages', [
            'group_id' => $this->group->id,
            'from_id' => $nonMember->id,
        ]);
    }

    /**
     * Test that non-members cannot read messages
     */
    public function test_non_members_cannot_read_messages()
    {
        $nonMember = User::factory()->create(['name' => 'Non Member']);
        
        // Create some messages
        ChMessage::create([
            'from_id' => $this->user1->id,
            'to_id' => 0,
            'group_id' => $this->group->id,
            'body' => 'Secret message'
        ]);

        $this->actingAs($nonMember);
        $response = $this->getJson("/groups/{$this->group->id}/messages");
        
        $response->assertStatus(403)
                 ->assertJson([
                     'error' => 'You are not a member of this group'
                 ]);
    }

    /**
     * Test message sender information is correct
     */
    public function test_message_sender_information_is_correct()
    {
        $this->actingAs($this->user2);

        $response = $this->postJson("/groups/{$this->group->id}/messages", [
            'message' => 'Test message with sender info'
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => [
                         'body' => 'Test message with sender info',
                         'from_id' => $this->user2->id,
                         'from_name' => 'Test User Two',
                     ]
                 ]);
    }

    /**
     * Test multiple members sending messages in sequence
     */
    public function test_multiple_members_can_send_messages_in_sequence()
    {
        // User1 sends a message
        $this->actingAs($this->user1);
        $this->postJson("/groups/{$this->group->id}/messages", [
            'message' => 'First message from user1'
        ])->assertStatus(200);

        // User2 sends a message
        $this->actingAs($this->user2);
        $this->postJson("/groups/{$this->group->id}/messages", [
            'message' => 'Second message from user2'
        ])->assertStatus(200);

        // User3 sends a message
        $this->actingAs($this->user3);
        $this->postJson("/groups/{$this->group->id}/messages", [
            'message' => 'Third message from user3'
        ])->assertStatus(200);

        // User1 sends another message
        $this->actingAs($this->user1);
        $this->postJson("/groups/{$this->group->id}/messages", [
            'message' => 'Fourth message from user1 again'
        ])->assertStatus(200);

        // Verify all messages exist
        $this->actingAs($this->user1);
        $response = $this->getJson("/groups/{$this->group->id}/messages");
        $messages = $response->json('messages');
        
        $this->assertCount(4, $messages);
        $this->assertEquals('First message from user1', $messages[0]['body']);
        $this->assertEquals('Second message from user2', $messages[1]['body']);
        $this->assertEquals('Third message from user3', $messages[2]['body']);
        $this->assertEquals('Fourth message from user1 again', $messages[3]['body']);
    }

    /**
     * Test group info remains accessible after sending messages
     */
    public function test_group_info_accessible_after_sending_messages()
    {
        $this->actingAs($this->user2);

        // Send a message
        $this->postJson("/groups/{$this->group->id}/messages", [
            'message' => 'Test message'
        ])->assertStatus(200);

        // Get group info
        $response = $this->getJson("/groups/{$this->group->id}");
        $response->assertStatus(200)
                 ->assertJson([
                     'group' => [
                         'id' => $this->group->id,
                         'name' => 'Test Group',
                         'description' => 'Test group for visibility testing',
                     ]
                 ]);
    }

    /**
     * Test message timestamps are correctly formatted
     */
    public function test_message_timestamps_are_correctly_formatted()
    {
        $this->actingAs($this->user1);

        $response = $this->postJson("/groups/{$this->group->id}/messages", [
            'message' => 'Timestamped message'
        ]);

        $response->assertStatus(200);
        $message = $response->json('message');
        
        $this->assertArrayHasKey('created_at', $message);
        $this->assertNotEmpty($message['created_at']);
    }

    /**
     * Test empty group has no messages
     */
    public function test_empty_group_has_no_messages()
    {
        $this->actingAs($this->user1);

        $response = $this->getJson("/groups/{$this->group->id}/messages");
        $response->assertStatus(200);
        $messages = $response->json('messages');
        
        $this->assertCount(0, $messages);
        $this->assertIsArray($messages);
    }

    /**
     * Test messages are returned in chronological order
     */
    public function test_messages_returned_in_chronological_order()
    {
        $this->actingAs($this->user1);

        // Create messages with specific timestamps
        $firstMessage = ChMessage::create([
            'from_id' => $this->user1->id,
            'to_id' => 0,
            'group_id' => $this->group->id,
            'body' => 'First message',
            'created_at' => now()->subMinutes(10),
        ]);

        $secondMessage = ChMessage::create([
            'from_id' => $this->user2->id,
            'to_id' => 0,
            'group_id' => $this->group->id,
            'body' => 'Second message',
            'created_at' => now()->subMinutes(5),
        ]);

        $thirdMessage = ChMessage::create([
            'from_id' => $this->user3->id,
            'to_id' => 0,
            'group_id' => $this->group->id,
            'body' => 'Third message',
            'created_at' => now(),
        ]);

        $response = $this->getJson("/groups/{$this->group->id}/messages");
        $messages = $response->json('messages');

        $this->assertCount(3, $messages);
        $this->assertEquals('First message', $messages[0]['body']);
        $this->assertEquals('Second message', $messages[1]['body']);
        $this->assertEquals('Third message', $messages[2]['body']);
    }
}
