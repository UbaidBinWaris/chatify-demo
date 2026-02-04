/**
 * Friend Request Management
 */

const friendshipUrl = '/friendships';

/**
 * Load pending friend requests
 */
function loadPendingFriendRequests() {
    $.ajax({
        url: `${friendshipUrl}/pending`,
        method: 'GET',
        success: function(response) {
            if (response.success && response.requests.length > 0) {
                let requestsHtml = '';
                response.requests.forEach(function(request) {
                    const user = request.user;
                    requestsHtml += `
                        <table class="messenger-list-item friend-request-item" data-request-id="${request.id}" data-user-id="${user.id}">
                            <tr data-action="0">
                                <td>
                                    <div class="avatar av-m" style="background-image: url('${user.avatar || '/storage/users-avatar/avatar.png'}');"></div>
                                </td>
                                <td>
                                    <p><strong>${user.name}</strong></p>
                                    <span style="font-size: 11px; color: #999;">${timeAgo(request.created_at)}</span>
                                </td>
                                <td style="text-align: right; padding-right: 10px;">
                                    <button class="btn-friend-action btn-accept-request-direct" data-request-id="${request.id}" data-user-id="${user.id}"
                                        style="padding: 5px 10px; font-size: 12px; background: #4caf50; border: none; color: white; border-radius: 4px; cursor: pointer; margin-right: 5px;">
                                        <i class="fas fa-check"></i> Accept
                                    </button>
                                    <button class="btn-friend-action btn-reject-request-direct" data-request-id="${request.id}" data-user-id="${user.id}"
                                        style="padding: 5px 10px; font-size: 12px; background: #f44336; border: none; color: white; border-radius: 4px; cursor: pointer;">
                                        <i class="fas fa-times"></i> Reject
                                    </button>
                                </td>
                            </tr>
                        </table>
                    `;
                });
                
                $('#pending-friend-requests').html(requestsHtml);
                
                // Update badge
                $('#friend-requests-badge').text(response.requests.length).show();
            } else {
                $('#pending-friend-requests').html('<p class="message-hint center-el"><span>No pending requests</span></p>');
                $('#friend-requests-badge').hide();
            }
        },
        error: function(xhr) {
            console.error('Failed to load friend requests:', xhr);
            $('#pending-friend-requests').html('<p class="message-hint center-el"><span>Error loading requests</span></p>');
        }
    });
}

/**
 * Time ago helper
 */
function timeAgo(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const seconds = Math.floor((now - date) / 1000);
    
    if (seconds < 60) return 'just now';
    if (seconds < 3600) return Math.floor(seconds / 60) + ' min ago';
    if (seconds < 86400) return Math.floor(seconds / 3600) + ' hr ago';
    if (seconds < 604800) return Math.floor(seconds / 86400) + ' day ago';
    return Math.floor(seconds / 604800) + ' week ago';
}

/**
 * Send friend request
 */
function sendFriendRequest(userId) {
    $.ajax({
        url: `${friendshipUrl}/send`,
        method: 'POST',
        data: {
            _token: csrfToken,
            friend_id: userId
        },
        success: function(response) {
            if (response.success) {
                // Update button to show pending
                const button = $(`.btn-send-request[data-user-id="${userId}"]`);
                button.removeClass('btn-send-request')
                    .addClass('btn-cancel-request')
                    .html('<i class="fas fa-clock"></i> Pending')
                    .css('background', '#999');
                
                // Update list item
                $(`.messenger-list-item[data-contact="${userId}"]`)
                    .addClass('search-item-no-click')
                    .attr('data-friendship-status', 'request_sent');
                
                showNotification('Friend request sent successfully', 'success');
            }
        },
        error: function(xhr) {
            const error = xhr.responseJSON?.error || 'Failed to send friend request';
            showNotification(error, 'error');
        }
    });
}

/**
 * Cancel friend request
 */
function cancelFriendRequest(userId) {
    // First get the friendship ID
    $.ajax({
        url: `${friendshipUrl}/sent`,
        method: 'GET',
        success: function(response) {
            const friendship = response.requests.find(r => r.friend_id == userId);
            if (friendship) {
                $.ajax({
                    url: `${friendshipUrl}/${friendship.id}/cancel`,
                    method: 'DELETE',
                    data: { _token: csrfToken },
                    success: function(response) {
                        if (response.success) {
                            // Update button to show add friend
                            const button = $(`.btn-cancel-request[data-user-id="${userId}"]`);
                            button.removeClass('btn-cancel-request')
                                .addClass('btn-send-request')
                                .html('<i class="fas fa-user-plus"></i> Add Friend')
                                .css('background', '#2196F3');
                            
                            // Update list item
                            $(`.messenger-list-item[data-contact="${userId}"]`)
                                .attr('data-friendship-status', 'none');
                            
                            showNotification('Friend request cancelled', 'info');
                        }
                    },
                    error: function(xhr) {
                        const error = xhr.responseJSON?.error || 'Failed to cancel request';
                        showNotification(error, 'error');
                    }
                });
            }
        }
    });
}

/**
 * Accept friend request (direct)
 */
function acceptFriendRequestDirect(requestId, userId) {
    $.ajax({
        url: `${friendshipUrl}/${requestId}/accept`,
        method: 'POST',
        data: { _token: csrfToken },
        success: function(response) {
            if (response.success) {
                // Remove the request from the list
                $(`.friend-request-item[data-request-id="${requestId}"]`).fadeOut(300, function() {
                    $(this).remove();
                    
                    // Check if there are any requests left
                    if ($('#pending-friend-requests .friend-request-item').length === 0) {
                        $('#pending-friend-requests').html('<p class="message-hint center-el"><span>No pending requests</span></p>');
                        $('#friend-requests-badge').hide();
                    } else {
                        // Update badge count
                        const count = $('#pending-friend-requests .friend-request-item').length;
                        $('#friend-requests-badge').text(count);
                    }
                });
                
                showNotification('Friend request accepted! You can now chat.', 'success');
                
                // Refresh contacts list to show the new friend
                setTimeout(() => {
                    getContacts();
                }, 1000);
            }
        },
        error: function(xhr) {
            const error = xhr.responseJSON?.error || 'Failed to accept request';
            showNotification(error, 'error');
        }
    });
}

/**
 * Reject friend request (direct)
 */
function rejectFriendRequestDirect(requestId, userId) {
    $.ajax({
        url: `${friendshipUrl}/${requestId}/reject`,
        method: 'POST',
        data: { _token: csrfToken },
        success: function(response) {
            if (response.success) {
                // Remove the request from the list
                $(`.friend-request-item[data-request-id="${requestId}"]`).fadeOut(300, function() {
                    $(this).remove();
                    
                    // Check if there are any requests left
                    if ($('#pending-friend-requests .friend-request-item').length === 0) {
                        $('#pending-friend-requests').html('<p class="message-hint center-el"><span>No pending requests</span></p>');
                        $('#friend-requests-badge').hide();
                    } else {
                        // Update badge count
                        const count = $('#pending-friend-requests .friend-request-item').length;
                        $('#friend-requests-badge').text(count);
                    }
                });
                
                showNotification('Friend request rejected', 'info');
            }
        },
        error: function(xhr) {
            const error = xhr.responseJSON?.error || 'Failed to reject request';
            showNotification(error, 'error');
        }
    });
}

/**
 * Accept friend request
 */
function acceptFriendRequest(userId) {
    // First get the friendship ID
    $.ajax({
        url: `${friendshipUrl}/pending`,
        method: 'GET',
        success: function(response) {
            const friendship = response.requests.find(r => r.user_id == userId);
            if (friendship) {
                $.ajax({
                    url: `${friendshipUrl}/${friendship.id}/accept`,
                    method: 'POST',
                    data: { _token: csrfToken },
                    success: function(response) {
                        if (response.success) {
                            // Remove buttons and show friends badge
                            const listItem = $(`.messenger-list-item[data-contact="${userId}"]`);
                            listItem.removeClass('search-item-no-click')
                                .attr('data-friendship-status', 'friends');
                            
                            listItem.find('td:last-child').html(
                                '<span class="friendship-badge" style="color: #4caf50; font-size: 12px;">' +
                                '<i class="fas fa-check-circle"></i> Friends</span>'
                            );
                            
                            showNotification('Friend request accepted! You can now chat.', 'success');
                            
                            // Refresh contacts list to show the new friend
                            setTimeout(() => {
                                getContacts();
                            }, 1000);
                        }
                    },
                    error: function(xhr) {
                        const error = xhr.responseJSON?.error || 'Failed to accept request';
                        showNotification(error, 'error');
                    }
                });
            }
        }
    });
}

/**
 * Reject friend request
 */
function rejectFriendRequest(userId) {
    // First get the friendship ID
    $.ajax({
        url: `${friendshipUrl}/pending`,
        method: 'GET',
        success: function(response) {
            const friendship = response.requests.find(r => r.user_id == userId);
            if (friendship) {
                $.ajax({
                    url: `${friendshipUrl}/${friendship.id}/reject`,
                    method: 'POST',
                    data: { _token: csrfToken },
                    success: function(response) {
                        if (response.success) {
                            // Update button to show add friend
                            const listItem = $(`.messenger-list-item[data-contact="${userId}"]`);
                            listItem.attr('data-friendship-status', 'none');
                            
                            listItem.find('td:last-child').html(
                                `<button class="btn-friend-action btn-send-request" data-user-id="${userId}" ` +
                                'style="padding: 5px 10px; font-size: 12px; background: #2196F3; border: none; ' +
                                'color: white; border-radius: 4px; cursor: pointer;">' +
                                '<i class="fas fa-user-plus"></i> Add Friend</button>'
                            );
                            
                            showNotification('Friend request rejected', 'info');
                        }
                    },
                    error: function(xhr) {
                        const error = xhr.responseJSON?.error || 'Failed to reject request';
                        showNotification(error, 'error');
                    }
                });
            }
        }
    });
}

/**
 * Show notification
 */
function showNotification(message, type = 'info') {
    const colors = {
        success: '#4caf50',
        error: '#f44336',
        info: '#2196F3',
        warning: '#ff9800'
    };
    
    const notification = $('<div>')
        .css({
            position: 'fixed',
            top: '20px',
            right: '20px',
            padding: '15px 20px',
            background: colors[type] || colors.info,
            color: 'white',
            borderRadius: '4px',
            boxShadow: '0 2px 5px rgba(0,0,0,0.2)',
            zIndex: 10000,
            maxWidth: '300px',
            animation: 'slideInRight 0.3s ease-out'
        })
        .text(message);
    
    $('body').append(notification);
    
    setTimeout(() => {
        notification.fadeOut(300, function() {
            $(this).remove();
        });
    }, 3000);
}

/**
 * Event Listeners
 */
$(document).ready(function() {
    // Load pending friend requests on page load
    loadPendingFriendRequests();
    
    // Reload friend requests when switching to the requests tab
    $(document).on('click', '.messenger-listView-tabs a[data-view="friendRequests"]', function() {
        loadPendingFriendRequests();
    });
    
    // Send friend request
    $('body').on('click', '.btn-send-request', function(e) {
        e.stopPropagation();
        const userId = $(this).data('user-id');
        sendFriendRequest(userId);
    });
    
    // Cancel friend request
    $('body').on('click', '.btn-cancel-request', function(e) {
        e.stopPropagation();
        const userId = $(this).data('user-id');
        cancelFriendRequest(userId);
    });
    
    // Accept friend request (from search)
    $('body').on('click', '.btn-accept-request', function(e) {
        e.stopPropagation();
        const userId = $(this).data('user-id');
        acceptFriendRequest(userId);
    });
    
    // Reject friend request (from search)
    $('body').on('click', '.btn-reject-request', function(e) {
        e.stopPropagation();
        const userId = $(this).data('user-id');
        rejectFriendRequest(userId);
    });
    
    // Accept friend request (from requests tab)
    $('body').on('click', '.btn-accept-request-direct', function(e) {
        e.stopPropagation();
        const requestId = $(this).data('request-id');
        const userId = $(this).data('user-id');
        acceptFriendRequestDirect(requestId, userId);
    });
    
    // Reject friend request (from requests tab)
    $('body').on('click', '.btn-reject-request-direct', function(e) {
        e.stopPropagation();
        const requestId = $(this).data('request-id');
        const userId = $(this).data('user-id');
        rejectFriendRequestDirect(requestId, userId);
    });
    
    // Prevent clicking on search items that aren't friends
    $('body').on('click', '.search-item-no-click', function(e) {
        const friendshipStatus = $(this).data('friendship-status');
        if (friendshipStatus !== 'friends') {
            e.stopPropagation();
            e.preventDefault();
            
            if (friendshipStatus === 'none') {
                showNotification('Please send a friend request first', 'info');
            } else if (friendshipStatus === 'request_sent') {
                showNotification('Waiting for friend request to be accepted', 'info');
            } else if (friendshipStatus === 'request_received') {
                showNotification('Please accept the friend request to chat', 'info');
            }
            
            return false;
        }
    });
});

// Add CSS animation
if (!document.getElementById('friendship-animations')) {
    const style = document.createElement('style');
    style.id = 'friendship-animations';
    style.textContent = `
        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        .btn-friend-action:hover {
            opacity: 0.9;
            transform: scale(1.05);
            transition: all 0.2s ease;
        }
        
        .search-item-no-click {
            cursor: default !important;
        }
        
        .search-item-no-click p[data-type="user"] {
            cursor: default !important;
        }
        
        #friend-requests-badge {
            background: #f44336;
            color: white;
            border-radius: 10px;
            padding: 2px 6px;
            font-size: 10px;
            margin-left: 5px;
            font-weight: bold;
        }
        
        .friend-request-item {
            cursor: default !important;
        }
        
        .friend-request-item:hover {
            background-color: rgba(0, 0, 0, 0.03);
        }
    `;
    document.head.appendChild(style);
}
