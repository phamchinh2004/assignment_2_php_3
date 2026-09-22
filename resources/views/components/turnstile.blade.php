@props(['action'])

@if(config('services.turnstile.enabled'))
    <div class="auth-turnstile">
        <div
            class="cf-turnstile"
            data-sitekey="{{ config('services.turnstile.site_key') }}"
            data-action="{{ $action }}"
            data-theme="dark"
            data-size="flexible"
        ></div>
    </div>
    @error('cf-turnstile-response')
        <span class="invalid-feedback d-block text-center mt-2">
            <strong>{{ $message }}</strong>
        </span>
    @enderror

    @once
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    @endonce
@endif
