/**
 *-------------------------------------------------------------
 * Message Reactions JavaScript
 * Handles emoji reactions for messages (WhatsApp-style)
 *-------------------------------------------------------------
 */

(function() {
    'use strict';

    // Global variables
    let currentMessageId = null;
    let frequentEmojis = [];
    let allEmojisData = {};
    let debugMode = true; // Enable debug logging
    
    // Helper function for debug logging
    function debugLog(...args) {
        if (debugMode) {
            console.log('[Reactions]', ...args);
        }
    }

    /**
     * Initialize reaction functionality
     */
    function initReactions() {
        loadFrequentEmojis();
        attachEventListeners();
        initializePusherListeners();
    }

    /**
     * Attach event listeners
     */
    function attachEventListeners() {
        // Close picker when clicking outside
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.reaction-picker-content, .reaction-trigger').length) {
                closeReactionPicker();
            }
        });

        // Reaction trigger click (using event delegation)
        $(document).on('click', '.reaction-trigger', function(e) {
            e.stopPropagation();
            const messageId = $(this).data('message-id');
            openReactionPicker(messageId, $(this));
        });

        // Emoji selection
        $(document).on('click', '.emoji-item', function() {
            const emoji = $(this).text().trim();
            addReaction(currentMessageId, emoji);
            closeReactionPicker();
        });

        // More emojis button
        $(document).on('click', '#moreEmojisBtn', function(e) {
            e.stopPropagation();
            toggleAllEmojisSection();
        });

        // Emoji category tabs
        $(document).on('click', '.emoji-tab', function() {
            const category = $(this).data('category');
            switchEmojiCategory(category);
        });

        // Reaction bubble click (toggle reaction)
        $(document).on('click', '.reaction-bubble', function(e) {
            e.stopPropagation();
            const messageId = $(this).closest('.message-reactions-container').data('message-id');
            const emoji = $(this).find('.reaction-bubble-emoji').text().trim();
            
            // If user already reacted with this emoji, remove it
            if ($(this).hasClass('user-reacted')) {
                addReaction(messageId, emoji); // Toggle will remove it
            } else {
                addReaction(messageId, emoji); // Toggle will add it
            }
        });

        // Reaction bubble hover (show details)
        $(document).on('mouseenter', '.reaction-bubble', function(e) {
            showReactionDetails($(this), e);
        });

        $(document).on('mouseleave', '.reaction-bubble', function() {
            hideReactionDetails();
        });
    }

    /**
     * Load frequent emojis from server
     */
    function loadFrequentEmojis() {
        $.ajax({
            url: url + '/reactions/frequent/emojis',
            method: 'GET',
            success: function(response) {
                if (response.success) {
                    frequentEmojis = response.emojis;
                    renderFrequentEmojis();
                }
            },
            error: function() {
                // Use default emojis on error
                frequentEmojis = ['👍', '❤️', '😂', '😮', '😢', '🙏'];
                renderFrequentEmojis();
            }
        });
    }

    /**
     * Render frequent emojis in the picker
     */
    function renderFrequentEmojis() {
        const container = $('#frequentEmojis');
        container.empty();
        
        frequentEmojis.forEach(function(emoji) {
            const emojiItem = $('<div>')
                .addClass('emoji-item')
                .text(emoji)
                .attr('title', emoji);
            container.append(emojiItem);
        });
    }

    /**
     * Load all emojis from server
     */
    function loadAllEmojis() {
        if (Object.keys(allEmojisData).length > 0) {
            renderAllEmojis();
            return;
        }

        $.ajax({
            url: url + '/reactions/all/emojis',
            method: 'GET',
            success: function(response) {
                if (response.success) {
                    allEmojisData = response.emojis;
                    renderAllEmojis();
                }
            },
            error: function() {
                console.error('Failed to load all emojis');
            }
        });
    }

    /**
     * Render all emojis in categories
     */
    function renderAllEmojis() {
        // Render each category
        Object.keys(allEmojisData).forEach(function(category) {
            const container = $(`#${category}Category`);
            if (container.length) {
                container.empty();
                
                allEmojisData[category].forEach(function(emoji) {
                    const emojiItem = $('<div>')
                        .addClass('emoji-item')
                        .text(emoji)
                        .attr('title', emoji);
                    container.append(emojiItem);
                });
            }
        });
    }

    /**
     * Open reaction picker
     */
    function openReactionPicker(messageId, triggerElement) {
        currentMessageId = messageId;
        
        const picker = $('#reactionPicker');
        picker.fadeIn(200);
        
        // Reset to frequent emojis view
        $('#allEmojisSection').hide();
        $('#frequentEmojis').parent().show();
    }

    /**
     * Close reaction picker
     */
    function closeReactionPicker() {
        $('#reactionPicker').fadeOut(200);
        currentMessageId = null;
    }

    /**
     * Toggle all emojis section
     */
    function toggleAllEmojisSection() {
        const allSection = $('#allEmojisSection');
        
        if (allSection.is(':visible')) {
            allSection.slideUp(200);
        } else {
            loadAllEmojis();
            allSection.slideDown(200);
        }
    }

    /**
     * Switch emoji category
     */
    function switchEmojiCategory(category) {
        // Update tabs
        $('.emoji-tab').removeClass('active');
        $(`.emoji-tab[data-category="${category}"]`).addClass('active');
        
        // Update content
        $('.emoji-category').removeClass('active');
        $(`.emoji-category[data-category="${category}"]`).addClass('active');
    }

    /**
     * Add/toggle reaction with optimistic UI update
     */
    function addReaction(messageId, emoji) {
        const userId = auth_id;
        
        debugLog('Adding reaction:', {messageId, emoji, userId});
        
        // Optimistic UI update - update immediately before server response
        updateReactionOptimistically(messageId, emoji, userId);
        
        $.ajax({
            url: url + '/reactions/toggle',
            method: 'POST',
            data: {
                _token: csrfToken,
                message_id: messageId,
                emoji: emoji
            },
            success: function(response) {
                debugLog('Reaction toggle response:', response);
                if (response.success) {
                    // Update with actual server data
                    updateMessageReactions(messageId, response.reactions);
                    
                    // Reload frequent emojis if action was 'added'
                    if (response.action === 'added') {
                        loadFrequentEmojis();
                    }
                }
            },
            error: function(xhr) {
                console.error('Failed to add reaction:', xhr.responseJSON);
                // Revert optimistic update on error
                loadMessageReactions(messageId);
            }
        });
    }
    
    /**
     * Optimistically update reaction UI before server response
     */
    function updateReactionOptimistically(messageId, emoji, userId) {
        const container = $(`.message-reactions-container[data-message-id="${messageId}"]`);
        if (!container.length) {
            console.warn('Message container not found for:', messageId);
            return;
        }
        
        const display = container.find('.message-reactions-display');
        
        // Get current reactions
        let existingBubbles = display.find('.reaction-bubble');
        let found = false;
        let userReacted = false;
        
        // Check if this emoji already exists
        existingBubbles.each(function() {
            const bubbleEmoji = $(this).find('.reaction-bubble-emoji').text().trim();
            if (bubbleEmoji === emoji) {
                found = true;
                const users = $(this).data('users') || [];
                
                // Check if user already reacted with this emoji
                userReacted = users.some(u => u.id == userId);
                
                if (userReacted) {
                    // Remove user's reaction
                    const newUsers = users.filter(u => u.id != userId);
                    if (newUsers.length === 0) {
                        $(this).fadeOut(200, function() { $(this).remove(); });
                    } else {
                        $(this).data('users', newUsers);
                        $(this).find('.reaction-bubble-count').text(newUsers.length);
                        $(this).removeClass('user-reacted');
                    }
                } else {
                    // Add user's reaction
                    users.push({id: parseInt(userId), name: 'You'});
                    $(this).data('users', users);
                    $(this).find('.reaction-bubble-count').text(users.length);
                    $(this).addClass('user-reacted');
                }
            }
        });
        
        // If emoji not found and user didn't have it, create new bubble
        if (!found) {
            const bubble = $('<div>')
                .addClass('reaction-bubble user-reacted')
                .css('display', 'none')
                .data('users', [{id: parseInt(userId), name: 'You'}])
                .html(`
                    <span class="reaction-bubble-emoji">${emoji}</span>
                    <span class="reaction-bubble-count">1</span>
                `);
            display.append(bubble);
            bubble.fadeIn(200);
        }
    }

    /**
     * Update message reactions display
     */
    function updateMessageReactions(messageId, reactions) {
        const container = $(`.message-reactions-container[data-message-id="${messageId}"]`);
        if (!container.length) {
            console.warn('Cannot update reactions - container not found for message:', messageId);
            return;
        }
        
        const display = container.find('.message-reactions-display');
        
        // Clear existing reactions
        display.empty();
        
        if (!reactions || reactions.length === 0) {
            return;
        }
        
        // Render each reaction
        reactions.forEach(function(reaction) {
            const bubble = $('<div>')
                .addClass('reaction-bubble')
                .addClass(reaction.hasReacted ? 'user-reacted' : '')
                .data('users', reaction.users)
                .css('display', 'none')
                .html(`
                    <span class="reaction-bubble-emoji">${reaction.emoji}</span>
                    <span class="reaction-bubble-count">${reaction.count}</span>
                `);
            
            display.append(bubble);
            bubble.fadeIn(150);
        });
    }

    /**
     * Load reactions for messages (called after messages are loaded)
     * Optimized version that batches requests
     */
    function loadReactionsForMessages() {
        const messageIds = [];
        $('.message-card').each(function() {
            const messageId = $(this).data('id');
            if (messageId) {
                messageIds.push(messageId);
            }
        });
        
        if (messageIds.length === 0) return;
        
        // Use batch loading if available, otherwise load individually
        if (messageIds.length > 5) {
            loadReactionsForMessagesOptimized(messageIds);
        } else {
            messageIds.forEach(loadMessageReactions);
        }
    }

    /**
     * Load reactions for a specific message
     */
    function loadMessageReactions(messageId) {
        $.ajax({
            url: url + `/reactions/${messageId}`,
            method: 'GET',
            success: function(response) {
                if (response.success) {
                    updateMessageReactions(messageId, response.reactions);
                }
            },
            error: function() {
                // Silently fail
            }
        });
    }

    /**
     * Show reaction details tooltip
     */
    function showReactionDetails(bubble, event) {
        const users = bubble.data('users');
        if (!users || users.length === 0) return;
        
        const tooltip = $('#reactionDetailsTooltip');
        const content = tooltip.find('.reaction-details-content');
        
        content.empty();
        
        users.forEach(function(user) {
            const userName = $('<span>')
                .addClass('reaction-user-name')
                .text(user.name);
            content.append(userName);
        });
        
        // Position tooltip
        const offset = bubble.offset();
        tooltip.css({
            top: offset.top - tooltip.outerHeight() - 10,
            left: offset.left + (bubble.outerWidth() / 2) - (tooltip.outerWidth() / 2)
        });
        
        tooltip.fadeIn(200);
    }

    /**
     * Hide reaction details tooltip
     */
    function hideReactionDetails() {
        $('#reactionDetailsTooltip').fadeOut(200);
    }
    
    /**
     * Initialize Pusher listeners for real-time reaction updates
     */
    function initializePusherListeners() {
        if (typeof pusher === 'undefined') {
            console.warn('Pusher not available for real-time reactions');
            return;
        }
        
        debugLog('Initializing Pusher listeners for reactions');
        
        // Use setTimeout to ensure channels are initialized
        setTimeout(function() {
            // Listen on the main chatify channel
            if (typeof channel !== 'undefined' && channel) {
                // Unbind first to prevent duplicates
                channel.unbind('message.reaction.updated');
                channel.bind('message.reaction.updated', function(data) {
                    debugLog('Reaction update received on main channel:', data);
                    handleReactionUpdate(data);
                });
                debugLog('Bound to main channel:', channel.name);
            }
            
            // Listen on client channels if they exist
            if (typeof clientListenChannel !== 'undefined' && clientListenChannel) {
                clientListenChannel.unbind('message.reaction.updated');
                clientListenChannel.bind('message.reaction.updated', function(data) {
                    debugLog('Reaction update via client channel:', data);
                    handleReactionUpdate(data);
                });
                debugLog('Bound to client listen channel:', clientListenChannel.name);
            }
        }, 1000);
        
        // Also hook into future channel initializations
        const originalInitClientChannel = window.initClientChannel;
        if (typeof originalInitClientChannel === 'function') {
            window.initClientChannel = function() {
                const result = originalInitClientChannel.apply(this, arguments);
                
                setTimeout(function() {
                    if (typeof clientListenChannel !== 'undefined' && clientListenChannel) {
                        clientListenChannel.unbind('message.reaction.updated');
                        clientListenChannel.bind('message.reaction.updated', function(data) {
                            debugLog('Reaction update via client channel (late bind):', data);
                            handleReactionUpdate(data);
                        });
                        debugLog('Bound to client channel (late):', clientListenChannel.name);
                    }
                }, 500);
                
                return result;
            };
        }
    }
    
    /**
     * Handle real-time reaction updates from Pusher
     */
    function handleReactionUpdate(data) {
        if (!data || !data.message_id) {
            console.warn('Invalid reaction update data:', data);
            return;
        }
        
        const messageId = data.message_id;
        const reactions = data.reactions;
        
        debugLog('Updating reactions for message:', messageId, 'Count:', reactions.length);
        
        // Update the UI with new reactions
        updateMessageReactions(messageId, reactions);
        
        // Show a subtle animation
        const container = $(`.message-reactions-container[data-message-id="${messageId}"]`);
        if (container.length) {
            container.addClass('reaction-updated');
            setTimeout(() => container.removeClass('reaction-updated'), 300);
        } else {
            debugLog('Warning: Message container not found for:', messageId);
        }
    }
    
    /**
     * Batch load reactions for multiple messages (optimized)
     */
    function loadReactionsForMessagesOptimized(messageIds) {
        if (!messageIds || messageIds.length === 0) return;
        
        // Batch request for all message reactions
        $.ajax({
            url: url + '/reactions/batch',
            method: 'POST',
            data: {
                _token: csrfToken,
                message_ids: messageIds
            },
            success: function(response) {
                if (response.success) {
                    Object.keys(response.reactions).forEach(function(messageId) {
                        updateMessageReactions(messageId, response.reactions[messageId]);
                    });
                }
            },
            error: function() {
                // Fallback to individual loads
                messageIds.forEach(loadMessageReactions);
            }
        });
    }

    /**
     * Public API
     */
    window.MessageReactions = {
        init: initReactions,
        loadReactionsForMessages: loadReactionsForMessages,
        loadMessageReactions: loadMessageReactions,
        handleReactionUpdate: handleReactionUpdate,
        updateMessageReactions: updateMessageReactions,
        get debugMode() { return debugMode; },
        set debugMode(value) { debugMode = value; }
    };

    // Auto-initialize when document is ready
    $(document).ready(function() {
        initReactions();
    });

})();
