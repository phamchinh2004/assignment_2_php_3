document.addEventListener('DOMContentLoaded', function () {
    // Xử lý xóa banner
    const deleteButtons = document.querySelectorAll('.btn-delete');

    deleteButtons.forEach(button => {
        button.addEventListener('click', function (e) {
            e.preventDefault();

            const form = this.closest('.form-delete');
            const bannerName = form.getAttribute('data-banner-name');

            swal({
                title: 'Xác nhận xóa banner?',
                text: `Bạn có chắc chắn muốn xóa banner "${bannerName}"? Tất cả hình ảnh liên quan cũng sẽ bị xóa. Hành động này không thể hoàn tác!`,
                icon: 'warning',
                buttons: {
                    cancel: {
                        text: 'Hủy',
                        visible: true,
                        className: 'btn-secondary',
                        closeModal: true,
                    },
                    confirm: {
                        text: 'Xóa',
                        className: 'btn-danger'
                    }
                },
                dangerMode: true,
            }).then((willDelete) => {
                if (willDelete) {
                    // Submit form để xóa banner
                    form.submit();
                }
            });
        });
    });
});