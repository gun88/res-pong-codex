<?php

class Res_Pong_User_Controller {
    private $service;

    public function __construct(Res_Pong_User_Service $service) {
        $this->service = $service;
    }

    public function init() {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes() {
        $namespace = 'res-pong/v1';

        $res_pong_admin = $this->service;

        register_rest_route('res-pong/v1', '/login', [
            'methods' => 'POST',
            'callback' => [$res_pong_admin, 'login'],
            'permission_callback' => '__return_true'
        ]);

        register_rest_route('res-pong/v1', '/logout', [
            'methods' => 'POST',
            'callback' => [$res_pong_admin, 'logout'],
            'permission_callback' => '__return_true'
        ]);

        register_rest_route('res-pong/v1', '/user', [
            'methods' => 'GET',
            'callback' => [$res_pong_admin, 'get_logged_user'],
            'permission_callback' => function () use ($res_pong_admin) {
                return $res_pong_admin->res_pong_get_logged_user_id();
            }
        ]);

        register_rest_route('res-pong/v1', '/events/(?P<event_id>[\w-]+?)', [
            'methods' => 'GET',
            'callback' => [$res_pong_admin, 'get_event_for_logged_user'],
            'permission_callback' => function () use ($res_pong_admin) {
                return $res_pong_admin->res_pong_get_logged_user_id();
            }
        ]);

        register_rest_route('res-pong/v1', '/events/(?P<event_id>[\w-]+?)/subscription', [
            'methods' => 'POST',
            'callback' => [$res_pong_admin, 'create_event_subscription_for_logged_user'],
            'permission_callback' => function () use ($res_pong_admin) {
                return $res_pong_admin->res_pong_get_logged_user_id();
            }
        ]);

        register_rest_route('res-pong/v1', '/events/(?P<event_id>[\w-]+?)/subscription', [
            'methods' => 'DELETE',
            'callback' => [$res_pong_admin, 'delete_event_subscription_for_logged_user'],
            'permission_callback' => function () use ($res_pong_admin) {
                return $res_pong_admin->res_pong_get_logged_user_id();
            }
        ]);

        register_rest_route('res-pong/v1', '/events', [
            'methods' => 'GET',
            'callback' => [$res_pong_admin, 'get_events'],
            'permission_callback' => function () use ($res_pong_admin) {
                return current_user_can('manage_options') || $res_pong_admin->res_pong_get_logged_user_id();
            }
        ]);

        register_rest_route('res-pong/v1', '/reservations', [
            'methods' => 'GET',
            'callback' => [$res_pong_admin, 'get_user_reservations_for_logged_user'],
            'permission_callback' => function () use ($res_pong_admin) {
                return $res_pong_admin->res_pong_get_logged_user_id();
            }
        ]);

        register_rest_route('res-pong/v1', '/reservations', [
            'methods' => 'POST',
            'callback' => [$res_pong_admin, 'create_user_reservations_for_logged_user'],
            'permission_callback' => function () use ($res_pong_admin) {
                return $res_pong_admin->res_pong_get_logged_user_id();
            }
        ]);

        register_rest_route('res-pong/v1', '/reservations', [
            'methods' => 'DELETE',
            'callback' => [$res_pong_admin, 'delete_user_reservations_for_logged_user'],
            'permission_callback' => function () use ($res_pong_admin) {
                return $res_pong_admin->res_pong_get_logged_user_id();
            }
        ]);

        register_rest_route('res-pong/v1', '/password/update', [
            'methods' => 'POST',
            'callback' => [$res_pong_admin, 'password_update_logged_user'],
            'permission_callback' => function () use ($res_pong_admin) {
                return $res_pong_admin->res_pong_get_logged_user_id();
            }
        ]);

        register_rest_route('res-pong/v1', '/user/email-preferences', [
            'methods' => 'POST',
            'callback' => [$res_pong_admin, 'update_logged_user_email_preferences'],
            'permission_callback' => function () use ($res_pong_admin) {
                return $res_pong_admin->res_pong_get_logged_user_id();
            }
        ]);

        register_rest_route('res-pong/v1', '/user-by-token', [
            'methods' => 'POST',
            'callback' => [$res_pong_admin, 'get_user_by_token'],
            'permission_callback' => '__return_true'
        ]);

        register_rest_route('res-pong/v1', '/password/reset', [
            'methods' => 'POST',
            'callback' => [$res_pong_admin, 'password_reset'],
            'permission_callback' => '__return_true'
        ]);

        register_rest_route('res-pong/v1', '/password/update-by-token', [
            'methods' => 'POST',
            'callback' => [$res_pong_admin, 'password_update_by_token'],
            'permission_callback' => '__return_true'
        ]);

        register_rest_route('res-pong/v1', '/configurations', [
            'methods' => 'GET',
            'callback' => [$res_pong_admin, 'get_public_configurations'],
            'permission_callback' => '__return_true'
        ]);
    }

}

