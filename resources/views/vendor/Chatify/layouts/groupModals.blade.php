<!-- Create Group Modal -->
<div class="app-modal" data-name="createGroup" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="app-modal-container">
        <div class="app-modal-card" data-name="createGroup" data-modal-action="hide">
            <form id="create-group-form">
                <div class="app-modal-header">Create New Group</div>
                <div class="app-modal-body">
                    <div class="form-group mb-3">
                        <label for="group-name">Group Name *</label>
                        <input type="text" id="group-name" name="name" class="form-control" required placeholder="Enter group name">
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="group-description">Description</label>
                        <textarea id="group-description" name="description" class="form-control" rows="3" placeholder="Optional group description"></textarea>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label>Add Members *</label>
                        <input type="text" id="search-members" class="form-control mb-2" placeholder="Search users...">
                        <div id="search-results" class="search-results-container" style="max-height: 200px; overflow-y: auto; border: 1px solid #ddd; border-radius: 4px; display: none;"></div>
                        <div id="selected-members" class="selected-members-container mt-2"></div>
                        <input type="hidden" id="group-members" name="members">
                    </div>
                </div>
                <div class="app-modal-footer">
                    <button type="button" class="btn-cancel" data-modal-action="hide">Cancel</button>
                    <button type="submit" class="btn-primary">Create Group</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Group Info Modal -->
<div class="app-modal" data-name="groupInfo" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="app-modal-container">
        <div class="app-modal-card" data-name="groupInfo" data-modal-action="hide">
            <div class="app-modal-header">Group Info</div>
            <div class="app-modal-body" id="group-info-body">
                <!-- Group info will be loaded here -->
            </div>
            <div class="app-modal-footer">
                <button type="button" class="btn-cancel" data-modal-action="hide">Close</button>
                <button type="button" id="add-group-members-btn" class="btn-primary" style="display: none;">Add Members</button>
            </div>
        </div>
    </div>
</div>

<style>
.selected-member {
    display: inline-block;
    background: #e3f2fd;
    padding: 5px 10px;
    margin: 3px;
    border-radius: 15px;
    font-size: 14px;
}
.selected-member .remove-member {
    margin-left: 8px;
    cursor: pointer;
    color: #f44336;
    font-weight: bold;
}
.search-result-item {
    padding: 10px;
    cursor: pointer;
    border-bottom: 1px solid #eee;
}
.search-result-item:hover {
    background: #f5f5f5;
}
.group-list-item {
    display: flex;
    align-items: center;
    padding: 10px;
    cursor: pointer;
    border-bottom: 1px solid #eee;
}
.group-list-item:hover {
    background: #f5f5f5;
}
.group-avatar {
    width: 45px;
    height: 45px;
    border-radius: 50%;
    margin-right: 10px;
    background: #4CAF50;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
    font-size: 18px;
}
.group-info {
    flex: 1;
}
.group-name {
    font-weight: bold;
    font-size: 15px;
}
.group-members-count {
    font-size: 12px;
    color: #888;
}
.form-control {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}
.form-control:focus {
    outline: none;
    border-color: var(--primary-color);
}
.btn-primary {
    background: var(--primary-color);
    color: white;
    padding: 8px 20px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}
.btn-primary:hover {
    opacity: 0.9;
}
.btn-cancel {
    background: #f5f5f5;
    color: #333;
    padding: 8px 20px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    margin-right: 10px;
}
.btn-cancel:hover {
    background: #e0e0e0;
}
.mb-3 {
    margin-bottom: 15px;
}
.mt-2 {
    margin-top: 10px;
}
</style>
