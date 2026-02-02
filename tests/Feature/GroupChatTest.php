<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Group;
use App\Models\ChMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;

class GroupChatTest extends TestCase
{
    use RefreshDatabase;

    protected $user1;
    protected $user2;
    protected $group;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test users
        $this->user1 = User::factory()->create(['name' => 'User One', 'email' => 'user1@test.com']);
        $this->user2 = User::factory()->create(['name' => 'User Two', 'email' => 'user2@test.com']);
        
        // Create a test group
        $this->group = Group::create([
            'name' => 'Test Group',
            'created_by' => $this->user1->id,
            'description' => 'Test group description'
        ]);
        
        // Add members to group
        $this->group->members()->attach($this->user1->id, ['role' => 'admin']);
        $this->group->members()->attach($this->user2->id, ['role' => 'member']);
    }

    public function test_can_list_user_groups()
    {
        $this->actingAs($this->user1);
        
        $response = $this->getJson('/groups');
        
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'groups' => [
                         '*' => [
                             'id',
                             'name',
                             'members_count',
                             'last_message',
                             'last_message_time',
                             'last_message_sender'
                         ]
                     ]
                 ]);
        
        $groups = $response->json('groups');
        $this->assertCount(1, $groups);
        $this->assertEquals('Test Group', $groups[0]['name']);
        $this->assertEquals(2, $groups[0]['members_count']);
    }

    public function test_can_get_group_messages()
    {
        $this->actingAs($this->user1);
        
        // Create test messages
        ChMessage::create([
            'from_id' => $this->user1->id,
            'to_id' => null,
            'group_id' => $this->group->id,
            'body' => 'Hello group!'
        ]);
        
        ChMessage::create([
            'from_id' => $this->user2->id,
            'to_id' => null,
            'group_id' => $this->group->id,
            'body' => 'Hi everyone!'
        ]);
        
        $response = $this->getJson("/groups/{$this->group->id}/messages");
        
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'messages' => [
                         '*' => [
                             'id',
                             'from_id',
                             'group_id',
                             'body',
                             'created_at',
                             'from' => ['id', 'name']
                         ]
                     ]
                 ]);
        
        $messages = $response->json('messages');
        $this->assertCount(2, $messages);
        
        // Verify chronological order (oldest first)
        $this->assertEquals('Hello group!', $messages[0]['body']);
        $this->assertEquals('Hi everyone!', $messages[1]['body']);
        
        // Verify sender info is included
        $this->assertEquals('User One', $messages[0]['from']['name']);
        $this->assertEquals('User Two', $messages[1]['from']['name']);
    }

    public function test_group_messages_have_proper_structure()
    {
        $this->actingAs($this->user1);
        
        ChMessage::create([
            'from_id' => $this->user1->id,
            'to_id' => null,
            'group_id' => $this->group->id,
            'body' => 'Test message'
        ]);
        
        $response = $this->getJson("/groups/{$this->group->id}/messages");
        $messages = $response->json('messages');
        
        $message = $messages[0];
        
        // Verify all required fields are present and not null
        $this->assertNotNull($message['id']);
        $this->assertNotNull($message['from_id']);
        $this->assertNotNull($message['group_id']);
        $this->assertNotNull($message['body']);
        $this->assertNotNull($message['created_at']);
        $this->assertNotNull($message['from']);
        $this->assertNotNull($message['from']['id']);
        $this->assertNotNull($message['from']['name']);
        
        // Verify body is string and not causing memory issues
        $this->assertIsString($message['body']);
        $this->assertEquals('Test message', $message['body']);
    }

    public function test_last_message_is_properly_formatted()
    {
        $this->actingAs($this->user1);
        
        // Create a message with body
        ChMessage::create([
            'from_id' => $this->user2->id,
            'to_id' => null,
            'group_id' => $this->group->id,
            'body' => 'This is a very long message that should be truncated to prevent memory issues'
        ]);
        
        $response = $this->getJson('/groups');
        $groups = $response->json('groups');
        
        $this->assertNotNull($groups[0]['last_message']);
        $this->assertNotNull($groups[0]['last_message_time']);
        $this->assertEquals('User Two', $groups[0]['last_message_sender']);
        
        // Verify message is truncated
        $this->assertLessThanOrEqual(33, strlen($groups[0]['last_message'])); // 30 chars + "..."
    }
}
