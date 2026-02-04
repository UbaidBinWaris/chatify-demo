/**
 *-------------------------------------------------------------
 * Global variables
 *-------------------------------------------------------------
 */
var messenger,
  typingTimeout,
  typingNow = 0,
  temporaryMsgId = 0,
  defaultAvatarInSettings = null,
  messengerColor,
  dark_mode,
  messages_page = 1;

const messagesContainer = $(".messenger-messagingView .m-body"),
  messengerTitleDefault = $(".messenger-headTitle").text(),
  messageInputContainer = $(".messenger-sendCard"),
  messageInput = $("#message-form .m-send"),
  auth_id = $("meta[name=url]").attr("data-user"),
  url = $("meta[name=url]").attr("content"),
  messengerTheme = $("meta[name=messenger-theme]").attr("content"),
  defaultMessengerColor = $("meta[name=messenger-color]").attr("content"),
  csrfToken = $('meta[name="csrf-token"]').attr("content");

const getMessengerId = () => $("meta[name=id]").attr("content");
const setMessengerId = (id) => $("meta[name=id]").attr("content", id);

/**
 *-------------------------------------------------------------
 * Pusher initialization
 *-------------------------------------------------------------
 */
Pusher.logToConsole = chatify.pusher.debug;
const pusher = new Pusher(chatify.pusher.key, {
  encrypted: chatify.pusher.options.encrypted,
  cluster: chatify.pusher.options.cluster,
  authEndpoint: chatify.pusherAuthEndpoint,
  auth: {
    headers: {
      "X-CSRF-TOKEN": csrfToken,
    },
  },
});
/**
 *-------------------------------------------------------------
 * Re-usable methods
 *-------------------------------------------------------------
 */
const escapeHtml = (unsafe) => {
  return unsafe
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;");
};
function actionOnScroll(selector, callback, topScroll = false) {
  $(selector).on("scroll", function () {
    let element = $(this).get(0);
    const condition = topScroll
      ? element.scrollTop == 0
      : element.scrollTop + element.clientHeight >= element.scrollHeight;
    if (condition) {
      callback();
    }
  });
}
function routerPush(title, url) {
  $("meta[name=url]").attr("content", url);
  return window.history.pushState({}, title || document.title, url);
}
function updateSelectedContact(user_id) {
  $(document).find(".messenger-list-item").removeClass("m-list-active");
  $(document)
    .find(
      ".messenger-list-item[data-contact=" + (user_id || getMessengerId()) + "]"
    )
    .addClass("m-list-active");
}
/**
 *-------------------------------------------------------------
 * Global Templates
 *-------------------------------------------------------------
 */
// Loading svg
function loadingSVG(size = "25px", className = "", style = "") {
  return `
<svg style="${style}" class="loadingSVG ${className}" xmlns="http://www.w3.org/2000/svg" width="${size}" height="${size}" viewBox="0 0 40 40" stroke="#ffffff">
<g fill="none" fill-rule="evenodd">
<g transform="translate(2 2)" stroke-width="3">
<circle stroke-opacity=".1" cx="18" cy="18" r="18"></circle>
<path d="M36 18c0-9.94-8.06-18-18-18" transform="rotate(349.311 18 18)">
<animateTransform attributeName="transform" type="rotate" from="0 18 18" to="360 18 18" dur=".8s" repeatCount="indefinite"></animateTransform>
</path>
</g>
</g>
</svg>
`;
}
function loadingWithContainer(className) {
  return `<div class="${className}" style="text-align:center;padding:15px">${loadingSVG(
    "25px",
    "",
    "margin:auto"
  )}</div>`;
}

// loading placeholder for users list item
function listItemLoading(items) {
  let template = "";
  for (let i = 0; i < items; i++) {
    template += `
<div class="loadingPlaceholder">
<div class="loadingPlaceholder-wrapper">
<div class="loadingPlaceholder-body">
<table class="loadingPlaceholder-header">
<tr>
<td style="width: 45px;"><div class="loadingPlaceholder-avatar"></div></td>
<td>
<div class="loadingPlaceholder-name"></div>
<div class="loadingPlaceholder-date"></div>
</td>
</tr>
</table>
</div>
</div>
</div>
`;
  }
  return template;
}

// loading placeholder for avatars
function avatarLoading(items) {
  let template = "";
  for (let i = 0; i < items; i++) {
    template += `
<div class="loadingPlaceholder">
<div class="loadingPlaceholder-wrapper">
<div class="loadingPlaceholder-body">
<table class="loadingPlaceholder-header">
<tr>
<td style="width: 45px;">
<div class="loadingPlaceholder-avatar" style="margin: 2px;"></div>
</td>
</tr>
</table>
</div>
</div>
</div>
`;
  }
  return template;
}

// While sending a message, show this temporary message card.
function sendTempMessageCard(message, id) {
  return `
 <div class="message-card mc-sender" data-id="${id}">
     <div class="message-card-content">
         <div class="message">
             ${message}
             <sub>
                 <span class="far fa-clock"></span>
             </sub>
         </div>
     </div>
 </div>
`;
}
// upload image preview card.
function attachmentTemplate(fileType, fileName, imgURL = null) {
  if (fileType != "image") {
    return (
      `
 <div class="attachment-preview">
     <span class="fas fa-times cancel"></span>
     <p style="padding:0px 30px;"><span class="fas fa-file"></span> ` +
      escapeHtml(fileName) +
      `</p>
 </div>
`
    );
  } else {
    return (
      `
<div class="attachment-preview">
 <span class="fas fa-times cancel"></span>
 <div class="image-file chat-image" style="background-image: url('` +
      imgURL +
      `');"></div>
 <p><span class="fas fa-file-image"></span> ` +
      escapeHtml(fileName) +
      `</p>
</div>
`
    );
  }
}

// Active Status Circle
function activeStatusCircle() {
  return `<span class="activeStatus"></span>`;
}

/**
 *-------------------------------------------------------------
 * Css Media Queries [For responsive design]
 *-------------------------------------------------------------
 */
$(window).resize(function () {
  cssMediaQueries();
});
function cssMediaQueries() {
  if (window.matchMedia("(min-width: 980px)").matches) {
    $(".messenger-listView").removeAttr("style");
  }
  if (window.matchMedia("(max-width: 980px)").matches) {
    $("body")
      .find(".messenger-list-item")
      .find("tr[data-action]")
      .attr("data-action", "1");
    $("body").find(".favorite-list-item").find("div").attr("data-action", "1");
  } else {
    $("body")
      .find(".messenger-list-item")
      .find("tr[data-action]")
      .attr("data-action", "0");
    $("body").find(".favorite-list-item").find("div").attr("data-action", "0");
  }
}

/**
 *-------------------------------------------------------------
 * App Modal
 *-------------------------------------------------------------
 */
let app_modal = function ({
  show = true,
  name,
  data = 0,
  buttons = true,
  header = null,
  body = null,
}) {
  const modal = $(".app-modal[data-name=" + name + "]");
  // header
  header ? modal.find(".app-modal-header").html(header) : "";

  // body
  body ? modal.find(".app-modal-body").html(body) : "";

  // buttons
  buttons == true
    ? modal.find(".app-modal-footer").show()
    : modal.find(".app-modal-footer").hide();

  // show / hide
  if (show == true) {
    modal.show();
    $(".app-modal-card[data-name=" + name + "]").addClass("app-show-modal");
    $(".app-modal-card[data-name=" + name + "]").attr("data-modal", data);
  } else {
    modal.hide();
    $(".app-modal-card[data-name=" + name + "]").removeClass("app-show-modal");
    $(".app-modal-card[data-name=" + name + "]").attr("data-modal", data);
  }
};

/**
 *-------------------------------------------------------------
 * Slide to bottom on [action] - e.g. [message received, sent, loaded]
 *-------------------------------------------------------------
 */
function scrollToBottom(container) {
  $(container)
    .stop()
    .animate({
      scrollTop: $(container)[0].scrollHeight,
    });
}

/**
 *-------------------------------------------------------------
 * click and drag to scroll - function
 *-------------------------------------------------------------
 */
function hScroller(scroller) {
  const slider = document.querySelector(scroller);
  let isDown = false;
  let startX;
  let scrollLeft;

  slider.addEventListener("mousedown", (e) => {
    isDown = true;
    startX = e.pageX - slider.offsetLeft;
    scrollLeft = slider.scrollLeft;
  });
  slider.addEventListener("mouseleave", () => {
    isDown = false;
  });
  slider.addEventListener("mouseup", () => {
    isDown = false;
  });
  slider.addEventListener("mousemove", (e) => {
    if (!isDown) return;
    e.preventDefault();
    const x = e.pageX - slider.offsetLeft;
    const walk = (x - startX) * 1;
    slider.scrollLeft = scrollLeft - walk;
  });
}

/**
 *-------------------------------------------------------------
 * Disable/enable message form fields, messaging container...
 * on load info or if needed elsewhere.
 *
 * Default : true
 *-------------------------------------------------------------
 */
function disableOnLoad(disable = true) {
  // Don't disable if we're in a group chat
  const messengerId = getMessengerId();
  if (messengerId && messengerId.toString().startsWith('group_')) {
    return;
  }
  if (disable) {
    // hide star button
    $(".add-to-favorite").hide();
    // hide send card
    $(".messenger-sendCard").hide();
    // add loading opacity to messages container
    messagesContainer.css("opacity", ".5");
    // disable message form fields
    messageInput.attr("readonly", "readonly");
    $("#message-form button").attr("disabled", "disabled");
    $(".upload-attachment").attr("disabled", "disabled");
  } else {
    // show star button
    if (getMessengerId() != auth_id) {
      $(".add-to-favorite").show();
    }
    // show send card
    $(".messenger-sendCard").show();
    // remove loading opacity to messages container
    messagesContainer.css("opacity", "1");
    // enable message form fields
    messageInput.removeAttr("readonly");
    $("#message-form button").removeAttr("disabled");
    $(".upload-attachment").removeAttr("disabled");
  }
}

/**
 *-------------------------------------------------------------
 * Error message card
 *-------------------------------------------------------------
 */
function errorMessageCard(id) {
  messagesContainer
    .find(".message-card[data-id=" + id + "]")
    .addClass("mc-error");
  messagesContainer
    .find(".message-card[data-id=" + id + "]")
    .find("svg.loadingSVG")
    .remove();
  messagesContainer
    .find(".message-card[data-id=" + id + "] p")
    .prepend('<span class="fas fa-exclamation-triangle"></span>');
}

/**
 *-------------------------------------------------------------
 * Fetch id data (user/group) and update the view
 *-------------------------------------------------------------
 */
function IDinfo(id) {
  // Don't run standard IDinfo for groups - they have their own info loading
  if (id && id.toString().startsWith('group_')) {
    // Still enable the message form for groups and ensure visibility
    disableOnLoad(false);
    $('.messenger-messagingView').css('display', 'flex').show();
    $('.messenger-sendCard').show();
    $('#message-form .m-send').removeAttr('readonly').removeAttr('disabled');
    $('.messenger-sendCard button').prop('disabled', false);
    return;
  }
  
  // clear temporary message id
  temporaryMsgId = 0;
  // clear typing now
  typingNow = 0;
  // show loading bar
  NProgress.start();
  // disable message form
  disableOnLoad();
  if (messenger != 0) {
    // get shared photos
    getSharedPhotos(id);
    // Get info
    $.ajax({
      url: url + "/idInfo",
      method: "POST",
      data: { _token: csrfToken, id },
      dataType: "JSON",
      success: (data) => {
        if (!data?.fetch) {
          NProgress.done();
          NProgress.remove();
          return;
        }
        // avatar photo
        $(".messenger-infoView")
          .find(".avatar")
          .css("background-image", 'url("' + data.user_avatar + '")');
        $(".header-avatar").css(
          "background-image",
          'url("' + data.user_avatar + '")'
        );
        // Show shared and actions
        $(".messenger-infoView-btns .delete-conversation").show();
        $(".messenger-infoView-shared").show();
        // fetch messages
        fetchMessages(id, true);
        // focus on messaging input
        messageInput.focus();
        // update info in view
        $(".messenger-infoView .info-name").text(data.fetch.name);
        $(".m-header-messaging .user-name").text(data.fetch.name);
        // Star status
        data.favorite > 0
          ? $(".add-to-favorite").addClass("favorite")
          : $(".add-to-favorite").removeClass("favorite");
        // form reset and focus
        $("#message-form").trigger("reset");
        cancelAttachment();
        messageInput.focus();
      },
      error: () => {
        console.error("Couldn't fetch user data!");
        // remove loading bar
        NProgress.done();
        NProgress.remove();
      },
    });
  } else {
    // remove loading bar
    NProgress.done();
    NProgress.remove();
  }
}

/**
 *-------------------------------------------------------------
 * Send message function
 *-------------------------------------------------------------
 */
function sendMessage() {
  temporaryMsgId += 1;
  let tempID = `temp_${temporaryMsgId}`;
  let hasFile = !!$(".upload-attachment").val();
  let hasVoiceRecording = window.voiceRecorder && window.voiceRecorder.hasRecordedAudio();
  const inputValue = $.trim(messageInput.val());
  
  if (inputValue.length > 0 || hasFile || hasVoiceRecording) {
    const formData = new FormData();
    
    // If there's a voice recording, add it to form data
    if (hasVoiceRecording && window.voiceRecorder) {
      const audioFile = window.voiceRecorder.getRecordedAudioFile();
      if (audioFile) {
        formData.set("file", audioFile);
        hasFile = true; // Treat voice as file attachment
      }
    } else if (hasFile) {
      // Only add the file input if there's actually a file selected
      const fileInput = $(".upload-attachment")[0];
      if (fileInput && fileInput.files && fileInput.files.length > 0) {
        formData.set("file", fileInput.files[0]);
      }
    }
    
    // Add other form fields
    formData.append("message", inputValue);
    formData.append("id", getMessengerId());
    formData.append("temporaryMsgId", tempID);
    formData.append("_token", csrfToken);
    
    $.ajax({
      url: $("#message-form").attr("action"),
      method: "POST",
      data: formData,
      dataType: "JSON",
      processData: false,
      contentType: false,
      beforeSend: () => {
        // remove message hint
        $(".messages").find(".message-hint").hide();
        // append a temporary message card
        if (hasFile) {
          messagesContainer
            .find(".messages")
            .append(
              sendTempMessageCard(
                inputValue + "\n" + loadingSVG("28px"),
                tempID
              )
            );
        } else {
          messagesContainer
            .find(".messages")
            .append(sendTempMessageCard(inputValue, tempID));
        }
        // scroll to bottom
        scrollToBottom(messagesContainer);
        messageInput.css({ height: "42px" });
        // form reset and focus
        $("#message-form").trigger("reset");
        cancelAttachment();
        
        // Remove audio preview UI but keep the blob for sending
        if (hasVoiceRecording && window.voiceRecorder) {
          window.voiceRecorder.removeAudioPreview(false); // Don't clear blob yet
        }
        
        messageInput.focus();
      },
      success: (data) => {
        if (data.error > 0) {
          // message card error status
          errorMessageCard(tempID);
          console.error(data.error_msg);
        } else {
          // Clear voice recording after successful send
          if (hasVoiceRecording && window.voiceRecorder) {
            window.voiceRecorder.removeAudioPreview(true);
          }
          
          // update contact item
          updateContactItem(getMessengerId());
          // temporary message card
          const tempMsgCardElement = messagesContainer.find(
            `.message-card[data-id=${data.tempID}]`
          );
          // add the message card coming from the server before the temp-card
          tempMsgCardElement.before(data.message);
          // then, remove the temporary message card
          tempMsgCardElement.remove();
          // scroll to bottom
          scrollToBottom(messagesContainer);
          
          // Load reactions for the new message (extract ID from message HTML)
          setTimeout(function() {
            if (typeof MessageReactions !== 'undefined') {
              const newMessageCard = messagesContainer.find('.message-card').last();
              const newMessageId = newMessageCard.data('id');
              if (newMessageId) {
                console.log('Loading reactions for new message:', newMessageId);
                // Don't load immediately as there likely aren't any yet
                // But ensure the container is ready for reactions
              }
            }
          }, 100);
          
          // send contact item updates
          sendContactItemUpdates(true);
        }
      },
      error: () => {
        // Clear voice recording on error too
        if (hasVoiceRecording && window.voiceRecorder) {
          window.voiceRecorder.clearRecording();
        }
        
        // message card error status
        errorMessageCard(tempID);
        // error log
        console.error(
          "Failed sending the message! Please, check your server response."
        );
      },
    });
  }
  return false;
}

/**
 *-------------------------------------------------------------
 * Fetch messages from database
 *-------------------------------------------------------------
 */
let messagesPage = 1;
let noMoreMessages = false;
let messagesLoading = false;
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
  // Don't fetch messages for groups - they have their own loading mechanism
  if (id && id.toString().startsWith('group_')) {
    return;
  }
  
  if (newFetch) {
    messagesPage = 1;
    noMoreMessages = false;
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
      },
      dataType: "JSON",
      success: (data) => {
        setMessagesLoading(false);
        if (messagesPage == 1) {
          messagesElement.html(data.messages);
          scrollToBottom(messagesContainer);
        } else {
          const lastMsg = messagesElement.find(
            messagesElement.find(".message-card")[0]
          );
          const curOffset =
            lastMsg.offset().top - messagesContainer.scrollTop();
          messagesElement.prepend(data.messages);
          messagesContainer.scrollTop(lastMsg.offset().top - curOffset);
        }
        // trigger seen event
        makeSeen(true);
        // Load reactions for messages
        if (typeof MessageReactions !== 'undefined') {
          MessageReactions.loadReactionsForMessages();
        }
        // Pagination lock & messages page
        noMoreMessages = messagesPage >= data?.last_page;
        if (!noMoreMessages) messagesPage += 1;
        // Enable message form if messenger not = 0; means if data is valid
        if (messenger != 0) {
          disableOnLoad(false);
        }
      },
      error: (error) => {
        setMessagesLoading(false);
        console.error(error);
      },
    });
  }
}

/**
 *-------------------------------------------------------------
 * Cancel file attached in the message.
 *-------------------------------------------------------------
 */
function cancelAttachment() {
  $(".messenger-sendCard").find(".attachment-preview").remove();
  $(".upload-attachment").replaceWith(
    $(".upload-attachment").val("").clone(true)
  );
}

/**
 *-------------------------------------------------------------
 * Cancel updating avatar in settings
 *-------------------------------------------------------------
 */
function cancelUpdatingAvatar() {
  $(".upload-avatar-preview").css("background-image", defaultAvatarInSettings);
  $(".upload-avatar").replaceWith($(".upload-avatar").val("").clone(true));
}

/**
 *-------------------------------------------------------------
 * Pusher channels and event listening..
 *-------------------------------------------------------------
 */

// subscribe to the channel
const channelName = "private-chatify";
var channel = pusher.subscribe(`${channelName}.${auth_id}`);
var clientSendChannel;
var clientListenChannel;

function initClientChannel() {
  if (getMessengerId()) {
    clientSendChannel = pusher.subscribe(`${channelName}.${getMessengerId()}`);
    clientListenChannel = pusher.subscribe(`${channelName}.${auth_id}`);
  }
}
initClientChannel();

// Listen to messages, and append if data received
channel.bind("messaging", function (data) {
  // Handle group messages
  if (data.group_id && getMessengerId() == 'group_' + data.group_id) {
    $(".messages").find(".message-hint").remove();
    messagesContainer.find(".messages").append(data.message);
    scrollToBottom(messagesContainer);
    // Ensure messaging view stays visible
    $('.messenger-messagingView').css('display', 'flex').show();
    $('.messenger-sendCard').show();
    
    // Play notification sound for group messages
    playNotificationSound("new_message", true);
  }
  // Handle group messages when not viewing the group (update group list)
  else if (data.group_id) {
    // Update group list with unread count
    const groupListItem = $(`.group-list-item[data-group-id="${data.group_id}"]`);
    if (groupListItem.length > 0) {
      // Increment unread count
      if (typeof incrementGroupUnreadCount === 'function') {
        incrementGroupUnreadCount(data.group_id);
      }
    }
    
    // Play notification sound
    playNotificationSound("new_message", true);
  }
  // Handle one-to-one messages
  else if (data.from_id == getMessengerId() && data.to_id == auth_id) {
    $(".messages").find(".message-hint").remove();
    messagesContainer.find(".messages").append(data.message);
    scrollToBottom(messagesContainer);
    makeSeen(true);
    // remove unseen counter for the user from the contacts list
    $(".messenger-list-item[data-contact=" + getMessengerId() + "]")
      .find("tr>td>b")
      .remove();
    
    // Play notification sound
    playNotificationSound("new_message", true);
  }
});

// listen to typing indicator
clientListenChannel.bind("client-typing", function (data) {
  if (data.from_id == getMessengerId() && data.to_id == auth_id) {
    data.typing == true
      ? messagesContainer.find(".typing-indicator").show()
      : messagesContainer.find(".typing-indicator").hide();
  }
  // scroll to bottom
  scrollToBottom(messagesContainer);
});

// listen to seen event
clientListenChannel.bind("client-seen", function (data) {
  if (data.from_id == getMessengerId() && data.to_id == auth_id) {
    if (data.seen == true) {
      $(".message-time")
        .find(".fa-check")
        .before('<span class="fas fa-check-double seen"></span> ');
      $(".message-time").find(".fa-check").remove();
    }
  }
});

// listen to contact item updates event
clientListenChannel.bind("client-contactItem", function (data) {
  if (data.to == auth_id) {
    if (data.update) {
      updateContactItem(data.from);
    } else {
      console.error("Can not update contact item!");
    }
  }
});

// listen on message delete event
clientListenChannel.bind("client-messageDelete", function (data) {
  $("body").find(`.message-card[data-id=${data.id}]`).remove();
});
// listen on delete conversation event
clientListenChannel.bind("client-deleteConversation", function (data) {
  if (data.from == getMessengerId() && data.to == auth_id) {
    $("body").find(`.messages`).html("");
    $(".messages").find(".message-hint").show();
  }
});
// -------------------------------------
// presence channel [User Active Status]
var activeStatusChannel = pusher.subscribe("presence-activeStatus");

// Joined
activeStatusChannel.bind("pusher:member_added", function (member) {
  // setActiveStatus(1); // REMOVED: This was incorrectly updating the current user's status when others join
  var contactItem = $(".messenger-list-item[data-contact=" + member.id + "]");
  
  // Add green dot
  contactItem.find(".activeStatus").remove();
  contactItem.find(".avatar").before(activeStatusCircle());

  // Update text to "Active Now"
  var statusText = contactItem.find(".user-status-text");
  if(statusText.length === 0) {
      // Create if doesn't exist (e.g. was offline/no last seen rendered)
      contactItem.find("td:eq(1)").append('<p class="user-status-text" data-status="online" style="font-size: 10px; color: #4caf50; margin: 0;">Active Now</p>');
  } else {
      statusText.attr('data-status', 'online')
                .css('color', '#4caf50')
                .text('Active Now')
                .removeAttr('data-last-seen');
  }
});

// Leaved
activeStatusChannel.bind("pusher:member_removed", function (member) {
  // setActiveStatus(0); // REMOVED: This was incorrectly updating the current user's status when others leave
  var contactItem = $(".messenger-list-item[data-contact=" + member.id + "]");
  
  // Remove green dot
  contactItem.find(".activeStatus").remove();
  
  // Update text to "Last seen just now"
  var statusText = contactItem.find(".user-status-text");
  var now = new Date().toISOString();
  
  if(statusText.length === 0) {
      contactItem.find("td:eq(1)").append('<p class="user-status-text" data-status="offline" data-last-seen="'+now+'" style="font-size: 10px; color: #999; margin: 0;">Last seen just now</p>');
  } else {
      statusText.attr('data-status', 'offline')
                .attr('data-last-seen', now)
                .css('color', '#999')
                .text('Last seen just now');
  }
});

// Initial Subscription Success - Sync valid online users
activeStatusChannel.bind("pusher:subscription_succeeded", function (members) {
    // Get all user IDs currently in the presence channel
    var onlineMemberIds = Object.keys(members.members);
    
    // Iterate over all contact items in the list
    $(".messenger-list-item").each(function() {
        var contactId = $(this).attr("data-contact");
        var isOnline = onlineMemberIds.includes(contactId);
        var contactItem = $(this);

        if (isOnline) {
             // Ensure it shows online
             if (contactItem.find(".activeStatus").length === 0) {
                 contactItem.find(".avatar").before(activeStatusCircle());
             }
             var statusText = contactItem.find(".user-status-text");
             if(statusText.length === 0) {
                 contactItem.find("td:eq(1)").append('<p class="user-status-text" data-status="online" style="font-size: 10px; color: #4caf50; margin: 0;">Active Now</p>');
             } else {
                 statusText.attr('data-status', 'online').css('color', '#4caf50').text('Active Now').removeAttr('data-last-seen');
             }
        } else {
             // If local DB thinks it's online but Pusher says offline, trust Pusher? 
             // Ideally yes, but maybe Pusher is just connecting. 
             // But subscription_succeeded means we have the full list.
             // So if they are NOT in the list, they are offline.
             
             // BUT: The current user (me) might not be in the list if I am not viewing? No, I am viewing.
             // NOTE: members.members includes me.

             // If visible as Online (green dot) but not in list -> remove green dot.
             if (contactItem.find(".activeStatus").length > 0) {
                 contactItem.find(".activeStatus").remove();
                 
                 // Fallback to "Last seen recently" if we don't have a time, or keep existing time
                 var statusText = contactItem.find(".user-status-text");
                 // If it said "Active Now", change it.
                 if (statusText.text() === "Active Now") {
                     var now = new Date().toISOString();
                     statusText.attr('data-status', 'offline')
                         .attr('data-last-seen', now) // Approximation since we detected them offline just now
                         .css('color', '#999')
                         .text('Last seen recently');
                 }
             }
        }
    });
});


function handleVisibilityChange() {
  if (!document.hidden) {
    makeSeen(true);
  }
}

document.addEventListener("visibilitychange", handleVisibilityChange, false);


// Update Last Seen timestamps every minute
setInterval(function() {
    $('.user-status-text[data-status="offline"]').each(function() {
        var lastSeen = $(this).attr('data-last-seen');
        if (lastSeen) {
            var timeAgo = timeAgoString(lastSeen);
            $(this).text('Last seen ' + timeAgo);
        }
    });
}, 60000); // 1 minute

function timeAgoString(dateString) {
    const now = new Date();
    const date = new Date(dateString);
    const seconds = Math.floor((now - date) / 1000);
    const minutes = Math.floor(seconds / 60);
    const hours = Math.floor(minutes / 60);
    const days = Math.floor(hours / 24);
    
    // Format time as "10:24 pm"
    const formatTime = (d) => {
        let hours = d.getHours();
        const minutes = d.getMinutes();
        const ampm = hours >= 12 ? 'pm' : 'am';
        hours = hours % 12;
        hours = hours ? hours : 12;
        const minutesStr = minutes < 10 ? '0' + minutes : minutes;
        return `${hours}:${minutesStr} ${ampm}`;
    };
    
    // Get day name
    const getDayName = (d) => {
        const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        return days[d.getDay()];
    };
    
    // Format date as "Jan 15"
    const formatDate = (d) => {
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        return `${months[d.getMonth()]} ${d.getDate()}`;
    };
    
    // Check if dates are on the same day
    const isSameDay = (d1, d2) => {
        return d1.getDate() === d2.getDate() &&
               d1.getMonth() === d2.getMonth() &&
               d1.getFullYear() === d2.getFullYear();
    };
    
    // Check if date is yesterday
    const isYesterday = (d) => {
        const yesterday = new Date(now);
        yesterday.setDate(yesterday.getDate() - 1);
        return isSameDay(d, yesterday);
    };
    
    // Less than 30 seconds
    if (seconds < 30) {
        return "just now";
    }
    // Less than 1 minute
    else if (seconds < 60) {
        return `${seconds} seconds ago`;
    }
    // Less than 1 hour
    else if (minutes < 60) {
        return `${minutes} minute${minutes === 1 ? '' : 's'} ago`;
    }
    // Today but more than 1 hour ago - show "today at [time]"
    else if (isSameDay(date, now)) {
        return `today at ${formatTime(date)}`;
    }
    // Yesterday
    else if (isYesterday(date)) {
        return `yesterday at ${formatTime(date)}`;
    }
    // Within last 7 days - show day name with time
    else if (days < 7) {
        return `${getDayName(date)} at ${formatTime(date)}`;
    }
    // More than a week - show date with time
    else {
        return `${formatDate(date)} at ${formatTime(date)}`;
    }
}

/**
 *-------------------------------------------------------------
 * Trigger typing event
 *-------------------------------------------------------------
 */
function isTyping(status) {
  const messengerId = getMessengerId();
  
  // Don't send client events for groups (they aren't supported yet)
  if (messengerId && messengerId.toString().startsWith('group_')) {
    return;
  }
  
  return clientSendChannel.trigger("client-typing", {
    from_id: auth_id, // Me
    to_id: messengerId, // Messenger
    typing: status,
  });
}

/**
 *-------------------------------------------------------------
 * Trigger seen event
 *-------------------------------------------------------------
 */
function makeSeen(status) {
  if (document?.hidden) {
    return;
  }
  
  const messengerId = getMessengerId();
  
  // Don't send client events for groups (they aren't supported yet)
  if (messengerId && messengerId.toString().startsWith('group_')) {
    // Only do server-side seen for groups
    $.ajax({
      url: url + "/makeSeen",
      method: "POST",
      data: { _token: csrfToken, id: messengerId },
      dataType: "JSON",
    });
    return;
  }
  
  // remove unseen counter for the user from the contacts list
  $(".messenger-list-item[data-contact=" + messengerId + "]")
    .find("tr>td>b")
    .remove();
  // seen
  $.ajax({
    url: url + "/makeSeen",
    method: "POST",
    data: { _token: csrfToken, id: messengerId },
    dataType: "JSON",
  });
  return clientSendChannel.trigger("client-seen", {
    from_id: auth_id, // Me
    to_id: messengerId, // Messenger
    seen: status,
  });
}

/**
 *-------------------------------------------------------------
 * Trigger contact item updates
 *-------------------------------------------------------------
 */
function sendContactItemUpdates(status) {
  return clientSendChannel.trigger("client-contactItem", {
    from: auth_id, // Me
    to: getMessengerId(), // Messenger
    update: status,
  });
}

/**
 *-------------------------------------------------------------
 * Trigger message delete
 *-------------------------------------------------------------
 */
function sendMessageDeleteEvent(messageId) {
  return clientSendChannel.trigger("client-messageDelete", {
    id: messageId,
  });
}
/**
 *-------------------------------------------------------------
 * Trigger delete conversation
 *-------------------------------------------------------------
 */
function sendDeleteConversationEvent() {
  return clientSendChannel.trigger("client-deleteConversation", {
    from: auth_id,
    to: getMessengerId(),
  });
}

/**
 *-------------------------------------------------------------
 * Check internet connection using pusher states
 *-------------------------------------------------------------
 */
function checkInternet(state, selector) {
  let net_errs = 0;
  const messengerTitle = $(".messenger-headTitle");
  switch (state) {
    case "connected":
      if (net_errs < 1) {
        messengerTitle.text(messengerTitleDefault);
        selector.addClass("successBG-rgba");
        selector.find("span").hide();
        selector.slideDown("fast", function () {
          selector.find(".ic-connected").show();
        });
        setTimeout(function () {
          $(".internet-connection").slideUp("fast");
        }, 3000);
      }
      break;
    case "connecting":
      messengerTitle.text($(".ic-connecting").text());
      selector.removeClass("successBG-rgba");
      selector.find("span").hide();
      selector.slideDown("fast", function () {
        selector.find(".ic-connecting").show();
      });
      net_errs = 1;
      break;
    // Not connected
    default:
      messengerTitle.text($(".ic-noInternet").text());
      selector.removeClass("successBG-rgba");
      selector.find("span").hide();
      selector.slideDown("fast", function () {
        selector.find(".ic-noInternet").show();
      });
      net_errs = 1;
      break;
  }
}

/**
 *-------------------------------------------------------------
 * Get contacts
 *-------------------------------------------------------------
 */
let contactsPage = 1;
let contactsLoading = false;
let noMoreContacts = false;
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
      data: { _token: csrfToken, page: contactsPage },
      dataType: "JSON",
      success: (data) => {
        setContactsLoading(false);
        if (contactsPage < 2) {
          $(".listOfContacts").html(data.contacts);
        } else {
          $(".listOfContacts").append(data.contacts);
        }
        updateSelectedContact();
        // update data-action required with [responsive design]
        cssMediaQueries();
        // Pagination lock & messages page
        noMoreContacts = contactsPage >= data?.last_page;
        if (!noMoreContacts) contactsPage += 1;
      },
      error: (error) => {
        setContactsLoading(false);
        console.error(error);
      },
    });
  }
}

/**
 *-------------------------------------------------------------
 * Update contact item
 *-------------------------------------------------------------
 */
function updateContactItem(user_id) {
  if (user_id != auth_id) {
    $.ajax({
      url: url + "/updateContacts",
      method: "POST",
      data: {
        _token: csrfToken,
        user_id,
      },
      dataType: "JSON",
      success: (data) => {
        $(".listOfContacts")
          .find(".messenger-list-item[data-contact=" + user_id + "]")
          .remove();
        if (data.contactItem) $(".listOfContacts").prepend(data.contactItem);
        if (user_id == getMessengerId()) updateSelectedContact(user_id);
        // show/hide message hint (empty state message)
        const totalContacts =
          $(".listOfContacts").find(".messenger-list-item")?.length || 0;
        if (totalContacts > 0) {
          $(".listOfContacts").find(".message-hint").hide();
        } else {
          $(".listOfContacts").find(".message-hint").show();
        }
        // update data-action required with [responsive design]
        cssMediaQueries();
      },
      error: (error) => {
        console.error(error);
      },
    });
  }
}

/**
 *-------------------------------------------------------------
 * Star
 *-------------------------------------------------------------
 */

function star(user_id) {
  if (getMessengerId() != auth_id) {
    $.ajax({
      url: url + "/star",
      method: "POST",
      data: { _token: csrfToken, user_id: user_id },
      dataType: "JSON",
      success: (data) => {
        data.status > 0
          ? $(".add-to-favorite").addClass("favorite")
          : $(".add-to-favorite").removeClass("favorite");
      },
      error: () => {
        console.error("Server error, check your response");
      },
    });
  }
}

/**
 *-------------------------------------------------------------
 * Get favorite list
 *-------------------------------------------------------------
 */
function getFavoritesList() {
  $(".messenger-favorites").html(avatarLoading(4));
  $.ajax({
    url: url + "/favorites",
    method: "POST",
    data: { _token: csrfToken },
    dataType: "JSON",
    success: (data) => {
      if (data.count > 0) {
        $(".favorites-section").show();
        $(".messenger-favorites").html(data.favorites);
      } else {
        $(".favorites-section").hide();
      }
      // update data-action required with [responsive design]
      cssMediaQueries();
    },
    error: () => {
      console.error("Server error, check your response");
    },
  });
}

/**
 *-------------------------------------------------------------
 * Get shared photos
 *-------------------------------------------------------------
 */
function getSharedPhotos(user_id) {
  $.ajax({
    url: url + "/shared",
    method: "POST",
    data: { _token: csrfToken, user_id: user_id },
    dataType: "JSON",
    success: (data) => {
      $(".shared-photos-list").html(data.shared);
    },
    error: () => {
      console.error("Server error, check your response");
    },
  });
}

/**
 *-------------------------------------------------------------
 * Search in messenger
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
      data: { _token: csrfToken, input: input, page: searchPage },
      dataType: "JSON",
      success: (data) => {
        setSearchLoading(false);
        if (searchPage < 2) {
          $(".search-records").html(data.records);
        } else {
          $(".search-records").append(data.records);
        }
        // update data-action required with [responsive design]
        cssMediaQueries();
        // Pagination lock & messages page
        noMoreDataSearch = searchPage >= data?.last_page;
        if (!noMoreDataSearch) searchPage += 1;
      },
      error: (error) => {
        setSearchLoading(false);
        console.error(error);
      },
    });
  }
}

/**
 *-------------------------------------------------------------
 * Delete Conversation
 *-------------------------------------------------------------
 */
function deleteConversation(id) {
  $.ajax({
    url: url + "/deleteConversation",
    method: "POST",
    data: { _token: csrfToken, id: id },
    dataType: "JSON",
    beforeSend: () => {
      // hide delete modal
      app_modal({
        show: false,
        name: "delete",
      });
      // Show waiting alert modal
      app_modal({
        show: true,
        name: "alert",
        buttons: false,
        body: loadingSVG("32px", null, "margin:auto"),
      });
    },
    success: (data) => {
      // delete contact from the list
      $(".listOfContacts")
        .find(".messenger-list-item[data-contact=" + id + "]")
        .remove();
      // refresh info
      IDinfo(id);

      if (!data.deleted)
        return alert("Error occurred, messages can not be deleted!");

      // Hide waiting alert modal
      app_modal({
        show: false,
        name: "alert",
        buttons: true,
        body: "",
      });

      sendDeleteConversationEvent();

      // update contact list item
      sendContactItemUpdates(true);
    },
    error: () => {
      console.error("Server error, check your response");
    },
  });
}

/**
 *-------------------------------------------------------------
 * Delete Message By ID
 *-------------------------------------------------------------
 */
function deleteMessage(id) {
  $.ajax({
    url: url + "/deleteMessage",
    method: "POST",
    data: { _token: csrfToken, id: id },
    dataType: "JSON",
    beforeSend: () => {
      // hide delete modal
      app_modal({
        show: false,
        name: "delete",
      });
      // Show waiting alert modal
      app_modal({
        show: true,
        name: "alert",
        buttons: false,
        body: loadingSVG("32px", null, "margin:auto"),
      });
    },
    success: (data) => {
      $(".messages").find(`.message-card[data-id=${id}]`).remove();
      if (!data.deleted)
        console.error("Error occurred, message can not be deleted!");

      sendMessageDeleteEvent(id);

      // Hide waiting alert modal
      app_modal({
        show: false,
        name: "alert",
        buttons: true,
        body: "",
      });
    },
    error: () => {
      console.error("Server error, check your response");
    },
  });
}

/**
 *-------------------------------------------------------------
 * Update Settings
 *-------------------------------------------------------------
 */
function updateSettings() {
  const formData = new FormData($("#update-settings")[0]);
  if (messengerColor) {
    formData.append("messengerColor", messengerColor);
  }
  if (dark_mode) {
    formData.append("dark_mode", dark_mode);
  }
  $.ajax({
    url: url + "/updateSettings",
    method: "POST",
    data: formData,
    dataType: "JSON",
    processData: false,
    contentType: false,
    beforeSend: () => {
      // close settings modal
      app_modal({
        show: false,
        name: "settings",
      });
      // Show waiting alert modal
      app_modal({
        show: true,
        name: "alert",
        buttons: false,
        body: loadingSVG("32px", null, "margin:auto"),
      });
    },
    success: (data) => {
      if (data.error) {
        // Show error message in alert modal
        app_modal({
          show: true,
          name: "alert",
          buttons: true,
          body: data.msg,
        });
      } else {
        // Hide alert modal
        app_modal({
          show: false,
          name: "alert",
          buttons: true,
          body: "",
        });

        // reload the page
        location.reload(true);
      }
    },
    error: () => {
      console.error("Server error, check your response");
    },
  });
}

/**
 *-------------------------------------------------------------
 * Set Active status
 *-------------------------------------------------------------
 */
function setActiveStatus(status) {
  $.ajax({
    url: url + "/setActiveStatus",
    method: "POST",
    data: { _token: csrfToken, status: status },
    dataType: "JSON",
    success: (data) => {
      // Nothing to do
    },
    error: () => {
      console.error("Server error, check your response");
    },
  });
}

/**
 *-------------------------------------------------------------
 * On DOM ready
 *-------------------------------------------------------------
 */
$(document).ready(function () {
  // get contacts list
  getContacts();

  // get contacts list
  getFavoritesList();

  // Clear typing timeout
  clearTimeout(typingTimeout);

  // NProgress configurations
  NProgress.configure({ showSpinner: false, minimum: 0.7, speed: 500 });

  // make message input autosize.
  autosize($(".m-send"));

  // check if pusher has access to the channel [Internet status]
  pusher.connection.bind("state_change", function (states) {
    let selector = $(".internet-connection");
    checkInternet(states.current, selector);
    // listening for pusher:subscription_succeeded
    channel.bind("pusher:subscription_succeeded", function () {
      // On connection state change [Updating] and get [info & msgs]
      if (getMessengerId() != 0) {
        if (
          $(".messenger-list-item")
            .find("tr[data-action]")
            .attr("data-action") == "1"
        ) {
          $(".messenger-listView").hide();
        }
        IDinfo(getMessengerId());
      }
    });
  });

  // tabs on click, show/hide...
  $(".messenger-listView-tabs a").on("click", function () {
    var dataView = $(this).attr("data-view");
    $(".messenger-listView-tabs a").removeClass("active-tab");
    $(this).addClass("active-tab");
    $(".messenger-tab").hide();
    $(".messenger-tab[data-view=" + dataView + "]").show();
  });

  // set item active on click
  $("body").on("click", ".messenger-list-item", function () {
    // Skip if this is a group item (handled by groups.js)
    if ($(this).hasClass('group-list-item')) {
      return;
    }
    
    $(".messenger-list-item").removeClass("m-list-active");
    $(this).addClass("m-list-active");
    const userID = $(this).attr("data-contact");
    
    // Only push route if userID is valid
    if (userID && userID !== 'undefined') {
      routerPush(document.title, `${url}/${userID}`);
    }
    updateSelectedContact(userID);
  });

  // show info side button
  $(".messenger-infoView nav a , .show-infoSide").on("click", function () {
    $(".messenger-infoView").toggle();
  });

  // make favorites card dragable on click to slide.
  hScroller(".messenger-favorites");

  // click action for list item [user/group]
  $("body").on("click", ".messenger-list-item", function () {
    // Skip if this is a group item (handled by groups.js)
    if ($(this).hasClass('group-list-item')) {
      return;
    }
    
    // Check if this is a search item and not friends yet
    if ($(this).hasClass('search-item-no-click')) {
      const friendshipStatus = $(this).data('friendship-status');
      if (friendshipStatus !== 'friends') {
        return false; // Prevent opening chat
      }
    }
    
    if ($(this).find("tr[data-action]").attr("data-action") == "1") {
      $(".messenger-listView").hide();
    }
    const dataId = $(this).find("p[data-id]").attr("data-id");
    setMessengerId(dataId);
    IDinfo(dataId);
  });

  // click action for favorite button
  $("body").on("click", ".favorite-list-item", function () {
    if ($(this).find("div").attr("data-action") == "1") {
      $(".messenger-listView").hide();
    }
    const uid = $(this).find("div.avatar").attr("data-id");
    setMessengerId(uid);
    IDinfo(uid);
    updateSelectedContact(uid);
    routerPush(document.title, `${url}/${uid}`);
  });

  // list view buttons
  $(".listView-x").on("click", function () {
    $(".messenger-listView").hide();
  });
  $(".show-listView").on("click", function () {
    routerPush(document.title, `${url}/`);
    $(".messenger-listView").show();
  });

  // click action for [add to favorite] button.
  $(".add-to-favorite").on("click", function () {
    star(getMessengerId());
  });

  // calling Css Media Queries
  cssMediaQueries();

  // message form on submit.
  $("#message-form").on("submit", (e) => {
    e.preventDefault();
    sendMessage();
  });

  // message input on keyup [Enter to send, Enter+Shift for new line]
  $("#message-form .m-send").on("keyup", (e) => {
    // if enter key pressed.
    if (e.which == 13 || e.keyCode == 13) {
      // if shift + enter key pressed, do nothing (new line).
      // if only enter key pressed, send message.
      if (!e.shiftKey) {
        triggered = isTyping(false);
        sendMessage();
      }
    }
  });

  // On [upload attachment] input change, show a preview of the image/file.
  $("body").on("change", ".upload-attachment", (e) => {
    let file = e.target.files[0];
    if (!attachmentValidate(file)) return false;
    let reader = new FileReader();
    let sendCard = $(".messenger-sendCard");
    reader.readAsDataURL(file);
    reader.addEventListener("loadstart", (e) => {
      $("#message-form").before(loadingSVG());
    });
    reader.addEventListener("load", (e) => {
      $(".messenger-sendCard").find(".loadingSVG").remove();
      if (!file.type.match("image.*")) {
        // if the file not image
        sendCard.find(".attachment-preview").remove(); // older one
        sendCard.prepend(attachmentTemplate("file", file.name));
      } else {
        // if the file is an image
        sendCard.find(".attachment-preview").remove(); // older one
        sendCard.prepend(
          attachmentTemplate("image", file.name, e.target.result)
        );
      }
    });
  });

  function attachmentValidate(file) {
    const fileElement = $(".upload-attachment");
    const { name: fileName, size: fileSize } = file;
    const fileExtension = fileName.split(".").pop();
    if (
      !chatify.allAllowedExtensions.includes(
        fileExtension.toString().toLowerCase()
      )
    ) {
      alert("file type not allowed");
      fileElement.val("");
      return false;
    }
    // Validate file size.
    if (fileSize > chatify.maxUploadSize) {
      alert("File is too large!");
      return false;
    }
    return true;
  }

  // Attachment preview cancel button.
  $("body").on("click", ".attachment-preview .cancel", () => {
    cancelAttachment();
  });

  // typing indicator on [input] keyDown
  $("#message-form .m-send").on("keydown", () => {
    if (typingNow < 1) {
      isTyping(true);
      typingNow = 1;
    }
    clearTimeout(typingTimeout);
    typingTimeout = setTimeout(function () {
      isTyping(false);
      typingNow = 0;
    }, 1000);
  });

  // Handle paste event for images from clipboard
  $("#message-form .m-send").on("paste", (e) => {
    const clipboardData = e.originalEvent.clipboardData || window.clipboardData;
    const items = clipboardData.items;
    
    if (!items) return;
    
    // Loop through clipboard items
    for (let i = 0; i < items.length; i++) {
      const item = items[i];
      
      // Check if the item is an image
      if (item.type.indexOf("image") !== -1) {
        e.preventDefault(); // Prevent the default paste behavior
        
        const blob = item.getAsFile();
        
        // Create a File object from the blob
        const fileName = `pasted-image-${Date.now()}.png`;
        const file = new File([blob], fileName, { type: blob.type });
        
        // Validate the file
        if (!attachmentValidate(file)) return false;
        
        // Create a DataTransfer object to set the file input
        const dataTransfer = new DataTransfer();
        dataTransfer.items.add(file);
        
        // Set the file to the upload input
        const uploadInput = $(".upload-attachment")[0];
        uploadInput.files = dataTransfer.files;
        
        // Trigger the change event to show preview
        $(uploadInput).trigger("change");
        
        // Focus back on the textarea
        setTimeout(() => {
          messageInput.focus();
        }, 100);
        
        break; // Only process the first image
      }
    }
  });

  // Image modal
  $("body").on("click", ".chat-image", function () {
    let src = $(this).css("background-image").split(/"/)[1];
    $("#imageModalBox").show();
    $("#imageModalBoxSrc").attr("src", src);
  });
  $(".imageModal-close").on("click", function () {
    $("#imageModalBox").hide();
  });

  // Search input on focus
  $(".messenger-search").on("focus", function () {
    $(".messenger-tab").hide();
    $('.messenger-tab[data-view="search"]').show();
  });
  $(".messenger-search").on("blur", function () {
    setTimeout(function () {
      $(".messenger-tab").hide();
      $('.messenger-tab[data-view="users"]').show();
    }, 200);
  });
  // Search action on keyup
  const debouncedSearch = debounce(function () {
    const value = $(".messenger-search").val();
    messengerSearch(value);
  }, 500);
  $(".messenger-search").on("keyup", function (e) {
    const value = $(this).val();
    if ($.trim(value).length > 0) {
      $(".messenger-search").trigger("focus");
      debouncedSearch();
    } else {
      $(".messenger-tab").hide();
      $('.messenger-listView-tabs a[data-view="users"]').trigger("click");
    }
  });

  // Delete Conversation button
  $(".messenger-infoView-btns .delete-conversation").on("click", function () {
    app_modal({
      name: "delete",
    });
  });
  // Delete Message Button
  $("body").on("click", ".message-card .actions .delete-btn", function () {
    app_modal({
      name: "delete",
      data: $(this).data("id"),
    });
  });
  // Delete modal [on delete button click]
  $(".app-modal[data-name=delete]")
    .find(".app-modal-footer .delete")
    .on("click", function () {
      const id = $("body")
        .find(".app-modal[data-name=delete]")
        .find(".app-modal-card")
        .attr("data-modal");
      if (id == 0) {
        deleteConversation(getMessengerId());
      } else {
        deleteMessage(id);
      }
      app_modal({
        show: false,
        name: "delete",
      });
    });
  // delete modal [cancel button]
  $(".app-modal[data-name=delete]")
    .find(".app-modal-footer .cancel")
    .on("click", function () {
      app_modal({
        show: false,
        name: "delete",
      });
    });

  // Settings button action to show settings modal
  $("body").on("click", ".settings-btn", function (e) {
    e.preventDefault();
    app_modal({
      show: true,
      name: "settings",
    });
  });

  // on submit settings' form
  $("#update-settings").on("submit", (e) => {
    e.preventDefault();
    updateSettings();
  });
  // Settings modal [cancel button]
  $(".app-modal[data-name=settings]")
    .find(".app-modal-footer .cancel")
    .on("click", function () {
      app_modal({
        show: false,
        name: "settings",
      });
      cancelUpdatingAvatar();
    });
  // upload avatar on change
  $("body").on("change", ".upload-avatar", (e) => {
    // store the original avatar
    if (defaultAvatarInSettings == null) {
      defaultAvatarInSettings = $(".upload-avatar-preview").css(
        "background-image"
      );
    }
    let file = e.target.files[0];
    if (!attachmentValidate(file)) return false;
    let reader = new FileReader();
    reader.readAsDataURL(file);
    reader.addEventListener("loadstart", (e) => {
      $(".upload-avatar-preview").append(
        loadingSVG("42px", "upload-avatar-loading")
      );
    });
    reader.addEventListener("load", (e) => {
      $(".upload-avatar-preview").find(".loadingSVG").remove();
      if (!file.type.match("image.*")) {
        // if the file is not an image
        console.error("File you selected is not an image!");
      } else {
        // if the file is an image
        $(".upload-avatar-preview").css(
          "background-image",
          'url("' + e.target.result + '")'
        );
      }
    });
  });
  // change messenger color button
  $("body").on("click", ".update-messengerColor .color-btn", function () {
    messengerColor = $(this).attr("data-color");
    $(".update-messengerColor .color-btn").removeClass("m-color-active");
    $(this).addClass("m-color-active");
  });
  // Switch to Dark/Light mode
  $("body").on("click", ".dark-mode-switch", function () {
    if ($(this).attr("data-mode") == "0") {
      $(this).attr("data-mode", "1");
      $(this).removeClass("far");
      $(this).addClass("fas");
      dark_mode = "dark";
    } else {
      $(this).attr("data-mode", "0");
      $(this).removeClass("fas");
      $(this).addClass("far");
      dark_mode = "light";
    }
  });

  //Messages pagination
  actionOnScroll(
    ".m-body.messages-container",
    function () {
      fetchMessages(getMessengerId());
    },
    true
  );
  //Contacts pagination
  actionOnScroll(".messenger-tab.users-tab", function () {
    getContacts();
  });
  //Search pagination
  actionOnScroll(".messenger-tab.search-tab", function () {
    messengerSearch($(".messenger-search").val());
  });
});

/**
 *-------------------------------------------------------------
 * Observer on DOM changes
 *-------------------------------------------------------------
 */
let previousMessengerId = getMessengerId();
const observer = new MutationObserver(function (mutations) {
  if (getMessengerId() !== previousMessengerId) {
    previousMessengerId = getMessengerId();
    initClientChannel();
  }
});
const config = { subtree: true, childList: true };

// start listening to changes
observer.observe(document, config);

// stop listening to changes
// observer.disconnect();

/**
 *-------------------------------------------------------------
 * Resize messaging area when resize the viewport.
 * on mobile devices when the keyboard is shown, the viewport
 * height is changed, so we need to resize the messaging area
 * to fit the new height.
 *-------------------------------------------------------------
 */
var resizeTimeout;
window.visualViewport.addEventListener("resize", (e) => {
  clearTimeout(resizeTimeout);
  resizeTimeout = setTimeout(function () {
    const h = e.target.height;
    if (h) {
      $(".messenger-messagingView").css({ height: h + "px" });
    }
  }, 100);
});

/**
 *-------------------------------------------------------------
 * Emoji Picker
 *-------------------------------------------------------------
 */
const emojiButton = document.querySelector(".emoji-button");

const emojiPicker = new EmojiButton({
  theme: messengerTheme,
  autoHide: false,
  position: "top-start",
});

emojiButton.addEventListener("click", (e) => {
  e.preventDefault();
  emojiPicker.togglePicker(emojiButton);
});

emojiPicker.on("emoji", (emoji) => {
  const el = messageInput[0];
  const startPos = el.selectionStart;
  const endPos = el.selectionEnd;
  const value = messageInput.val();
  const newValue =
    value.substring(0, startPos) +
    emoji +
    value.substring(endPos, value.length);
  messageInput.val(newValue);
  el.selectionStart = el.selectionEnd = startPos + emoji.length;
  el.focus();
});

/**
 *-------------------------------------------------------------
 * Notification sounds
 *-------------------------------------------------------------
 */
let userHasInteracted = false;

// Track user interaction to enable autoplay
document.addEventListener('click', () => { userHasInteracted = true; }, { once: true });
document.addEventListener('keydown', () => { userHasInteracted = true; }, { once: true });
document.addEventListener('touchstart', () => { userHasInteracted = true; }, { once: true });

function playNotificationSound(soundName, condition = false) {
  // Only play sounds if user has interacted and sounds are enabled
  if ((document.hidden || condition) && chatify.sounds.enabled && userHasInteracted) {
    const soundPath = `/${chatify.sounds.public_path}/${chatify.sounds[soundName]}`;
    const sound = new Audio(soundPath);
    
    // Set volume to ensure it's not muted
    sound.volume = 0.5;
    
    sound.play().catch((error) => {
      // Silently fail - don't spam console with autoplay errors
      if (error.name !== 'NotAllowedError') {
        console.warn('Sound notification skipped:', error.message);
      }
    });
  }
}
/**
 *-------------------------------------------------------------
 * Update and format dates to time ago.
 *-------------------------------------------------------------
 */
function updateElementsDateToTimeAgo() {
  $(".message-time").each(function () {
    const time = $(this).attr("data-time");
    $(this).find(".time").text(dateStringToTimeAgo(time));
  });
  $(".contact-item-time").each(function () {
    const time = $(this).attr("data-time");
    $(this).text(dateStringToTimeAgo(time));
  });
}

// Update every 30 seconds for better accuracy with "just now" and "X sec ago"
setInterval(() => {
  updateElementsDateToTimeAgo();
}, 30000);
