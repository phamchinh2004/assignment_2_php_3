document.addEventListener('DOMContentLoaded', function () {
    const list = document.getElementById('list_permissions');
    const form = document.getElementById('permission_bulk_form');
    const search = document.getElementById('permission_search');
    const searchEmpty = document.getElementById('permission_search_empty');
    const selectedCount = document.getElementById('selected_permission_count');
    const selectAllButton = document.getElementById('select_all_permissions');
    const deselectAllButton = document.getElementById('deselect_all_permissions');
    const itemCheckboxes = Array.from(document.querySelectorAll('.permission-item-select'));
    const moduleCheckboxes = Array.from(document.querySelectorAll('.permission-module-select'));

    function itemsForModule(moduleKey) {
        return itemCheckboxes.filter((checkbox) => checkbox.dataset.module === moduleKey);
    }

    function syncModuleState(moduleCheckbox) {
        const children = itemsForModule(moduleCheckbox.dataset.module);
        const checkedCount = children.filter((checkbox) => checkbox.checked).length;
        moduleCheckbox.checked = children.length > 0 && checkedCount === children.length;
        moduleCheckbox.indeterminate = checkedCount > 0 && checkedCount < children.length;
    }

    function syncSelectionState() {
        const checkedCount = itemCheckboxes.filter((checkbox) => checkbox.checked).length;
        selectedCount.textContent = `${checkedCount} quyền đã chọn`;
        moduleCheckboxes.forEach(syncModuleState);
    }

    function setAll(checked) {
        itemCheckboxes.forEach((checkbox) => {
            checkbox.checked = checked;
        });
        syncSelectionState();
    }

    moduleCheckboxes.forEach((moduleCheckbox) => {
        moduleCheckbox.addEventListener('change', function () {
            itemsForModule(moduleCheckbox.dataset.module).forEach((checkbox) => {
                checkbox.checked = moduleCheckbox.checked;
            });
            syncSelectionState();
        });
    });

    itemCheckboxes.forEach((checkbox) => {
        checkbox.addEventListener('change', syncSelectionState);
    });

    selectAllButton?.addEventListener('click', () => setAll(true));
    deselectAllButton?.addEventListener('click', () => setAll(false));

    search?.addEventListener('input', function () {
        const query = search.value.trim().toLocaleLowerCase('vi');
        let visibleModules = 0;

        document.querySelectorAll('.permission-module').forEach((module) => {
            const moduleMatches = module.dataset.search.includes(query);
            let visiblePermissions = 0;

            module.querySelectorAll('.permission-card').forEach((card) => {
                const visible = query === '' || moduleMatches || card.dataset.search.includes(query);
                card.hidden = !visible;
                if (visible) {
                    visiblePermissions++;
                }
            });

            module.hidden = visiblePermissions === 0;
            if (!module.hidden) {
                visibleModules++;
            }
        });

        if (searchEmpty) {
            searchEmpty.hidden = visibleModules !== 0;
        }
    });

    form?.addEventListener('submit', function (event) {
        if (!itemCheckboxes.some((checkbox) => checkbox.checked)) {
            event.preventDefault();
            notification('error', 'Vui lòng chọn ít nhất một quyền.', 'Chưa chọn quyền');
        }
    });

    async function togglePermission(icon) {
        const id = icon.dataset.id;
        if (!id || icon.dataset.loading === '1') {
            return;
        }

        icon.dataset.loading = '1';
        if (typeof spinner !== 'undefined') {
            spinner.hidden = false;
        }

        try {
            const response = await fetch(route_change_status_permission, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ id }),
            });
            const result = await response.json();

            if (!response.ok || result.status !== 200) {
                notification('error', result.message || 'Không thể cập nhật quyền.', 'Lỗi');
                return;
            }

            const isActive = Boolean(result.is_active);
            icon.classList.toggle('fa-toggle-on', isActive);
            icon.classList.toggle('fa-toggle-off', !isActive);
            icon.title = isActive ? 'Thu hồi quyền' : 'Cấp quyền';
            notification('success', result.message, 'Thành công!');
        } catch (error) {
            console.error(error);
            notification('error', 'Không thể kết nối tới máy chủ.', 'Lỗi');
        } finally {
            icon.dataset.loading = '0';
            if (typeof spinner !== 'undefined') {
                spinner.hidden = true;
            }
        }
    }

    list?.addEventListener('click', function (event) {
        const icon = event.target.closest('.change_status_permission');
        if (icon) {
            togglePermission(icon);
        }
    });

    list?.addEventListener('keydown', function (event) {
        if (!['Enter', ' '].includes(event.key)) {
            return;
        }

        const icon = event.target.closest('.change_status_permission');
        if (icon) {
            event.preventDefault();
            togglePermission(icon);
        }
    });

    syncSelectionState();
});
