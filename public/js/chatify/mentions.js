/**
 *-------------------------------------------------------------
 * Mentions System for Group Chat
 *-------------------------------------------------------------
 */

class MentionSystem {
    constructor() {
        this.isActive = false;
        this.mentionStartPos = -1;
        this.searchText = '';
        this.selectedIndex = 0;
        this.groupMembers = [];
        this.dropdown = null;
        this.messageInput = null;
        this.currentGroupId = null;
        
        this.init();
    }
    
    init() {
        this.messageInput = $('.m-send');
        this.createDropdown();
        this.attachEventListeners();
    }
    
    createDropdown() {
        // Create mention dropdown element
        const dropdownHtml = `
            <div class="mention-dropdown" id="mention-dropdown" style="display: none;">
                <div class="mention-dropdown-header">Select a member to mention</div>
                <div class="mention-dropdown-list" id="mention-list"></div>
            </div>
        `;
        
        $('.messenger-sendCard').append(dropdownHtml);
        this.dropdown = $('#mention-dropdown');
        
        // Add CSS styles
        if (!$('#mention-styles').length) {
            $('<style id="mention-styles">')
                .text(`
                    .mention-dropdown {
                        position: absolute;
                        bottom: 100%;
                        left: 0;
                        right: 0;
                        max-height: 250px;
                        background: var(--primary-bg-color);
                        border: 1px solid var(--border-color);
                        border-radius: 8px 8px 0 0;
                        box-shadow: 0 -4px 12px rgba(0, 0, 0, 0.1);
                        z-index: 1000;
                        overflow: hidden;
                    }
                    
                    .mention-dropdown-header {
                        padding: 10px 15px;
                        font-size: 12px;
                        font-weight: 600;
                        color: var(--text-secondary);
                        border-bottom: 1px solid var(--border-color);
                        background: var(--secondary-bg-color);
                    }
                    
                    .mention-dropdown-list {
                        max-height: 200px;
                        overflow-y: auto;
                    }
                    
                    .mention-item {
                        padding: 10px 15px;
                        cursor: pointer;
                        display: flex;
                        align-items: center;
                        gap: 10px;
                        transition: background 0.2s;
                    }
                    
                    .mention-item:hover,
                    .mention-item.selected {
                        background: var(--hover-bg-color);
                    }
                    
                    .mention-item-avatar {
                        width: 32px;
                        height: 32px;
                        border-radius: 50%;
                        background: var(--primary-color);
                        color: white;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        font-weight: 600;
                        font-size: 12px;
                        flex-shrink: 0;
                    }
                    
                    .mention-item-info {
                        flex: 1;
                        min-width: 0;
                    }
                    
                    .mention-item-name {
                        font-weight: 500;
                        font-size: 14px;
                        color: var(--text-color);
                        white-space: nowrap;
                        overflow: hidden;
                        text-overflow: ellipsis;
                    }
                    
                    .mention-item-username {
                        font-size: 12px;
                        color: var(--text-secondary);
                    }
                    
                    .mention-item.all {
                        background: linear-gradient(90deg, rgba(var(--primary-color-rgb), 0.05) 0%, transparent 100%);
                        border-left: 3px solid var(--primary-color);
                    }
                    
                    .mention-item.all .mention-item-avatar {
                        background: var(--primary-color);
                    }
                    
                    .mention-highlight {
                        background: #e3f2fd;
                        color: #1976d2;
                        font-weight: 500;
                        padding: 2px 4px;
                        border-radius: 3px;
                    }
                    
                    .dark-mode .mention-highlight {
                        background: rgba(25, 118, 210, 0.2);
                        color: #64b5f6;
                    }
                `)
                .appendTo('head');
        }
    }
    
    attachEventListeners() {
        const self = this;
        
        // Handle input for @ trigger
        this.messageInput.on('input keydown', function(e) {
            if (e.type === 'input') {
                self.handleInput();
            } else if (e.type === 'keydown') {
                self.handleKeydown(e);
            }
        });
        
        // Handle click outside to close dropdown
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.mention-dropdown, .m-send').length) {
                self.hideDropdown();
            }
        });
        
        // Handle mention item click
        $(document).on('click', '.mention-item', function() {
            const userId = $(this).data('user-id');
            const userName = $(this).data('user-name');
            self.insertMention(userId, userName);
        });
    }
    
    handleInput() {
        const text = this.messageInput.val();
        const cursorPos = this.messageInput[0].selectionStart;
        
        // Find @ symbol before cursor
        let atPos = -1;
        for (let i = cursorPos - 1; i >= 0; i--) {
            if (text[i] === '@') {
                // Check if @ is at start or preceded by whitespace
                if (i === 0 || /\s/.test(text[i - 1])) {
                    atPos = i;
                    break;
                }
            } else if (/\s/.test(text[i])) {
                break;
            }
        }
        
        if (atPos !== -1) {
            this.mentionStartPos = atPos;
            this.searchText = text.substring(atPos + 1, cursorPos);
            this.showDropdown();
        } else {
            this.hideDropdown();
        }
    }
    
    handleKeydown(e) {
        if (!this.isActive) return;
        
        const items = $('.mention-item');
        const visibleItems = items.filter(':visible');
        
        switch(e.key) {
            case 'ArrowDown':
                e.preventDefault();
                this.selectedIndex = Math.min(this.selectedIndex + 1, visibleItems.length - 1);
                this.updateSelection();
                break;
                
            case 'ArrowUp':
                e.preventDefault();
                this.selectedIndex = Math.max(this.selectedIndex - 1, 0);
                this.updateSelection();
                break;
                
            case 'Enter':
            case 'Tab':
                if (this.isActive && visibleItems.length > 0) {
                    e.preventDefault();
                    const selected = visibleItems.eq(this.selectedIndex);
                    selected.click();
                }
                break;
                
            case 'Escape':
                e.preventDefault();
                this.hideDropdown();
                break;
        }
    }
    
    async loadGroupMembers(groupId) {
        this.currentGroupId = groupId;
        
        try {
            const response = await $.ajax({
                url: `/groups/${groupId}/members`,
                method: 'GET'
            });
            
            this.groupMembers = response.members || [];
        } catch(error) {
            console.error('Failed to load group members:', error);
            this.groupMembers = [];
        }
    }
    
    showDropdown() {
        if (!this.currentGroupId) {
            const messengerId = getMessengerId();
            if (messengerId && messengerId.startsWith('group_')) {
                const groupId = messengerId.replace('group_', '');
                this.loadGroupMembers(groupId);
            } else {
                return; // Not in a group chat
            }
        }
        
        this.isActive = true;
        this.selectedIndex = 0;
        this.renderMembers();
        this.dropdown.fadeIn(150);
        this.updateSelection();
    }
    
    hideDropdown() {
        this.isActive = false;
        this.mentionStartPos = -1;
        this.searchText = '';
        this.selectedIndex = 0;
        this.dropdown.fadeOut(150);
    }
    
    renderMembers() {
        const searchLower = this.searchText.toLowerCase();
        const currentUserId = parseInt(auth_id);
        
        // Filter members based on search
        let filteredMembers = this.groupMembers.filter(member => 
            member.id !== currentUserId &&
            (member.name.toLowerCase().includes(searchLower) ||
             member.email.toLowerCase().includes(searchLower))
        );
        
        let html = '';
        
        // Add "All" option at the top if search is empty or matches
        if (!searchLower || 'all'.includes(searchLower) || 'everyone'.includes(searchLower)) {
            html += `
                <div class="mention-item all" data-user-id="all" data-user-name="all">
                    <div class="mention-item-avatar">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="mention-item-info">
                        <div class="mention-item-name">All</div>
                        <div class="mention-item-username">Mention everyone in the group</div>
                    </div>
                </div>
            `;
        }
        
        // Add filtered members
        filteredMembers.forEach(member => {
            const initials = member.name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
            html += `
                <div class="mention-item" data-user-id="${member.id}" data-user-name="${member.name}">
                    <div class="mention-item-avatar">${initials}</div>
                    <div class="mention-item-info">
                        <div class="mention-item-name">${this.highlightMatch(member.name, searchLower)}</div>
                        <div class="mention-item-username">${member.email}</div>
                    </div>
                </div>
            `;
        });
        
        if (!html) {
            html = '<div class="mention-item" style="pointer-events: none; opacity: 0.5;">No members found</div>';
        }
        
        $('#mention-list').html(html);
    }
    
    highlightMatch(text, search) {
        if (!search) return text;
        
        const regex = new RegExp(`(${search})`, 'gi');
        return text.replace(regex, '<span class="text-primary">$1</span>');
    }
    
    updateSelection() {
        $('.mention-item').removeClass('selected');
        const items = $('.mention-item:visible');
        items.eq(this.selectedIndex).addClass('selected');
        
        // Scroll to selected item
        const selected = items.eq(this.selectedIndex);
        if (selected.length) {
            const list = $('#mention-list');
            const itemTop = selected.position().top;
            const itemBottom = itemTop + selected.outerHeight();
            const listHeight = list.height();
            
            if (itemBottom > listHeight) {
                list.scrollTop(list.scrollTop() + itemBottom - listHeight);
            } else if (itemTop < 0) {
                list.scrollTop(list.scrollTop() + itemTop);
            }
        }
    }
    
    insertMention(userId, userName) {
        const text = this.messageInput.val();
        const beforeMention = text.substring(0, this.mentionStartPos);
        const afterMention = text.substring(this.messageInput[0].selectionStart);
        
        const mentionText = `@${userName}`;
        const newText = beforeMention + mentionText + ' ' + afterMention;
        
        this.messageInput.val(newText);
        
        // Set cursor position after mention
        const newCursorPos = this.mentionStartPos + mentionText.length + 1;
        this.messageInput[0].setSelectionRange(newCursorPos, newCursorPos);
        
        // Store mention data in input element for later extraction
        this.storeMentionData(userId, userName);
        
        this.hideDropdown();
        this.messageInput.focus();
    }
    
    storeMentionData(userId, userName) {
        const existingMentions = this.messageInput.data('mentions') || [];
        existingMentions.push({
            user_id: userId,
            user_name: userName
        });
        this.messageInput.data('mentions', existingMentions);
    }
    
    extractMentions() {
        const text = this.messageInput.val();
        const mentions = [];
        
        // Extract @mentions from text
        const mentionRegex = /@(\w+)/g;
        let match;
        
        while ((match = mentionRegex.exec(text)) !== null) {
            const mentionName = match[1].toLowerCase();
            
            if (mentionName === 'all') {
                mentions.push({
                    user_id: 'all',
                    user_name: 'all'
                });
            } else {
                // Find matching member
                const member = this.groupMembers.find(m => 
                    m.name.toLowerCase().replace(/\s+/g, '') === mentionName.replace(/\s+/g, '') ||
                    m.name.toLowerCase().includes(mentionName)
                );
                
                if (member) {
                    mentions.push({
                        user_id: member.id,
                        user_name: member.name
                    });
                }
            }
        }
        
        return mentions;
    }
    
    clearMentions() {
        this.messageInput.removeData('mentions');
    }
    
    highlightMentionsInMessage(messageText) {
        if (!messageText) return messageText;
        
        // Replace @mentions with highlighted spans
        return messageText.replace(/@(\w+)/g, '<span class="mention-highlight">@$1</span>');
    }
}

// Initialize mention system
let mentionSystem;

$(document).ready(function() {
    mentionSystem = new MentionSystem();
});

// Make it globally accessible
window.MentionSystem = MentionSystem;
window.mentionSystem = mentionSystem;
