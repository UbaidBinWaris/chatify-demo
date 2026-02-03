/**
 *-------------------------------------------------------------
 * Voice Recorder Test Suite
 * Comprehensive tests for voice recording functionality
 *-------------------------------------------------------------
 */

window.VoiceRecorderTests = {
    
    /**
     * Test 1: Check if voice recorder is initialized
     */
    testInitialization: function() {
        console.log('\n=== Test 1: Initialization ===');
        const passed = !!window.voiceRecorder;
        console.log('Voice Recorder exists:', passed ? '✓ PASS' : '✗ FAIL');
        console.log('Instance:', window.voiceRecorder);
        return passed;
    },

    /**
     * Test 2: Check if all required methods exist
     */
    testMethods: function() {
        console.log('\n=== Test 2: Required Methods ===');
        
        if (!window.voiceRecorder) {
            console.log('✗ FAIL: Voice recorder not initialized');
            return false;
        }

        const requiredMethods = [
            'startRecording',
            'stopRecording',
            'cancelRecording',
            'hasRecordedAudio',
            'getRecordedAudioFile',
            'clearRecording',
            'showAudioPreview',
            'removeAudioPreview'
        ];

        let allPassed = true;
        requiredMethods.forEach(method => {
            const exists = typeof window.voiceRecorder[method] === 'function';
            console.log(`${method}:`, exists ? '✓' : '✗ FAIL');
            if (!exists) allPassed = false;
        });

        return allPassed;
    },

    /**
     * Test 3: Check if UI elements exist
     */
    testUIElements: function() {
        console.log('\n=== Test 3: UI Elements ===');
        
        const elements = {
            'Record Button': '#voice-record-btn',
            'Recording Interface': '#voice-recording-interface',
            'Cancel Button': '#recording-cancel-btn',
            'Stop Button': '#recording-stop-btn',
            'Message Form': '#message-form',
            'Send Button': '.send-button'
        };

        let allPassed = true;
        for (let [name, selector] of Object.entries(elements)) {
            const exists = $(selector).length > 0;
            console.log(`${name} (${selector}):`, exists ? '✓' : '✗ FAIL');
            if (!exists) allPassed = false;
        }

        return allPassed;
    },

    /**
     * Test 4: Check browser compatibility
     */
    testBrowserSupport: function() {
        console.log('\n=== Test 4: Browser Support ===');
        
        const tests = {
            'MediaRecorder API': 'MediaRecorder' in window,
            'getUserMedia': navigator.mediaDevices && 'getUserMedia' in navigator.mediaDevices,
            'Blob Support': 'Blob' in window,
            'File Support': 'File' in window,
            'FormData Support': 'FormData' in window
        };

        let allPassed = true;
        for (let [name, supported] of Object.entries(tests)) {
            console.log(`${name}:`, supported ? '✓' : '✗ FAIL');
            if (!supported) allPassed = false;
        }

        return allPassed;
    },

    /**
     * Test 5: Check supported MIME types
     */
    testMimeTypes: function() {
        console.log('\n=== Test 5: Supported Audio MIME Types ===');
        
        const mimeTypes = [
            'audio/webm;codecs=opus',
            'audio/webm',
            'audio/ogg;codecs=opus',
            'audio/mp4',
            'audio/wav'
        ];

        let supportedCount = 0;
        mimeTypes.forEach(mimeType => {
            if (typeof MediaRecorder !== 'undefined') {
                const supported = MediaRecorder.isTypeSupported(mimeType);
                console.log(`${mimeType}:`, supported ? '✓' : '✗');
                if (supported) supportedCount++;
            }
        });

        console.log(`Total supported: ${supportedCount}/${mimeTypes.length}`);
        return supportedCount > 0;
    },

    /**
     * Test 6: Check configuration
     */
    testConfiguration: function() {
        console.log('\n=== Test 6: Configuration ===');
        
        const config = window.chatify;
        console.log('Chatify config exists:', !!config ? '✓' : '✗ FAIL');
        
        if (config) {
            console.log('Allowed audio formats:', config.allowedAudio);
            console.log('All allowed extensions:', config.allAllowedExtensions);
            console.log('Max upload size:', config.maxUploadSize);
        }

        return !!config && !!config.allowedAudio;
    },

    /**
     * Test 7: Test audio file creation (simulation)
     */
    testFileCreation: function() {
        console.log('\n=== Test 7: File Creation ===');
        
        try {
            // Create a test blob
            const testBlob = new Blob(['test'], { type: 'audio/webm' });
            const testFile = new File([testBlob], 'test-voice.webm', { type: 'audio/webm' });
            
            console.log('Blob creation:', testBlob ? '✓' : '✗ FAIL');
            console.log('File creation:', testFile ? '✓' : '✗ FAIL');
            console.log('File details:', {
                name: testFile.name,
                size: testFile.size,
                type: testFile.type
            });

            return !!(testBlob && testFile);
        } catch (error) {
            console.log('✗ FAIL:', error.message);
            return false;
        }
    },

    /**
     * Test 8: Test FormData handling
     */
    testFormData: function() {
        console.log('\n=== Test 8: FormData Handling ===');
        
        try {
            const formData = new FormData();
            const testBlob = new Blob(['test'], { type: 'audio/webm' });
            const testFile = new File([testBlob], 'test-voice.webm', { type: 'audio/webm' });
            
            formData.append('file', testFile);
            formData.append('message', 'Test message');
            formData.append('id', '123');
            
            console.log('FormData created:', !!formData ? '✓' : '✗ FAIL');
            console.log('FormData entries:');
            
            let entryCount = 0;
            for (let [key, value] of formData.entries()) {
                console.log(`  ${key}:`, value);
                entryCount++;
            }
            
            console.log(`Total entries: ${entryCount}`);
            return entryCount === 3;
        } catch (error) {
            console.log('✗ FAIL:', error.message);
            return false;
        }
    },

    /**
     * Test 9: Test recording state management
     */
    testStateManagement: function() {
        console.log('\n=== Test 9: State Management ===');
        
        if (!window.voiceRecorder) {
            console.log('✗ FAIL: Voice recorder not initialized');
            return false;
        }

        const initialState = {
            isRecording: window.voiceRecorder.isRecording,
            hasRecording: window.voiceRecorder.hasRecordedAudio(),
            recordedBlob: window.voiceRecorder.recordedBlob
        };

        console.log('Initial state:', initialState);
        console.log('Is Recording:', initialState.isRecording === false ? '✓' : '✗ FAIL');
        console.log('Has Recording:', initialState.hasRecording === false ? '✓' : '✗ FAIL');
        console.log('Recorded Blob:', initialState.recordedBlob === null ? '✓' : '✗ FAIL');

        return !initialState.isRecording && !initialState.hasRecording && !initialState.recordedBlob;
    },

    /**
     * Test 10: Test CSS styles loaded
     */
    testStylesLoaded: function() {
        console.log('\n=== Test 10: CSS Styles ===');
        
        const styleTests = {
            'Voice Record Button': '.voice-record-button',
            'Recording Interface': '.voice-recording-interface',
            'Audio Preview': '.audio-preview-container',
            'Audio Message': '.audio-message'
        };

        let allPassed = true;
        for (let [name, selector] of Object.entries(styleTests)) {
            const element = $(selector);
            const hasStyles = element.length > 0 || $(`<div class="${selector.substring(1)}"></div>`).css('display') !== undefined;
            console.log(`${name}:`, hasStyles ? '✓' : '✗');
        }

        return allPassed;
    },

    /**
     * Manual Test: Request microphone permission
     */
    testMicrophonePermission: async function() {
        console.log('\n=== Manual Test: Microphone Permission ===');
        console.log('Requesting microphone access...');
        
        try {
            const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
            console.log('✓ Microphone permission granted');
            console.log('Audio tracks:', stream.getAudioTracks().length);
            
            // Stop the stream
            stream.getTracks().forEach(track => track.stop());
            console.log('✓ Stream stopped successfully');
            
            return true;
        } catch (error) {
            console.log('✗ FAIL:', error.message);
            console.log('Error name:', error.name);
            return false;
        }
    },

    /**
     * Integration Test: Test complete recording flow (simulation)
     */
    testRecordingFlow: function() {
        console.log('\n=== Integration Test: Recording Flow ===');
        
        if (!window.voiceRecorder) {
            console.log('✗ FAIL: Voice recorder not initialized');
            return false;
        }

        console.log('Testing recording flow simulation...');
        
        // Test hasRecordedAudio when empty
        const hasRecordingBefore = window.voiceRecorder.hasRecordedAudio();
        console.log('Has recording (before):', hasRecordingBefore === false ? '✓' : '✗ FAIL');

        // Simulate a recorded blob
        window.voiceRecorder.recordedBlob = new Blob(['test'], { type: 'audio/webm' });
        
        // Test hasRecordedAudio after setting blob
        const hasRecordingAfter = window.voiceRecorder.hasRecordedAudio();
        console.log('Has recording (after):', hasRecordingAfter === true ? '✓' : '✗ FAIL');

        // Test getRecordedAudioFile
        const audioFile = window.voiceRecorder.getRecordedAudioFile();
        console.log('Get audio file:', audioFile ? '✓' : '✗ FAIL');
        
        if (audioFile) {
            console.log('File details:', {
                name: audioFile.name,
                size: audioFile.size,
                type: audioFile.type
            });
        }

        // Clean up
        window.voiceRecorder.clearRecording();
        const hasRecordingAfterClear = window.voiceRecorder.hasRecordedAudio();
        console.log('Has recording (after clear):', hasRecordingAfterClear === false ? '✓' : '✗ FAIL');

        return hasRecordingBefore === false && 
               hasRecordingAfter === true && 
               !!audioFile && 
               hasRecordingAfterClear === false;
    },

    /**
     * Run all automated tests
     */
    runAll: function() {
        console.log('\n╔═══════════════════════════════════════════╗');
        console.log('║   Voice Recorder Test Suite              ║');
        console.log('╚═══════════════════════════════════════════╝');

        const tests = [
            { name: 'Initialization', fn: this.testInitialization },
            { name: 'Required Methods', fn: this.testMethods },
            { name: 'UI Elements', fn: this.testUIElements },
            { name: 'Browser Support', fn: this.testBrowserSupport },
            { name: 'MIME Types', fn: this.testMimeTypes },
            { name: 'Configuration', fn: this.testConfiguration },
            { name: 'File Creation', fn: this.testFileCreation },
            { name: 'FormData Handling', fn: this.testFormData },
            { name: 'State Management', fn: this.testStateManagement },
            { name: 'CSS Styles', fn: this.testStylesLoaded },
            { name: 'Recording Flow', fn: this.testRecordingFlow }
        ];

        const results = {
            passed: 0,
            failed: 0,
            total: tests.length
        };

        tests.forEach((test, index) => {
            try {
                const result = test.fn.call(this);
                if (result) {
                    results.passed++;
                } else {
                    results.failed++;
                }
            } catch (error) {
                console.error(`Test "${test.name}" threw error:`, error);
                results.failed++;
            }
        });

        console.log('\n╔═══════════════════════════════════════════╗');
        console.log('║   Test Results                            ║');
        console.log('╚═══════════════════════════════════════════╝');
        console.log(`Total Tests: ${results.total}`);
        console.log(`Passed: ${results.passed} ✓`);
        console.log(`Failed: ${results.failed} ✗`);
        console.log(`Success Rate: ${Math.round((results.passed / results.total) * 100)}%`);

        console.log('\n📝 Manual Tests Available:');
        console.log('  - VoiceRecorderTests.testMicrophonePermission() - Test mic access');
        console.log('  - testVoiceRecorder() - Quick status check');

        return results;
    },

    /**
     * Quick debugging info
     */
    debug: function() {
        console.log('\n=== Voice Recorder Debug Info ===');
        console.log('Recorder instance:', window.voiceRecorder);
        
        if (window.voiceRecorder) {
            console.log('Is recording:', window.voiceRecorder.isRecording);
            console.log('Has audio:', window.voiceRecorder.hasRecordedAudio());
            console.log('Blob:', window.voiceRecorder.recordedBlob);
            console.log('Audio chunks:', window.voiceRecorder.audioChunks?.length || 0);
        }
        
        console.log('UI Elements:');
        console.log('  Record button:', $('#voice-record-btn').length > 0);
        console.log('  Recording interface:', $('#voice-recording-interface').length > 0);
        console.log('  Audio preview:', $('#audio-preview').length > 0);
        
        console.log('Browser Support:');
        console.log('  MediaRecorder:', 'MediaRecorder' in window);
        console.log('  getUserMedia:', !!(navigator.mediaDevices && navigator.mediaDevices.getUserMedia));
    }
};

// Auto-run tests when loaded (optional)
$(document).ready(function() {
    console.log('Voice Recorder Tests loaded. Run VoiceRecorderTests.runAll() to start testing.');
});
