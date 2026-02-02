/**
 *-------------------------------------------------------------
 * Group Management JavaScript
 *-------------------------------------------------------------
 */

let selectedMembers = [];
let currentGroupId = null;

/**
 * Load user's groups
 */
function loadGroups() {
    $.ajax({
        url: '/groups',
        method: 'GET',
        success: function(data) {
            $('.listOfGroups').html('');
            if (data.groups && data.groups.length > 0) {
                data.groups.forEach(function(group) {
                    $('.listOfGroups').append(groupListItem(group));
                });
            } else {
                $('.listOfGroups').html('<p class="message-hint center-el"><span>No groups yet</span></p>');
            }
        },
        error: function() {
            console.error('Failed to load groups');
        }
    });
}

/**
 * Group list item template
 */
function groupListItem(group) {
    const initials = group.name.substring(0, 2).toUpperCase();
    const lastMessage = group.last_message || `${group.members_count} members`;
    const lastMessageSender = group.last_message_sender || '';
    const lastMessageTime = group.last_message_time || '';
    
    return `
    <table class="messenger-list-item group-list-item" data-group-id="${group.id}">
        <tbody>
            <tr data-action="0">
                <td style="position: relative">
                    <div class="avatar av-m" style="background-color: var(--primary-color) !important; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; background-image: none;">
                        ${initials}
                    </div>
                </td>
                <td>
                    <p data-id="${group.id}" data-type="group">
                        ${group.name}
                        <span class="contact-item-time">${lastMessageTime}</span>
                    </p>
                    <span>
                        ${lastMessageSender ? `<span class="lastMessageIndicator">${lastMessageSender}</span>` : ''}
                        ${lastMessage}
                    </span>
                </td>
            </tr>
        </tbody>
    </table>`;
}

/**
 * Show create group modal
 */
function showCreateGroupModal() {
    selectedMembers = [];
    $('#group-name').val('');
    $('#group-description').val('');
    $('#selected-members').html('');
    $('#search-members').val('');
    $('#search-results').hide();
    app_modal({ show: true, name: 'createGroup' });
}

/**
 * Search users for group
 */
let searchTimeout;
function searchUsersForGroup(query) {
    clearTimeout(searchTimeout);
    
    if (query.length < 2) {
        $('#search-results').hide();
        return;
    }
    
    searchTimeout = setTimeout(function() {
        $.ajax({
            url: '/groups/search/users',
            method: 'GET',
            data: { query: query, group_id: currentGroupId },
            success: function(data) {
                $('#search-results').html('');
                if (data.users && data.users.length > 0) {
                    data.users.forEach(function(user) {
                        if (!selectedMembers.includes(user.id)) {
                            $('#search-results').append(`
                                <div class="search-result-item" data-user-id="${user.id}" data-user-name="${user.name}">
                                    <strong>${user.name}</strong>
                                    <br><small>${user.email}</small>
                                </div>
                            `);
                        }
                    });
                    $('#search-results').show();
                } else {
                    $('#search-results').html('<div class="search-result-item">No users found</div>').show();
                }
            }
        });
    }, 300);
}

/**
 * Add member to selection
 */
function addMember(userId, userName) {
    if (!selectedMembers.includes(userId)) {
        selectedMembers.push(userId);
        $('#selected-members').append(`
            <span class="selected-member" data-user-id="${userId}">
                ${userName}
                <span class="remove-member" onclick="removeMember(${userId})">×</span>
            </span>
        `);
        $('#group-members').val(selectedMembers.join(','));
    }
    $('#search-members').val('');
    $('#search-results').hide();
}

/**
 * Remove member from selection
 */
function removeMember(userId) {
    selectedMembers = selectedMembers.filter(id => id !== userId);
    $(`.selected-member[data-user-id="${userId}"]`).remove();
    $('#group-members').val(selectedMembers.join(','));
}

/**
 * Create group
 */
function createGroup(formData) {
    if (selectedMembers.length === 0) {
        alert('Please select at least one member');
        return;
    }
    
    $.ajax({
        url: '/groups',
        method: 'POST',
        data: {
            _token: csrfToken,
            name: formData.name,
            description: formData.description,
            members: selectedMembers
        },
        success: function(response) {
            if (response.success) {
                app_modal({ show: false, name: 'createGroup' });
                loadGroups();
                alert('Group created successfully!');
            }
        },
        error: function(xhr) {
            if (xhr.responseJSON && xhr.responseJSON.errors) {
                const errors = Object.values(xhr.responseJSON.errors).flat();
                alert('Error: ' + errors.join(', '));
            } else {
                alert('Failed to create group');
            }
        }
    });
}

/**
 * Open group chat
 */
function openGroupChat(groupId) {
    console.log('Opening group chat:', groupId);
    currentGroupId = groupId;
    setMessengerId('group_' + groupId);
    
    // Ensure messaging view is visible first
    $('.messenger-messagingView').css('display', 'flex').show();
    
    // Then load info and messages
    loadGroupInfo(groupId);
    loadGroupMessages(groupId);
    
    // On mobile, we might want to hide the list view, but typically Chatify handles this via CSS
    if($(window).width() < 768) {
        $('.messenger-listView').hide();
    }
}

/**
 * Load group info
 */
function loadGroupInfo(groupId) {
    $.ajax({
        url: `/groups/${groupId}`,
        method: 'GET',
        success: function(data) {
            const group = data.group;
            const initials = group.name.substring(0, 2).toUpperCase();
            
            // Update header
            $('.messenger-headTitle').text(group.name);
            $('.header-avatar').html(`<div class="group-avatar">${initials}</div>`);
            
            // Update info sidebar
            let membersHTML = '<h4>Members (' + group.members.length + ')</h4><ul>';
            group.members.forEach(function(member) {
                membersHTML += `<li>${member.name} ${member.is_admin ? '(Admin)' : ''}</li>`;
            });
            membersHTML += '</ul>';
            
            $('.messenger-infoView nav p').text('Group Details');
            $('.messenger-infoView .avatar').html(`<div class="group-avatar">${initials}</div>`);
            
            // Show messaging view and ensure it's visible
            $('.messenger-messagingView').css('display', 'flex').show();
        },
        error: function(xhr) {
            console.error('Failed to load group info:', xhr);
        }
    });
}

/**
 * Load group messages
 */
function loadGroupMessages(groupId) {
    const messagesElement = messagesContainer.find('.messages');
    
    // Clear existing messages first
    messagesElement.html('');
    
    $.ajax({
        url: `/groups/${groupId}/messages`,
        method: 'GET',
        success: function(data) {
            console.log('Group messages loaded:', data);
            
            if (data.messages && data.messages.length > 0) {
                let messagesHtml = '';
                data.messages.forEach(function(msg) {
                    const isOwn = msg.from_id == auth_id;
                    messagesHtml += groupMessageCard(msg, isOwn);
                });
                messagesElement.html(messagesHtml);
                
                // Ensure messages are visible
                setTimeout(function() {
                    scrollToBottom(messagesContainer);
                }, 100);
            } else {
                messagesElement.html('<p class="message-hint center-el"><span>No messages yet</span></p>');
            }
        },
        error: function(xhr) {
            console.error('Failed to load group messages:', xhr);
            messagesElement.html('<p class="message-hint center-el"><span>Failed to load messages</span></p>');
        }
    });
}

/**
 * Group message card template
 */
function groupMessageCard(message, isOwn) {
    const messageClass = isOwn ? 'mc-sender' : 'mc-receiver';
    const senderName = isOwn ? 'You' : message.from.name;
    
    return `
        <div class="message-card ${messageClass}" data-id="${message.id}">
            <div class="message-card-content">
                ${!isOwn ? `<div class="message-sender">${senderName}</div>` : ''}
                <div class="message">
                    ${message.body || ''}
                    ${message.attachment ? `<div class="attachment"><a href="${message.attachment}" target="_blank">View Attachment</a></div>` : ''}
                    <sub>
                        <span class="time">${message.created_at}</span>
                    </sub>
                </div>
            </div>
        </div>
    `;
}

/**
 * Send group message
 */
function sendGroupMessage() {
    const messengerId = getMessengerId();
    if (!messengerId || !messengerId.startsWith('group_')) {
        return sendMessage(); // Fall back to normal message sending
    }
    
    const groupId = messengerId.replace('group_', '');
    const inputValue = $.trim(messageInput.val());
    const hasFile = !!$('.upload-attachment').val();
    
    if (inputValue.length > 0 || hasFile) {
        const formData = new FormData($('#message-form')[0]);
        formData.append('message', inputValue);
        formData.append('_token', csrfToken);
        
        $.ajax({
            url: `/groups/${groupId}/messages`,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function() {
                messageInput.val('');
                $('#message-form').trigger('reset');
            },
            success: function(response) {
                if (response.success) {
                    messagesContainer.find('.messages').append(
                        groupMessageCard(response.message, true)
                    );
                    scrollToBottom(messagesContainer);
                }
            },
            error: function() {
                alert('Failed to send message');
            }
        });
    }
}

/**
 *-------------------------------------------------------------
 * Event Listeners
 *-------------------------------------------------------------
 */

$(document).ready(function() {
    // Create group button
    $('#create-group-btn').on('click', function() {
        showCreateGroupModal();
    });
    
    // Create group form submission
    $('#create-group-form').on('submit', function(e) {
        e.preventDefault();
        const formData = {
            name: $('#group-name').val(),
            description: $('#group-description').val()
        };
        createGroup(formData);
    });
    
    // Search members input
    $('#search-members').on('input', function() {
        searchUsersForGroup($(this).val());
    });
    
    // Click on search result
    $(document).on('click', '.search-result-item', function() {
        const userId = $(this).data('user-id');
        const userName = $(this).data('user-name');
        if (userId && userName) {
            addMember(userId, userName);
        }
    });
    
    // Click on group list item
    $(document).on('click', '.group-list-item', function() {
        // Toggle active state
        $('.group-list-item').removeClass('m-list-active');
        $(this).addClass('m-list-active');

        const groupId = $(this).data('group-id');
        openGroupChat(groupId);
    });
    
    // Tab switching
    $('.messenger-listView-tabs a').on('click', function(e) {
        e.preventDefault();
        const view = $(this).data('view');
        
        // Update active tab
        $('.messenger-listView-tabs a').removeClass('active-tab');
        $(this).addClass('active-tab');
        
        // Show corresponding tab
        $('.messenger-tab').removeClass('show');
        $(`.messenger-tab[data-view="${view}"]`).addClass('show');
        
        // Load groups if groups tab is selected
        if (view === 'groups') {
            loadGroups();
        }
    });
    
    // Override message send for groups
    const originalSendMessage = window.sendMessage;
    window.sendMessage = function() {
        const messengerId = getMessengerId();
        if (messengerId && messengerId.startsWith('group_')) {
            sendGroupMessage();
        } else {
            originalSendMessage();
        }
    };
});
