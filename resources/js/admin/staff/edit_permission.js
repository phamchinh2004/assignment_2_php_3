document.addEventListener('DOMContentLoaded', function () {
    document.getElementById('list_permissions').addEventListener('click', async function (e) {
        if (e.target.classList.contains('change_status_permission')) {
            let id = e.target.dataset.id;
            spinner.hidden = false;
            let result = await change_status_permission(id);
            if (result.status == 400) {
                notification('error', result.message, 'Lỗi');
            } else if (result.status == 200) {
                notification('success', result.message, 'Thành công!');
                if (e.target.classList.contains('fa-toggle-on')) {
                    e.target.classList.remove('fa-toggle-on');
                    e.target.classList.add('fa-toggle-off');
                } else if (e.target.classList.contains('fa-toggle-off')) {
                    e.target.classList.remove('fa-toggle-off');
                    e.target.classList.add('fa-toggle-on');
                }
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