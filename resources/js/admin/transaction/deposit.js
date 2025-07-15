document.addEventListener('DOMContentLoaded', function () {
    const btn_submit = document.getElementById('btn_submit');
    const form = document.getElementById('form');
    btn_submit.addEventListener('click', function () {
        swal({
            title: "VUI LÒNG ĐỌC",
            text: "Lưu ý: xóa giao dịch sẽ trừ thẳng vào tài khoản của người dùng!",
            icon: "warning",
            buttons: true,
            dangerMode: true,
        })
            .then((isConfirmed) => {
                if (isConfirmed) {
                    form.submit();
                }
            });
    })
})