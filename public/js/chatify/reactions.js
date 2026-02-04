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

    /**
     * Initialize reaction functionality
     */
    function initReactions() {
        loadFrequentEmojis();
        attachEventListeners();
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
     * Add/toggle reaction
     */
    function addReaction(messageId, emoji) {
        $.ajax({
            url: url + '/reactions/toggle',
            method: 'POST',
            data: {
                _token: csrfToken,
                message_id: messageId,
                emoji: emoji
            },
            success: function(response) {
                if (response.success) {
                    updateMessageReactions(messageId, response.reactions);
                    
                    // Reload frequent emojis if action was 'added'
                    if (response.action === 'added') {
                        loadFrequentEmojis();
                    }
                }
            },
            error: function(xhr) {
                console.error('Failed to add reaction:', xhr.responseJSON);
            }
        });
    }

    /**
     * Update message reactions display
     */
    function updateMessageReactions(messageId, reactions) {
        const container = $(`.message-reactions-container[data-message-id="${messageId}"]`);
        const display = container.find('.message-reactions-display');
        
        display.empty();
        
        if (reactions.length === 0) {
            return;
        }
        
        reactions.forEach(function(reaction) {
            const bubble = $('<div>')
                .addClass('reaction-bubble')
                .addClass(reaction.hasReacted ? 'user-reacted' : '')
                .data('users', reaction.users)
                .html(`
                    <span class="reaction-bubble-emoji">${reaction.emoji}</span>
                    <span class="reaction-bubble-count">${reaction.count}</span>
                `);
            
            display.append(bubble);
        });
    }

    /**
     * Load reactions for messages (called after messages are loaded)
     */
    function loadReactionsForMessages() {
        $('.message-card').each(function() {
            const messageId = $(this).data('id');
            if (messageId) {
                loadMessageReactions(messageId);
            }
        });
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
     * Public API
     */
    window.MessageReactions = {
        init: initReactions,
        loadReactionsForMessages: loadReactionsForMessages,
        loadMessageReactions: loadMessageReactions
    };

    // Auto-initialize when document is ready
    $(document).ready(function() {
        initReactions();
    });

})();
