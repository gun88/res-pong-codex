<?php
/**
 * Plugin Name: Res Pong
 * Plugin URI: https://github.com/gun88/res-pong-codex
 * Description: Prenotazioni per giornate di gioco libero in palestra.
 * Version: 0.3.13
 * Author: tpomante
 * Author URI: https://github.com/gun88
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: res-pong
 * Domain Path: /languages
 */

// Prevent direct access to this file
defined('ABSPATH') || exit;

// Define plugin constants
define('RES_PONG_DEV', true);
define('RES_PONG_VERSION', RES_PONG_DEV ? time() : '0.3.13');
define('RES_PONG_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('RES_PONG_PLUGIN_URL', plugin_dir_url(__FILE__));
define('RS_FITET_MONITOR_ACTIVE', is_plugin_active('fitet-monitor/fitet-monitor.php'));
define('RS_FITET_MONITOR_DIR', plugin_dir_path('fitet-monitor/fitet-monitor.php'));
define('RES_PONG_COOKIE_NAME', 'respong_auth');
define('RES_PONG_COOKIE_KEY', 'kJ#9mP$2vL&5nQ@8xC*4hF!7tR^3wS%6yD_1bN?0aM+9pE{4gU}2jZ'); // Strong random 64-char string

date_default_timezone_set('Europe/Rome');


require_once RES_PONG_PLUGIN_DIR . 'includes/class-res-pong.php';

$res_pong = new Res_Pong();

/**
 * Plugin activation function
 */
register_activation_hook(__FILE__, function () use ($res_pong) {
    $res_pong->activate();
});

add_filter('upgrader_process_complete', [$res_pong, 'copy_app_folder'], 10, 2);

/**
 * Plugin deactivation function
 */
register_deactivation_hook(__FILE__, function () use ($res_pong) {
    $res_pong->deactivate();
});

add_action('res_pong_send_availability_notifications', function (array $data) use ($res_pong) {

    $event = !empty($data['event']) ? $data['event'] : '';
    $notification_subscribers = !empty($data['notification_subscribers']) ? $data['notification_subscribers'] : '';

    if (!$event || !$notification_subscribers) return;

    require_once RES_PONG_PLUGIN_DIR . 'includes/common/class-res-pong-util.php';

    $res_pong->send_notification_messages($event, $notification_subscribers);

}, 10, 1);


if (RES_PONG_DEV) {
    add_filter('cron_request', function (array $request) {
        $u = wp_parse_url($request['url']);
        $scheme = $u['scheme'] ?? 'http';
        $host = 'localhost';
        $port = 80;
        $path = $u['path'] ?? '/wp-cron.php';
        $query = $u['query'] ?? '';

        $request['url'] = $scheme . '://' . $host . ($port ? ':' . $port : '') . $path . ($query ? '?' . $query : '');
        $request['args']['timeout'] = 3;
        $request['args']['blocking'] = false;
        $request['args']['sslverify'] = false;
        return $request;
    });
}

require_once RES_PONG_PLUGIN_DIR . 'includes/plugin-update-checker/plugin-update-checker.php';

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$resPongUpdateChecker = PucFactory::buildUpdateChecker(
    'https://github.com/gun88/res-pong-codex/',
    __FILE__,
    'res-pong'
);

$resPongUpdateChecker->setBranch('main');
