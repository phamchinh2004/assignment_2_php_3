document.addEventListener('DOMContentLoaded', function() {
    const deletedImages = [];
    const deletedImagesInput = document.getElementById('deleted_images');
    
    // Xử lý xóa ảnh
    document.querySelectorAll('.btn-delete').forEach(button => {
        button.addEventListener('click', function() {
            const imageId = this.getAttribute('data-image-id');
            const imagePreview = this.closest('.image-preview');
            
            swal({
                title: 'Xác nhận xóa?',
                text: "Ảnh này sẽ bị xóa khi bạn cập nhật banner!",
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
                    // Thêm ID vào mảng các ảnh cần xóa
                    deletedImages.push(imageId);
                    deletedImagesInput.value = deletedImages.join(',');
                    
                    // Ẩn ảnh khỏi giao diện
                    imagePreview.style.display = 'none';
                    
                    swal({
                        title: 'Đã đánh dấu xóa',
                        text: 'Ảnh sẽ bị xóa khi bạn cập nhật banner',
                        icon: 'success',
                        timer: 1500,
                        buttons: false
                    });
                }
            });
        });
    });
    
    // Xử lý submit form
    document.getElementById('btn_submit').addEventListener('click', function() {
        swal({
            title: 'Xác nhận cập nhật?',
            text: "Bạn có chắc chắn muốn cập nhật banner này?",
            icon: 'info',
            buttons: {
                cancel: {
                    text: 'Hủy',
                    visible: true,
                    className: 'btn-secondary',
                    closeModal: true,
                },
                confirm: {
                    text: 'Cập nhật',
                    className: 'btn-primary'
                }
            }
        }).then((willUpdate) => {
            if (willUpdate) {
                document.getElementById('form').submit();
            }
        });
    });
});