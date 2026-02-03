<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Group;
use App\Models\ChMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Test cases to verify group chat UI behavior and prevent auto-hiding issues
 */
class GroupChatUIBehaviorTest extends TestCase
{
    use RefreshDatabase;

    protected $creator;
    protected $member1;
    protected $member2;
    protected $group;

    protected function setUp(): void
    {
        parent::setUp();

        $this->creator = User::factory()->create(['name' => 'Group Creator']);
        $this->member1 = User::factory()->create(['name' => 'Member One']);
        $this->member2 = User::factory()->create(['name' => 'Member Two']);

        $this->group = Group::create([
            'name' => 'UI Test Group',
            'created_by' => $this->creator->id,
            'description' => 'Testing UI behavior'
        ]);

        $this->group->members()->attach($this->creator->id, ['role' => 'admin']);
        $this->group->members()->attach($this->member1->id, ['role' => 'member']);
        $this->group->members()->attach($this->member2->id, ['role' => 'member']);
    }

    /**
     * Test that group data is correctly structured for UI rendering
     */
    public function test_group_data_structure_for_ui()
    {
        $this->actingAs($this->creator);

        $response = $this->getJson("/groups/{$this->group->id}");
        
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'group' => [
                         'id',
                         'name',
                         'description',
                         'created_by',
                         'members' => [
                             '*' => [
                                 'id',
                                 'name',
                                 'role'
                             ]
                         ]
                     ]
                 ]);

        $data = $response->json('group');
        $this->assertCount(3, $data['members']);
        $this->assertEquals('UI Test Group', $data['name']);
    }

    /**
     * Test that messages include all necessary data for UI display
     */
    public function test_messages_include_ui_display_data()
    {
        ChMessage::create([
            'from_id' => $this->member1->id,
            'to_id' => 0,
            'group_id' => $this->group->id,
            'body' => 'Test message for UI'
        ]);

        $this->actingAs($this->creator);
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
                             'from' => [
                                 'id',
                                 'name'
                             ]
                         ]
                     ]
                 ]);

        $message = $response->json('messages')[0];
        $this->assertEquals('Member One', $message['from']['name']);
        $this->assertEquals($this->member1->id, $message['from_id']);
    }

    /**
     * Test rapid message sending doesn't cause issues
     */
    public function test_rapid_message_sending()
    {
        $this->actingAs($this->member1);

        // Send 10 messages rapidly
        for ($i = 1; $i <= 10; $i++) {
            $response = $this->postJson("/groups/{$this->group->id}/messages", [
                'message' => "Rapid message $i"
            ]);
            $response->assertStatus(200);
        }

        // Verify all messages exist
        $response = $this->getJson("/groups/{$this->group->id}/messages");
        $messages = $response->json('messages');
        $this->assertCount(10, $messages);
    }

    /**
     * Test alternating senders
     */
    public function test_alternating_message_senders()
    {
        // Member1 sends
        $this->actingAs($this->member1);
        $this->postJson("/groups/{$this->group->id}/messages", [
            'message' => 'Message from member1'
        ])->assertStatus(200);

        // Creator sends
        $this->actingAs($this->creator);
        $this->postJson("/groups/{$this->group->id}/messages", [
            'message' => 'Message from creator'
        ])->assertStatus(200);

        // Member2 sends
        $this->actingAs($this->member2);
        $this->postJson("/groups/{$this->group->id}/messages", [
            'message' => 'Message from member2'
        ])->assertStatus(200);

        // Member1 sends again
        $this->actingAs($this->member1);
        $this->postJson("/groups/{$this->group->id}/messages", [
            'message' => 'Another message from member1'
        ])->assertStatus(200);

        // Verify correct sender attribution
        $this->actingAs($this->creator);
        $response = $this->getJson("/groups/{$this->group->id}/messages");
        $messages = $response->json('messages');

        $this->assertEquals($this->member1->id, $messages[0]['from_id']);
        $this->assertEquals($this->creator->id, $messages[1]['from_id']);
        $this->assertEquals($this->member2->id, $messages[2]['from_id']);
        $this->assertEquals($this->member1->id, $messages[3]['from_id']);
    }

    /**
     * Test group list includes latest message info
     */
    public function test_group_list_includes_latest_message()
    {
        // Send a message
        ChMessage::create([
            'from_id' => $this->member1->id,
            'to_id' => 0,
            'group_id' => $this->group->id,
            'body' => 'Latest message for list view'
        ]);

        $this->actingAs($this->creator);
        $response = $this->getJson('/groups');

        $response->assertStatus(200);
        $groups = $response->json('groups');
        
        $this->assertCount(1, $groups);
        $this->assertEquals('Latest message for list view', $groups[0]['last_message']);
        $this->assertEquals('Member One', $groups[0]['last_message_sender']);
        $this->assertNotNull($groups[0]['last_message_time']);
    }

    /**
     * Test long message truncation in group list
     */
    public function test_long_message_truncation_in_list()
    {
        $longMessage = str_repeat('This is a very long message. ', 20);
        
        ChMessage::create([
            'from_id' => $this->member1->id,
            'to_id' => 0,
            'group_id' => $this->group->id,
            'body' => $longMessage
        ]);

        $this->actingAs($this->creator);
        $response = $this->getJson('/groups');

        $groups = $response->json('groups');
        $lastMessage = $groups[0]['last_message'];
        
        // Should be truncated (30 chars + "...")
        $this->assertLessThanOrEqual(33, strlen($lastMessage));
        $this->assertStringEndsWith('...', $lastMessage);
    }

    /**
     * Test group without messages shows null for last_message (UI will show member count)
     */
    public function test_empty_group_shows_member_count()
    {
        $this->actingAs($this->creator);
        $response = $this->getJson('/groups');

        $groups = $response->json('groups');
        // Backend returns null, frontend JavaScript converts to "3 members"
        $this->assertNull($groups[0]['last_message']);
        $this->assertEquals(3, $groups[0]['members_count']);
    }

    /**
     * Test message validation
     */
    public function test_message_validation()
    {
        $this->actingAs($this->member1);

        // Empty message should fail
        $response = $this->postJson("/groups/{$this->group->id}/messages", [
            'message' => ''
        ]);

        $response->assertStatus(422);
    }

    /**
     * Test concurrent message fetching
     */
    public function test_concurrent_message_fetching()
    {
        // Create some messages
        for ($i = 1; $i <= 5; $i++) {
            ChMessage::create([
                'from_id' => $this->member1->id,
                'to_id' => 0,
                'group_id' => $this->group->id,
                'body' => "Message $i"
            ]);
        }

        // Multiple users fetch messages concurrently
        $this->actingAs($this->creator);
        $response1 = $this->getJson("/groups/{$this->group->id}/messages");
        
        $this->actingAs($this->member1);
        $response2 = $this->getJson("/groups/{$this->group->id}/messages");
        
        $this->actingAs($this->member2);
        $response3 = $this->getJson("/groups/{$this->group->id}/messages");

        // All should get the same messages
        $this->assertEquals(
            $response1->json('messages'),
            $response2->json('messages')
        );
        $this->assertEquals(
            $response2->json('messages'),
            $response3->json('messages')
        );
    }

    /**
     * Test group info availability for all members
     */
    public function test_all_members_can_access_group_info()
    {
        // Creator
        $this->actingAs($this->creator);
        $response = $this->getJson("/groups/{$this->group->id}");
        $response->assertStatus(200);

        // Member1
        $this->actingAs($this->member1);
        $response = $this->getJson("/groups/{$this->group->id}");
        $response->assertStatus(200);

        // Member2
        $this->actingAs($this->member2);
        $response = $this->getJson("/groups/{$this->group->id}");
        $response->assertStatus(200);
    }

    /**
     * Test message persistence across multiple fetches
     */
    public function test_message_persistence_across_fetches()
    {
        $this->actingAs($this->member1);

        // Send a message
        $this->postJson("/groups/{$this->group->id}/messages", [
            'message' => 'Persistent message'
        ])->assertStatus(200);

        // Fetch messages multiple times
        for ($i = 0; $i < 5; $i++) {
            $response = $this->getJson("/groups/{$this->group->id}/messages");
            $messages = $response->json('messages');
            
            $this->assertCount(1, $messages);
            $this->assertEquals('Persistent message', $messages[0]['body']);
        }
    }

    /**
     * Test sender name is correctly displayed
     */
    public function test_sender_name_correctly_displayed()
    {
        ChMessage::create([
            'from_id' => $this->member1->id,
            'to_id' => 0,
            'group_id' => $this->group->id,
            'body' => 'Message with sender name'
        ]);

        $this->actingAs($this->creator);
        $response = $this->getJson("/groups/{$this->group->id}/messages");
        
        $message = $response->json('messages')[0];
        $this->assertEquals('Member One', $message['from']['name']);
        $this->assertNotEquals('undefined', $message['from']['name']);
        $this->assertNotEquals('null', $message['from']['name']);
        $this->assertNotNull($message['from']['name']);
    }

    /**
     * Test timestamps are not undefined or NaN
     */
    public function test_timestamps_are_valid()
    {
        ChMessage::create([
            'from_id' => $this->member1->id,
            'to_id' => 0,
            'group_id' => $this->group->id,
            'body' => 'Message with timestamp'
        ]);

        $this->actingAs($this->creator);
        $response = $this->getJson("/groups/{$this->group->id}/messages");
        
        $message = $response->json('messages')[0];
        $this->assertNotNull($message['created_at']);
        $this->assertNotEquals('undefined', $message['created_at']);
        $this->assertNotEquals('NaN', $message['created_at']);
        $this->assertIsString($message['created_at']);
    }

    /**
     * Test group list timestamps are valid
     */
    public function test_group_list_timestamps_valid()
    {
        ChMessage::create([
            'from_id' => $this->member1->id,
            'to_id' => 0,
            'group_id' => $this->group->id,
            'body' => 'Message for timestamp check'
        ]);

        $this->actingAs($this->creator);
        $response = $this->getJson('/groups');

        $groups = $response->json('groups');
        $this->assertNotNull($groups[0]['last_message_time']);
        $this->assertNotEquals('undefined', $groups[0]['last_message_time']);
        $this->assertNotEquals('NaN', $groups[0]['last_message_time']);
        $this->assertNotEquals('null', $groups[0]['last_message_time']);
    }
}
