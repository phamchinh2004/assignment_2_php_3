<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class ApproximateLocationService
{
    public const REFRESH_MINUTES = 10;

    public function refresh(User $user, Request $request, bool $force = false): bool
    {
        if ($user->role !== User::ROLE_MEMBER) {
            return false;
        }

        if (!$force
            && filled($user->approx_location_country_code)
            && $user->approx_location_updated_at
            && $user->approx_location_updated_at->gt(now()->subMinutes(self::REFRESH_MINUTES))) {
            return true;
        }

        $location = $this->resolve($request);

        if (!$location) {
            return filled($user->approx_location_country_code);
        }

        $sameCountry = strtoupper((string) $user->approx_location_country_code) === $location['country_code'];

        $user->forceFill([
            'approx_location_country_code' => $location['country_code'],
            'approx_location_country' => $location['country']
                ?? ($sameCountry ? $user->approx_location_country : null),
            'approx_location_updated_at' => now(),
        ])->save();

        return true;
    }

    private function resolve(Request $request): ?array
    {
        foreach (['CF-IPCountry', 'CloudFront-Viewer-Country', 'X-Vercel-IP-Country'] as $header) {
            $countryCode = $this->normalizeCountryCode($request->header($header));
            if ($countryCode) {
                return [
                    'country_code' => $countryCode,
                    'country' => null,
                ];
            }
        }

        $ip = $request->ip();
        if (!$this->isPublicIp($ip)) {
            return null;
        }

        return Cache::remember(
            'approx-location:' . hash('sha256', $ip),
            now()->addMinutes(self::REFRESH_MINUTES),
            function () use ($ip) {
                try {
                    $response = Http::acceptJson()
                        ->connectTimeout(2)
                        ->timeout(4)
                        ->get('https://ipwho.is/' . rawurlencode($ip), [
                            'fields' => 'success,country,country_code',
                        ]);

                    if (!$response->successful()) {
                        return null;
                    }

                    $data = $response->json();
                    if (($data['success'] ?? true) === false) {
                        return null;
                    }

                    $countryCode = $this->normalizeCountryCode($data['country_code'] ?? null);
                    if (!$countryCode) {
                        return null;
                    }

                    $country = trim((string) ($data['country'] ?? ''));

                    return [
                        'country_code' => $countryCode,
                        'country' => $country !== '' ? $country : null,
                    ];
                } catch (\Throwable) {
                    return null;
                }
            }
        );
    }

    private function normalizeCountryCode(mixed $value): ?string
    {
        $countryCode = strtoupper(trim((string) $value));

        return preg_match('/^[A-Z]{2}$/', $countryCode) && !in_array($countryCode, ['XX', 'T1'], true)
            ? $countryCode
            : null;
    }

    private function isPublicIp(?string $ip): bool
    {
        return (bool) ($ip
            && filter_var($ip, FILTER_VALIDATE_IP)
            && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE));
    }
}
