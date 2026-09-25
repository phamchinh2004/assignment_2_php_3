<div class="action-btn-group">
    <a href="{{ route('manager_setting.show', $item) }}" class="btn-action-icon view" aria-label="Xem {{ $item->manager_name }}"><i class="fas fa-eye"></i></a>
    <a href="{{ route('manager_setting.edit', $item) }}" class="btn-action-icon edit" aria-label="Sửa {{ $item->manager_name }}"><i class="fas fa-pen"></i></a>
    <form action="{{ route('manager_setting.destroy', $item) }}" method="POST" onsubmit="return confirm('Bạn có chắc muốn xóa chức năng này?')">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn-action-icon text-danger" aria-label="Xóa {{ $item->manager_name }}"><i class="fas fa-trash"></i></button>
    </form>
</div>
