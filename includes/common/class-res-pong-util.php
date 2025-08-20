<?php

class Res_Pong_Util {

    public static function send_email($to, $subject, $message, $headers = []) {
        $headers[] = 'Content-Type: text/html; charset=UTF-8';
        $message = wpautop($message);
        $message = wp_kses_post($message);
        wp_mail($to, $subject, $message, $headers);
    }


    public static function res_pong_token_make(int $userId, int $ttl): string {
        $exp = time() + $ttl;
        $payload = $userId . '|' . $exp;
        $sig = hash_hmac('sha256', $payload, RES_PONG_COOKIE_KEY);
        return base64_encode($payload . '|' . $sig);
    }

    public static function res_pong_set_cookie(string $value, int $ttl) {
        $params = [
            'expires' => time() + $ttl,
            'path' => '/',
            'secure' => !RES_PONG_DEV,    // in dev su http
            'httponly' => true,
            'samesite' => RES_PONG_DEV ? 'Lax' : 'None' // DEV: lascia Lax
        ];
        setcookie(RES_PONG_COOKIE_NAME, $value, $params);
    }

    public static function res_pong_clear_cookie() {
        setcookie(RES_PONG_COOKIE_NAME, '', [
            'expires' => time() - 3600, 'path' => '/',
            'secure' => !RES_PONG_DEV,    // in dev su http
            'httponly' => true,
            'samesite' => RES_PONG_DEV ? 'Lax' : 'None' // DEV: lascia Lax
        ]);
    }

    public static function adjust_user($user) {
        $user = (object)$user;
        $user->monogram = substr($user->first_name, 0, 1) . substr($user->last_name, 0, 1);
        unset($user->password, $user->reset_token, $user->enabled);
    }

    public static function base64url_encode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    public static function token_parse(string $token) {
        $raw = base64_decode($token, true);
        if ($raw === false) return null;
        $parts = explode('|', $raw);
        if (count($parts) !== 3) return null;
        $uid = $parts[0];
        $exp = $parts[1];
        $sig = $parts[2];
        $payload = $uid . '|' . $exp;
        $calc = hash_hmac('sha256', $payload, RES_PONG_COOKIE_KEY);
        if (!hash_equals($calc, $sig)) return null;
        if ((int)$exp <= time()) return null;
        return (int)$uid;
    }

    public static function base64url_decode($data) {
        $replaced = strtr($data, '-_', '+/');
        $padded = str_pad($replaced, strlen($replaced) % 4 === 0 ? strlen($replaced) : strlen($replaced) + (4 - strlen($replaced) % 4), '=', STR_PAD_RIGHT);
        return base64_decode($padded);
    }

    public static function generate_reset_token($expires = 3600) {
        $expires += time();
        $random = bin2hex(random_bytes(16));
        return $expires . '|' . $random;
    }
}

