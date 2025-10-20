<?php

use App\Models\User;
use Illuminate\Support\Str;

if (!function_exists('format_money')) {
    function format_money($amount, $decimals = 4)
    {
        return rtrim(rtrim(number_format((float) $amount, $decimals, '.', ','), '0'), '.');
    }
}

if (!function_exists('get_user_avatar')) {
    function get_user_avatar($user, $defaultAvatar = 'images/default-avatar-gray.svg')
    {
        if ($user->avatar && file_exists(public_path('storage/' . $user->avatar))) {
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