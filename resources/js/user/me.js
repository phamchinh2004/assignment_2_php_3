window.thong_bao_lien_he_cskh = function () {
    notification('warning', trans.VuiLongLienHeCskh, trans.ThongBao, 5000);
};

window.toggleLanguageDropdown = function () {
    const dropdown = document.getElementById('languageDropdown');
    if (!dropdown) return;

    dropdown.classList.toggle('show'); // Bootstrap hiển thị dropdown bằng class .show
};
