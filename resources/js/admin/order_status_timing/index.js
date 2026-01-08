// Order Status Timing Management

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('timingForm');
    
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(form);
            const data = {};
            
            // Convert FormData to object
            for (let [key, value] of formData.entries()) {
                const keys = key.match(/timings\[(\d+)\]\[(\w+)\]/);
                if (keys) {
                    const id = keys[1];
                    const field = keys[2];
                    if (!data[id]) {
                        data[id] = {};
                    }
                    data[id][field] = value;
                }
            }
            
            // Convert to array format
            const timings = Object.keys(data).map(id => ({
                id: id,
                ...data[id],
                is_active: data[id].is_active === 'on' ? true : false
            }));
            
            // Send AJAX request
            fetch(form.action, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ timings: timings })
            })
            .then(response => response.json())
            .then(result => {
                if (result.status === 200) {
                    alert('Cập nhật thành công!');
                    window.location.reload();
                } else {
                    alert('Có lỗi xảy ra: ' + result.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Có lỗi xảy ra khi cập nhật!');
            });
        });
    }
    
    // Validate min_time <= max_time
    const minTimeInputs = document.querySelectorAll('input[name*="[min_time]"]');
    const maxTimeInputs = document.querySelectorAll('input[name*="[max_time]"]');
    
    minTimeInputs.forEach((minInput, index) => {
        minInput.addEventListener('change', function() {
            const maxInput = maxTimeInputs[index];
            if (parseInt(this.value) > parseInt(maxInput.value)) {
                maxInput.value = this.value;
            }
        });
    });
    
    maxTimeInputs.forEach((maxInput, index) => {
        maxInput.addEventListener('change', function() {
            const minInput = minTimeInputs[index];
            if (parseInt(this.value) < parseInt(minInput.value)) {
                this.value = minInput.value;
            }
        });
    });
});

