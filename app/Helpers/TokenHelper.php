<?php

namespace App\Helpers;

class TokenHelper
{
    public static function generateLoginToken($userId, $minutes = 10)
    {
        $expires = now()->addMinutes($minutes)->timestamp;
        $tokenData = $userId . '|' . $expires;
        $token = base64_encode($tokenData);
        return rtrim(strtr($token, '+/', '-_'), '=');
    }

    public static function validateLoginToken($token)
    {
        try {
            // Add padding if needed
            $padding = strlen($token) % 4;
            if ($padding) {
                $token .= str_repeat('=', 4 - $padding);
            }

            $decoded = base64_decode(strtr($token, '-_', '+/'));
            
            if ($decoded === false || !str_contains($decoded, '|')) {
                return false;
            }

            [$userId, $expires] = explode('|', $decoded, 2);

            if (!is_numeric($expires) || $expires < now()->timestamp) {
                return false;
            }

            return $userId;
        } catch (\Exception $e) {
            return false;
        }
    }
}