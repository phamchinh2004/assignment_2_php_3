<?php

namespace App\Services;

use Illuminate\Http\JsonResponse;
use Illuminate\Contracts\View\View;

class ReactPageService
{
    public function admin(string $page, array $props = [], ?string $title = null): View|JsonResponse
    {
        return $this->render('admin', 'react.admin', $page, $props, $title);
    }

    public function guest(string $page, array $props = [], ?string $title = null): View|JsonResponse
    {
        return $this->render('guest', 'react.guest', $page, array_merge([
            'csrf' => csrf_token(),
            'legalRoutes' => [
                'about' => route('about'),
                'contact' => route('contact'),
                'privacy' => route('privacy'),
                'terms' => route('terms'),
                'paymentRefund' => route('payment_refund_policy'),
            ],
            'turnstile' => [
                'enabled' => (bool) config('services.turnstile.enabled'),
                'siteKey' => config('services.turnstile.site_key'),
            ],
        ], $props), $title);
    }

    public function user(string $page, array $props = [], ?string $title = null): View|JsonResponse
    {
        return $this->render('user', 'react.user', $page, array_merge([
            'csrf' => csrf_token(),
        ], $props), $title);
    }

    private function render(string $surface, string $view, string $page, array $props, ?string $title): View|JsonResponse
    {
        $bootstrap = $this->bootstrap($surface, $page, $props, $title);

        if (request()->header('X-React-Navigation') === '1') {
            return response()
                ->json($bootstrap)
                ->header('Vary', 'X-React-Navigation');
        }

        return view($view, [
            'reactPageBootstrap' => $bootstrap,
            'title' => $title,
        ]);
    }

    private function bootstrap(string $surface, string $page, array $props, ?string $title): array
    {
        $errorBag = session('errors');

        return [
            'surface' => $surface,
            'page' => $page,
            'title' => $title ?? config('app.name'),
            'props' => array_merge([
                'form' => [
                    'old' => session()->getOldInput(),
                    'errors' => $errorBag ? $errorBag->getBag('default')->toArray() : [],
                ],
                'flash' => [
                    'success' => session('success'),
                    'error' => session('error'),
                    'warning' => session('warning'),
                ],
            ], $props),
        ];
    }
}
