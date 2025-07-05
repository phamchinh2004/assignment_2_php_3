tinymce.init({
    selector: '#sectionContent',
    plugins: 'anchor autolink charmap codesample emoticons link lists media searchreplace table visualblocks wordcount',
    automatic_uploads: true,
    license_key: 'gpl'
});
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