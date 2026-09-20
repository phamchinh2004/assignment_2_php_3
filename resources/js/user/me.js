window.thong_bao_lien_he_cskh = function () {
    notification('warning', trans.VuiLongLienHeCskh, trans.ThongBao, 5000);
};

window.toggleLanguageDropdown = function () {
    const dropdown = document.getElementById('languageDropdown');
    const dropdownButton = document.getElementById('languageDropdownButton');
    if (!dropdown) return;

    dropdown.hidden = !dropdown.hidden;
    dropdownButton?.setAttribute('aria-expanded', String(!dropdown.hidden));
};

const closeLanguageDropdown = () => {
    const dropdown = document.getElementById('languageDropdown');
    const dropdownButton = document.getElementById('languageDropdownButton');
    if (!dropdown) return;

    dropdown.hidden = true;
    dropdownButton?.setAttribute('aria-expanded', 'false');
};

document.addEventListener('click', function (event) {
    const setting = document.querySelector('.language-setting');
    if (setting && !setting.contains(event.target)) closeLanguageDropdown();
});

document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') {
        closeLanguageDropdown();
    }
});
