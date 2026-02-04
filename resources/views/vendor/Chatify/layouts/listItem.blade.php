{{-- -------------------- Saved Messages -------------------- --}}
@if($get == 'saved')
    <table class="messenger-list-item" data-contact="{{ Auth::user()->id }}">
        <tr data-action="0">
            {{-- Avatar side --}}
            <td>
            <div class="saved-messages avatar av-m">
                <span class="far fa-bookmark"></span>
            </div>
            </td>
            {{-- center side --}}
            <td>
                <p data-id="{{ Auth::user()->id }}" data-type="user">Saved Messages <span>You</span></p>
                <span>Save messages secretly</span>
            </td>
        </tr>
    </table>
@endif

{{-- -------------------- Contact list -------------------- --}}
@if($get == 'users' && !!$lastMessage)
<?php
$lastMessageBody = mb_convert_encoding($lastMessage->body, 'UTF-8', 'UTF-8');
$lastMessageBody = strlen($lastMessageBody) > 30 ? mb_substr($lastMessageBody, 0, 30, 'UTF-8').'..' : $lastMessageBody;
?>
<table class="messenger-list-item" data-contact="{{ $user->id }}">
    <tr data-action="0">
        {{-- Avatar side --}}
        <td style="position: relative">
            @if($user->active_status)
                <span class="activeStatus"></span>
            @endif
        <div class="avatar av-m"
        style="background-image: url('{{ $user->avatar }}');">
        </div>
        </td>
        {{-- center side --}}
        <td>
        <p data-id="{{ $user->id }}" data-type="user">
            {{ strlen($user->name) > 12 ? trim(substr($user->name,0,12)).'..' : $user->name }}
            <span class="contact-item-time" data-time="{{$lastMessage->created_at}}">{{ $lastMessage->timeAgo }}</span>
        </p>
        @if($user->active_status)
             <p class="user-status-text" data-status="online" style="font-size: 10px; color: #4caf50; margin: 0;">Active Now</p>
        @elseif($user->last_seen_at)
             <p class="user-status-text" data-status="offline" data-last-seen="{{ $user->last_seen_at }}" style="font-size: 10px; color: #999; margin: 0;">Last seen {{ \Carbon\Carbon::parse($user->last_seen_at)->diffForHumans() }}</p>
        @endif
        <span>
            {{-- Last Message user indicator --}}
            {!!
                $lastMessage->from_id == Auth::user()->id
                ? '<span class="lastMessageIndicator">You :</span>'
                : ''
            !!}
            {{-- Last message body --}}
            @if($lastMessage->attachment == null)
            {!!
                $lastMessageBody
            !!}
            @else
            <span class="fas fa-file"></span> Attachment
            @endif
        </span>
        {{-- New messages counter --}}
            {!! $unseenCounter > 0 ? "<b>".$unseenCounter."</b>" : '' !!}
        </td>
    </tr>
</table>
@endif

{{-- -------------------- Search Item -------------------- --}}
@if($get == 'search_item')
<table class="messenger-list-item {{ isset($friendshipStatus) && $friendshipStatus == 'friends' ? '' : 'search-item-no-click' }}" data-contact="{{ $user->id }}" data-friendship-status="{{ $friendshipStatus ?? 'none' }}">
    <tr data-action="0">
        {{-- Avatar side --}}
        <td>
        <div class="avatar av-m"
        style="background-image: url('{{ $user->avatar }}');">
        </div>
        </td>
        {{-- center side --}}
        <td>
            <p data-id="{{ $user->id }}" data-type="user">
            {{ strlen($user->name) > 12 ? trim(substr($user->name,0,12)).'..' : $user->name }}
            </p>
        </td>
        {{-- Action buttons based on friendship status --}}
        <td style="text-align: right; padding-right: 10px;">
            @if(isset($friendshipStatus))
                @if($friendshipStatus == 'friends')
                    <span class="friendship-badge" style="color: #4caf50; font-size: 12px;">
                        <i class="fas fa-check-circle"></i> Friends
                    </span>
                @elseif($friendshipStatus == 'request_sent')
                    <button class="btn-friend-action btn-cancel-request" data-user-id="{{ $user->id }}" style="padding: 5px 10px; font-size: 12px; background: #999; border: none; color: white; border-radius: 4px; cursor: pointer;">
                        <i class="fas fa-clock"></i> Pending
                    </button>
                @elseif($friendshipStatus == 'request_received')
                    <button class="btn-friend-action btn-accept-request" data-user-id="{{ $user->id }}" style="padding: 5px 10px; font-size: 12px; background: #4caf50; border: none; color: white; border-radius: 4px; cursor: pointer; margin-right: 5px;">
                        <i class="fas fa-check"></i> Accept
                    </button>
                    <button class="btn-friend-action btn-reject-request" data-user-id="{{ $user->id }}" style="padding: 5px 10px; font-size: 12px; background: #f44336; border: none; color: white; border-radius: 4px; cursor: pointer;">
                        <i class="fas fa-times"></i>
                    </button>
                @else
                    <button class="btn-friend-action btn-send-request" data-user-id="{{ $user->id }}" style="padding: 5px 10px; font-size: 12px; background: #2196F3; border: none; color: white; border-radius: 4px; cursor: pointer;">
                        <i class="fas fa-user-plus"></i> Add Friend
                    </button>
                @endif
            @endif
        </td>
    </tr>
</table>
@endif

{{-- -------------------- Shared photos Item -------------------- --}}
@if($get == 'sharedPhoto')
<div class="shared-photo chat-image" style="background-image: url('{{ $image }}')"></div>
@endif


