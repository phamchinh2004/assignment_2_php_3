@php
    $quickMessagePreview = \Illuminate\Support\Str::limit(
        preg_replace('/\s+/u', ' ', trim($messageText)),
        60
    );
@endphp

<div class="quick-msg-item" wire:key="quick-message-{{ $context }}-{{ $messageKey }}">
    @if($editingQuickMessageKey === $messageKey)
        <div class="quick-msg-row is-editing">
            <span class="quick-msg-index" aria-hidden="true">{{ $messageNumber ?? 1 }}</span>
            <div class="quick-msg-editor">
                <textarea
                    class="form-control quick-msg-editor__textarea"
                    wire:model="editingQuickMessageText"
                    x-data
                    x-init="$nextTick(() => { $el.focus(); $el.setSelectionRange($el.value.length, $el.value.length) })"
                    x-on:keydown.enter="if (!$event.shiftKey) { $event.preventDefault(); $wire.saveQuickMessage($el.value) }"
                    x-on:keydown.escape.prevent="$wire.cancelQuickMessageEdit()"
                    maxlength="2000"
                    aria-label="Chỉnh sửa tin nhắn nhanh"></textarea>

                @error('editingQuickMessageText')
                    <div class="quick-msg-editor__error">{{ $message }}</div>
                @enderror

                <div class="quick-msg-editor__footer">
                    <span>Enter: hoàn tất · Shift+Enter: xuống dòng</span>
                    <div class="quick-msg-editor__actions">
                        <button type="button" class="quick-msg-editor__action" wire:click="cancelQuickMessageEdit"
                            title="Hủy chỉnh sửa" aria-label="Hủy chỉnh sửa">
                            <i class="fas fa-times" aria-hidden="true"></i>
                        </button>
                        <button type="button" class="quick-msg-editor__action is-save" wire:click="saveQuickMessage"
                            title="Lưu tin nhắn" aria-label="Lưu tin nhắn">
                            <i class="fas fa-check" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="quick-msg-row">
            <span class="quick-msg-index" aria-hidden="true">{{ $messageNumber ?? 1 }}</span>
            <button type="button" class="quick-msg-btn text-start"
                data-message="{{ $messageText }}"
                onclick="copyQuickMessage(this.dataset.message)" title="Click để đưa vào ô soạn tin">
                <span class="quick-msg-preview">{{ $quickMessagePreview }}</span>
            </button>
            <div class="quick-msg-actions">
                <button type="button" class="quick-msg-edit-btn"
                    wire:click="startEditingQuickMessage('{{ $messageKey }}')"
                    title="Chỉnh sửa tin nhắn nhanh" aria-label="Chỉnh sửa tin nhắn nhanh">
                    <i class="fas fa-pen" aria-hidden="true"></i>
                </button>
                <button type="button" class="quick-msg-edit-btn is-delete"
                    wire:click="deleteQuickMessage('{{ $messageKey }}')"
                    wire:confirm="Bạn có chắc muốn xóa tin nhắn nhanh này?"
                    title="Xóa tin nhắn nhanh" aria-label="Xóa tin nhắn nhanh">
                    <i class="fas fa-trash" aria-hidden="true"></i>
                </button>
            </div>
        </div>
    @endif
</div>
