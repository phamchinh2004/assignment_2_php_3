<div class="form-group-modern">
    <label for="parent_manager_setting_id" class="form-label-modern">Chức năng cha</label>
    <select name="parent_manager_setting_id" id="parent_manager_setting_id" class="form-control-modern @error('parent_manager_setting_id') is-invalid @enderror">
        <option value="">Không có / Đây là chức năng cha</option>
        @foreach($parents as $parent)
            <option value="{{ $parent->id }}" @selected((string) old('parent_manager_setting_id', $manager_setting->parent_manager_setting_id ?? '') === (string) $parent->id)>{{ $parent->manager_name }}</option>
        @endforeach
    </select>
    @error('parent_manager_setting_id')<span class="form-error-modern">{{ $message }}</span>@enderror
</div>
