// Order Detail Page JavaScript
console.log('Order detail JS file loaded');

// Helper để lấy biến global với fallback
function getGlobalVar(name, defaultValue = null) {
    if (typeof window !== 'undefined' && typeof window[name] !== 'undefined') {
        return window[name];
    }
    return defaultValue;
}

// Lấy config từ DOM (được render từ Blade)
function getOrderDetailConfig() {
    try {
        const el = document.getElementById('order-detail-config');
        if (!el) return {};
        const raw = el.getAttribute('data-config') || '{}';
        return JSON.parse(raw);
    } catch (e) {
        console.warn('Không thể parse order detail config:', e);
        return {};
    }
}

const pageConfig = getOrderDetailConfig();

// Đảm bảo các biến được truy cập đúng cách
const trans = getGlobalVar('trans', pageConfig?.trans || {});
const route_confirm_order = getGlobalVar('route_confirm_order', pageConfig?.routes?.confirm || '');
const route_cancel_order = getGlobalVar('route_cancel_order', pageConfig?.routes?.cancel || '');
const route_report_order = getGlobalVar('route_report_order', pageConfig?.routes?.report || '');
const route_order = getGlobalVar('route_order', pageConfig?.routes?.order || '');
const csrf = getGlobalVar('csrf', pageConfig?.csrf || document.querySelector('meta[name="csrf-token"]')?.content || '');

// Expose lại lên window để các đoạn debug/khác có thể dùng
if (typeof window !== 'undefined') {
    window.trans = trans;
    window.route_confirm_order = route_confirm_order;
    window.route_cancel_order = route_cancel_order;
    window.route_report_order = route_report_order;
    window.route_order = route_order;
    window.csrf = csrf;
}

console.log('Variables loaded:', {
    trans,
    route_confirm_order,
    route_cancel_order,
    route_report_order,
    route_order,
    csrf: csrf ? 'CSRF token exists' : 'No CSRF token'
});

document.addEventListener('DOMContentLoaded', function() {
    console.log('Order detail script loaded');
    
    const btnConfirm = document.getElementById('btn_confirm_order');
    const btnCancel = document.getElementById('btn_cancel_order');
    const btnReport = document.getElementById('btn_report_fake_order');
    const spinner = document.getElementById('spinner');

    console.log('btnConfirm:', btnConfirm);
    console.log('btnCancel:', btnCancel);
    console.log('btnReport:', btnReport);
    console.log('route_confirm_order:', route_confirm_order);
    console.log('csrf:', csrf);

    // Xử lý xác nhận đơn hàng
    // Sử dụng biến flag để tránh double click
    let isConfirming = false;
    
    if (btnConfirm) {
        console.log('Attaching click event to confirm button');
        btnConfirm.addEventListener('click', async function(e) {
            console.log('Confirm button clicked!', e);
            e.preventDefault();
            e.stopPropagation();
            
            // Ngăn chặn double click
            if (isConfirming) {
                console.log('Already processing confirmation, ignoring click');
                return;
            }
            
            const confirmMessage = trans.XacNhanDonHang ? 
                `Bạn có chắc chắn muốn ${trans.XacNhanDonHang.toLowerCase()} này?` : 
                'Bạn có chắc chắn muốn xác nhận đơn hàng này?';
            
            if (!confirm(confirmMessage)) {
                return;
            }

            // Đánh dấu đang xử lý
            isConfirming = true;
            btnConfirm.disabled = true;
            const originalText = btnConfirm.innerHTML;
            btnConfirm.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Đang xử lý...';
            
            // Hiển thị spinner
            if (spinner) {
                spinner.hidden = false;
            }

            try {
                if (!route_confirm_order) {
                    throw new Error('Route không được định nghĩa');
                }

                const response = await fetch(route_confirm_order, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json'
                    }
                });

                const result = await response.json();

                // Chỉ kiểm tra result.status, không dùng response.ok để tránh nhầm lẫn
                // Đảm bảo result có status và status === 200 mới hiển thị thành công
                if (result && result.status === 200) {
                    // Ẩn spinner
                    if (spinner) {
                        spinner.hidden = true;
                    }
                    
                    // Hiển thị thông báo thành công
                    const successMessage = result.message || 'Đơn hàng đã được xác nhận thành công!';
                    const successTitle = trans.ThanhCong || 'Thành công';
                    
                    if (typeof notification !== 'undefined') {
                        notification('success', successMessage, successTitle);
                    } else {
                        alert(successMessage);
                    }

                    // Reload trang sau 1.5 giây
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    // Ẩn spinner
                    if (spinner) {
                        spinner.hidden = true;
                    }
                    
                    const errorMessage = result.message || 'Có lỗi xảy ra khi xác nhận đơn hàng';
                    const errorTitle = trans.Loi || 'Lỗi';
                    
                    if (typeof notification !== 'undefined') {
                        notification('error', errorMessage, errorTitle);
                    } else {
                        alert(errorMessage);
                    }
                    isConfirming = false;
                    btnConfirm.disabled = false;
                    btnConfirm.innerHTML = originalText;
                }
            } catch (error) {
                console.error('Error:', error);
                
                // Ẩn spinner
                if (spinner) {
                    spinner.hidden = true;
                }
                
                const errorMessage = 'Có lỗi xảy ra khi xác nhận đơn hàng. Vui lòng thử lại!';
                const errorTitle = trans.Loi || 'Lỗi';
                
                if (typeof notification !== 'undefined') {
                    notification('error', errorMessage, errorTitle);
                } else {
                    alert(errorMessage);
                }
                isConfirming = false;
                btnConfirm.disabled = false;
                btnConfirm.innerHTML = originalText;
            }
        });
    }

    // Xử lý nút Liên hệ CSKH (cho đơn đặc biệt)
    const btnContactCSKH = document.getElementById('btn_contact_cskh');
    if (btnContactCSKH) {
        console.log('Attaching click event to contact CSKH button');
        btnContactCSKH.addEventListener('click', function(e) {
            console.log('Contact CSKH button clicked!', e);
            e.preventDefault();
            e.stopPropagation();
            
            // Tìm Livewire component và gọi toggleBox để mở chat
            const chatRoot = document.getElementById('chat-root');
            if (chatRoot && typeof Livewire !== 'undefined') {
                const wireId = chatRoot.getAttribute('wire:id');
                if (wireId) {
                    try {
                        const component = Livewire.find(wireId);
                        if (component) {
                            component.call('toggleBox');
                            console.log('Chat box opened successfully');
                        } else {
                            // Nếu không tìm thấy component, thử dispatch event
                            Livewire.dispatch('toggleChatBox');
                        }
                    } catch (error) {
                        console.error('Error opening chat:', error);
                        // Fallback: Dispatch event
                        if (typeof Livewire !== 'undefined') {
                            Livewire.dispatch('toggleChatBox');
                        } else {
                            alert('Vui lòng liên hệ CSKH qua chat box ở góc dưới màn hình.');
                        }
                    }
                } else {
                    alert('Vui lòng liên hệ CSKH qua chat box ở góc dưới màn hình.');
                }
            } else {
                alert('Vui lòng liên hệ CSKH qua chat box ở góc dưới màn hình.');
            }
        });
    }

    // Xử lý hủy đơn hàng
    if (btnCancel) {
        console.log('Attaching click event to cancel button');
        btnCancel.addEventListener('click', async function(e) {
            console.log('Cancel button clicked!', e);
            e.preventDefault();
            e.stopPropagation();
            const cancelMessage = trans.HuyDonHang ? 
                `Bạn có chắc chắn muốn ${trans.HuyDonHang.toLowerCase()} này? Hành động này không thể hoàn tác.` : 
                'Bạn có chắc chắn muốn hủy đơn hàng này? Hành động này không thể hoàn tác.';
            
            if (!confirm(cancelMessage)) {
                return;
            }

            btnCancel.disabled = true;
            const originalText = btnCancel.innerHTML;
            btnCancel.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Đang xử lý...';
            
            // Hiển thị spinner
            if (spinner) {
                spinner.hidden = false;
            }

            try {
                if (!route_cancel_order) {
                    throw new Error('Route không được định nghĩa');
                }

                const response = await fetch(route_cancel_order, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json'
                    }
                });

                const result = await response.json();

                if (result.status === 200 || response.ok) {
                    // Ẩn spinner
                    if (spinner) {
                        spinner.hidden = true;
                    }
                    
                    // Hiển thị thông báo thành công
                    const successMessage = result.message || 'Đơn hàng đã được hủy thành công!';
                    const successTitle = trans.ThanhCong || 'Thành công';
                    
                    if (typeof notification !== 'undefined') {
                        notification('success', successMessage, successTitle);
                    } else {
                        alert(successMessage);
                    }

                    // Redirect về trang danh sách đơn hàng sau 1.5 giây
                    setTimeout(() => {
                        if (route_order) {
                            window.location.href = route_order;
                        } else {
                            window.location.reload();
                        }
                    }, 1500);
                } else {
                    // Ẩn spinner
                    if (spinner) {
                        spinner.hidden = true;
                    }
                    
                    const errorMessage = result.message || 'Có lỗi xảy ra khi hủy đơn hàng';
                    const errorTitle = trans.Loi || 'Lỗi';
                    
                    if (typeof notification !== 'undefined') {
                        notification('error', errorMessage, errorTitle);
                    } else {
                        alert(errorMessage);
                    }
                    btnCancel.disabled = false;
                    btnCancel.innerHTML = originalText;
                }
            } catch (error) {
                console.error('Error:', error);
                
                // Ẩn spinner
                if (spinner) {
                    spinner.hidden = true;
                }
                
                const errorMessage = 'Có lỗi xảy ra khi hủy đơn hàng. Vui lòng thử lại!';
                const errorTitle = trans.Loi || 'Lỗi';
                
                if (typeof notification !== 'undefined') {
                    notification('error', errorMessage, errorTitle);
                } else {
                    alert(errorMessage);
                }
                btnCancel.disabled = false;
                btnCancel.innerHTML = originalText;
            }
        });
    }

    // Xử lý báo cáo đơn hàng
    let isReporting = false;
    if (btnReport) {
        console.log('Attaching click event to report button');
        btnReport.addEventListener('click', async function(e) {
            console.log('Report button clicked!', e);
            e.preventDefault();
            e.stopPropagation();

            if (btnReport.disabled) {
                return;
            }

            // Ngăn double click
            if (isReporting) {
                return;
            }

            const confirmMessage =
                'Bạn có chắc chắn muốn báo cáo đơn hàng? Đơn hàng sẽ được chuyển sang admin để kiểm tra.';

            if (!confirm(confirmMessage)) {
                return;
            }

            const reason = prompt('Nhập lý do báo cáo (không bắt buộc):', '') ?? '';

            isReporting = true;
            btnReport.disabled = true;
            const originalText = btnReport.innerHTML;
            btnReport.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Đang gửi báo cáo...';

            if (spinner) {
                spinner.hidden = false;
            }

            try {
                if (!route_report_order) {
                    throw new Error('Route không được định nghĩa');
                }

                const response = await fetch(route_report_order, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ reason })
                });

                const result = await response.json();

                if (result && result.status === 200) {
                    if (spinner) {
                        spinner.hidden = true;
                    }

                    const successMessage = result.message || 'Đã gửi báo cáo đơn hàng.';
                    const successTitle = trans.ThanhCong || 'Thành công';
                    if (typeof notification !== 'undefined') {
                        notification('success', successMessage, successTitle);
                    } else {
                        alert(successMessage);
                    }

                    setTimeout(() => {
                        // Reload để cập nhật trạng thái nút "đã báo cáo"
                        window.location.reload();
                    }, 1200);
                } else {
                    if (spinner) {
                        spinner.hidden = true;
                    }

                    const errorMessage = (result && result.message) ? result.message : 'Có lỗi xảy ra khi gửi báo cáo';
                    const errorTitle = trans.Loi || 'Lỗi';
                    if (typeof notification !== 'undefined') {
                        notification('error', errorMessage, errorTitle);
                    } else {
                        alert(errorMessage);
                    }

                    isReporting = false;
                    btnReport.disabled = false;
                    btnReport.innerHTML = originalText;
                }
            } catch (error) {
                console.error('Error:', error);

                if (spinner) {
                    spinner.hidden = true;
                }

                const errorMessage = 'Có lỗi xảy ra khi gửi báo cáo. Vui lòng thử lại!';
                const errorTitle = trans.Loi || 'Lỗi';
                if (typeof notification !== 'undefined') {
                    notification('error', errorMessage, errorTitle);
                } else {
                    alert(errorMessage);
                }

                isReporting = false;
                btnReport.disabled = false;
                btnReport.innerHTML = originalText;
            }
        });
    }
});

