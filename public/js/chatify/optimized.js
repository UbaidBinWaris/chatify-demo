/**
 *-------------------------------------------------------------
 * Optimized Chat Loading Functions
 * Production-level improvements for Chatify
 *-------------------------------------------------------------
 */

/**
 * Debounce function to limit API calls
 * Prevents excessive requests during rapid user actions
 */
function debounce(func, wait) {
  let timeout;
  return function executedFunction(...args) {
    const later = () => {
      clearTimeout(timeout);
      func(...args);
    };
    clearTimeout(timeout);
    timeout = setTimeout(later, wait);
  };
}

/**
 * Throttle function to limit scroll event handling
 * Ensures smooth scrolling without performance issues
 */
function throttle(func, limit) {
  let inThrottle;
  return function(...args) {
    if (!inThrottle) {
      func.apply(this, args);
      inThrottle = true;
      setTimeout(() => inThrottle = false, limit);
    }
  };
}

/**
 *-------------------------------------------------------------
 * Enhanced Get Contacts with Performance Optimizations
 *-------------------------------------------------------------
 */
let contactsPage = 1;
let contactsLoading = false;
let noMoreContacts = false;
let contactsCache = new Map(); // Cache for contact items

function setContactsLoading(loading = false) {
  if (!loading) {
    $(".listOfContacts").find(".loading-contacts").remove();
  } else {
    $(".listOfContacts").append(
      `<div class="loading-contacts">${listItemLoading(4)}</div>`
    );
  }
  contactsLoading = loading;
}

function getContacts() {
  if (!contactsLoading && !noMoreContacts) {
    setContactsLoading(true);
    
    $.ajax({
      url: url + "/getContacts",
      method: "GET",
      data: { 
        _token: csrfToken, 
        page: contactsPage,
        per_page: 20 // Optimized page size
      },
      dataType: "JSON",
      success: (data) => {
        setContactsLoading(false);
        
        if (contactsPage < 2) {
          $(".listOfContacts").html(data.contacts);
        } else {
          $(".listOfContacts").append(data.contacts);
        }
        
        updateSelectedContact();
        cssMediaQueries();
        
        // Pagination control
        noMoreContacts = contactsPage >= data?.last_page;
        if (!noMoreContacts) contactsPage += 1;
        
        // Log performance metrics in development
        if (typeof console !== 'undefined' && data.total) {
          console.log(`Loaded ${contactsPage - 1} pages, ${data.total} total contacts`);
        }
      },
      error: (error) => {
        setContactsLoading(false);
        console.error('Failed to load contacts:', error);
        
        // Show user-friendly error message
        if (contactsPage < 2) {
          $(".listOfContacts").html(
            '<p class="message-hint center-el"><span>Failed to load contacts. Please refresh.</span></p>'
          );
        }
      },
      timeout: 10000 // 10 second timeout
    });
  }
}

/**
 *-------------------------------------------------------------
 * Enhanced Fetch Messages with Lazy Loading
 *-------------------------------------------------------------
 */
let messagesPage = 1;
let noMoreMessages = false;
let messagesLoading = false;
let lastFetchedUserId = null;

function setMessagesLoading(loading = false) {
  if (!loading) {
    messagesContainer.find(".messages").find(".loading-messages").remove();
    NProgress.done();
    NProgress.remove();
  } else {
    messagesContainer
      .find(".messages")
      .prepend(loadingWithContainer("loading-messages"));
  }
  messagesLoading = loading;
}

function fetchMessages(id, newFetch = false) {
  // Reset pagination when switching users
  if (newFetch || lastFetchedUserId !== id) {
    messagesPage = 1;
    noMoreMessages = false;
    lastFetchedUserId = id;
  }
  
  if (messenger != 0 && !noMoreMessages && !messagesLoading) {
    const messagesElement = messagesContainer.find(".messages");
    setMessagesLoading(true);
    
    $.ajax({
      url: url + "/fetchMessages",
      method: "POST",
      data: {
        _token: csrfToken,
        id: id,
        page: messagesPage,
        per_page: 20 // Optimized page size
      },
      dataType: "JSON",
      success: (data) => {
        setMessagesLoading(false);
        
        if (messagesPage == 1) {
          // First page - replace all messages
          messagesElement.html(data.messages);
          scrollToBottom(messagesContainer);
        } else {
          // Subsequent pages - prepend messages
          const lastMsg = messagesElement.find(
            messagesElement.find(".message-card")[0]
          );
          const curOffset =
            lastMsg.offset().top - messagesContainer.scrollTop();
          messagesElement.prepend(data.messages);
          messagesContainer.scrollTop(lastMsg.offset().top - curOffset);
        }
        
        // Mark messages as seen
        makeSeen(true);
        
        // Pagination control
        noMoreMessages = messagesPage >= data?.last_page;
        if (!noMoreMessages) messagesPage += 1;
        
        // Enable message form
        if (messenger != 0) {
          disableOnLoad(false);
        }
        
        // Log performance in development
        if (typeof console !== 'undefined' && data.total) {
          console.log(`Loaded page ${messagesPage - 1}, ${data.total} total messages`);
        }
      },
      error: (error) => {
        setMessagesLoading(false);
        console.error('Failed to load messages:', error);
        
        // Show error message
        if (messagesPage == 1) {
          messagesElement.html(
            '<p class="message-hint center-el"><span>Failed to load messages. Please try again.</span></p>'
          );
        }
      },
      timeout: 10000 // 10 second timeout
    });
  }
}

/**
 *-------------------------------------------------------------
 * Enhanced Search with Debouncing
 *-------------------------------------------------------------
 */
let searchPage = 1;
let noMoreDataSearch = false;
let searchLoading = false;
let searchTempVal = "";

function setSearchLoading(loading = false) {
  if (!loading) {
    $(".search-records").find(".loading-search").remove();
  } else {
    $(".search-records").append(
      `<div class="loading-search">${listItemLoading(4)}</div>`
    );
  }
  searchLoading = loading;
}

function messengerSearch(input) {
  // Reset pagination if search term changed
  if (input != searchTempVal) {
    searchPage = 1;
    noMoreDataSearch = false;
    searchLoading = false;
  }
  searchTempVal = input;
  
  if (!searchLoading && !noMoreDataSearch) {
    if (searchPage < 2) {
      $(".search-records").html("");
    }
    
    setSearchLoading(true);
    
    $.ajax({
      url: url + "/search",
      method: "GET",
      data: { 
        _token: csrfToken, 
        input: input, 
        page: searchPage,
        per_page: 15 // Optimized for search
      },
      dataType: "JSON",
      success: (data) => {
        setSearchLoading(false);
        
        if (searchPage < 2) {
          $(".search-records").html(data.records);
        } else {
          $(".search-records").append(data.records);
        }
        
        cssMediaQueries();
        
        // Pagination control
        noMoreDataSearch = searchPage >= data?.last_page;
        if (!noMoreDataSearch) searchPage += 1;
      },
      error: (error) => {
        setSearchLoading(false);
        console.error('Search failed:', error);
        
        if (searchPage < 2) {
          $(".search-records").html(
            '<p class="message-hint center-el"><span>Search failed. Please try again.</span></p>'
          );
        }
      },
      timeout: 8000 // 8 second timeout for search
    });
  }
}

// Debounced search function (300ms delay)
const debouncedSearch = debounce(function() {
  const value = $(".messenger-search").val();
  if ($.trim(value).length > 0) {
    messengerSearch(value);
  }
}, 300);

/**
 *-------------------------------------------------------------
 * Optimized Scroll Handler with Throttling
 *-------------------------------------------------------------
 */
function actionOnScroll(selector, callback, offset = 100) {
  const throttledScroll = throttle(function() {
    const element = $(selector);
    if (element.length) {
      const scrollTop = element.scrollTop();
      const scrollHeight = element[0].scrollHeight;
      const clientHeight = element.height();
      
      // Trigger when near bottom (within offset pixels)
      if (scrollTop + clientHeight >= scrollHeight - offset) {
        callback();
      }
    }
  }, 200); // Throttle to max once per 200ms
  
  $(selector).on("scroll", throttledScroll);
}

/**
 *-------------------------------------------------------------
 * Performance Monitoring (Development Only)
 *-------------------------------------------------------------
 */
function logPerformanceMetric(metricName, duration) {
  if (typeof console !== 'undefined' && window.location.hostname === 'localhost') {
    console.log(`[Performance] ${metricName}: ${duration}ms`);
  }
}

/**
 *-------------------------------------------------------------
 * Lazy Image Loading for Avatars
 *-------------------------------------------------------------
 */
function lazyLoadImages() {
  const imageObserver = new IntersectionObserver((entries, observer) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        const img = entry.target;
        const src = img.getAttribute('data-src');
        if (src) {
          img.style.backgroundImage = `url(${src})`;
          img.removeAttribute('data-src');
        }
        observer.unobserve(img);
      }
    });
  });
  
  document.querySelectorAll('[data-src]').forEach(img => {
    imageObserver.observe(img);
  });
}

/**
 *-------------------------------------------------------------
 * Initialize Optimized Features
 *-------------------------------------------------------------
 */
$(document).ready(function() {
  // Initialize lazy loading
  if ('IntersectionObserver' in window) {
    lazyLoadImages();
  }
  
  // Log initialization
  console.log('Chatify optimized features loaded');
});

// Export functions for use in main code
window.ChatifyOptimized = {
  debounce,
  throttle,
  getContacts,
  fetchMessages,
  messengerSearch,
  debouncedSearch,
  actionOnScroll,
  lazyLoadImages
};
