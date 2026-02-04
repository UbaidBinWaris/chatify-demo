{{-- Reaction Picker Modal --}}
<div class="reaction-picker" id="reactionPicker" style="display: none;">
    <div class="reaction-picker-content">
        {{-- Frequent Emojis Section --}}
        <div class="reaction-picker-frequent">
            <div class="frequent-emojis" id="frequentEmojis">
                {{-- Dynamically loaded frequent emojis --}}
            </div>
            <button class="more-emojis-btn" id="moreEmojisBtn">
                <i class="fas fa-plus"></i>
            </button>
        </div>
        
        {{-- All Emojis Section (Hidden by default) --}}
        <div class="reaction-picker-all" id="allEmojisSection" style="display: none;">
            <div class="emoji-categories-tabs">
                <button class="emoji-tab active" data-category="frequent">
                    <i class="fas fa-clock"></i>
                </button>
                <button class="emoji-tab" data-category="smileys">
                    <i class="far fa-smile"></i>
                </button>
                <button class="emoji-tab" data-category="hearts">
                    <i class="fas fa-heart"></i>
                </button>
                <button class="emoji-tab" data-category="gestures">
                    <i class="far fa-hand-paper"></i>
                </button>
                <button class="emoji-tab" data-category="emotions">
                    <i class="fas fa-sad-tear"></i>
                </button>
            </div>
            
            <div class="emoji-categories-content">
                <div class="emoji-category active" data-category="frequent">
                    <div class="category-title">Frequently Used</div>
                    <div class="emoji-grid" id="frequentCategory"></div>
                </div>
                <div class="emoji-category" data-category="smileys">
                    <div class="category-title">Smileys & People</div>
                    <div class="emoji-grid" id="smileysCategory"></div>
                </div>
                <div class="emoji-category" data-category="hearts">
                    <div class="category-title">Hearts</div>
                    <div class="emoji-grid" id="heartsCategory"></div>
                </div>
                <div class="emoji-category" data-category="gestures">
                    <div class="category-title">Gestures</div>
                    <div class="emoji-grid" id="gesturesCategory"></div>
                </div>
                <div class="emoji-category" data-category="emotions">
                    <div class="category-title">Emotions</div>
                    <div class="emoji-grid" id="emotionsCategory"></div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Reaction Details Tooltip --}}
<div class="reaction-details-tooltip" id="reactionDetailsTooltip" style="display: none;">
    <div class="reaction-details-content"></div>
</div>
