<?php

class Res_Pong_Admin_Controller {
    private $service;

    public function __construct(Res_Pong_Admin_Service $service) {
        $this->service = $service;
    }

    public function init() {
        add_action('rest_api_init', [$this->service, 'register_routes']);
    }

    public function register_routes() {
        $namespace = 'res-pong/v1';

        // Users
        register_rest_route($namespace, '/users', [
            'methods' => 'GET',
            'callback' => [$this->service, 'rest_get_users'],
            'permission_callback' => function () {
                return current_user_can('manage_options');
            },
        ]);
        register_rest_route($namespace, '/users', [
            'methods' => 'POST',
            'callback' => [$this->service, 'rest_create_user'],
            'permission_callback' => function () {
                return current_user_can('manage_options');
            },
        ]);
        register_rest_route($namespace, '/users/export', [
            'methods' => 'GET',
            'callback' => [$this->service, 'rest_export_users'],
            'permission_callback' => function () {
                return current_user_can('manage_options');
            },
        ]);
        register_rest_route($namespace, '/users/import', [
            'methods' => 'POST',
            'callback' => [$this->service, 'rest_import_users'],
            'permission_callback' => function () {
                return current_user_can('manage_options');
            },
        ]);
        register_rest_route($namespace, '/users/(?P<id>[\w-]+)', [
            'methods' => 'GET',
            'callback' => [$this->service, 'rest_get_user'],
            'permission_callback' => function () {
                return current_user_can('manage_options');
            },
        ]);
        register_rest_route($namespace, '/users/(?P<id>[\w-]+)', [
            'methods' => 'PUT',
            'callback' => [$this->service, 'rest_update_user'],
            'permission_callback' => function () {
                return current_user_can('manage_options');
            },
        ]);
        register_rest_route($namespace, '/users/(?P<id>[\w-]+)', [
            'methods' => 'DELETE',
            'callback' => [$this->service, 'rest_delete_user'],
            'permission_callback' => function () {
                return current_user_can('manage_options');
            },
        ]);
        register_rest_route($namespace, '/users/(?P<id>[\w-]+)/invite', [
            'methods' => 'POST',
            'callback' => [$this->service, 'rest_invite_user'],
            'permission_callback' => function () {
                return current_user_can('manage_options');
            },
        ]);
        register_rest_route($namespace, '/users/(?P<id>[\w-]+)/reset-password', [
            'methods' => 'POST',
            'callback' => [$this->service, 'rest_reset_password'],
            'permission_callback' => function () {
                return current_user_can('manage_options');
            },
        ]);
        register_rest_route($namespace, '/users/(?P<id>[\w-]+)/impersonate', [
            'methods' => 'POST',
            'callback' => [$this->service, 'rest_impersonate_user'],
            'permission_callback' => function () {
                return current_user_can('manage_options');
            },
        ]);


        // Events
        register_rest_route($namespace, '/events', [
            'methods' => 'GET',
            'callback' => [$this->service, 'rest_get_events'],
            'permission_callback' => function () {
                return current_user_can('manage_options');
            },
        ]);
        register_rest_route($namespace, '/events', [
            'methods' => 'POST',
            'callback' => [$this->service, 'rest_create_event'],
            'permission_callback' => function () {
                return current_user_can('manage_options');
            },
        ]);
        register_rest_route($namespace, '/events/export', [
            'methods' => 'GET',
            'callback' => [$this->service, 'rest_export_events'],
            'permission_callback' => function () {
                return current_user_can('manage_options');
            },
        ]);
        register_rest_route($namespace, '/events/import', [
            'methods' => 'POST',
            'callback' => [$this->service, 'rest_import_events'],
            'permission_callback' => function () {
                return current_user_can('manage_options');
            },
        ]);
        register_rest_route($namespace, '/events/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this->service, 'rest_get_event'],
            'permission_callback' => function () {
                return current_user_can('manage_options');
            },
        ]);
        register_rest_route($namespace, '/events/(?P<id>\d+)', [
            'methods' => 'PUT',
            'callback' => [$this->service, 'rest_update_event'],
            'permission_callback' => function () {
                return current_user_can('manage_options');
            },
        ]);
        register_rest_route($namespace, '/events/(?P<id>\d+)', [
            'methods' => 'DELETE',
            'callback' => [$this->service, 'rest_delete_event'],
            'permission_callback' => function () {
                return current_user_can('manage_options');
            },
        ]);

        // Reservations
        register_rest_route($namespace, '/reservations', [
            'methods' => 'GET',
            'callback' => [$this->service, 'rest_get_reservations'],
            'permission_callback' => function () {
                return current_user_can('manage_options');
            },
        ]);
        register_rest_route($namespace, '/reservations', [
            'methods' => 'POST',
            'callback' => [$this->service, 'rest_create_reservation'],
            'permission_callback' => function () {
                return current_user_can('manage_options');
            },
        ]);
        register_rest_route($namespace, '/reservations/export', [
            'methods' => 'GET',
            'callback' => [$this->service, 'rest_export_reservations'],
            'permission_callback' => function () {
                return current_user_can('manage_options');
            },
        ]);
        register_rest_route($namespace, '/reservations/import', [
            'methods' => 'POST',
            'callback' => [$this->service, 'rest_import_reservations'],
            'permission_callback' => function () {
                return current_user_can('manage_options');
            },
        ]);
        register_rest_route($namespace, '/reservations/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this->service, 'rest_get_reservation'],
            'permission_callback' => function () {
                return current_user_can('manage_options');
            },
        ]);
        register_rest_route($namespace, '/reservations/(?P<id>\d+)', [
            'methods' => 'PUT',
            'callback' => [$this->service, 'rest_update_reservation'],
            'permission_callback' => function () {
                return current_user_can('manage_options');
            },
        ]);
        register_rest_route($namespace, '/reservations/(?P<id>\d+)', [
            'methods' => 'DELETE',
            'callback' => [$this->service, 'rest_delete_reservation'],
            'permission_callback' => function () {
                return current_user_can('manage_options');
            },
        ]);
    }

}

