import SlimSelect from 'slim-select';
import 'slim-select/styles';

document.addEventListener('DOMContentLoaded', function () {
    const bankSelect = document.getElementById('bank_name');
    if (bankSelect) {
        new SlimSelect({
            select: '#bank_name',
            settings: {
                placeholderText: 'Chọn ngân hàng',
                searchPlaceholder: 'Tìm kiếm ngân hàng...',
                searchText: 'Không tìm thấy ngân hàng',
                allowDeselect: true,
                contentLocation: document.body,
                contentPosition: 'fixed',
                keepOrder: true,
            },
        });
    }

    const btn_submit = document.getElementById('btn_submit');
    const form = document.getElementById('form');
    if (btn_submit && form) {
        btn_submit.addEventListener('click', function () {
            AppDialog.confirm({
                title: 'Lưu thay đổi?',
                text: 'Thông tin tài khoản sẽ được cập nhật ngay sau khi xác nhận.',
                icon: 'warning',
                buttons: true,
                dangerMode: false,
            }).then((isConfirmed) => {
                if (!isConfirmed) return;

                btn_submit.disabled = true;
                form.submit();
            });
        });
    }

    const deleteLocationButton = document.getElementById('deleteLocationButton');
    const deleteLocationForm = document.getElementById('deleteLocationForm');
    if (deleteLocationButton && deleteLocationForm && !deleteLocationButton.disabled) {
        deleteLocationButton.addEventListener('click', function () {
            AppDialog.confirm({
                title: 'Xóa dữ liệu vị trí?',
                text: 'Toàn bộ vị trí chính xác và vị trí tương đối hiện tại của người dùng sẽ bị xóa.',
                icon: 'warning',
                buttons: true,
                dangerMode: true,
            }).then((isConfirmed) => {
                if (!isConfirmed) return;

                deleteLocationButton.disabled = true;
                deleteLocationForm.submit();
            });
        });
    }
});
