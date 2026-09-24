@php
    $announcement = $featureAnnouncement ?? null;
    $selectedRoles = old('target_roles', $announcement?->target_roles ?? array_keys($roleOptions));
    $selectedPriority = old('priority', $announcement?->priority ?? \App\Models\FeatureAnnouncement::PRIORITY_NORMAL);
    $startsAt = old('starts_at', $announcement?->starts_at?->format('Y-m-d\TH:i') ?? now()->format('Y-m-d\TH:i'));
    $endsAt = old('ends_at', $announcement?->ends_at?->format('Y-m-d\TH:i'));
    $isActive = (bool) old('is_active', $announcement?->is_active ?? true);
@endphp

<div class="form-body-modern">
    <div class="row">
        <div class="col-12 col-xl-9 mx-auto">
            <div class="form-section-modern">
                <div class="form-section-title">
                    <i class="fas fa-bullhorn"></i> Nội dung thông báo
                </div>

                <div class="form-group-modern">
                    <label class="form-label-modern" for="title">Tiêu đề <span class="text-danger">*</span></label>
                    <input type="text" name="title" id="title"
                        class="form-control-modern @error('title') is-invalid @enderror"
                        value="{{ old('title', $announcement?->title) }}" maxlength="180" required>
                    @error('title') <span class="form-error-modern">{{ $message }}</span> @enderror
                </div>

                <div class="form-group-modern">
                    <label class="form-label-modern" for="content">Nội dung <span class="text-danger">*</span></label>
                    <textarea name="content" id="content" rows="7"
                        class="form-control-modern @error('content') is-invalid @enderror"
                        required>{{ old('content', $announcement?->content) }}</textarea>
                    @error('content') <span class="form-error-modern">{{ $message }}</span> @enderror
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group-modern">
                            <label class="form-label-modern" for="priority">Mức độ ưu tiên <span class="text-danger">*</span></label>
                            <select name="priority" id="priority" class="form-control-modern" required>
                                <option value="normal" @selected($selectedPriority === 'normal')>Bình thường</option>
                                <option value="important" @selected($selectedPriority === 'important')>Quan trọng</option>
                                <option value="critical" @selected($selectedPriority === 'critical')>Khẩn cấp</option>
                            </select>
                            @error('priority') <span class="form-error-modern">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group-modern">
                            <label class="form-label-modern" for="starts_at">Bắt đầu <span class="text-danger">*</span></label>
                            <input type="datetime-local" name="starts_at" id="starts_at"
                                class="form-control-modern @error('starts_at') is-invalid @enderror"
                                value="{{ $startsAt }}" required>
                            @error('starts_at') <span class="form-error-modern">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group-modern">
                            <label class="form-label-modern" for="ends_at">Kết thúc</label>
                            <input type="datetime-local" name="ends_at" id="ends_at"
                                class="form-control-modern @error('ends_at') is-invalid @enderror"
                                value="{{ $endsAt }}">
                            <small class="text-muted">Để trống nếu không có ngày hết hạn.</small>
                            @error('ends_at') <span class="form-error-modern">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-section-modern">
                <div class="form-section-title">
                    <i class="fas fa-users"></i> Đối tượng nhận
                </div>
                <div class="row">
                    @foreach($roleOptions as $role => $label)
                        <div class="col-md-4 mb-2">
                            <label class="border rounded px-3 py-3 d-flex align-items-center w-100 mb-0" style="gap: 10px; cursor: pointer;">
                                <input type="checkbox" name="target_roles[]" value="{{ $role }}"
                                    @checked(in_array($role, $selectedRoles ?? [], true))>
                                <span>
                                    <strong class="d-block">{{ $label }}</strong>
                                    <small class="text-muted">{{ $role }}</small>
                                </span>
                            </label>
                        </div>
                    @endforeach
                </div>
                @error('target_roles') <span class="form-error-modern d-block mt-2">{{ $message }}</span> @enderror
                @error('target_roles.*') <span class="form-error-modern d-block mt-2">{{ $message }}</span> @enderror
            </div>

            <div class="form-section-modern">
                <div class="form-section-title">
                    <i class="fas fa-link"></i> Hành động và hình ảnh
                </div>
                <div class="row">
                    <div class="col-md-5">
                        <div class="form-group-modern">
                            <label class="form-label-modern" for="action_text">Nhãn nút hành động</label>
                            <input type="text" name="action_text" id="action_text"
                                class="form-control-modern @error('action_text') is-invalid @enderror"
                                value="{{ old('action_text', $announcement?->action_text) }}"
                                placeholder="Ví dụ: Xem tính năng mới">
                            @error('action_text') <span class="form-error-modern">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="col-md-7">
                        <div class="form-group-modern">
                            <label class="form-label-modern" for="action_url">Liên kết</label>
                            <input type="text" name="action_url" id="action_url"
                                class="form-control-modern @error('action_url') is-invalid @enderror"
                                value="{{ old('action_url', $announcement?->action_url) }}"
                                placeholder="/admin/... hoặc https://...">
                            <small class="text-muted">Mở liên kết không tự động tính là đã xác nhận.</small>
                            @error('action_url') <span class="form-error-modern">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>

                @if($announcement?->image_path)
                    <div class="mb-3">
                        <img src="{{ Storage::url($announcement->image_path) }}" alt="Ảnh thông báo"
                            class="img-fluid rounded border" style="max-height: 220px;">
                        <label class="d-block mt-2 mb-0">
                            <input type="checkbox" name="remove_image" value="1"> Xóa ảnh hiện tại
                        </label>
                    </div>
                @endif

                <div class="form-group-modern">
                    <label class="form-label-modern" for="image">Ảnh / screenshot</label>
                    <input type="file" name="image" id="image" accept="image/jpeg,image/png,image/webp,image/gif"
                        class="form-control-file">
                    <small class="text-muted">JPG, PNG, WEBP hoặc GIF, tối đa 5 MB.</small>
                    @error('image') <span class="form-error-modern d-block">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="form-section-modern">
                <div class="form-section-title">
                    <i class="fas fa-sliders"></i> Trạng thái
                </div>
                <input type="hidden" name="is_active" value="0">
                <label class="d-flex align-items-center mb-0" style="gap: 10px;">
                    <input type="checkbox" name="is_active" value="1" @checked($isActive)>
                    <span>Kích hoạt thông báo</span>
                </label>

                @if($announcement)
                    <hr>
                    <input type="hidden" name="require_reacknowledgement" value="0">
                    <label class="d-flex align-items-start mb-0" style="gap: 10px;">
                        <input type="checkbox" name="require_reacknowledgement" value="1"
                            @checked((bool) old('require_reacknowledgement', false))>
                        <span>
                            <strong>Yêu cầu người dùng xác nhận lại</strong>
                            <small class="text-muted d-block">
                                Tăng version từ {{ $announcement->version }} lên {{ $announcement->version + 1 }}.
                                Lịch sử xác nhận cũ vẫn được giữ.
                            </small>
                        </span>
                    </label>
                @endif
            </div>
        </div>
    </div>
</div>
