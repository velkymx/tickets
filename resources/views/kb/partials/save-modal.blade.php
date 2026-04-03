<div x-data="{ showModal: false }" @keydown.escape.window="showModal = false">
    <button type="button" class="btn btn-success" @click="showModal = true">
        {{ $buttonLabel ?? 'Save Article' }}
    </button>

    <div x-show="showModal" x-cloak class="modal d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);" @click.self="showModal = false">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Save Article</h5>
                    <button type="button" class="btn-close" @click="showModal = false"></button>
                </div>
                <div class="modal-body">
                    <label for="commit_message" class="form-label">Commit Message</label>
                    <input type="text" name="commit_message" id="commit_message" class="form-control"
                           placeholder="Describe your changes..." required>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" @click="showModal = false">Cancel</button>
                    <button type="submit" class="btn btn-success">Save</button>
                </div>
            </div>
        </div>
    </div>
</div>
