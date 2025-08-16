<?php

class Res_Pong_User_Service {
    private $repository;
    private $configuration;

    public function __construct(Res_Pong_User_Repository $repository, Res_Pong_Configuration $configuration) {
        $this->configuration = $configuration;
        $this->repository = $repository;
    }

    public function get_events($request) {
        $start = $request->get_param('start');
        $end = $request->get_param('end');
        $user_id = $this->res_pong_get_logged_user_id();
        $events = $this->repository->get_events($start, $end, $user_id);
        foreach ($events as $event) {
            $event->event_countdown_minutes = $this->minutes_until($event->start_datetime);
            $event->status = $this->calculate_event_status($event);
        }
        return rest_ensure_response($events);
    }

    public function get_user_reservations_for_logged_user() {
        $user_id = $this->res_pong_get_logged_user_id();
        $reservations_by_user_id = $this->repository->get_reservations_by_user_id($user_id);
        return rest_ensure_response($reservations_by_user_id);
    }

    public function get_event_for_logged_user($request) {
        $event_id = $request->get_param('event_id');
        $user_id = $this->res_pong_get_logged_user_id();
        $event = $this->_get_event_for_logged_user($event_id, $user_id);
        return rest_ensure_response($event);
    }

    public function create_user_reservations_for_logged_user($request) {
        $event_id = $request->get_param('event_id');
        $user_id = $this->res_pong_get_logged_user_id();
        $event = $this->_get_event_for_logged_user($event_id, $user_id);

        if ($event->can_join) {
            $created_at = date('Y-m-d H:i:s');
            $this->repository->insert_reservation(['event_id' => $event_id, 'user_id' => $user_id, 'created_at' => $created_at]);
            $event = $this->_get_event_for_logged_user($event_id, $user_id);
        } else {
            if (!empty($event->status_message)) {
                $event->status_message['text'] = 'Impossibile completare la prenotazione. ' . $event->status_message['text'];
                $event->status_message['type'] = 'error';
            }
        }
        return rest_ensure_response($event);
    }

    public function delete_user_reservations_for_logged_user($request) {
        $event_id = $request->get_param('event_id');
        $user_id = $this->res_pong_get_logged_user_id();
        $event = $this->_get_event_for_logged_user($event_id, $user_id);

        if ($event->can_remove) {
            $this->repository->delete_reservation_by_user_and_event($user_id, $event_id);
            $event = $this->_get_event_for_logged_user($event_id, $user_id);
        } else {
            if (!empty($event->status_message)) {
                $event->status_message['text'] = 'Impossibile cancellare la prenotazione. ' . $event->status_message['text'];
                $event->status_message['type'] = 'error';
            }
        }
        return rest_ensure_response($event);
    }

    public function login($request) {
        $username = strtolower($request->get_param('username'));
        $password = $request->get_param('password');
        $remember = $request->get_param('remember');

        $user = $this->repository->get_enabled_user_by_username_or_email($username);
        if ($user && $user->enabled == 0) {
            return new \WP_REST_Response(['success' => false, 'error' => 'Utente disabilitato.', 'user' => null,], 403);
        }
        if (!$user || !password_verify($password, $user->password)) {
            return new \WP_REST_Response(['success' => false, 'error' => 'Username o password non validi.', 'user' => null,], 401);
        }

        $ttl = $remember ? 60 * 60 * 24 * 365 * 5 : 60 * 60 * 12; // 5 anni vs 12 ore
        $token = Res_Pong_Util::res_pong_token_make((int)$user->id, $ttl);
        Res_Pong_Util::res_pong_set_cookie($token, $ttl);
        Res_Pong_Util::adjust_user($user);
        $this->load_avatar($user);

        return new \WP_REST_Response(['success' => true, 'error' => null, 'user' => $user], 200);

    }

    public function logout() {
        Res_Pong_Util::res_pong_clear_cookie();
        return new \WP_REST_Response(['success' => true], 200);
    }

    public function password_update_by_token(\WP_REST_Request $req) {

        try {
            $token = Res_Pong_Util::base64url_decode($req->get_param('token'));
        } catch (Exception $e) {
            return new \WP_REST_Response(['success' => false, 'message' => 'Link scaduto'], 400);
        }

        list($ts, $rand) = explode('|', $token);
        if (time() > intval($ts)) {
            return new \WP_REST_Response(['success' => false, 'message' => 'Link scaduto'], 400);
        }
        $user = $this->repository->get_enabled_user_by_token($token);
        if ($user === null) {
            return new \WP_REST_Response(['success' => false, 'message' => 'Link scaduto'], 400);
        }
        return $this->password_update($req, $user);

    }

    public function password_update_logged_user($req) {
        $user_id = $this->res_pong_get_logged_user_id();
        $user = $this->repository->get_user_by_id($user_id);
        return $this->password_update($req, $user);
    }

    public function password_reset(\WP_REST_Request $req) {
        $email = trim((string)$req->get_param('email'));
        if (empty($email) || !is_email($email)) {
            return new \WP_REST_Response(['success' => false, 'error' => 'Email non valida'], 400);
        }

        $user = $this->repository->get_enabled_user_by_email($email);
        if ($user) {
            $token = $this->generate_reset_token();
            $this->repository->update_user_token($user->id, $token);
            $url = $this->configuration->get('app_url') . '/#/password-update?token=' . Res_Pong_Util::base64url_encode($token);
            $text = $this->configuration->get('reset_password_text');
            $placeholders = ['#email', '#username', '#last_name', '#first_name', '#category'];
            $replacements = [$user->email, $user->username, $user->last_name, $user->first_name, $user->category];
            $text = str_replace($placeholders, $replacements, $text);
            $message = $text . "\n\nClicca qui: " . $url;
            $subject = $this->configuration->get('reset_password_subject');
            wp_mail($email, $subject, $message);
        }
        // Rispondi comunque success per non rivelare se l'utente esiste
        return new \WP_REST_Response(['success' => true], 200);
    }

    public function get_logged_user() {
        $user_id = $this->res_pong_get_logged_user_id();
        $user = $this->repository->get_user_by_id($user_id);
        if (!$user->enabled) {
            return new \WP_REST_Response('Utente disabilitato', 401);
        }
        Res_Pong_Util::adjust_user($user);
        $this->load_avatar($user);
        return rest_ensure_response($user);
    }

    public function get_user_by_token(\WP_REST_Request $req) {
        try {
            $token = Res_Pong_Util::base64url_decode($req->get_param('token'));
        } catch (Exception $e) {
            return new \WP_REST_Response(['success' => false, 'message' => 'Link scaduto'], 400);
        }

        list($ts, $rand) = explode('|', $token);
        if (time() > intval($ts)) {
            return new \WP_REST_Response(['success' => false, 'message' => 'Link scaduto'], 400);
        }
        $user = $this->repository->get_enabled_user_by_token($token);
        if ($user === null) {
            return new \WP_REST_Response(['success' => false, 'message' => 'Link scaduto'], 400);
        }

        Res_Pong_Util::adjust_user($user);
        $this->load_avatar($user);
        return rest_ensure_response($user);
    }

    public function res_pong_get_logged_user_id() {
        $t = $_COOKIE[RES_PONG_COOKIE_NAME] ?? null;
        if (!$t) return null;
        $uid = Res_Pong_Util::token_parse($t);
        return $uid ?: null;
    }

    // ---------------------------------------------------------------

    private function load_avatar($user) {
        $user->avatar = null;
        if ($this->configuration->get('avatar_management') === 'fitet_monitor') {
            $fitet_id = $this->repository->get_fitet_monitor_id($user->id);
            foreach (['jpg', 'jpeg', 'png', 'JPG', 'JPEG', 'PNG'] as $extension) {
                $candidate = ABSPATH . "wp-content/uploads/fitet-monitor/players/$fitet_id.$extension";
                if (file_exists($candidate)) {
                    $user->avatar = get_site_url()."/wp-content/uploads/fitet-monitor/players/$fitet_id.$extension";
                    return;
                }
            }
        }
    }

    private function generate_reset_token() {
        $expires = time() + 3600; // scade tra 1 ora
        $random = bin2hex(random_bytes(16));
        $token = $expires . '|' . $random;
        return $token;
    }

    private function _get_event_for_logged_user($event_id, int $user_id) {
        $event = $this->repository->get_event_by_id($event_id);
        if (!$event) {
            return new \WP_REST_Response(['error' => 'Evento non trovato'], 404);
        }
        $user = $this->repository->get_user_by_id_with_active_reservations($user_id, date('Y-m-d H:i:s'), $event->group_id);
        $players = $this->repository->get_reservations_by_event_id_with_user_data($event_id);
        foreach ($players as $player) {
            $player->current_user = $user_id == $player->user_id;
            $this->load_avatar($player);
        }
        $other_events = $this->repository->get_next_and_previous_event($event_id, $event->start_datetime);
        if (!empty($other_events)) {
            $other_events = $other_events[0];
        } else {
            $other_events = [];
        }
        $event->players = $players;
        $event->players_count = count($players);;
        $event->other_events = $other_events;
        $event->event_countdown_minutes = $this->minutes_until($event->start_datetime);
        $event->status = $this->calculate_event_status($event);
        $event->user_status = $this->calculate_user_status($user, $event);
        $event->booked = $this->calculate_reservation_status($event);

        $out = $this->decide_event($event, $user);

        $event->status_message = $out['status_message'];
        $event->can_join = $out['can_join'];
        $event->can_remove = $out['can_remove'];
        return $event;
    }

    private function password_update(\WP_REST_Request $req, $user) {

        $password = $req->get_param('password');
        $confirm = $req->get_param('confirm');

        if (empty($password)) {
            return new \WP_REST_Response(['success' => false, 'message' => 'La password deve essere lunga almeno 6 caratteri'], 400);
        } else if ($password !== $confirm) {
            return new \WP_REST_Response(['success' => false, 'message' => 'Le password non coincidono'], 400);
        } else if (strlen($password) < 6) {
            return new \WP_REST_Response(['success' => false, 'message' => 'La password deve essere lunga almeno 6 caratteri'], 400);
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $this->repository->update_user_password($user->id, $hashed_password);
            $this->repository->update_user_token($user->id, null);
            return new \WP_REST_Response(['success' => true, 'message' => 'Password aggiornata'], 200);
        }
    }

    private function minutes_until($eventDate): int {
        // Create DateTime object for the event
        $event = DateTime::createFromFormat('Y-m-d H:i:s', $eventDate);

        // If the date format is invalid, return 0
        if (!$event) {
            return 0;
        }

        $now = new DateTime();

        // Difference in seconds
        $diffSeconds = $event->getTimestamp() - $now->getTimestamp();

        // Convert to whole minutes
        return (int)floor($diffSeconds / 60);
    }

    private function calculate_event_status($event) {
        // stati evento: closed, almost-closed, disabled, available, almost-full, full
        if ($event->event_countdown_minutes < 0) return 'closed';
        if (empty($event->enabled)) return 'disabled';
        if ($event->event_countdown_minutes < $this->configuration->get('almost_closed_minutes')) return 'almost-closed';
        if (empty($event->max_players)) return 'available'; // Nessun limite giocatori → disponibile
        if ($event->players_count >= $event->max_players) return 'full';
        if ($event->players_count >= $event->max_players - $this->configuration->get('almost_full_players')) return 'almost-full';
        return 'available';
    }

    private function calculate_user_status($user, $event) {
        // stato utente: disabled, timeout, max-booking-reached, enabled
        if (!$user->enabled) return 'disabled';
        if (!empty ($event->category) && strpos(strtolower($event->category), strtolower($user->category)) === false) return 'out-of-category';
        if ($user->active_reservations >= $this->configuration->get('max_active_reservations')) return 'max-booking-reached';
        if ($this->minutes_until($user->timeout) > 0) return 'timeout';
        return 'enabled';
    }

    private function calculate_reservation_status($event) {
        // stato prenotazione:  booked, not-booked
        // Se già registrato o l'utente corrente è tra i giocatori
        return ((!empty($event->players) && array_filter($event->players, function ($p) {
                return !empty($p->current_user);
            })));
    }

    private function decide_event($event, $user) {
        $status_message = null;
        $can_join = false;
        $can_remove = false;
        if ($event->user_status == 'disabled') {
            // se evento chiuso... messaggio e no azioni
            $status_message = ['type' => 'error', 'text' => 'Utente disabilitato.'];
        } else if ($event->status == 'closed') {
            // se evento chiuso... messaggio e no azioni
            $status_message = ['type' => 'secondary', 'text' => 'Evento terminato.'];
        } else if ($event->status == 'disabled') {
            // se evento disabilitato... messaggio e no azioni
            $status_message = ['type' => 'contrast', 'text' => 'Evento disabilitato.',];
        } else if ($event->status == 'almost-closed') {
            // e manca poco... messaggio e no azioni
            $status_message = ['type' => 'warn', 'text' => 'Manca poco all\'inizio dell\'evento. Per comunicazioni urgenti, contattare il responsabile.'];
        } else if ($event->booked) { // utente già prenotato, vediamo se possiamo concedere la cancellazione
            // se prenotato
            // do la possibilità di cancellare la prenotazione. Azione 'cancella' e messaggio
            $can_remove = true;
            $status_message = ['type' => 'success', 'text' => 'La tua prenotazione è attiva'];
        } else if ($event->user_status == 'out-of-category') { // IMPORTANTE! Da qui in poi lo user è sempre not-booked. Vediamo se può prenotare
            // se fuori categoria... messaggio e no azioni
            $status_message = ['type' => 'warn', 'text' => 'Evento riservato alle categorie: ' . $event->category];
        } else if ($event->status == 'full') {
            // evento al completo, inutile approfondire... messaggio e no azioni
            $status_message = ['type' => 'info', 'text' => "Evento al completo."];;
        } else if ($event->user_status == 'max-booking-reached') {
            // max numero prenotazioni raggiunto... messaggio e no azioni
            $active_reservations = $user->active_reservations;
            $status_message = ['type' => 'warn', 'text' => "Hai già " . ($active_reservations == 1 ? "una prenotazione" : "$active_reservations prenotazioni") . " attiva in un altra data per questa tipologia di evento. Non puoi effettuare altre prenotazioni."];
        } else if ($event->user_status == 'timeout') {
            // utente in castigo 😂... messaggio e no azioni
            $status_message = ['type' => 'warn', 'text' => "Sei in timeout! Potrai effettuare di nuovo la prenotazione solo dopo questa data: " . $user->timeout . "."];
        } else {
            // qui arriva (o dovrebbe arrivare se ho fatto bene i conti solo se:
            //  - l'utente non ha gi' prenotato questo evento
            //  - l'utente non ha blocchi (timeout, out-of-category, max-booking-reached, o disabled)
            //  - l'evento ha posti disponibili (available o almost-full)
            // do la possibilità di effettuare la prenotazione. Azione 'Prenota' e nessun messaggio
            $can_join = true;
        }
        return [
            'can_join' => $can_join,
            'can_remove' => $can_remove,
            'status_message' => $status_message
        ];
    }

    /*private function test() {
        $matrix = [
            // event-status   user-status           booked   can_join  can_remove  message
            ['closed', 'disabled', true, false, false, 'Utente disabilitato.'],
            ['closed', 'disabled', false, false, false, 'Utente disabilitato.'],
            ['closed', 'out-of-category', true, false, false, 'Evento terminato.'],
            ['closed', 'out-of-category', false, false, false, 'Evento terminato.'],
            ['closed', 'timeout', true, false, false, 'Evento terminato.'],
            ['closed', 'timeout', false, false, false, 'Evento terminato.'],
            ['closed', 'max-booking-reached', true, false, false, 'Evento terminato.'],
            ['closed', 'max-booking-reached', false, false, false, 'Evento terminato.'],
            ['closed', 'enabled', true, false, false, 'Evento terminato.'],
            ['closed', 'enabled', false, false, false, 'Evento terminato.'],

            ['almost-closed', 'disabled', true, false, false, 'Utente disabilitato.'],
            ['almost-closed', 'disabled', false, false, false, 'Utente disabilitato.'],
            ['almost-closed', 'out-of-category', true, false, false, 'Manca poco all\'inizio dell\'evento. Per comunicazioni urgenti, contattare il responsabile.'],
            ['almost-closed', 'out-of-category', false, false, false, 'Manca poco all\'inizio dell\'evento. Per comunicazioni urgenti, contattare il responsabile.'],
            ['almost-closed', 'timeout', true, false, false, 'Manca poco all\'inizio dell\'evento. Per comunicazioni urgenti, contattare il responsabile.'],
            ['almost-closed', 'timeout', false, false, false, 'Manca poco all\'inizio dell\'evento. Per comunicazioni urgenti, contattare il responsabile.'],
            ['almost-closed', 'max-booking-reached', true, false, false, 'Manca poco all\'inizio dell\'evento. Per comunicazioni urgenti, contattare il responsabile.'],
            ['almost-closed', 'max-booking-reached', false, false, false, 'Manca poco all\'inizio dell\'evento. Per comunicazioni urgenti, contattare il responsabile.'],
            ['almost-closed', 'enabled', true, false, false, 'Manca poco all\'inizio dell\'evento. Per comunicazioni urgenti, contattare il responsabile.'],
            ['almost-closed', 'enabled', false, false, false, 'Manca poco all\'inizio dell\'evento. Per comunicazioni urgenti, contattare il responsabile.'],

            ['disabled', 'disabled', true, false, false, 'Utente disabilitato.'],
            ['disabled', 'disabled', false, false, false, 'Utente disabilitato.'],
            ['disabled', 'out-of-category', true, false, false, 'Evento disabilitato.'],
            ['disabled', 'out-of-category', false, false, false, 'Evento disabilitato.'],
            ['disabled', 'timeout', true, false, false, 'Evento disabilitato.'],
            ['disabled', 'timeout', false, false, false, 'Evento disabilitato.'],
            ['disabled', 'max-booking-reached', true, false, false, 'Evento disabilitato.'],
            ['disabled', 'max-booking-reached', false, false, false, 'Evento disabilitato.'],
            ['disabled', 'enabled', true, false, false, 'Evento disabilitato.'],
            ['disabled', 'enabled', false, false, false, 'Evento disabilitato.'],

            ['available', 'disabled', true, false, false, 'Utente disabilitato.'],
            ['available', 'disabled', false, false, false, 'Utente disabilitato.'],
            ['available', 'out-of-category', true, false, true, null],
            ['available', 'out-of-category', false, false, false, 'Evento riservato alle categorie: VIP'],
            ['available', 'timeout', true, false, true, null],
            ['available', 'timeout', false, false, false, 'Sei in timeout! Potrai effettuare di nuovo la prenotazione solo dopo questa data: 2030-01-01.'],
            ['available', 'max-booking-reached', true, false, true, null],
            ['available', 'max-booking-reached', false, false, false, 'Hai raggiunto il numero massimo di prenotazioni per questa tipologia di evento.'],
            ['available', 'enabled', true, false, true, null],
            ['available', 'enabled', false, true, false, null],

            ['almost-full', 'disabled', true, false, false, 'Utente disabilitato.'],
            ['almost-full', 'disabled', false, false, false, 'Utente disabilitato.'],
            ['almost-full', 'out-of-category', true, false, true, null],
            ['almost-full', 'out-of-category', false, false, false, 'Evento riservato alle categorie: VIP'],
            ['almost-full', 'timeout', true, false, true, null],
            ['almost-full', 'timeout', false, false, false, 'Sei in timeout! Potrai effettuare di nuovo la prenotazione solo dopo questa data: 2030-01-01.'],
            ['almost-full', 'max-booking-reached', true, false, true, null],
            ['almost-full', 'max-booking-reached', false, false, false, 'Hai raggiunto il numero massimo di prenotazioni per questa tipologia di evento.'],
            ['almost-full', 'enabled', true, false, true, null],
            ['almost-full', 'enabled', false, true, false, null],

            ['full', 'disabled', true, false, false, 'Utente disabilitato.'],
            ['full', 'disabled', false, false, false, 'Utente disabilitato.'],
            ['full', 'out-of-category', true, false, true, null],
            ['full', 'out-of-category', false, false, false, 'Evento riservato alle categorie: VIP'],
            ['full', 'timeout', true, false, true, null],
            ['full', 'timeout', false, false, false, 'Evento al completo.'],
            ['full', 'max-booking-reached', true, false, true, null],
            ['full', 'max-booking-reached', false, false, false, 'Evento al completo.'],
            ['full', 'enabled', true, false, true, null],
            ['full', 'enabled', false, false, false, 'Evento al completo.'],
        ];

        foreach ($matrix as $row) {
            $event = new \stdClass();
            $event->status = $row[0];
            $event->user_status = $row[1];
            $event->booked = $row[2];
            $event->category = 'VIP';
            $user = new \stdClass();
            $user->timeout = '2030-01-01';

            $decide_event = $this->decide_event($event, $user);
            if ($decide_event['can_join'] != $row[3] || $decide_event['can_remove'] != $row[4] || $decide_event['status_message']['text'] != $row[5]) {
                error_log("error at " . json_encode($row) . " " . json_encode($decide_event));
            }

        }
        error_log("done___________________________");
        die(1);
    }*/


}
