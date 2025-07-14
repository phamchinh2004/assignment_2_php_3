<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class BlockBots
{
    protected $blockedUserAgents = [
        'Googlebot',
        'Bingbot',
        'YandexBot',
        'DuckDuckBot',
        'Baiduspider',
        'curl',
        'wget',
        'python',
        'crawler',
        'scanner',
        'virustotal',
        'PhishTank',
        'MJ12bot',
        'CensysInspect',
        'Applebot',
    ];


    public function handle(Request $request, Closure $next)
    {
        $userAgent = strtolower($request->header('User-Agent'));

        foreach ($this->blockedUserAgents as $bot) {
            if (strpos($userAgent, strtolower($bot)) !== false) {
                \Log::warning('Bot bị chặn', [
                    'ip' => $request->ip(),
                    'user_agent' => $userAgent,
                    'url' => $request->fullUrl(),
                ]);
                abort(403, 'Access denied');
            }
        }

        return $next($request);
    }
}
