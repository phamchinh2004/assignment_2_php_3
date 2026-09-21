document.addEventListener('DOMContentLoaded', function () {
    const selectAll = document.getElementById('select_all_permissions');
    const permissionCheckboxes = Array.from(document.querySelectorAll('.permission-item-select'));
    const selectedCount = document.getElementById('selected_permission_count');

    function syncSelectionState() {
        const checkedCount = permissionCheckboxes.filter(checkbox => checkbox.checked).length;

        selectedCount.textContent = `${checkedCount} quyền đã chọn`;
        selectAll.checked = permissionCheckboxes.length > 0 && checkedCount === permissionCheckboxes.length;
        selectAll.indeterminate = checkedCount > 0 && checkedCount < permissionCheckboxes.length;
    }

    selectAll.addEventListener('change', function () {
        permissionCheckboxes.forEach(checkbox => {
            checkbox.checked = selectAll.checked;
        });
        syncSelectionState();
    });

    permissionCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', syncSelectionState);
    });

    syncSelectionState();

    document.getElementById('list_permissions').addEventListener('click', async function (e) {
        if (e.target.classList.contains('change_status_permission')) {
            let id = e.target.dataset.id;
            spinner.hidden = false;
            let result = await change_status_permission(id);
            if (result.status == 400) {
                notification('error', result.message, 'Lỗi');
            } else if (result.status == 200) {
                notification('success', result.message, 'Thành công!');
                const isActive = Boolean(result.is_active);
                e.target.classList.toggle('fa-toggle-on', isActive);
                e.target.classList.toggle('fa-toggle-off', !isActive);
            }
            spinner.hidden = true;
        }
    })
    function change_status_permission(id) {
        return new Promise((resolve, reject) => {
            fetch(route_change_status_permission, {
                method: "POST",
                headers: {
                    'Content-Type': "application/json",
                    'X-CSRF-TOKEN': csrf
                },
                body: JSON.stringify({
                    id: id
                })
            })
                .then(response => response.json())
                .then(data => {
                    return resolve(data);
                })
                .catch(error => {
                    console.log(error);
                    reject(error);
                });
        })
    }
})
