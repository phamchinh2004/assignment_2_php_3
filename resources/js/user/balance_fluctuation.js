window.addEventListener('DOMContentLoaded', function () {
    const params = new URLSearchParams(window.location.search);
    const tab = params.get('tab');

    if (tab === 'withdraw') {
        activeTab('btn_withdraw');
    } else if (tab === 'deposit') {
        activeTab('btn_deposit');
    } else if (tab === 'distribution') {
        activeTab('btn_distribution');
    }
});

function activeTab(btnId) {
    const buttons = document.querySelectorAll('.btn_tittle');
    buttons.forEach(btn => btn.classList.remove('active-tab'));

    const activeBtn = document.getElementById(btnId);
    if (activeBtn) {
        activeBtn.classList.add('active-tab');

        loadDanhSachTheoTab(btnId);
    }
}