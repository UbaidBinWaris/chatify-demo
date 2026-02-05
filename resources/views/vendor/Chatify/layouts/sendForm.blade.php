<div class="messenger-sendCard">
    <form id="message-form" method="POST" action="{{ route('send.message') }}" enctype="multipart/form-data">
        @csrf
        <label><span class="fas fa-plus-circle"></span><input type="file" class="upload-attachment" name="file" accept=".{{implode(', .',config('chatify.attachments.allowed_images'))}}, .{{implode(', .',config('chatify.attachments.allowed_files'))}}, .{{implode(', .',config('chatify.attachments.allowed_audio'))}" /></label>
        <button class="emoji-button"></span><span class="fas fa-smile"></button>
        <textarea name="message" class="m-send app-scroll" placeholder="Type a message.."></textarea>
        {{-- Voice Record Button --}}
        <button type="button" class="voice-record-button relative z-50 text-xl text-[var(--primary-color)] p-2 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors focus:outline-none" id="voice-record-btn">
            <span class="fas fa-microphone"></span>
        </button>
        <button class="send-button"><span class="fas fa-paper-plane"></span></button>
    </form>
    
    {{-- Voice Recording Interface (Tailwind Styled) --}}
    <div class="voice-recording-interface hidden absolute top-0 left-0 w-full h-full bg-white dark:bg-[#1f1f1f] rounded-lg items-center z-[100] px-3 shadow-[0_-2px_10px_rgba(0,0,0,0.05)] border dark:border-[#2f2f2f]" id="voice-recording-interface">
        <div class="voice-recording-content flex items-center w-full gap-3 h-full relative">
            <!-- Status Dot -->
            <div class="recording-status-dot w-2.5 h-2.5 bg-red-500 rounded-full animate-pulse mr-1"></div>
            
            <!-- Timer -->
            <div class="recording-timer text-gray-800 dark:text-gray-200 font-medium min-w-[45px] tabular-nums text-sm">00:00</div>
            
            <!-- Visualizer -->
            <div class="voice-visualizer flex-1 flex items-center gap-[2px] h-6 mx-2 overflow-hidden justify-start" id="voice-visualizer">
                <!-- JS injects bars -->
            </div>

            <!-- Slide Text -->
            <div class="slide-to-cancel absolute right-12 text-gray-400 text-xs flex items-center gap-1 opacity-0 transition-all duration-300 pointer-events-none transform translate-x-4" id="slide-to-cancel">
                 <i class="fas fa-chevron-left text-[10px]"></i> Slide to cancel
            </div>

            <!-- Actions (Locked Mode) -->
            <div class="recording-actions hidden gap-3 ml-auto items-center" id="recording-actions">
                <button type="button" class="recording-cancel-btn text-red-500 font-medium text-sm hover:text-red-600 focus:outline-none" id="recording-cancel-btn">Cancel</button>
                <button type="button" class="recording-send-btn w-8 h-8 bg-[var(--primary-color)] text-white rounded-full flex items-center justify-center hover:opacity-90 transition-colors shadow-sm focus:outline-none" id="recording-stop-btn">
                    <i class="fas fa-paper-plane text-xs"></i>
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Lock Indicator (External to relative container, fixed) --}}
<div class="voice-lock-indicator fixed bottom-24 right-6 flex flex-col items-center gap-2 bg-black/60 p-2 rounded-full text-white text-xs opacity-0 pointer-events-none transition-all duration-300 transform translate-y-4 z-[200]" id="voice-lock-indicator">
    <div class="voice-lock-icon w-6 h-6 bg-white text-gray-800 rounded-full flex items-center justify-center">
        <i class="fas fa-lock text-[10px]"></i>
    </div>
    <div class="voice-lock-arrow animate-bounce">
        <i class="fas fa-chevron-up"></i>
    </div>
</div>

