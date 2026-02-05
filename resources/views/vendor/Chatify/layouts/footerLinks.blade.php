<script src="https://js.pusher.com/7.2.0/pusher.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@joeattardi/emoji-button@3.0.3/dist/index.min.js"></script>
<script >
    // Gloabl Chatify variables from PHP to JS
    window.chatify = {
        name: "{{ config('chatify.name') }}",
        sounds: {!! json_encode(config('chatify.sounds')) !!},
        allowedImages: {!! json_encode(config('chatify.attachments.allowed_images')) !!},
        allowedFiles: {!! json_encode(config('chatify.attachments.allowed_files')) !!},
        allowedAudio: {!! json_encode(config('chatify.attachments.allowed_audio')) !!},
        maxUploadSize: {{ Chatify::getMaxUploadSize() }},
        pusher: {!! json_encode(config('chatify.pusher')) !!},
        pusherAuthEndpoint: '{{route("pusher.auth")}}'
    };
    window.chatify.allAllowedExtensions = chatify.allowedImages.concat(chatify.allowedFiles).concat(chatify.allowedAudio);
</script>
<script src="{{ asset('js/timezone-handler.js') }}"></script>
<script src="{{ asset('js/chatify/utils.js') }}"></script>
<script src="{{ asset('js/chatify/voice-recorder.js') }}"></script>
<script src="{{ asset('js/chatify/code.js') }}"></script>
<script src="{{ asset('js/chatify/groups.js') }}"></script>
<script src="{{ asset('js/chatify/mentions.js') }}"></script>
<script src="{{ asset('js/chatify/reactions.js') }}"></script>
@if(env('APP_DEBUG', false))
<script src="{{ asset('js/chatify/voice-recorder-tests.js') }}"></script>
@endif
</head>
</html>
