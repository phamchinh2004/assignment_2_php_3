<div class="absolute-spinner" id="spinner" role="status" aria-live="polite" aria-label="Đang xử lý" hidden>
    <div class="app-loader">
        <div
            class="app-loader-visual app-loader-lottie"
            data-loading-animation
            data-animation-path="{{ asset('json/tiktok_loading.json') }}"
            aria-hidden="true"
        ></div>
        <div class="app-loader-content">
            <strong>Đang xử lý</strong>
            <span>Vui lòng chờ trong giây lát</span>
        </div>
        <span class="app-loader-dots" aria-hidden="true"><i></i><i></i><i></i></span>
    </div>
</div>
