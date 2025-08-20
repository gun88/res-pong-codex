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
        add_submenu_page('res-pong-users', 'Utenti', 'Utenti', 'manage_options', 'res-pong-users', [ $this, 'render_users_page' ]);
        add_submenu_page('res-pong-users', 'Eventi', 'Eventi', 'manage_options', 'res-pong-events', [ $this, 'render_events_page' ]);
        add_submenu_page('res-pong-users', 'Prenotazioni', 'Prenotazioni', 'manage_options', 'res-pong-reservations', [ $this, 'render_reservations_page' ]);
        add_submenu_page('res-pong-users', 'Configurazioni', 'Configurazioni', 'manage_options', 'res-pong-configurations', [ $this, 'render_configurations_page' ]);
        add_submenu_page('res-pong-users', 'Email', 'Email', 'manage_options', 'res-pong-email', [ $this, 'render_email_page' ]);
        add_submenu_page(null, 'Dettaglio Utente', 'Dettaglio Utente', 'manage_options', 'res-pong-user-detail', [ $this, 'render_user_detail' ]);
        add_submenu_page(null, 'Dettaglio Evento', 'Dettaglio Evento', 'manage_options', 'res-pong-event-detail', [ $this, 'render_event_detail' ]);
        add_submenu_page(null, 'Dettaglio Prenotazione', 'Dettaglio Prenotazione', 'manage_options', 'res-pong-reservation-detail', [ $this, 'render_reservation_detail' ]);
    }

    public function enqueue_assets($hook) {
        if (strpos($hook, 'res-pong') === false) {
            return;
        }
        wp_enqueue_style('res-pong-datatables', 'https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css');
        wp_enqueue_style('res-pong-admin', RES_PONG_PLUGIN_URL . 'assets/css/res-pong-admin.css', [], RES_PONG_VERSION);
        wp_enqueue_script('res-pong-datatables', 'https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js', [ 'jquery' ], null, true);
        wp_enqueue_script('jquery-ui-autocomplete');
        wp_enqueue_script('res-pong-admin', RES_PONG_PLUGIN_URL . 'assets/js/res-pong-admin.js', [ 'jquery', 'res-pong-datatables', 'jquery-ui-autocomplete' ], RES_PONG_VERSION, true);
        wp_localize_script('res-pong-admin', 'rp_admin', [
            'rest_url'  => esc_url_raw(rest_url('res-pong-admin/v1/')),
            'nonce'     => wp_create_nonce('wp_rest'),
            'admin_url' => admin_url('admin.php'),
        ]);
    }

    // List pages
    public function render_users_page() {
        echo '<div class="wrap rp-wrap">';
        echo '<h1>' . esc_html__('Utenti', 'res-pong') . '</h1>';
        echo '<table id="res-pong-list" class="display" data-entity="users"></table>';
        $this->render_progress_overlay();
        echo '</div>';
    }

    public function render_events_page() {
        echo '<div class="wrap rp-wrap">';
        echo '<h1>' . esc_html__('Eventi', 'res-pong') . '</h1>';
        echo '<table id="res-pong-list" class="display" data-entity="events"></table>';
        $this->render_progress_overlay();
        echo '</div>';
    }

    public function render_reservations_page() {
        echo '<div class="wrap rp-wrap">';
        echo '<h1>' . esc_html__('Prenotazioni', 'res-pong') . '</h1>';
        echo '<table id="res-pong-list" class="display" data-entity="reservations"></table>';
        $this->render_progress_overlay();
        echo '</div>';
    }

    public function render_email_page() {
        echo '<div class="wrap rp-wrap">';
        echo '<h1>Email</h1>';
        echo '<form id="rp-messenger-form">';
        echo '<table class="form-table">';
        echo '<tr><th><label for="rp-messenger-to">Destinatari</label></th><td><input id="rp-messenger-to" type="text" class="large-text"></td></tr>';
        echo '<tr><th><label for="rp-messenger-subject">Oggetto</label></th><td><input id="rp-messenger-subject" type="text" class="large-text"></td></tr>';
        $editor_settings = [
            'textarea_name' => 'rp-messenger-text',
            'editor_height' => 200,
            'media_buttons' => false,
            'teeny' => true,
            'quicktags' => true,
        ];
        ob_start();
        wp_editor('', 'rp-messenger-text', $editor_settings);
        $editor = ob_get_clean();
        echo '<tr><th><label for="rp-messenger-text">Messaggio</label></th><td><div style="max-width:600px;">' . $editor . '</div><p style="font-size:12px;color:#555;margin-top:0;max-width:600px;">Usa i seguenti placeholder per personalizzare l\'email: #email, #username, #last_name, #first_name, #category</p></td></tr>';
        echo '<tr><th></th><td><button type="submit" class="button button-primary">Invia</button></td></tr>';
        echo '</table>';
        echo '</form>';
        $this->render_progress_overlay();
        echo '</div>';
    }

    public function render_configurations_page() {
        $reinitialize_notice = '';
        if (isset($_POST['rp_reinitialize_nonce']) && wp_verify_nonce($_POST['rp_reinitialize_nonce'], 'rp_reinitialize')) {
            $repo = new Res_Pong_Admin_Repository();
            global $wpdb;
            $repo->drop_tables();
            $repo->create_tables();
            delete_option('res_pong_configuration');
            if (!empty($wpdb->last_error)) {
                $reinitialize_notice = '<div class="notice notice-error is-dismissible"><p>' . esc_html__('Errore durante la reinizializzazione', 'res-pong') . '</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Ignora questa notifica.</span></button></div>';
                $wpdb->last_error = '';
            } else {
                $reinitialize_notice = '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Reinizializzazione completata', 'res-pong') . '</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Ignora questa notifica.</span></button></div>';
            }
        }
        if (isset($_POST['rp_configurations_nonce']) && wp_verify_nonce($_POST['rp_configurations_nonce'], 'rp_save_configurations')) {
            $data = [
                'almost_closed_minutes'     => isset($_POST['almost_closed_minutes']) ? intval($_POST['almost_closed_minutes']) : 0,
                'almost_full_players'      => isset($_POST['almost_full_players']) ? intval($_POST['almost_full_players']) : 0,
                'max_active_reservations'  => isset($_POST['max_active_reservations']) ? intval($_POST['max_active_reservations']) : 0,
                'next_reservation_delay'   => isset($_POST['next_reservation_delay']) ? intval($_POST['next_reservation_delay']) : 0,
                'default_email_address'    => isset($_POST['default_email_address']) ? sanitize_email($_POST['default_email_address']) : '',
                'app_url' => isset($_POST['app_url']) ? esc_url_raw($_POST['app_url']) : '',
                'avatar_management'       => isset($_POST['avatar_management']) ? sanitize_text_field($_POST['avatar_management']) : 'none',
                'invitation_subject'       => isset($_POST['invitation_subject']) ? sanitize_text_field($_POST['invitation_subject']) : '',
                'invitation_text'          => isset($_POST['invitation_text']) ? sanitize_textarea_field($_POST['invitation_text']) : '',
                'reset_password_subject'   => isset($_POST['reset_password_subject']) ? sanitize_text_field($_POST['reset_password_subject']) : '',
                'reset_password_text'      => isset($_POST['reset_password_text']) ? sanitize_textarea_field($_POST['reset_password_text']) : '',
                'update_password_subject'   => isset($_POST['update_password_subject']) ? sanitize_text_field($_POST['update_password_subject']) : '',
                'update_password_text'      => isset($_POST['update_password_text']) ? sanitize_textarea_field($_POST['update_password_text']) : '',
                'mail_signature'            => isset($_POST['mail_signature']) ? sanitize_textarea_field($_POST['mail_signature']) : '',

            ];
            $this->configuration->update($data);
            echo '<div class="updated"><p>' . esc_html__('Impostazioni salvate', 'res-pong') . '</p></div>';
        }
        $config = $this->configuration->get_all();
        echo '<div class="wrap rp-wrap">';
        echo '<h1>' . esc_html__('Configurazioni', 'res-pong') . '</h1>';
        echo '<form method="post">';
        wp_nonce_field('rp_save_configurations', 'rp_configurations_nonce');
        echo '<table class="form-table">';
        echo '<tr><th><label for="almost_closed_minutes">Minuti alla chiusura</label></th><td><input name="almost_closed_minutes" id="almost_closed_minutes" type="number" value="' . esc_attr($config['almost_closed_minutes']) . '"></td></tr>';
        echo '<tr><th><label for="almost_full_players">Giocatori quasi completi</label></th><td><input name="almost_full_players" id="almost_full_players" type="number" value="' . esc_attr($config['almost_full_players']) . '"></td></tr>';
        echo '<tr><th><label for="max_active_reservations">Max prenotazioni attive</label></th><td><input name="max_active_reservations" id="max_active_reservations" type="number" value="' . esc_attr($config['max_active_reservations']) . '"></td></tr>';
        echo '<tr><th><label for="next_reservation_delay">Ritardo prossima prenotazione</label></th><td><input name="next_reservation_delay" id="next_reservation_delay" type="number" value="' . esc_attr($config['next_reservation_delay']) . '"></td></tr>';
        echo '<tr><th><label for="default_email_address">Email di default</label></th><td><input name="default_email_address" id="default_email_address" type="email" class="regular-text" value="' . esc_attr($config['default_email_address']) . '"></td></tr>';
        echo '<tr><th><label for="app_url">URL Applicazione</label></th><td><input name="app_url" id="app_url" type="text" style="max-width:600px;" class="large-text" value="' . esc_attr($config['app_url']) . '"></td></tr>';
        echo '<tr><th><label for="avatar_management">Gestione Avatar</label></th><td><select name="avatar_management" id="avatar_management"><option value="none"' . selected($config['avatar_management'], 'none', false) . '>Nessuna</option><option value="fitet_monitor"' . selected($config['avatar_management'], 'fitet_monitor', false) . '>Fitet Monitor</option><option value="custom"' . selected($config['avatar_management'], 'custom', false) . '>Personalizzata</option></select></td></tr>';
        echo '<tr><th colspan="2"><h2 style="margin: 0">E-mail primo accesso</h2></th></tr>';
        echo '<tr><th><label for="invitation_subject">Oggetto invito</label></th><td><input name="invitation_subject" id="invitation_subject" type="text" class="large-text" style="max-width:600px;" value="' . esc_attr($config['invitation_subject']) . '"></td></tr>';
        $invitation_settings = [
            'textarea_name' => 'invitation_text',
            'editor_height' => 200,
            'media_buttons' => false,
            'teeny' => true,
            'quicktags' => true,
        ];
        ob_start();
        wp_editor($config['invitation_text'], 'invitation_text', $invitation_settings);
        $invitation_editor = ob_get_clean();
        echo '<tr><th><label for="invitation_text">Testo invito</label></th><td><div style="max-width:600px;">' . $invitation_editor . '</div><p style="font-size:12px;color:#555;margin-top:0;max-width:600px;">Il link di invito sarà aggiunto in coda all\'email. Usa i seguenti placeholder per personalizzare l\'email: #email, #username, #last_name, #first_name, #category</p></td></tr>';
        echo '<tr><th colspan="2"><h2 style="margin: 0">E-mail reset password</h2></th></tr>';
        echo '<tr><th><label for="reset_password_subject">Oggetto reset password</label></th><td><input name="reset_password_subject" id="reset_password_subject" type="text" style="max-width:600px;" class="large-text" value="' . esc_attr($config['reset_password_subject']) . '"></td></tr>';
        $reset_settings = [
            'textarea_name' => 'reset_password_text',
            'editor_height' => 200,
            'media_buttons' => false,
            'teeny' => true,
            'quicktags' => true,
        ];
        ob_start();
        wp_editor($config['reset_password_text'], 'reset_password_text', $reset_settings);
        $reset_editor = ob_get_clean();
        echo '<tr><th><label for="reset_password_text">Testo reset password</label></th><td><div style="max-width:600px;">' . $reset_editor . '</div><p style="font-size:12px;color:#555;margin-top:0;max-width:600px;">Il link di reset password sarà aggiunto in coda all\'email. Usa i seguenti placeholder per personalizzare l\'email: #email, #username, #last_name, #first_name, #category</p></td></tr>';
        echo '<tr><th colspan="2"><h2 style="margin: 0">E-mail aggiornamento password</h2></th></tr>';
        echo '<tr><th><label for="update_password_subject">Oggetto aggiornamento password</label></th><td><input name="update_password_subject" id="update_password_subject" type="text" style="max-width:600px;" class="large-text" value="' . esc_attr($config['update_password_subject']) . '"></td></tr>';
        $update_settings = [
            'textarea_name' => 'update_password_text',
            'editor_height' => 200,
            'media_buttons' => false,
            'teeny' => true,
            'quicktags' => true,
        ];
        ob_start();
        wp_editor($config['update_password_text'], 'update_password_text', $update_settings);
        $update_editor = ob_get_clean();
        echo '<tr><th><label for="update_password_text">Testo aggiornamento password</label></th><td><div style="max-width:600px;">' . $update_editor . '</div><p style="font-size:12px;color:#555;margin-top:0;max-width:600px;">Usa i seguenti placeholder per personalizzare l\'email: #email, #username, #last_name, #first_name, #category</p></td></tr>';
        echo '<tr><th colspan="2"><h2 style="margin: 0">Firma E-mail</h2></th></tr>';
        $signature_settings = [
            'textarea_name' => 'mail_signature',
            'editor_height' => 200,
            'media_buttons' => false,
            'teeny' => true,
            'quicktags' => true,
        ];
        ob_start();
        wp_editor($config['mail_signature'], 'mail_signature', $signature_settings);
        $signature_editor = ob_get_clean();
        echo '<tr><th><label for="mail_signature">Firma Email</label></th><td><div style="max-width:600px;">' . $signature_editor . '</div></td></tr>';
        echo '</table>';
        echo '<p class="submit"><button type="submit" class="button button-primary">' . esc_html__('Salva', 'res-pong') . '</button></p>';
        echo '</form>';
        echo '<h2>' . esc_html__('Reinizializza', 'res-pong') . '</h2>';
        echo '<p>' . esc_html__('Clicca reinizializza per cancellare e ricreare le tabelle del db e reimpostare i parametri di configurazione al default del plugin. L\'esecuzione di questa azione comporta la perdita di tutti i dati, assicurati di aver fatto degli export.', 'res-pong') . '</p>';
        echo '<form method="post" onsubmit="return confirm(\'' . esc_js(__('Sei sicuro di voler reinizializzare? Questa azione cancellerà tutti i dati.', 'res-pong')) . '\');">';
        wp_nonce_field('rp_reinitialize', 'rp_reinitialize_nonce');
        echo '<p class="submit"><button type="submit" class="button rp-button-danger">' . esc_html__('Reinizializza', 'res-pong') . '</button></p>';
        if ($reinitialize_notice !== '') {
            echo $reinitialize_notice;
        }
        echo '</form>';
        echo '</div>';
    }

    // Detail pages
    public function render_user_detail() {
        $id = isset($_GET['id']) ? sanitize_text_field($_GET['id']) : '';
        $editing = !empty($id);
        $config = $this->configuration->get_all();
        echo '<div class="wrap rp-wrap">';
        echo '<h1>' . ($editing ? esc_html__('Modifica utente', 'res-pong') : esc_html__('Aggiungi utente', 'res-pong')) . '</h1>';
        echo '<form id="res-pong-detail-form" data-entity="users" data-id="' . esc_attr($id) . '">';
        echo '<table class="form-table">';
        echo '<tr><th><label for="id">ID</label></th><td><input name="id" id="id" type="text"' . ( $editing ? ' readonly' : '' ) . '></td></tr>';
        echo '<tr><th><label for="email">Email</label></th><td><input name="email" id="email" type="email"></td></tr>';
        echo '<tr><th><label for="username">Nome utente</label></th><td><input name="username" id="username" type="text"></td></tr>';
        echo '<tr><th><label for="first_name">Nome</label></th><td><input name="first_name" id="first_name" type="text"></td></tr>';
        echo '<tr><th><label for="last_name">Cognome</label></th><td><input name="last_name" id="last_name" type="text"></td></tr>';
        echo '<tr><th><label for="category">Categoria</label></th><td><input name="category" id="category" type="text"></td></tr>';
        echo '<tr><th><label for="enabled">Abilitato</label></th><td><input name="enabled" id="enabled" type="checkbox" value="1"' . ( $editing ? '' : ' checked' ) . '></td></tr>';
        echo '</table>';
        echo '<p class="submit"><button type="submit" class="button button-primary">' . esc_html__('Salva', 'res-pong') . '</button>';
        if ($editing) {
            echo ' <button type="button" class="button rp-button-danger" id="res-pong-delete">' . esc_html__('Elimina', 'res-pong') . '</button>';
        }
        echo ' <button type="button" class="button" id="res-pong-impersonate"' . ( $editing ? '' : ' disabled' ) . '>' . esc_html__('Impersona', 'res-pong') . '</button>';
        echo ' <a href="' . esc_url(admin_url('admin.php?page=res-pong-users')) . '" class="button button-secondary" id="res-pong-back">' . esc_html__('Indietro', 'res-pong') . '</a>';
        echo '</p></form>';
        if ($editing) {
            echo '<h2>' . esc_html__('Reset password', 'res-pong') . '</h2>';
            echo '<form id="res-pong-password-form" data-entity="users" data-id="' . esc_attr($id) . '">';
            echo '<table class="form-table">';
            echo '<tr><th><label for="new_password">Nuova password</label></th><td><input name="new_password" id="new_password" type="password"></td></tr>';
            echo '<tr><th><label for="confirm_password">Conferma password</label></th><td><input name="confirm_password" id="confirm_password" type="password"></td></tr>';
            echo '</table>';
            echo '<p class="submit"><button type="submit" class="button button-primary">' . esc_html__('Salva password', 'res-pong') . '</button></p>';
            echo '</form>';
            echo '<div id="rp-invite-wrapper" style="display:none;">';
            echo '<h2>' . esc_html__('Invia Messaggio di Invito', 'res-pong') . '</h2>';
            echo '<p><input type="text" readonly class="large-text" style="max-width: 600px;" id="rp-invite-subject" value="' . esc_attr($config['invitation_subject']) . '"></p>';
            $invite_settings = [
                'textarea_name' => 'rp-invite-text',
                'editor_height' => 200,
                'media_buttons' => false,
                'teeny' => true,
                'quicktags' => true,
            ];
            ob_start();
            wp_editor($config['invitation_text'], 'rp-invite-text', $invite_settings);
            $invite_editor = ob_get_clean();
            echo '<div style="margin-bottom: 0; max-width:600px;">' . $invite_editor . '</div>';
            echo '<p style="font-size:12px;color:#555; margin-top: 0; max-width:600px;">Il link di invito sarà aggiunto in coda all\'email. Usa i seguenti placeholder per personalizzare l\'email: #email, #username, #last_name, #first_name, #category</p>';
            echo '<p><button type="button" class="button button-primary" id="rp-send-invite">' . esc_html__('Invia', 'res-pong') . '</button></p>';
            echo '</div>';
            echo '<div id="rp-reset-wrapper" style="display:none;">';
            echo '<h2>' . esc_html__('Invia link di reset password', 'res-pong') . '</h2>';
            echo '<p><input type="text" readonly class="large-text" style="max-width: 600px;" id="rp-reset-subject" value="' . esc_attr($config['reset_password_subject']) . '"></p>';
            $reset_text_settings = [
                'textarea_name' => 'rp-reset-text',
                'editor_height' => 200,
                'media_buttons' => false,
                'teeny' => true,
                'quicktags' => true,
            ];
            ob_start();
            wp_editor($config['reset_password_text'], 'rp-reset-text', $reset_text_settings);
            $reset_text_editor = ob_get_clean();
            echo '<div style="margin-bottom: 0; max-width:600px;">' . $reset_text_editor . '</div>';
            echo '<p style="font-size:12px;color:#555; margin-top: 0; max-width:600px;">Il link di reset password sarà aggiunto in coda all\'email. Usa i seguenti placeholder per personalizzare l\'email: #email, #username, #last_name, #first_name, #category</p>';
            echo '<p><button type="button" class="button button-primary" id="rp-send-reset">' . esc_html__('Invia', 'res-pong') . '</button></p>';
            echo '</div>';
            $default_timeout = date('Y-m-d\\T00:00:00', strtotime('+7 days'));
            echo '<h2>' . esc_html__('Timeout', 'res-pong') . '</h2>';
            echo '<form id="res-pong-timeout-form" data-entity="users" data-id="' . esc_attr($id) . '">';
            echo '<table class="form-table">';
            echo '<tr><th><label for="timeout">Timeout</label></th><td><input name="timeout" id="timeout" type="datetime-local" step="1" value="' . esc_attr($default_timeout) . '"></td></tr>';
            echo '</table>';
            echo '<p class="submit"><button type="submit" class="button button-primary">' . esc_html__('Salva timeout', 'res-pong') . '</button> <button type="button" class="button" id="rp-remove-timeout">' . esc_html__('Rimuovi timeout', 'res-pong') . '</button></p>';
            echo '</form>';
            echo '<h2>' . esc_html__('Prenotazioni utente', 'res-pong') . '</h2>';
            echo '<table id="res-pong-user-reservations" class="display" data-user="' . esc_attr($id) . '"></table>';
        }
        $this->render_progress_overlay();
        echo '</div>';
    }

    public function render_event_detail() {
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $editing = !empty($id);
        $default_start = date('Y-m-d\\T21:30:00');
        $default_end = date('Y-m-d\\T23:00:00');
        echo '<div class="wrap rp-wrap">';
        echo '<h1>' . ($editing ? esc_html__('Modifica evento', 'res-pong') : esc_html__('Aggiungi evento', 'res-pong')) . '</h1>';
        echo '<form id="res-pong-detail-form" data-entity="events" data-id="' . esc_attr($id) . '">';
        echo '<table class="form-table">';
        echo '<tr><th><label for="group_id">Gruppo</label></th><td><select name="group_id" id="group_id"></select></td></tr>';
        echo '<tr id="recurrence_row"><th><label for="recurrence">Ricorrenza</label></th><td><select name="recurrence" id="recurrence"><option value="none">Mai</option><option value="daily">Giornaliera</option><option value="weekly">Settimanale</option><option value="monthly">Mensile</option></select></td></tr>';
        echo '<tr id="recurrence_end_row"><th><label for="recurrence_end">Termine ricorrenza</label></th><td><input name="recurrence_end" id="recurrence_end" type="date" disabled></td></tr>';
        echo '<tr><th><label for="category">Categoria</label></th><td><input name="category" id="category" type="text"></td></tr>';
        echo '<tr><th><label for="name">Nome</label></th><td><input name="name" id="name" type="text"></td></tr>';
        echo '<tr><th><label for="note">Nota</label></th><td><textarea name="note" id="note"></textarea></td></tr>';
        echo '<tr><th><label for="start_datetime">Inizio</label></th><td><input name="start_datetime" id="start_datetime" type="datetime-local" step="1"' . ( $editing ? '' : ' value="' . esc_attr($default_start) . '"' ) . '></td></tr>';
        echo '<tr><th><label for="end_datetime">Fine</label></th><td><input name="end_datetime" id="end_datetime" type="datetime-local" step="1"' . ( $editing ? '' : ' value="' . esc_attr($default_end) . '"' ) . '></td></tr>';
        echo '<tr><th><label for="max_players">Giocatori max</label></th><td><input name="max_players" id="max_players" type="number"></td></tr>';
        echo '<tr><th><label for="enabled">Abilitato</label></th><td><input name="enabled" id="enabled" type="checkbox" value="1"' . ( $editing ? '' : ' checked' ) . '></td></tr>';
        echo '</table>';
        echo '<p class="submit"><button type="submit" class="button button-primary">' . esc_html__('Salva', 'res-pong') . '</button>';
        if ($editing) {
            echo ' <button type="button" class="button rp-button-danger" id="res-pong-delete">' . esc_html__('Elimina', 'res-pong') . '</button>';
        }
        echo ' <a href="' . esc_url(admin_url('admin.php?page=res-pong-events')) . '" class="button button-secondary" id="res-pong-back">' . esc_html__('Indietro', 'res-pong') . '</a>';
        echo '</p></form>';
        if ($editing) {
            echo '<p><a class="button" href="' . esc_url(admin_url('admin.php?page=res-pong-email&event_id=' . $id)) . '">Contatta i partecipanti</a></p>';
            echo '<h2>' . esc_html__('Prenotazioni evento', 'res-pong') . '</h2>';
            echo '<table id="res-pong-event-reservations" class="display" data-event="' . esc_attr($id) . '"></table>';
        }
        $this->render_progress_overlay();
        echo '</div>';
    }

    public function render_reservation_detail() {
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $editing = !empty($id);
        $default_created = date('Y-m-d\\TH:i:s');
        echo '<div class="wrap rp-wrap">';
        echo '<h1>' . ($editing ? esc_html__('Modifica prenotazione', 'res-pong') : esc_html__('Aggiungi prenotazione', 'res-pong')) . '</h1>';
        echo '<form id="res-pong-detail-form" data-entity="reservations" data-id="' . esc_attr($id) . '">';
        echo '<table class="form-table">';
        echo '<tr><th><label for="user_id">ID utente</label></th><td><select name="user_id" id="user_id"></select></td></tr>';
        echo '<tr><th><label for="event_id">ID evento</label></th><td><select name="event_id" id="event_id"></select></td></tr>';
        echo '<tr><th><label for="created_at">Prenotato il</label></th><td><input name="created_at" id="created_at" type="datetime-local" step="1"' . ( $editing ? '' : ' value="' . esc_attr($default_created) . '"' ) . '></td></tr>';
        echo '<tr><th><label for="presence_confirmed">Presenza confermata</label></th><td><input name="presence_confirmed" id="presence_confirmed" type="checkbox" value="1"></td></tr>';
        echo '</table>';
        echo '<p class="submit"><button type="submit" class="button button-primary">' . esc_html__('Salva', 'res-pong') . '</button>';
        if ($editing) {
            echo ' <button type="button" class="button rp-button-danger" id="res-pong-delete">' . esc_html__('Elimina', 'res-pong') . '</button>';
        }
        echo ' <a href="' . esc_url(admin_url('admin.php?page=res-pong-reservations')) . '" class="button button-secondary" id="res-pong-back">' . esc_html__('Indietro', 'res-pong') . '</a>';
        echo '</p></form>';
        $this->render_progress_overlay();
        echo '</div>';
    }
}

