/**
 *-------------------------------------------------------------
 * Voice Recording Module
 * Handles voice message recording, preview, sending, and visualization
 * Mimics WhatsApp-style interaction (Hold-to-record, Slide-to-lock)
 *-------------------------------------------------------------
 */

class VoiceRecorder {
    constructor() {
        this.mediaRecorder = null;
        this.audioChunks = [];
        this.recordingStream = null;
        this.recordingStartTime = null;
        this.timerInterval = null;
        this.recordedBlob = null;
        
        // State
        this.isRecording = false;
        this.isLocked = false;
        this.startY = 0;
        this.startX = 0;
        
        // Audio Context for Visualization
        this.audioContext = null;
        this.analyser = null;
        this.dataArray = null;
        this.rafId = null;

        this.initializeElements();
        this.attachEventListeners();
    }

    initializeElements() {
        this.recordBtn = document.getElementById('voice-record-btn');
        // Ensure we have the recording interface structure
        this.recordingInterface = document.getElementById('voice-recording-interface');
        
        // If interface doesn't exist or needs update, we can inject/modify here
        if (this.recordingInterface) {
           // Ensure the correct internal structure exists or inject it
           const content = this.recordingInterface.querySelector('.voice-recording-content');
           // Quick check if we need to upgrade the DOM
           if (content && !content.querySelector('.voice-visualizer')) {
                content.innerHTML = `
                    <div class="recording-status-dot"></div>
                    <div class="recording-timer">00:00</div>
                    <div class="voice-visualizer" id="voice-visualizer"></div>
                    <div class="slide-to-cancel" id="slide-to-cancel">
                        <i class="fas fa-chevron-left"></i> Slide to cancel
                    </div>
                    <div class="recording-actions" id="recording-actions">
                        <button type="button" class="recording-cancel-btn">Cancel</button>
                        <button type="button" class="recording-send-btn"><i class="fas fa-paper-plane"></i></button>
                    </div>
                `;
           }
        }
        
        // Inject Lock Indicator if missing
        if (!document.getElementById('voice-lock-indicator')) {
            const lockIndicator = document.createElement('div');
            lockIndicator.id = 'voice-lock-indicator';
            lockIndicator.className = 'voice-lock-indicator';
            lockIndicator.innerHTML = `
                <div class="voice-lock-icon"><i class="fas fa-lock"></i></div>
                <div class="voice-lock-arrow"><i class="fas fa-chevron-up"></i></div>
            `;
            document.body.appendChild(lockIndicator);
        }

        // Re-fetch references (some might be newly created)
        this.timerDisplay = document.querySelector('.recording-timer');
        this.visualizerContainer = document.getElementById('voice-visualizer');
        this.lockIndicator = document.getElementById('voice-lock-indicator');
        this.slideToCancel = document.getElementById('slide-to-cancel');
        this.recordingActions = document.getElementById('recording-actions');
        
        this.actionCancelBtn = document.querySelector('.recording-cancel-btn');
        this.actionSendBtn = document.querySelector('.recording-send-btn');
        
        this.messageForm = document.getElementById('message-form');
        this.sendCard = document.querySelector('.messenger-sendCard');
    }

    attachEventListeners() {
        if (!this.recordBtn) return;

        // Clone to remove old listeners (critical for preventing duplicates)
        const newBtn = this.recordBtn.cloneNode(true);
        this.recordBtn.parentNode.replaceChild(newBtn, this.recordBtn);
        this.recordBtn = newBtn;

        // Interaction Start (Mobile & Desktop)
        // Using both can cause double-firing on some hybrids, but preventDefault covers most
        this.recordBtn.addEventListener('mousedown', (e) => this.handleStart(e));
        this.recordBtn.addEventListener('touchstart', (e) => this.handleStart(e), { passive: false });

        // Action Buttons inside the locked UI
        if (this.actionCancelBtn) {
            this.actionCancelBtn.addEventListener('click', () => this.cancelRecording());
        }
        if (this.actionSendBtn) {
            this.actionSendBtn.addEventListener('click', () => this.stopRecording());
        }
    }

    handleStart(e) {
        // Prevent default to stop focus changes/scrolling
        if(e.type === 'touchstart') e.preventDefault(); 
        
        if (this.isRecording) return; // Already recording

        this.isLocked = false;
        // Coordinates for gesture tracking
        this.startY = e.touches ? e.touches[0].clientY : e.clientY;
        this.startX = e.touches ? e.touches[0].clientX : e.clientX;

        // Bind global move/up handlers
        this.boundMove = (ev) => this.handleMove(ev);
        this.boundEnd = (ev) => this.handleEnd(ev);

        document.addEventListener('mousemove', this.boundMove);
        document.addEventListener('mouseup', this.boundEnd);
        document.addEventListener('touchmove', this.boundMove, { passive: false });
        document.addEventListener('touchend', this.boundEnd);

        this.startRecording();
    }

    handleMove(e) {
        if (!this.isRecording || this.isLocked) return;

        const currentY = e.touches ? e.touches[0].clientY : e.clientY;
        const currentX = e.touches ? e.touches[0].clientX : e.clientX;
        
        const deltaY = this.startY - currentY; // Positive = Dragging UP
        const deltaX = this.startX - currentX; // Positive = Dragging LEFT

        // SLIDE UP TO LOCK Logic (Threshold: 50px)
        if (deltaY > 50) {
            this.lockRecording();
        }

        // SLIDE LEFT TO CANCEL Logic (Visual feedback)
        if (deltaX > 20 && this.slideToCancel) {
            const opacity = Math.min(1, deltaX / 100);
            this.slideToCancel.style.opacity = Math.max(0.3, 1 - opacity); // Fade out as you slide? Or fade in?
            // Usually "Slide to cancel" text fades as you approach it? 
            // Let's just keep it simple: Show it clearly.
            if(deltaX > 100) {
                // Trigger cancel
                this.handleEnd(e); // Trigger end
                this.cancelRecording();
            }
        }
    }

    handleEnd(e) {
        // Clean up listeners
        document.removeEventListener('mousemove', this.boundMove);
        document.removeEventListener('mouseup', this.boundEnd);
        document.removeEventListener('touchmove', this.boundMove);
        document.removeEventListener('touchend', this.boundEnd);

        if (this.isLocked) {
            // Keep recording
            return;
        }

        // If simple tap (short duration) or release without lock -> Stop/Send?
        // WhatsApp behavior: Release sends immediately.
        // We will safeguard against micro-taps (< 500ms).
        const duration = Date.now() - this.recordingStartTime;
        if (duration < 500) {
            this.cancelRecording(); // Assume accidental
        } else {
            // Normal release -> Send (Stop and process)
            // Note: stopRecording triggers onstop event which calls handleRecordingComplete
            this.stopRecording(); 
        }
    }

    lockRecording() {
        if (this.isLocked) return;
        this.isLocked = true;
        
        // Update UI for Locked State
        if (this.lockIndicator) {
            this.lockIndicator.classList.remove('visible'); // Hide the arrow
        }
        if (this.slideToCancel) {
            this.slideToCancel.style.display = 'none';
        }
        if (this.recordingActions) {
            // Fix visibility: Remove 'hidden' and ensure 'flex' display
            this.recordingActions.classList.remove('hidden');
            this.recordingActions.classList.add('flex');
            
            // Re-bind click handlers to ensure they work
            const cancelBtn = this.recordingActions.querySelector('.recording-cancel-btn');
            const sendBtn = this.recordingActions.querySelector('.recording-send-btn');
            
            // Remove old listeners to avoid duplicates
            const newCancel = cancelBtn.cloneNode(true);
            const newSend = sendBtn.cloneNode(true);
            cancelBtn.parentNode.replaceChild(newCancel, cancelBtn);
            sendBtn.parentNode.replaceChild(newSend, sendBtn);
            
            newCancel.onclick = () => this.cancelRecording();
            // Send Immediately when clicking SEND in locked mode
            newSend.onclick = () => this.stopRecording(true); 
        }
        
        // Visual feedback on button
        this.recordBtn.classList.remove('recording-active'); // Stop pulsing the main button
    }

    async startRecording() {
        try {
            // Request permissions
            this.recordingStream = await navigator.mediaDevices.getUserMedia({ 
                audio: { echoCancellation: true, noiseSuppression: true } 
            });

            // Init Visualization
            this.setupVisualizer(this.recordingStream);

            // Init MediaRecorder
            // Try/Catch for MIME types
            const options = { mimeType: this.getSupportedMimeType() };
            try {
                this.mediaRecorder = new MediaRecorder(this.recordingStream, options);
            } catch (e) {
                // Fallback
                this.mediaRecorder = new MediaRecorder(this.recordingStream);
            }
            
            this.audioChunks = [];
            this.shouldSendImmediately = false; // Reset state

            this.mediaRecorder.ondataavailable = (e) => {
                if (e.data.size > 0) this.audioChunks.push(e.data);
            };

            this.mediaRecorder.onstop = () => this.handleRecordingComplete();

            this.mediaRecorder.start();
            this.isRecording = true;
            this.recordingStartTime = Date.now();

            this.showRecordingInterface();
            this.startTimer();
            this.updateUIForRecordingStart();

        } catch (error) {
            console.error('Mic Error:', error);
            alert('Could not access microphone.');
            this.cleanupRecordingState();
        }
    }

    setupVisualizer(stream) {
        if (!this.visualizerContainer) return;
        
        // Create context
        if (!this.audioContext) {
            this.audioContext = new (window.AudioContext || window.webkitAudioContext)();
        }
        // If context was suspended (autoplay policy), resume it
        if (this.audioContext.state === 'suspended') {
            this.audioContext.resume();
        }

        this.analyser = this.audioContext.createAnalyser();
        const source = this.audioContext.createMediaStreamSource(stream);
        source.connect(this.analyser);
        
        this.analyser.fftSize = 64; // Small size for bars (32 bins)
        const bufferLength = this.analyser.frequencyBinCount;
        this.dataArray = new Uint8Array(bufferLength);

        // Create DOM bars
        this.visualizerContainer.innerHTML = '';
        const barCount = 25; // Number of bars to display
        for (let i = 0; i < barCount; i++) {
            const bar = document.createElement('div');
            // Tailwind classes: width 3px, PRIMARY COLOR background, rounded, transition height
            bar.className = 'w-[3px] bg-[var(--primary-color)] rounded-full transition-[height] duration-75 min-h-[4px] opacity-80'; 
            this.visualizerContainer.appendChild(bar);
        }
        
        this.drawVisualizer();
    }

    drawVisualizer() {
        if (!this.isRecording) return;

        this.rafId = requestAnimationFrame(() => this.drawVisualizer());
        this.analyser.getByteFrequencyData(this.dataArray);
        
        const bars = this.visualizerContainer.children;
        // Map frequency data to bars
        for (let i = 0; i < bars.length; i++) {
            // Use lower frequencies which are more active for voice
            const value = this.dataArray[i + 2]; // offset slightly
            // Scale value (0-255) to height (4px - 30px)
            const height = Math.max(4, (value / 255) * 28); 
            bars[i].style.height = `${height}px`;
        }
    }

    stopRecording(sendImmediately = false) {
        this.shouldSendImmediately = sendImmediately;
        if (this.mediaRecorder && this.isRecording) {
            this.mediaRecorder.stop();
            // Cleanup happens in onstop -> handleRecordingComplete -> cleanup
        } else {
             this.cleanupRecordingState();
        }
    }

    cancelRecording() {
        if (this.mediaRecorder && this.isRecording) {
            // Remove onstop handler to prevent processing
            this.mediaRecorder.onstop = null; 
            this.mediaRecorder.stop();
        }
         this.cleanupRecordingState(true);
    }

    cleanupRecordingState(isCancelled = false) {
        this.isRecording = false;
        this.isLocked = false;
        this.shouldSendImmediately = false;
        
        if (this.rafId) cancelAnimationFrame(this.rafId);
        // Do NOT close AudioContext globally if you want to reuse it, but closing input source is good
        // Current impl re-creates context or resumes. 
        
        this.stopTimer();       
        this.hideRecordingInterface();
        
        // Stop all tracks
        if (this.recordingStream) {
            this.recordingStream.getTracks().forEach(track => track.stop());
            this.recordingStream = null;
        }

        if (isCancelled) {
            this.recordedBlob = null;
            this.audioChunks = [];
        }
    }

    handleRecordingComplete() {
        // Create blob
        const mimeType = this.getSupportedMimeType() || 'audio/webm';
        this.recordedBlob = new Blob(this.audioChunks, { type: mimeType });
        
        // Capture intention before cleanup resets it
        const sendNow = this.shouldSendImmediately;
        
        // Cleanup UI/Stream (Resets shouldSendImmediately to false)
        this.cleanupRecordingState(false);
        
        if (sendNow) {
            console.log('Attempting immediate send...');
            // Immediate Send (Locked mode send)
            // Trigger the global sendMessage function from code.js
             if (typeof sendMessage === 'function') {
                 // The sendMessage function checks window.voiceRecorder.hasRecordedAudio()
                 // So we just need to call it.
                 sendMessage();
             } else {
                 console.error('sendMessage function not found, falling back to preview.');
                 this.showAudioPreview();
             }
        } else {
            // Show Preview (Default / Hold-Release behavior)
            this.showAudioPreview();
        }
    }

    // Helper: Mime Check
    getSupportedMimeType() {
        const types = [
            'audio/webm;codecs=opus', 'audio/webm',
            'audio/ogg;codecs=opus', 'audio/ogg',
            'audio/mp4', 'audio/wav'
        ];
        for (let t of types) if (MediaRecorder.isTypeSupported(t)) return t;
        return '';
    }
    
    // UI Helpers
    updateUIForRecordingStart() {
        if (this.lockIndicator) {
             this.lockIndicator.classList.add('visible');
        }
        if (this.slideToCancel) {
            this.slideToCancel.classList.add('visible');
            this.slideToCancel.style.display = 'flex';
        }
        if (this.recordingActions) {
            // Ensure actions are hidden initially (until locked)
            this.recordingActions.classList.add('hidden');
            this.recordingActions.classList.remove('flex');
            this.recordingActions.classList.remove('locked-mode');
        }
        this.recordBtn.classList.add('recording-active');
        
        // Hide Main Input Area
         $(this.sendCard).find('.chat-footer').css('visibility', 'hidden'); // Or similar selector
         // Actually, chatify uses .m-send, .upload-attachment, etc.
         // Let's rely on CSS overlay
    }
    
    showRecordingInterface() {
        if (this.recordingInterface) {
            this.recordingInterface.style.display = 'flex';
        }
    }

    hideRecordingInterface() {
        if (this.recordingInterface) {
            this.recordingInterface.style.display = 'none';
        }
        if (this.lockIndicator) this.lockIndicator.classList.remove('visible');
        this.recordBtn.classList.remove('recording-active');
        
        // Reset Actions Visibility
        if (this.recordingActions) {
            this.recordingActions.classList.add('hidden');
            this.recordingActions.classList.remove('flex');
        }

    }

    // Timer
    startTimer() {
        if (this.timerDisplay) this.timerDisplay.textContent = '00:00';
        this.timerInterval = setInterval(() => {
            const elapsed = Date.now() - this.recordingStartTime;
            const m = Math.floor(elapsed/60000);
            const s = Math.floor((elapsed%60000)/1000);
            if (this.timerDisplay) this.timerDisplay.textContent = `${m.toString().padStart(2,'0')}:${s.toString().padStart(2,'0')}`;
        }, 100);
    }
    stopTimer() { clearInterval(this.timerInterval); }

    // Preview
    showAudioPreview() {
        const audioUrl = URL.createObjectURL(this.recordedBlob);
        
        $('#audio-preview').remove();
        
        // Tailwind Styled Preview
        const previewHtml = `
            <div class="w-full p-1" id="audio-preview">
                <div class="flex items-center gap-2 bg-gray-100 dark:bg-[#2b2b2b] rounded-lg p-2 border border-gray-200 dark:border-gray-700 w-fit shadow-sm">
                    <button type="button" class="p-2 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-full transition-colors focus:outline-none" onclick="window.voiceRecorder.removeAudioPreview(true)">
                        <i class="fas fa-trash"></i>
                    </button>
                    <div class="flex items-center">
                        <audio controls src="${audioUrl}" class="h-8 w-48"></audio>
                    </div>
                </div>
            </div>
        `;
        $(this.sendCard).prepend(previewHtml);
        $('.send-button').removeAttr('disabled');
        // $('.m-send').focus(); // Focusing textarea might send cursor there, but we want user to hit send button
    }

    removeAudioPreview(clearBlob = true) {
        $('#audio-preview').remove();
        if (clearBlob) {
            this.recordedBlob = null;
            this.audioChunks = [];
        }
        // Button state check
        const hasFile = !!$('.upload-attachment').val();
         const hasMessage = $.trim($('.m-send').val()).length > 0;
         const hasAudio = !!this.recordedBlob;
         if (!hasFile && !hasMessage && !hasAudio) $('.send-button').attr('disabled', 'disabled');
    }

    getRecordedAudioFile() {
        if (!this.recordedBlob) return null;
        let ext = 'webm';
        if (this.recordedBlob.type.includes('mp4')) ext = 'm4a';
        else if (this.recordedBlob.type.includes('ogg')) ext = 'ogg';
        
        return new File([this.recordedBlob], `voice-message.${ext}`, { type: this.recordedBlob.type });
    }
    
    hasRecordedAudio() { return !!this.recordedBlob; }
}

// Global Init
window.voiceRecorder = new VoiceRecorder();
console.log('VoiceRecorder Loaded (WhatsApp Style)');
