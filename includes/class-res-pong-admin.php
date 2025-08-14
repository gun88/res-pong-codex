<?php

class Res_Pong_Admin {
    private $repository;

    public function __construct($repository) {
        $this->repository = $repository;
        add_action('admin_menu', [ $this, 'register_menu' ]);
        add_action('admin_enqueue_scripts', [ $this, 'enqueue_assets' ]);
    }

    public function register_menu() {
        add_menu_page('Res Pong', 'Res Pong', 'manage_options', 'res-pong-users', [ $this, 'render_users_page' ], 'dashicons-table-row-after');
        add_submenu_page('res-pong-users', 'Users', 'Users', 'manage_options', 'res-pong-users', [ $this, 'render_users_page' ]);
        add_submenu_page('res-pong-users', 'Events', 'Events', 'manage_options', 'res-pong-events', [ $this, 'render_events_page' ]);
        add_submenu_page('res-pong-users', 'Reservations', 'Reservations', 'manage_options', 'res-pong-reservations', [ $this, 'render_reservations_page' ]);
        add_submenu_page(null, 'User Detail', 'User Detail', 'manage_options', 'res-pong-user-detail', [ $this, 'render_user_detail' ]);
        add_submenu_page(null, 'Event Detail', 'Event Detail', 'manage_options', 'res-pong-event-detail', [ $this, 'render_event_detail' ]);
        add_submenu_page(null, 'Reservation Detail', 'Reservation Detail', 'manage_options', 'res-pong-reservation-detail', [ $this, 'render_reservation_detail' ]);
    }

    public function enqueue_assets($hook) {
        if (strpos($hook, 'res-pong') === false) {
            return;
        }
        wp_enqueue_style('res-pong-datatables', 'https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css');
        wp_enqueue_script('res-pong-datatables', 'https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js', [ 'jquery' ], null, true);
        wp_enqueue_script('res-pong-admin', RES_PONG_PLUGIN_URL . 'assets/js/res-pong-admin.js', [ 'jquery', 'res-pong-datatables' ], RES_PONG_VERSION, true);
        wp_localize_script('res-pong-admin', 'rp_admin', [
            'rest_url'  => esc_url_raw(rest_url('res-pong/v1/')),
            'nonce'     => wp_create_nonce('wp_rest'),
            'admin_url' => admin_url('admin.php'),
        ]);
    }

    // List pages
    public function render_users_page() {
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Users', 'res-pong') . ' <a href="#" id="res-pong-add" class="page-title-action">' . esc_html__('Add New', 'res-pong') . '</a></h1>';
        echo '<table id="res-pong-list" class="display" data-entity="users"></table>';
        echo '</div>';
    }

    public function render_events_page() {
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Events', 'res-pong') . ' <a href="#" id="res-pong-add" class="page-title-action">' . esc_html__('Add New', 'res-pong') . '</a></h1>';
        echo '<table id="res-pong-list" class="display" data-entity="events"></table>';
        echo '</div>';
    }

    public function render_reservations_page() {
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Reservations', 'res-pong') . ' <a href="#" id="res-pong-add" class="page-title-action">' . esc_html__('Add New', 'res-pong') . '</a></h1>';
        echo '<table id="res-pong-list" class="display" data-entity="reservations"></table>';
        echo '</div>';
    }

    // Detail pages
    public function render_user_detail() {
        $id = isset($_GET['id']) ? sanitize_text_field($_GET['id']) : '';
        $editing = !empty($id);
        echo '<div class="wrap">';
        echo '<h1>' . ($editing ? esc_html__('Edit User', 'res-pong') : esc_html__('Add User', 'res-pong')) . '</h1>';
        echo '<form id="res-pong-detail-form" data-entity="users" data-id="' . esc_attr($id) . '">';
        echo '<table class="form-table">';
        echo '<tr><th><label for="id">ID</label></th><td><input name="id" id="id" type="text"></td></tr>';
        echo '<tr><th><label for="email">Email</label></th><td><input name="email" id="email" type="email"></td></tr>';
        echo '<tr><th><label for="username">Username</label></th><td><input name="username" id="username" type="text"></td></tr>';
        echo '<tr><th><label for="first_name">First Name</label></th><td><input name="first_name" id="first_name" type="text"></td></tr>';
        echo '<tr><th><label for="last_name">Last Name</label></th><td><input name="last_name" id="last_name" type="text"></td></tr>';
        echo '<tr><th><label for="category">Category</label></th><td><input name="category" id="category" type="text"></td></tr>';
        echo '<tr><th><label for="password">Password</label></th><td><input name="password" id="password" type="password"></td></tr>';
        echo '<tr><th><label for="enabled">Enabled</label></th><td><input name="enabled" id="enabled" type="checkbox" value="1"></td></tr>';
        echo '</table>';
        echo '<p class="submit"><button type="submit" class="button button-primary">' . esc_html__('Save', 'res-pong') . '</button>';
        if ($editing) {
            echo ' <button type="button" class="button button-secondary" id="res-pong-delete">' . esc_html__('Delete', 'res-pong') . '</button>';
        }
        echo '</p></form></div>';
    }

    public function render_event_detail() {
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $editing = !empty($id);
        echo '<div class="wrap">';
        echo '<h1>' . ($editing ? esc_html__('Edit Event', 'res-pong') : esc_html__('Add Event', 'res-pong')) . '</h1>';
        echo '<form id="res-pong-detail-form" data-entity="events" data-id="' . esc_attr($id) . '">';
        echo '<table class="form-table">';
        echo '<tr><th><label for="group_id">Group ID</label></th><td><input name="group_id" id="group_id" type="number"></td></tr>';
        echo '<tr><th><label for="category">Category</label></th><td><input name="category" id="category" type="text"></td></tr>';
        echo '<tr><th><label for="name">Name</label></th><td><input name="name" id="name" type="text"></td></tr>';
        echo '<tr><th><label for="note">Note</label></th><td><textarea name="note" id="note"></textarea></td></tr>';
        echo '<tr><th><label for="start_datetime">Start</label></th><td><input name="start_datetime" id="start_datetime" type="text"></td></tr>';
        echo '<tr><th><label for="end_datetime">End</label></th><td><input name="end_datetime" id="end_datetime" type="text"></td></tr>';
        echo '<tr><th><label for="max_players">Max Players</label></th><td><input name="max_players" id="max_players" type="number"></td></tr>';
        echo '<tr><th><label for="enabled">Enabled</label></th><td><input name="enabled" id="enabled" type="checkbox" value="1"></td></tr>';
        echo '</table>';
        echo '<p class="submit"><button type="submit" class="button button-primary">' . esc_html__('Save', 'res-pong') . '</button>';
        if ($editing) {
            echo ' <button type="button" class="button button-secondary" id="res-pong-delete">' . esc_html__('Delete', 'res-pong') . '</button>';
        }
        echo '</p></form></div>';
    }

    public function render_reservation_detail() {
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $editing = !empty($id);
        echo '<div class="wrap">';
        echo '<h1>' . ($editing ? esc_html__('Edit Reservation', 'res-pong') : esc_html__('Add Reservation', 'res-pong')) . '</h1>';
        echo '<form id="res-pong-detail-form" data-entity="reservations" data-id="' . esc_attr($id) . '">';
        echo '<table class="form-table">';
        echo '<tr><th><label for="user_id">User ID</label></th><td><input name="user_id" id="user_id" type="text"></td></tr>';
        echo '<tr><th><label for="event_id">Event ID</label></th><td><input name="event_id" id="event_id" type="number"></td></tr>';
        echo '<tr><th><label for="created_at">Created At</label></th><td><input name="created_at" id="created_at" type="text"></td></tr>';
        echo '<tr><th><label for="presence_confirmed">Presence Confirmed</label></th><td><input name="presence_confirmed" id="presence_confirmed" type="checkbox" value="1"></td></tr>';
        echo '</table>';
        echo '<p class="submit"><button type="submit" class="button button-primary">' . esc_html__('Save', 'res-pong') . '</button>';
        if ($editing) {
            echo ' <button type="button" class="button button-secondary" id="res-pong-delete">' . esc_html__('Delete', 'res-pong') . '</button>';
        }
        echo '</p></form></div>';
    }
}

