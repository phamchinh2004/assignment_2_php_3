@extends('admin.layouts.master')
@section('title')
    Chỉnh sửa đơn hàng — {{ $order->order_code }}
@endsection

@section('style-libs')
    @vite(['resources/css/admin/common-modern.css', 'resources/css/admin/order/edit.css'])
@endsection

@section('script-libs')
    @vite('resources/js/admin/order/edit.js')
    <script>
        window.currentPermissionCode = "quan_ly_don_hang";
    </script>
@endsection

@section('content')
<div class="container-fluid px-4 pb-5">

    {{-- Back link --}}
    <a href="{{ route('order.index') }}" class="btn-back-modern mb-3 d-inline-flex">
        <i class="fas fa-arrow-left"></i> Quay lại danh sách đơn hàng
    </a>

    {{-- Page card --}}
    <div class="card shadow border-0 rounded-lg" style="overflow:hidden;">

        {{-- Header --}}
        <div class="edit-page-header card-header">
            <div class="edit-page-title">
                <span class="page-icon"><i class="fas fa-edit"></i></span>
                Chỉnh sửa đơn hàng
                <span class="order-code-badge">{{ $order->order_code }}</span>
            </div>
            <div style="display:flex;align-items:center;gap:8px;font-size:12px;color:#718096;">
                @if($order->status == 1)
                    <span style="display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:20px;background:#c6f6d5;color:#22543d;font-weight:700;font-size:11px;">
                        <span style="width:6px;height:6px;border-radius:50%;background:#38a169;display:inline-block;"></span> Đang hoạt động
                    </span>
                @else
                    <span style="display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:20px;background:#fed7d7;color:#742a2a;font-weight:700;font-size:11px;">
                        <span style="width:6px;height:6px;border-radius:50%;background:#e53e3e;display:inline-block;"></span> Ngừng hoạt động
                    </span>
                @endif
                <span>Tạo: <b>{{ $order->created_at->format('d/m/Y H:i') }}</b></span>
            </div>
        </div>

        {{-- Form --}}
        <form id="form" action="{{ route('order.update', ['order' => $order->id]) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="card-body p-4" style="background:#f8fafc;">
                <div class="row" style="gap:0;">

                    {{-- ===== LEFT COLUMN ===== --}}
                    <div class="col-12 col-lg-6 pr-lg-3">

                        {{-- Section: Thông tin sản phẩm --}}
                        <div class="edit-section">
                            <div class="edit-section-header">
                                <i class="fas fa-box"></i> Thông tin sản phẩm
                            </div>
                            <div class="edit-section-body">
                                <div class="form-grid-1">

                                    {{-- Image preview + upload --}}
                                    <div class="edit-form-group">
                                        <label class="edit-label">Hình ảnh sản phẩm</label>
                                        <div class="img-preview-wrap" id="imgPreviewWrap">
                                            <img src="{{ Storage::url($order->image) }}" alt="Order image" id="imgPreview" />
                                        </div>
                                    </div>
                                    <div class="edit-form-group">
                                        <label class="edit-label">Thay hình ảnh</label>
                                        <input type="file" accept="image/*" name="image" id="imageInput"
                                               class="edit-control @error('image') is-invalid @enderror"
                                               style="padding:5px 10px;cursor:pointer;">
                                        @error('image')
                                            <p class="edit-error">{{ $message }}</p>
                                        @enderror
                                        <p class="edit-hint">Để trống nếu không muốn thay ảnh.</p>
                                    </div>

                                    <div class="form-grid-2">
                                        {{-- Name --}}
                                        <div class="edit-form-group form-span-2">
                                            <label class="edit-label">Tên đơn hàng</label>
                                            <input type="text" name="name"
                                                   value="{{ old('name', $order->name) }}"
                                                   class="edit-control @error('name') is-invalid @enderror"
                                                   placeholder="Tên đơn hàng">
                                            @error('name')
                                                <p class="edit-error">{{ $message }}</p>
                                            @enderror
                                        </div>

                                        {{-- Order code --}}
                                        <div class="edit-form-group form-span-2">
                                            <label class="edit-label">Mã đơn hàng</label>
                                            <input type="text" name="order_code"
                                                   value="{{ old('order_code', $order->order_code) }}"
                                                   class="edit-control @error('order_code') is-invalid @enderror"
                                                   style="font-family:'Courier New',monospace;font-size:12px;"
                                                   placeholder="ORDER CODE">
                                            @error('order_code')
                                                <p class="edit-error">{{ $message }}</p>
                                            @enderror
                                        </div>

                                        {{-- Price --}}
                                        <div class="edit-form-group">
                                            <label class="edit-label">Giá ($)</label>
                                            <input type="number" name="price" step="0.01" min="0"
                                                   value="{{ old('price', $order->price) }}"
                                                   class="edit-control @error('price') is-invalid @enderror"
                                                   placeholder="0.00">
                                            @error('price')
                                                <p class="edit-error">{{ $message }}</p>
                                            @enderror
                                        </div>

                                        {{-- Quantity --}}
                                        <div class="edit-form-group">
                                            <label class="edit-label">Số lượng</label>
                                            <input type="number" name="quantity" min="1"
                                                   value="{{ old('quantity', $order->quantity) }}"
                                                   class="edit-control @error('quantity') is-invalid @enderror"
                                                   placeholder="1">
                                            @error('quantity')
                                                <p class="edit-error">{{ $message }}</p>
                                            @enderror
                                        </div>
                                    </div>

                                    {{-- Commission info (read-only display) --}}
                                    @if($order->commission_percentage !== null || $order->rank)
                                    <div style="display:flex;gap:10px;padding:10px 14px;background:#f0fff4;border-radius:8px;border:1px solid #c6f6d5;font-size:12px;align-items:center;">
                                        <i class="fas fa-percent" style="color:#38a169;"></i>
                                        <span style="color:#22543d;font-weight:600;">Hoa hồng: <b>{{ $order->commission_percentage ?? 0 }}%</b></span>
                                        @if($order->rank)
                                            <span style="color:#718096;">·</span>
                                            <span style="color:#4a5568;">Cấp độ: <b>{{ $order->rank->name }}</b></span>
                                        @endif
                                    </div>
                                    @endif

                                </div>
                            </div>
                        </div>

                    </div>

                    {{-- ===== RIGHT COLUMN ===== --}}
                    <div class="col-12 col-lg-6 pl-lg-3">

                        {{-- Section: Khách hàng --}}
                        <div class="edit-section">
                            <div class="edit-section-header">
                                <i class="fas fa-user"></i> Thông tin khách hàng
                            </div>
                            <div class="edit-section-body">
                                <div class="form-grid-1">
                                    {{-- Customer name --}}
                                    <div class="edit-form-group">
                                        <label class="edit-label"><i class="fas fa-user" style="font-size:9px;"></i> Họ tên khách hàng</label>
                                        <input type="text" name="customer_name"
                                               value="{{ old('customer_name', $order->customer_name) }}"
                                               class="edit-control @error('customer_name') is-invalid @enderror"
                                               placeholder="Họ và tên khách hàng">
                                        @error('customer_name')
                                            <p class="edit-error">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    {{-- Customer phone --}}
                                    <div class="edit-form-group">
                                        <label class="edit-label"><i class="fas fa-phone" style="font-size:9px;"></i> Số điện thoại</label>
                                        <input type="text" name="customer_phone"
                                               value="{{ old('customer_phone', $order->customer_phone) }}"
                                               class="edit-control @error('customer_phone') is-invalid @enderror"
                                               placeholder="+84 987654321">
                                        @error('customer_phone')
                                            <p class="edit-error">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    {{-- Customer address --}}
                                    <div class="edit-form-group">
                                        <label class="edit-label"><i class="fas fa-map-marker-alt" style="font-size:9px;"></i> Địa chỉ giao hàng</label>
                                        <textarea name="customer_address" rows="3"
                                                  class="edit-control @error('customer_address') is-invalid @enderror"
                                                  placeholder="Địa chỉ nhận hàng đầy đủ">{{ old('customer_address', $order->customer_address) }}</textarea>
                                        @error('customer_address')
                                            <p class="edit-error">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    {{-- Customer note --}}
                                    <div class="edit-form-group">
                                        <label class="edit-label"><i class="fas fa-sticky-note" style="font-size:9px;"></i> Ghi chú từ khách hàng</label>
                                        <textarea name="customer_note" rows="2"
                                                  class="edit-control @error('customer_note') is-invalid @enderror"
                                                  placeholder="Ghi chú giao hàng, yêu cầu đặc biệt...">{{ old('customer_note', $order->customer_note) }}</textarea>
                                        @error('customer_note')
                                            <p class="edit-error">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Section: Thanh toán & Nền tảng --}}
                        <div class="edit-section">
                            <div class="edit-section-header">
                                <i class="fas fa-credit-card"></i> Thanh toán & Nền tảng
                            </div>
                            <div class="edit-section-body">
                                <div class="form-grid-1">

                                    {{-- Partner --}}
                                    <div class="edit-form-group">
                                        <label class="edit-label"><i class="fas fa-store" style="font-size:9px;"></i> Nền tảng bán hàng</label>
                                        <select name="partner_id" class="edit-control @error('partner_id') is-invalid @enderror">
                                            <option value="">— Chọn nền tảng —</option>
                                            @foreach($partners as $partner)
                                                <option value="{{ $partner->id }}"
                                                    {{ old('partner_id', $order->partner_id) == $partner->id ? 'selected' : '' }}>
                                                    {{ $partner->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('partner_id')
                                            <p class="edit-error">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    {{-- Payment method --}}
                                    <div class="edit-form-group">
                                        <label class="edit-label"><i class="fas fa-wallet" style="font-size:9px;"></i> Hình thức thanh toán</label>
                                        <select name="payment_method" id="payment_method"
                                                class="edit-control @error('payment_method') is-invalid @enderror">
                                            <option value="">— Chọn hình thức —</option>
                                            <option value="COD"           {{ old('payment_method', $order->payment_method) == 'COD'           ? 'selected' : '' }}>💵 COD (Thanh toán khi nhận hàng)</option>
                                            <option value="vnpay"         {{ old('payment_method', $order->payment_method) == 'vnpay'         ? 'selected' : '' }}>🏦 VNPay</option>
                                            <option value="momo"          {{ old('payment_method', $order->payment_method) == 'momo'          ? 'selected' : '' }}>💜 MoMo</option>
                                            <option value="paypal"        {{ old('payment_method', $order->payment_method) == 'paypal'        ? 'selected' : '' }}>🔵 PayPal</option>
                                            <option value="bank_transfer" {{ old('payment_method', $order->payment_method) == 'bank_transfer' ? 'selected' : '' }}>🏛 Chuyển khoản ngân hàng</option>
                                            <option value="other"         {{ old('payment_method', $order->payment_method) == 'other'         ? 'selected' : '' }}>📦 Khác</option>
                                        </select>
                                        @error('payment_method')
                                            <p class="edit-error">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    {{-- Is paid (checkbox toggle) --}}
                                    <div class="edit-form-group">
                                        <label class="edit-label"><i class="fas fa-check-circle" style="font-size:9px;"></i> Trạng thái thanh toán</label>
                                        <label class="toggle-wrap" for="is_paid" id="is_paid_wrap">
                                            <input type="checkbox" name="is_paid" value="1" id="is_paid"
                                                   {{ old('is_paid', $order->is_paid) ? 'checked' : '' }}>
                                            <span>
                                                Đã thanh toán
                                                <span class="toggle-desc" id="is_paid_hint">
                                                    @if(old('payment_method', $order->payment_method) === 'COD')
                                                        (COD — không thể đánh dấu đã thanh toán)
                                                    @endif
                                                </span>
                                            </span>
                                        </label>
                                        @error('is_paid')
                                            <p class="edit-error">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    {{-- API string --}}
                                    <div class="edit-form-group">
                                        <label class="edit-label"><i class="fas fa-code" style="font-size:9px;"></i> API Tracking String</label>
                                        <div class="api-input-wrap">
                                            <input type="text" name="api"
                                                   value="{{ old('api', $order->api) }}"
                                                   class="edit-control @error('api') is-invalid @enderror"
                                                   placeholder="Chuỗi API để theo dõi đơn hàng">
                                            <span class="api-badge">API</span>
                                        </div>
                                        @error('api')
                                            <p class="edit-error">{{ $message }}</p>
                                        @enderror
                                    </div>

                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            {{-- Sticky submit bar --}}
            <div class="submit-bar">
                <a href="{{ route('order.index') }}" class="btn-cancel">
                    <i class="fas fa-times"></i> Hủy bỏ
                </a>
                <button type="button" class="btn-save" id="btn_submit">
                    <i class="fas fa-save"></i> Lưu thay đổi
                </button>
            </div>

        </form>
    </div>
</div>

<script>
// Preview image before upload
document.getElementById('imageInput') && document.getElementById('imageInput').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(evt) {
            document.getElementById('imgPreview').src = evt.target.result;
        };
        reader.readAsDataURL(file);
    }
});

// COD logic: disable is_paid when COD is selected
const paymentMethodSel = document.getElementById('payment_method');
const isPaidChk = document.getElementById('is_paid');
const isPaidWrap = document.getElementById('is_paid_wrap');
const isPaidHint = document.getElementById('is_paid_hint');

function updateCODState() {
    if (!paymentMethodSel || !isPaidChk) return;
    if (paymentMethodSel.value === 'COD') {
        isPaidChk.checked = false;
        isPaidChk.disabled = true;
        isPaidWrap && (isPaidWrap.style.opacity = '0.55');
        isPaidHint && (isPaidHint.textContent = '(COD — không thể đánh dấu đã thanh toán)');
    } else {
        isPaidChk.disabled = false;
        isPaidWrap && (isPaidWrap.style.opacity = '1');
        isPaidHint && (isPaidHint.textContent = '');
    }
}
paymentMethodSel && paymentMethodSel.addEventListener('change', updateCODState);
updateCODState();
</script>
@endsection