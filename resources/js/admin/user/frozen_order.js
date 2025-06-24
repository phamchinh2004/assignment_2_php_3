document.addEventListener('DOMContentLoaded', function () {
    const btn_submit = document.getElementById('btn_submit');
    const form = document.getElementById('form');
    btn_submit.addEventListener('click', function () {
        swal({
            title: "Xác nhận",
            text: "Bạn đã chắc chắn chưa?",
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