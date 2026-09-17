<?php

use App\Models\User;
use Illuminate\Support\Str;

if (!function_exists('format_money')) {
    function format_money($amount, $decimals = null)
    {
        $value = (float) $amount;

        if (!is_finite($value)) {
            return '0';
        }

        if ($decimals !== null) {
            $digits = max(0, (int) $decimals);
            return rtrim(rtrim(number_format($value, $digits, '.', ','), '0'), '.');
        }

        $abs = abs($value);
        $digits = $abs >= 1 ? 4 : 8;

        $formatted = number_format($value, $digits, '.', ',');
        return rtrim(rtrim($formatted, '0'), '.');
    }
}

if (!function_exists('get_user_avatar')) {
    function get_user_avatar($user, $defaultAvatar = 'images/default-avatar-gray.svg')
    {
        if ($user->avatar) {
            return asset('storage/' . $user->avatar);
        }

        return asset($defaultAvatar);
    }
}
class ReferralCodeHelper
{
    public static function generate()
    {
        do {
            $random_code = strtoupper(Str::random(6));
            $exists = User::where('referral_code', $random_code)->exists();
        } while ($exists);

        return $random_code;
    }
}