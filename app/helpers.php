<?php

if (!function_exists('format_money')) {
    function format_money($amount, $decimals = 4)
    {
        return rtrim(rtrim(number_format((float) $amount, $decimals, '.', ','), '0'), '.');
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