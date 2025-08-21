<?php

class Res_Pong_Util {

    public static function enqueue_notification_messages($event, $notification_subscribers) {

        $args = ['event' => $event, 'notification_subscribers' => $notification_subscribers];
        Res_Pong_Util::rp_normalize_args($args);

        if (!defined('DISABLE_WP_CRON') || DISABLE_WP_CRON === false) {
            wp_schedule_single_event(time() - 86400, 'res_pong_send_availability_notifications', [$args]);

            for ($i = 0; $i < 5; $i++) {
                $enqueued = wp_get_scheduled_event('res_pong_send_availability_notifications', [$args]);
                if ($enqueued) {
                    break;
                }
                error_log("Enqueued job not found. Tentative: " . ($i + 1) . " of 5. Sleeping 200ms.");
                usleep(200 * 1000);
            }

            Res_Pong_Util::wake_up_wp_cron();
        } else {
            do_action('res_pong_send_availability_notifications', $args);
        }
    }

    public static function wake_up_wp_cron() {
        if (!defined('DISABLE_WP_CRON') || DISABLE_WP_CRON === false) {
            if (function_exists('spawn_cron')) {
                spawn_cron();
            } else {
                $url = site_url('wp-cron.php?doing_wp_cron=' . rawurlencode(microtime(true)));
                if (RES_PONG_DEV) {
                    $url = str_replace(':8080', '', $url);
                }
                wp_remote_post($url, ['timeout' => 5, 'blocking' => false]);
            }
        }
    }

    public static function send_email($to, $subject, $message, $signature) {
        $headers = ['Content-Type: text/html; charset=UTF-8'];
        $message = "$message\n\n<hr>$signature";
        $message = wpautop($message);
        $message = wp_kses_post($message);

        wp_mail($to, $subject, $message, $headers);

        if (RES_PONG_DEV) {
            error_log("SENT EMAIL TO [$to] SUBJECT [$subject]");
        }
    }

    private static function rp_normalize_args(array &$a) {
        ksort($a);
        foreach ($a as &$v) {
            if (is_array($v)) Res_Pong_Util::rp_normalize_args($v);
        }
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

    public static function date_now_formatted($pattern = "EEEE dd/MM/yyyy HH:mm", $date = 'now') {
        $date = new DateTime($date);

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

    private static function format_duration($start, $end) {
        $startDate = new DateTime($start);
        $endDate = new DateTime($end);

        $diff = $startDate->diff($endDate);

        $parts = [];
        if ($diff->h > 0) {
            $parts[] = $diff->h . 'h';
        }
        if ($diff->i > 0) {
            $parts[] = $diff->i . 'm';
        }

        return implode(' e ', $parts);
    }

    public static function replace_user_placeholders($string, $user) {
        $user = (array)$user;
        $placeholders = ['#email', '#username', '#last_name', '#first_name', '#category'];
        $replacements = [$user['email'], $user['username'], $user['last_name'], $user['first_name'], $user['category']];
        return str_replace($placeholders, $replacements, $string);
    }

    public static function replace_event_placeholders($string, $event) {
        $event = (array)$event;

        $event_date_short = Res_Pong_Util::date_now_formatted('d MMMM yyyy', $event['start_datetime']);
        $event_date_full = Res_Pong_Util::date_now_formatted('EEEE d MMMM yyyy - HH:mm', $event['start_datetime']);
        $event_date_only = Res_Pong_Util::date_now_formatted('EEEE d MMMM yyyy', $event['start_datetime']);
        $event_time_start = Res_Pong_Util::date_now_formatted('HH:mm', $event['start_datetime']);
        $event_time_end = Res_Pong_Util::date_now_formatted('HH:mm', $event['end_datetime']);
        $event_duration = Res_Pong_Util:: format_duration($event['start_datetime'], $event['end_datetime']);
        $event_note = empty($event['note']) ? 'Nessuna' : $event['note'];
        $placeholders = ['#event_id', '#event_category', '#event_name', '#event_note', '#event_start_datetime', '#event_end_datetime', '#event_max_players', '#event_players_count', '#event_date_short', '#event_date_full', '#event_date_only', '#event_time_start', '#event_time_end', '#event_duration'];
        $replacements = [$event['id'], $event['category'], $event['name'], $event_note, $event['start_datetime'], $event['end_datetime'], $event['max_players'], $event['players_count'], $event_date_short, $event_date_full, $event_date_only, $event_time_start, $event_time_end, $event_duration];
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

    public static function replace_broadcast_message_warning($string, $event_notified_count) {
        // broadcast_message_warning
        $replacement = $event_notified_count > 1 ? "NOTA: questa notifica è stata inviata a <b>$event_notified_count utenti</b> della piattaforma." : '';
        return str_replace('#broadcast_message_warning', $replacement, $string);
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

    public static function parse_flags($user) {
        if (!empty($user->flags)) {
            return [
                'send_email_on_reservation' => (($user->flags & 1) >> 0) == 1,
                'send_email_on_deletion' => (($user->flags & 2) >> 1) == 1,
            ];
        } else {
            return [
                'send_email_on_reservation' => false,
                'send_email_on_deletion' => false,
            ];
        }
    }


}

