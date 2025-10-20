window.thong_bao_lien_he_cskh = function () {
    notification('warning', trans.VuiLongLienHeCskh, trans.ThongBao, 5000);
};

window.toggleLanguageDropdown = function () {
    const dropdown = document.getElementById('languageDropdown');
    if (!dropdown) return;

    // Toggle dropdown visibility
    dropdown.classList.toggle('show');
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function(event) {
        const dropdownButton = document.getElementById('languageDropdownButton');
        const dropdownMenu = document.getElementById('languageDropdown');
        
        if (!dropdownButton.contains(event.target) && !dropdownMenu.contains(event.target)) {
            dropdownMenu.classList.remove('show');
        }
    });
};

// Close dropdown on escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        const dropdown = document.getElementById('languageDropdown');
        if (dropdown) {
            dropdown.classList.remove('show');
        }
    }
});
