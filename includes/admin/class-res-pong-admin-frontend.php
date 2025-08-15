<?php

class Res_Pong_Admin_Frontend {
    private $configuration;

    public function __construct(Res_Pong_Configuration $configuration) {
        $this->configuration = $configuration;
        add_action('admin_menu', [ $this, 'register_menu' ]);
        add_action('admin_enqueue_scripts', [ $this, 'enqueue_assets' ]);
    }

    public function init() {
        add_action('admin_menu', [ $this, 'register_menu' ]);
        add_action('admin_enqueue_scripts', [ $this, 'enqueue_assets' ]);
    }

    private function render_progress_overlay() {
        echo '<div id="rp-progress-overlay"><div class="rp-progress-dialog"><progress></progress><span id="rp-progress-text">0%</span></div></div>';
    }

    public function register_menu() {
        add_menu_page('Res Pong', 'Res Pong', 'manage_options', 'res-pong-users', [ $this, 'render_users_page' ], 'dashicons-table-row-after');
        add_submenu_page('res-pong-users', 'Users', 'Users', 'manage_options', 'res-pong-users', [ $this, 'render_users_page' ]);
        add_submenu_page('res-pong-users', 'Events', 'Events', 'manage_options', 'res-pong-events', [ $this, 'render_events_page' ]);
        add_submenu_page('res-pong-users', 'Reservations', 'Reservations', 'manage_options', 'res-pong-reservations', [ $this, 'render_reservations_page' ]);
        add_submenu_page('res-pong-users', 'Configurations', 'Configurations', 'manage_options', 'res-pong-configurations', [ $this, 'render_configurations_page' ]);
        add_submenu_page(null, 'User Detail', 'User Detail', 'manage_options', 'res-pong-user-detail', [ $this, 'render_user_detail' ]);
        add_submenu_page(null, 'Event Detail', 'Event Detail', 'manage_options', 'res-pong-event-detail', [ $this, 'render_event_detail' ]);
        add_submenu_page(null, 'Reservation Detail', 'Reservation Detail', 'manage_options', 'res-pong-reservation-detail', [ $this, 'render_reservation_detail' ]);
    }

    public function enqueue_assets($hook) {
        if (strpos($hook, 'res-pong') === false) {
            return;
        }
        wp_enqueue_style('res-pong-datatables', 'https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css');
        wp_enqueue_style('res-pong-admin', RES_PONG_PLUGIN_URL . 'assets/css/res-pong-admin.css', [], RES_PONG_VERSION);
        wp_enqueue_script('res-pong-datatables', 'https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js', [ 'jquery' ], null, true);
        wp_enqueue_script('res-pong-admin', RES_PONG_PLUGIN_URL . 'assets/js/res-pong-admin.js', [ 'jquery', 'res-pong-datatables' ], RES_PONG_VERSION, true);
        wp_localize_script('res-pong-admin', 'rp_admin', [
            'rest_url'  => esc_url_raw(rest_url('res-pong-admin/v1/')),
            'nonce'     => wp_create_nonce('wp_rest'),
            'admin_url' => admin_url('admin.php'),
        ]);
    }

    // List pages
    public function render_users_page() {
        echo '<div class="wrap rp-wrap">';
        echo '<h1>' . esc_html__('Users', 'res-pong') . '</h1>';
        echo '<table id="res-pong-list" class="display" data-entity="users"></table>';
        $this->render_progress_overlay();
        echo '</div>';
    }

    public function render_events_page() {
        echo '<div class="wrap rp-wrap">';
        echo '<h1>' . esc_html__('Events', 'res-pong') . '</h1>';
        echo '<table id="res-pong-list" class="display" data-entity="events"></table>';
        $this->render_progress_overlay();
        echo '</div>';
    }

    public function render_reservations_page() {
        echo '<div class="wrap rp-wrap">';
        echo '<h1>' . esc_html__('Reservations', 'res-pong') . '</h1>';
        echo '<table id="res-pong-list" class="display" data-entity="reservations"></table>';
        $this->render_progress_overlay();
        echo '</div>';
    }

    public function render_configurations_page() {
        if (isset($_POST['rp_configurations_nonce']) && wp_verify_nonce($_POST['rp_configurations_nonce'], 'rp_save_configurations')) {
            $data = [
                'almost_closed_minutes'     => isset($_POST['almost_closed_minutes']) ? intval($_POST['almost_closed_minutes']) : 0,
                'almost_full_players'      => isset($_POST['almost_full_players']) ? intval($_POST['almost_full_players']) : 0,
                'max_active_reservations'  => isset($_POST['max_active_reservations']) ? intval($_POST['max_active_reservations']) : 0,
                'next_reservation_delay'   => isset($_POST['next_reservation_delay']) ? intval($_POST['next_reservation_delay']) : 0,
                'first_access_page_url'    => isset($_POST['first_access_page_url']) ? esc_url_raw($_POST['first_access_page_url']) : '',
                'password_update_page_url' => isset($_POST['password_update_page_url']) ? esc_url_raw($_POST['password_update_page_url']) : '',
                'invitation_subject'       => isset($_POST['invitation_subject']) ? sanitize_text_field($_POST['invitation_subject']) : '',
                'invitation_text'          => isset($_POST['invitation_text']) ? sanitize_textarea_field($_POST['invitation_text']) : '',
                'reset_password_subject'   => isset($_POST['reset_password_subject']) ? sanitize_text_field($_POST['reset_password_subject']) : '',
                'reset_password_text'      => isset($_POST['reset_password_text']) ? sanitize_textarea_field($_POST['reset_password_text']) : '',
            ];
            $this->configuration->update($data);
            echo '<div class="updated"><p>' . esc_html__('Settings saved', 'res-pong') . '</p></div>';
        }
        $config = $this->configuration->get_all();
        echo '<div class="wrap rp-wrap">';
        echo '<h1>' . esc_html__('Configurations', 'res-pong') . '</h1>';
        echo '<form method="post">';
        wp_nonce_field('rp_save_configurations', 'rp_configurations_nonce');
        echo '<table class="form-table">';
        echo '<tr><th><label for="almost_closed_minutes">Almost closed minutes</label></th><td><input name="almost_closed_minutes" id="almost_closed_minutes" type="number" value="' . esc_attr($config['almost_closed_minutes']) . '"></td></tr>';
        echo '<tr><th><label for="almost_full_players">Almost full players</label></th><td><input name="almost_full_players" id="almost_full_players" type="number" value="' . esc_attr($config['almost_full_players']) . '"></td></tr>';
        echo '<tr><th><label for="max_active_reservations">Max active reservations</label></th><td><input name="max_active_reservations" id="max_active_reservations" type="number" value="' . esc_attr($config['max_active_reservations']) . '"></td></tr>';
        echo '<tr><th><label for="next_reservation_delay">Next reservation delay</label></th><td><input name="next_reservation_delay" id="next_reservation_delay" type="number" value="' . esc_attr($config['next_reservation_delay']) . '"></td></tr>';
        echo '<tr><th colspan="2"><h2 style="margin: 0">First Access E-Mail</h2></th></tr>';
        echo '<tr><th><label for="first_access_page_url">First access page URL</label></th><td><input name="first_access_page_url" id="first_access_page_url" type="text" style="max-width:600px;" class="large-text" value="' . esc_attr($config['first_access_page_url']) . '"></td></tr>';
        echo '<tr><th><label for="invitation_subject">Invitation Subject</label></th><td><input name="invitation_subject" id="invitation_subject" type="text" class="large-text" style="max-width:600px;" value="' . esc_attr($config['invitation_subject']) . '"></td></tr>';
        echo '<tr><th><label for="invitation_text">Invitation Text</label></th><td><textarea name="invitation_text" id="invitation_text" rows="3" class="large-text" style="max-width:600px;min-height:10rem">' . esc_textarea($config['invitation_text']) . '</textarea></td></tr>';
        echo '<tr><th colspan="2"><h2 style="margin: 0">Password Update E-Mail</h2></th></tr>';
        echo '<tr><th><label for="password_update_page_url">Password update page URL</label></th><td><input name="password_update_page_url" id="password_update_page_url" style="max-width:600px;" class="large-text" type="text" value="' . esc_attr($config['password_update_page_url']) . '"></td></tr>';
        echo '<tr><th><label for="reset_password_subject">Reset Password Subject</label></th><td><input name="reset_password_subject" id="reset_password_subject" type="text" style="max-width:600px;" class="large-text" value="' . esc_attr($config['reset_password_subject']) . '"></td></tr>';
        echo '<tr><th><label for="reset_password_text">Reset Password Text</label></th><td><textarea name="reset_password_text" id="reset_password_text" rows="3" class="large-text" style="max-width:600px;min-height:10rem">' . esc_textarea($config['reset_password_text']) . '</textarea></td></tr>';
        echo '</table>';
        echo '<p class="submit"><button type="submit" class="button button-primary">' . esc_html__('Save', 'res-pong') . '</button></p>';
        echo '</form>';
        echo '</div>';
    }

    // Detail pages
    public function render_user_detail() {
        $id = isset($_GET['id']) ? sanitize_text_field($_GET['id']) : '';
        $editing = !empty($id);
        $config = $this->configuration->get_all();
        echo '<div class="wrap rp-wrap">';
        echo '<h1>' . ($editing ? esc_html__('Edit User', 'res-pong') : esc_html__('Add User', 'res-pong')) . '</h1>';
        echo '<form id="res-pong-detail-form" data-entity="users" data-id="' . esc_attr($id) . '">';
        echo '<table class="form-table">';
        echo '<tr><th><label for="id">ID</label></th><td><input name="id" id="id" type="text"' . ( $editing ? ' readonly' : '' ) . '></td></tr>';
        echo '<tr><th><label for="email">Email</label></th><td><input name="email" id="email" type="email"></td></tr>';
        echo '<tr><th><label for="username">Username</label></th><td><input name="username" id="username" type="text"></td></tr>';
        echo '<tr><th><label for="first_name">First Name</label></th><td><input name="first_name" id="first_name" type="text"></td></tr>';
        echo '<tr><th><label for="last_name">Last Name</label></th><td><input name="last_name" id="last_name" type="text"></td></tr>';
        echo '<tr><th><label for="category">Category</label></th><td><input name="category" id="category" type="text"></td></tr>';
        echo '<tr><th><label for="enabled">Enabled</label></th><td><input name="enabled" id="enabled" type="checkbox" value="1"' . ( $editing ? '' : ' checked' ) . '></td></tr>';
        echo '</table>';
        echo '<p class="submit"><button type="submit" class="button button-primary">' . esc_html__('Save', 'res-pong') . '</button>';
        if ($editing) {
            echo ' <button type="button" class="button rp-button-danger" id="res-pong-delete">' . esc_html__('Delete', 'res-pong') . '</button>';
        }
        echo ' <button type="button" class="button" id="res-pong-impersonate"' . ( $editing ? '' : ' disabled' ) . '>' . esc_html__('Impersona', 'res-pong') . '</button>';
        echo ' <a href="' . esc_url(admin_url('admin.php?page=res-pong-users')) . '" class="button button-secondary" id="res-pong-back">' . esc_html__('Back', 'res-pong') . '</a>';
        echo '</p></form>';
        echo '<h2>' . esc_html__('Password Reset', 'res-pong') . '</h2>';
        echo '<form id="res-pong-password-form" data-entity="users" data-id="' . esc_attr($id) . '">';
        echo '<table class="form-table">';
        echo '<tr><th><label for="new_password">New Password</label></th><td><input name="new_password" id="new_password" type="password"></td></tr>';
        echo '<tr><th><label for="confirm_password">Confirm Password</label></th><td><input name="confirm_password" id="confirm_password" type="password"></td></tr>';
        echo '</table>';
        echo '<p class="submit"><button type="submit" class="button button-primary">' . esc_html__('Save Password', 'res-pong') . '</button></p>';
        echo '</form>';
        echo '<div id="rp-invite-wrapper" style="display:none;">';
        echo '<h2>' . esc_html__('Invia Messaggio di Invito', 'res-pong') . '</h2>';
        echo '<p><input type="text" readonly class="large-text" style="max-width: 600px;" id="rp-invite-subject" value="' . esc_attr($config['invitation_subject']) . '"></p>';
        echo '<p style=" margin-bottom: 0;"><textarea id="rp-invite-text" rows="5" class="large-text" style="max-width:600px;min-height:10rem;">' . esc_textarea($config['invitation_text']) . '</textarea></p>';
        echo '<p style="font-size:12px;color:#555; margin-top: 0; max-width:600px;">Il link di invito sarà aggiunto in coda all\'email. Usa i seguenti placeholder per personalizzare l\'email: #email, #username, #last_name, #first_name, #category</p>';
        echo '<p><button type="button" class="button button-primary" id="rp-send-invite">' . esc_html__('Invia', 'res-pong') . '</button></p>';
        echo '</div>';
        echo '<div id="rp-reset-wrapper" style="display:none;">';
        echo '<h2>' . esc_html__('Send Password Reset Link', 'res-pong') . '</h2>';
        echo '<p><input type="text" readonly class="large-text" style="max-width: 600px;" id="rp-reset-subject" value="' . esc_attr($config['reset_password_subject']) . '"></p>';
        echo '<p style=" margin-bottom: 0;"><textarea id="rp-reset-text" rows="5" class="large-text" style="max-width:600px;min-height:10rem;">' . esc_textarea($config['reset_password_text']) . '</textarea></p>';
        echo '<p style="font-size:12px;color:#555; margin-top: 0; max-width:600px;">Il link di reset password sarà aggiunto in coda all\'email. Usa i seguenti placeholder per personalizzare l\'email: #email, #username, #last_name, #first_name, #category</p>';
        echo '<p><button type="button" class="button button-primary" id="rp-send-reset">' . esc_html__('Invia', 'res-pong') . '</button></p>';
        echo '</div>';
        $default_timeout = date('Y-m-d\\T00:00:00', strtotime('+7 days'));
        echo '<h2>' . esc_html__('Timeout', 'res-pong') . '</h2>';
        echo '<form id="res-pong-timeout-form" data-entity="users" data-id="' . esc_attr($id) . '">';
        echo '<table class="form-table">';
        echo '<tr><th><label for="timeout">Timeout</label></th><td><input name="timeout" id="timeout" type="datetime-local" step="1" value="' . esc_attr($default_timeout) . '"></td></tr>';
        echo '</table>';
        echo '<p class="submit"><button type="submit" class="button button-primary">' . esc_html__('Save Timeout', 'res-pong') . '</button></p>';
        echo '</form>';
        echo '<h2>' . esc_html__('User Reservations', 'res-pong') . '</h2>';
        echo '<table id="res-pong-user-reservations" class="display" data-user="' . esc_attr($id) . '"></table>';
        $this->render_progress_overlay();
        echo '</div>';
    }

    public function render_event_detail() {
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $editing = !empty($id);
        $default_start = date('Y-m-d\\T21:30:00');
        $default_end = date('Y-m-d\\T23:00:00');
        echo '<div class="wrap rp-wrap">';
        echo '<h1>' . ($editing ? esc_html__('Edit Event', 'res-pong') : esc_html__('Add Event', 'res-pong')) . '</h1>';
        echo '<form id="res-pong-detail-form" data-entity="events" data-id="' . esc_attr($id) . '">';
        echo '<table class="form-table">';
        echo '<tr><th><label for="group_id">Group ID</label></th><td><select name="group_id" id="group_id"></select></td></tr>';
        echo '<tr id="recurrence_row"><th><label for="recurrence">Ricorrenza</label></th><td><select name="recurrence" id="recurrence"><option value="none">Mai</option><option value="daily">Giornaliera</option><option value="weekly">Settimanale</option><option value="monthly">Mensile</option></select></td></tr>';
        echo '<tr id="recurrence_end_row"><th><label for="recurrence_end">Termine ricorrenza</label></th><td><input name="recurrence_end" id="recurrence_end" type="date" disabled></td></tr>';
        echo '<tr><th><label for="category">Category</label></th><td><input name="category" id="category" type="text"></td></tr>';
        echo '<tr><th><label for="name">Name</label></th><td><input name="name" id="name" type="text"></td></tr>';
        echo '<tr><th><label for="note">Note</label></th><td><textarea name="note" id="note"></textarea></td></tr>';
        echo '<tr><th><label for="start_datetime">Start</label></th><td><input name="start_datetime" id="start_datetime" type="datetime-local" step="1"' . ( $editing ? '' : ' value="' . esc_attr($default_start) . '"' ) . '></td></tr>';
        echo '<tr><th><label for="end_datetime">End</label></th><td><input name="end_datetime" id="end_datetime" type="datetime-local" step="1"' . ( $editing ? '' : ' value="' . esc_attr($default_end) . '"' ) . '></td></tr>';
        echo '<tr><th><label for="max_players">Max Players</label></th><td><input name="max_players" id="max_players" type="number"></td></tr>';
        echo '<tr><th><label for="enabled">Enabled</label></th><td><input name="enabled" id="enabled" type="checkbox" value="1"' . ( $editing ? '' : ' checked' ) . '></td></tr>';
        echo '</table>';
        echo '<p class="submit"><button type="submit" class="button button-primary">' . esc_html__('Save', 'res-pong') . '</button>';
        if ($editing) {
            echo ' <button type="button" class="button rp-button-danger" id="res-pong-delete">' . esc_html__('Delete', 'res-pong') . '</button>';
        }
        echo ' <a href="' . esc_url(admin_url('admin.php?page=res-pong-events')) . '" class="button button-secondary" id="res-pong-back">' . esc_html__('Back', 'res-pong') . '</a>';
        echo '</p></form>';
        echo '<h2>' . esc_html__('Event Reservations', 'res-pong') . '</h2>';
        echo '<table id="res-pong-event-reservations" class="display" data-event="' . esc_attr($id) . '"></table>';
        $this->render_progress_overlay();
        echo '</div>';
    }

    public function render_reservation_detail() {
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $editing = !empty($id);
        $default_created = date('Y-m-d\\TH:i:s');
        echo '<div class="wrap rp-wrap">';
        echo '<h1>' . ($editing ? esc_html__('Edit Reservation', 'res-pong') : esc_html__('Add Reservation', 'res-pong')) . '</h1>';
        echo '<form id="res-pong-detail-form" data-entity="reservations" data-id="' . esc_attr($id) . '">';
        echo '<table class="form-table">';
        echo '<tr><th><label for="user_id">User ID</label></th><td><select name="user_id" id="user_id"></select></td></tr>';
        echo '<tr><th><label for="event_id">Event ID</label></th><td><select name="event_id" id="event_id"></select></td></tr>';
        echo '<tr><th><label for="created_at">Created At</label></th><td><input name="created_at" id="created_at" type="datetime-local" step="1"' . ( $editing ? '' : ' value="' . esc_attr($default_created) . '"' ) . '></td></tr>';
        echo '<tr><th><label for="presence_confirmed">Presence Confirmed</label></th><td><input name="presence_confirmed" id="presence_confirmed" type="checkbox" value="1"></td></tr>';
        echo '</table>';
        echo '<p class="submit"><button type="submit" class="button button-primary">' . esc_html__('Save', 'res-pong') . '</button>';
        if ($editing) {
            echo ' <button type="button" class="button rp-button-danger" id="res-pong-delete">' . esc_html__('Delete', 'res-pong') . '</button>';
        }
        echo ' <a href="' . esc_url(admin_url('admin.php?page=res-pong-reservations')) . '" class="button button-secondary" id="res-pong-back">' . esc_html__('Back', 'res-pong') . '</a>';
        echo '</p></form>';
        $this->render_progress_overlay();
        echo '</div>';
    }
}

