<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class VerifyTurnstile
{
    public function handle(Request $request, Closure $next, ?string $expectedAction = null): Response
    {
        if (! config('services.turnstile.enabled')) {
            return $next($request);
        }

        $token = $request->input('cf-turnstile-response');

        if (! is_string($token) || $token === '' || strlen($token) > 2048) {
            return $this->reject($request, 'Vui lòng hoàn tất xác minh bảo mật.');
        }

        $secretKey = config('services.turnstile.secret_key');

        if (! is_string($secretKey) || $secretKey === '') {
            report(new \RuntimeException('Cloudflare Turnstile is enabled but TURNSTILE_SECRET_KEY is missing.'));

            return $this->reject($request, 'Không thể xác minh bảo mật lúc này. Vui lòng thử lại.');
        }

        try {
            $response = Http::asForm()
                ->connectTimeout(3)
                ->timeout(5)
                ->post(config('services.turnstile.verify_url'), [
                    'secret' => $secretKey,
                    'response' => $token,
                ]);
        } catch (Throwable $exception) {
            report($exception);

            return $this->reject($request, 'Không thể xác minh bảo mật lúc này. Vui lòng thử lại.');
        }

        if (! $response->successful()) {
            return $this->reject($request, 'Không thể xác minh bảo mật lúc này. Vui lòng thử lại.');
        }

        $result = $response->json();

        if (! is_array($result) || ! ($result['success'] ?? false)) {
            return $this->reject($request, 'Xác minh bảo mật không hợp lệ hoặc đã hết hạn. Vui lòng thử lại.');
        }

        if ($expectedAction !== null && ($result['action'] ?? null) !== $expectedAction) {
            return $this->reject($request, 'Xác minh bảo mật không hợp lệ. Vui lòng thử lại.');
        }

        return $next($request);
    }

    private function reject(Request $request, string $message): Response
    {
        return redirect()
            ->back()
            ->withInput($request->except(['password', 'repassword', 'cf-turnstile-response']))
            ->withErrors(['cf-turnstile-response' => $message]);
    }
}
