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
    const lastMessageTime = (group.last_message_time && group.last_message_time !== 'null') ? group.last_message_time : '';
    
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
                        <!-- Temporarily hidden: Time display causing NaN issue -->
                        <!-- <span class="contact-item-time" data-time="${lastMessageTime}">${lastMessageTime ? dateStringToTimeAgo(lastMessageTime) : ''}</span> -->
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
let isOpeningGroup = false;
function openGroupChat(groupId) {
    // Prevent multiple simultaneous opens
    if (isOpeningGroup) {
        return;
    }
    
    isOpeningGroup = true;
    currentGroupId = groupId;
    const groupMessengerId = 'group_' + groupId;
    setMessengerId(groupMessengerId);
    
    // Clear unread count for this group
    clearGroupUnreadCount(groupId);
    
    // Update URL
    const chatifyUrl = $("meta[name=url]").attr("content");
    if (chatifyUrl && window.history) {
        window.history.pushState({}, document.title, `${chatifyUrl}/${groupMessengerId}`);
    }
    
    // Ensure messaging view is visible first and stays visible
    $('.messenger-messagingView').css('display', 'flex').show();
    
    // Enable message input for groups (prevent disable)
    $('#message-form .m-send').removeAttr('readonly').removeAttr('disabled');
    $('.messenger-sendCard button').prop('disabled', false);
    $('.messenger-sendCard').show();
    
    // Then load info and messages
    loadGroupInfo(groupId);
    loadGroupMessages(groupId);
    
    // On mobile, we might want to hide the list view, but typically Chatify handles this via CSS
    if($(window).width() < 768) {
        $('.messenger-listView').hide();
    }
    
    // Reset the flag after a short delay
    setTimeout(() => {
        isOpeningGroup = false;
    }, 500);
}

/**
 * Load group info
 */
function loadGroupInfo(groupId) {
    $.ajax({
        url: `/groups/${groupId}`,
        method: 'GET',
        success: function(data) {
            if (!data || !data.group) {
                console.error('Group data not found');
                return;
            }
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
            
            // Show info view sections
            $('.messenger-infoView-btns .delete-conversation').show();
            $('.messenger-infoView-shared').show();
            
            // Show messaging view and ensure it's visible and stays visible
            $('.messenger-messagingView').css('display', 'flex').show();
            
            // Enable message input and keep it enabled
            $('#message-form .m-send').removeAttr('readonly').removeAttr('disabled');
            $('.messenger-sendCard button').prop('disabled', false);
            $('.messenger-sendCard').show();
            
            // Focus on message input
            $('#message-form .m-send').focus();
        },
        error: function(xhr) {
            // Handle error silently
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
            if (data.messages && data.messages.length > 0) {
                let messagesHtml = '';
                data.messages.forEach(function(msg) {
                    const isOwn = msg.from_id == auth_id;
                    messagesHtml += groupMessageCard(msg, isOwn);
                });
                
                messagesElement.html(messagesHtml);
                
                // Load reactions for group messages
                if (typeof MessageReactions !== 'undefined') {
                    MessageReactions.loadReactionsForMessages();
                }
                
                // Ensure messaging view and messages stay visible
                $('.messenger-messagingView').css('display', 'flex').show();
                $('.messenger-sendCard').show();
                
                // Ensure messages are visible
                setTimeout(function() {
                    scrollToBottom(messagesContainer);
                }, 100);
                
            } else {
                messagesElement.html('<p class="message-hint center-el"><span>No messages yet</span></p>');
            }
        },
        error: function(xhr) {
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
    
    // Handle voice message attachment
    let attachmentHtml = '';
    if (message.attachment) {
        const isVoice = message.attachment.includes('.webm') || 
                       message.attachment.includes('.wav') || 
                       message.attachment.includes('.mp3') ||
                       message.attachment.includes('.ogg');
        
        if (isVoice) {
            attachmentHtml = `
                <div class="voice-message-container">
                    <audio controls>
                        <source src="${message.attachment}" type="audio/webm">
                        <source src="${message.attachment}" type="audio/mpeg">
                        Your browser does not support audio playback.
                    </audio>
                </div>`;
        } else {
            attachmentHtml = `<div class="attachment"><a href="${message.attachment}" target="_blank">📎 View Attachment</a></div>`;
        }
    }
    
    return `
        <div class="message-card ${messageClass}" data-id="${message.id}">
            <div class="message-card-content">
                ${!isOwn ? `<div class="message-sender">${senderName}</div>` : ''}
                <div class="message">
                    ${message.body || ''}
                    ${attachmentHtml}
                    <sub>
                        <span class="time">${message.created_at}</span>
                    </sub>
                </div>
                <div class="message-reactions-container" data-message-id="${message.id}">
                    <div class="message-reactions-display"></div>
                </div>
            </div>
            <div class="reaction-trigger" data-message-id="${message.id}">
                <i class="far fa-smile"></i>
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
    let hasFile = !!$('.upload-attachment').val();
    let hasVoiceRecording = window.voiceRecorder && window.voiceRecorder.hasRecordedAudio();
    
    if (inputValue.length > 0 || hasFile || hasVoiceRecording) {
        const formData = new FormData();
        
        // Handle voice recording
        if (hasVoiceRecording && window.voiceRecorder) {
            const audioFile = window.voiceRecorder.getRecordedAudioFile();
            if (audioFile) {
                formData.set('attachment', audioFile);
                hasFile = true;
            }
        } else if (hasFile) {
            // Handle regular file attachment
            const fileInput = $('.upload-attachment')[0];
            if (fileInput && fileInput.files && fileInput.files.length > 0) {
                formData.set('attachment', fileInput.files[0]);
            }
        }
        
        // Add message text (empty string if only voice message)
        formData.append('message', inputValue || '');
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
                
                // Clear voice recording if present
                if (hasVoiceRecording && window.voiceRecorder) {
                    window.voiceRecorder.removeAudioPreview(true);
                }
            },
            success: function(response) {
                if (response.success) {
                    messagesContainer.find('.messages').append(
                        groupMessageCard(response.message, true)
                    );
                    scrollToBottom(messagesContainer);
                }
            },
            error: function(xhr, status, error) {
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
    
    // Update group message timestamps every 30 seconds
    setInterval(() => {
        $('.group-list-item .contact-item-time').each(function() {
            const time = $(this).attr('data-time');
            if (time) {
                $(this).text(dateStringToTimeAgo(time));
            }
        });
    }, 30000);
});

/**
 * Group unread message counter
 */
const groupUnreadCounts = {};

function incrementGroupUnreadCount(groupId) {
    if (!groupUnreadCounts[groupId]) {
        groupUnreadCounts[groupId] = 0;
    }
    groupUnreadCounts[groupId]++;
    updateGroupUnreadBadge(groupId);
}

function clearGroupUnreadCount(groupId) {
    groupUnreadCounts[groupId] = 0;
    updateGroupUnreadBadge(groupId);
}

function updateGroupUnreadBadge(groupId) {
    const count = groupUnreadCounts[groupId] || 0;
    const groupListItem = $(`.group-list-item[data-group-id="${groupId}"]`);
    
    if (groupListItem.length > 0) {
        // Remove existing badge
        groupListItem.find('tr > td > b').remove();
        
        if (count > 0) {
            // Add badge with count
            const displayCount = count > 99 ? '99+' : count;
            groupListItem.find('tr > td:last-child').append(
                `<b>${displayCount}</b>`
            );
        }
    }
}

// Make functions globally accessible for Pusher event handler
window.incrementGroupUnreadCount = incrementGroupUnreadCount;
window.clearGroupUnreadCount = clearGroupUnreadCount;
