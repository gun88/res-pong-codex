<?php

class Res_Pong_Util {

    public static function send_email($to, $subject, $message, $signature) {
        $headers = ['Content-Type: text/html; charset=UTF-8'];
        $message = "$message\n\n<hr>$signature";
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

    public static function date_now_formatted($pattern = "EEEE dd/MM/yyyy HH:mm") {
        $date = new DateTime("now");

        $formatter = new IntlDateFormatter(
            'it_IT',
            IntlDateFormatter::FULL,
            IntlDateFormatter::SHORT,
            'Europe/Rome',
            IntlDateFormatter::GREGORIAN,
            $pattern
        );

        return $formatter->format($date);
    }

    public static function replace_user_placeholders($string, $user) {
        $user = (array)$user;
        $placeholders = ['#email', '#username', '#last_name', '#first_name', '#category'];
        $replacements = [$user['email'], $user['username'], $user['last_name'], $user['first_name'], $user['category']];
        return str_replace($placeholders, $replacements, $string);
    }

    public static function replace_temporal_placeholders($string) {
        $placeholders = ['#now_date_and_time', '#now_date_only'];
        $replacements = [
            Res_Pong_Util::date_now_formatted("EEEE d MMMM yyyy - HH:mm"),
            Res_Pong_Util::date_now_formatted("d MMMM yyyy"),
        ];
        return str_replace($placeholders, $replacements, $string);
    }

    public static function replace_configuration_placeholders($string, Res_Pong_Configuration $configuration) {
        $placeholders = ['#app_url', '#site_url', '#app_name', '#site_name'];
        $replacements = [
            $configuration->get('app_url'),
            $configuration->get('site_url'),
            $configuration->get('app_name'),
            $configuration->get('site_name'),
        ];
        return str_replace($placeholders, $replacements, $string);
    }

}

