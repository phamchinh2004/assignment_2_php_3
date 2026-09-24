@if($addingQuickMessage)
    <div class="quick-msg-editor quick-msg-editor--create" wire:key="quick-message-create-{{ $context }}">
        <textarea
            class="form-control quick-msg-editor__textarea"
            wire:model="newQuickMessageText"
            x-data
            x-init="$nextTick(() => $el.focus())"
            x-on:keydown.enter="if (!$event.shiftKey) { $event.preventDefault(); $wire.saveNewQuickMessage($el.value) }"
            x-on:keydown.escape.prevent="$wire.cancelQuickMessageAdd()"
            maxlength="2000"
            placeholder="Nhập tin nhắn nhanh mới..."
            aria-label="Thêm tin nhắn nhanh"></textarea>

        @error('newQuickMessageText')
            <div class="quick-msg-editor__error">{{ $message }}</div>
        @enderror

        <div class="quick-msg-editor__footer">
            <span>Enter: thêm · Shift+Enter: xuống dòng</span>
            <div class="quick-msg-editor__actions">
                <button type="button" class="quick-msg-editor__action" wire:click="cancelQuickMessageAdd"
                    title="Hủy" aria-label="Hủy thêm tin nhắn nhanh">
                    <i class="fas fa-times" aria-hidden="true"></i>
                </button>
                <button type="button" class="quick-msg-editor__action is-save" wire:click="saveNewQuickMessage"
                    title="Thêm tin nhắn" aria-label="Thêm tin nhắn nhanh">
                    <i class="fas fa-check" aria-hidden="true"></i>
                </button>
            </div>
        </div>
    </div>
@endif
