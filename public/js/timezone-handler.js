/**
 * Timezone-aware time display utility
 * Handles real-time updates of timestamps in the UI
 */

(function() {
    'use strict';

    /**
     * Format timestamp for display
     */
    function formatTimeDisplay(timestamp) {
        if (!timestamp) return '';

        const date = new Date(timestamp * 1000);
        const now = new Date();
        const diffMs = now - date;
        const diffMins = Math.floor(diffMs / 60000);
        const diffHours = Math.floor(diffMs / 3600000);
        const diffDays = Math.floor(diffMs / 86400000);

        if (diffMins < 1) {
            return 'Just now';
        } else if (diffMins < 60) {
            return diffMins + ' min';
        } else if (diffHours < 24) {
            return diffHours + ' hr';
        } else if (diffDays < 7) {
            return diffDays + ' day' + (diffDays > 1 ? 's' : '');
        } else {
            return formatDate(date, 'MMM d');
        }
    }

    /**
     * Format date with pattern
     */
    function formatDate(date, pattern) {
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 
                       'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        
        if (pattern === 'MMM d') {
            return months[date.getMonth()] + ' ' + date.getDate();
        }
        
        return date.toLocaleDateString();
    }

    /**
     * Update all time elements on the page
     */
    function updateAllTimeDisplays() {
        // Update message times
        document.querySelectorAll('[data-timestamp]').forEach(function(element) {
            const timestamp = parseInt(element.getAttribute('data-timestamp'));
            if (timestamp) {
                const timeElement = element.querySelector('.time');
                if (timeElement) {
                    timeElement.textContent = formatTimeDisplay(timestamp);
                }
            }
        });

        // Update contact list times
        document.querySelectorAll('.contact-item-time[data-time]').forEach(function(element) {
            const isoTime = element.getAttribute('data-time');
            if (isoTime) {
                const timestamp = new Date(isoTime).getTime() / 1000;
                element.textContent = formatTimeDisplay(timestamp);
            }
        });

        // Update last seen times
        document.querySelectorAll('[data-last-seen]').forEach(function(element) {
            const isoTime = element.getAttribute('data-last-seen');
            if (isoTime) {
                const timestamp = new Date(isoTime).getTime() / 1000;
                const relativeTime = formatTimeDisplay(timestamp);
                const currentText = element.textContent;
                
                // Only update the time part, not the "Last seen" prefix
                if (currentText.includes('Last seen')) {
                    element.textContent = 'Last seen ' + relativeTime;
                }
            }
        });
    }

    /**
     * Initialize time display updates
     */
    function initializeTimeUpdates() {
        // Update immediately
        updateAllTimeDisplays();

        // Update every minute
        setInterval(updateAllTimeDisplays, 60000);
    }

    /**
     * Get user's browser timezone
     */
    function getUserTimezone() {
        try {
            return Intl.DateTimeFormat().resolvedOptions().timeZone;
        } catch (e) {
            console.error('Failed to detect timezone:', e);
            return 'UTC';
        }
    }

    /**
     * Send detected timezone to server
     */
    function sendTimezoneToServer() {
        const timezone = getUserTimezone();
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

        if (!csrfToken) {
            console.warn('CSRF token not found, skipping timezone update');
            return;
        }

        fetch('/api/user/timezone', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({ timezone: timezone }),
        }).catch(function(error) {
            console.error('Failed to send timezone to server:', error);
        });
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            initializeTimeUpdates();
            sendTimezoneToServer();
        });
    } else {
        initializeTimeUpdates();
        sendTimezoneToServer();
    }

    // Export for use in other scripts
    window.ChatifyTime = {
        formatTimeDisplay: formatTimeDisplay,
        updateAllTimeDisplays: updateAllTimeDisplays,
        getUserTimezone: getUserTimezone,
    };
})();
