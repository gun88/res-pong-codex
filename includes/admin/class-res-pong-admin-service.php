<?php

class Res_Pong_Admin_Service {
    private $repository;
    private $configuration;

    public function __construct(Res_Pong_Admin_Repository $repository, Res_Pong_Configuration $configuration) {
        $this->configuration = $configuration;
        $this->repository = $repository;
    }

    // User handlers
    public function rest_get_users() {
        return rest_ensure_response($this->repository->get_users());
    }

    public function rest_get_user($request) {
        $id = $request['id'];
        $user = $this->repository->get_user($id);
        if (!$user) {
            return new WP_Error('not_found', 'Utente non trovato', ['status' => 404]);
        }
        return rest_ensure_response($user);
    }

    public function rest_create_user($request) {
        $data = $request->get_json_params();
        $required = ['id', 'email', 'username', 'first_name', 'last_name'];
        foreach ($required as $field) {
            if (!isset($data[$field]) || trim($data[$field]) === '') {
                return new WP_Error('missing_field', 'Il campo ' . $field . ' è obbligatorio', ['status' => 400]);
            }
        }
        if ($this->repository->get_user($data['id'])) {
            return new WP_Error('user_exists', 'Utente già esistente', ['status' => 409]);
        }
        $inserted = $this->repository->insert_user($data);
        if ($inserted === false) {
            return new WP_Error('insert_failed', 'Creazione utente fallita', ['status' => 500]);
        }
        return new WP_REST_Response($data, 201);
    }

    public function rest_update_user($request) {
        $id = $request['id'];
        $data = $request->get_json_params();
        $this->repository->update_user($id, $data);
        return rest_ensure_response($this->repository->get_user($id));
    }

    public function rest_delete_user($request) {
        $id = $request['id'];
        $this->repository->delete_user($id);
        return new WP_REST_Response(null, 204);
    }

    // Event handlers
    public function rest_get_events($request) {
        $open_only = $request->get_param('open_only');
        $open_only = is_null($open_only) ? true : (bool)intval($open_only);
        return rest_ensure_response($this->repository->get_events($open_only));
    }

    public function rest_get_event($request) {
        $id = (int)$request['id'];
        $event = $this->repository->get_event($id);
        if (!$event) {
            return new WP_Error('not_found', 'Evento non trovato', ['status' => 404]);
        }
        return rest_ensure_response($event);
    }

    public function rest_create_event($request) {
        $data = $request->get_json_params();
        if (!isset($data['name']) || trim($data['name']) === '') {
            return new WP_Error('invalid_data', 'Il nome evento è obbligatorio', ['status' => 400]);
        }
        unset($data['id']);
        $group_id = isset($data['group_id']) && $data['group_id'] !== '' ? (int)$data['group_id'] : null;
        $recurrence = isset($data['recurrence']) ? $data['recurrence'] : 'none';
        $recurrence_end = isset($data['recurrence_end']) ? $data['recurrence_end'] : null;
        if (isset($data['start_datetime'])) {
            $start_dt = new DateTime($data['start_datetime']);
            $data['start_datetime'] = $start_dt->format('Y-m-d H:i:s');
        }
        if (isset($data['end_datetime'])) {
            $end_dt = new DateTime($data['end_datetime']);
            $data['end_datetime'] = $end_dt->format('Y-m-d H:i:s');
        }
        if ($recurrence !== 'none' && $recurrence_end) {
            $limit = new DateTime();
            $limit->add(new DateInterval('P1Y1D'));
            $recurrence_end_dt = new DateTime($recurrence_end);
            if ($recurrence_end_dt > $limit) {
                return new WP_Error('invalid_recurrence_end', 'Il termine di ricorrenza non può superare un anno e un giorno da adesso', ['status' => 400]);
            }
        }
        unset($data['recurrence'], $data['recurrence_end']);
        if ($group_id) {
            $group_event = $this->repository->get_event($group_id);
            if ($group_event) {
                if ($group_event['group_id'] === null) {
                    $this->repository->update_event($group_id, ['group_id' => 0]);
                } elseif ((int)$group_event['group_id'] > 0) {
                    $group_id = (int)$group_event['group_id'];
                }
            }
            $data['group_id'] = $group_id;
            $id = $this->repository->insert_event($data);
            $data['id'] = $id;
            return new WP_REST_Response($data, 201);
        }
        $data['group_id'] = null;
        $id = $this->repository->insert_event($data);
        $data['id'] = $id;
        if ($recurrence !== 'none' && $recurrence_end) {
            $this->repository->update_event($id, ['group_id' => 0]);
            $start = new DateTime($data['start_datetime']);
            $end = new DateTime($data['end_datetime']);
            $limit = new DateTime($recurrence_end . ' 23:59:59');
            switch ($recurrence) {
                case 'daily':
                    $interval = new DateInterval('P1D');
                    break;
                case 'weekly':
                    $interval = new DateInterval('P1W');
                    break;
                case 'monthly':
                    $interval = new DateInterval('P1M');
                    break;
                default:
                    $interval = null;
                    break;
            }
            if ($interval) {
                while (true) {
                    $start->add($interval);
                    $end->add($interval);
                    if ($start > $limit) {
                        break;
                    }
                    $e = $data;
                    unset($e['id']);
                    $e['start_datetime'] = $start->format('Y-m-d H:i:s');
                    $e['end_datetime'] = $end->format('Y-m-d H:i:s');
                    $e['group_id'] = $id;
                    $this->repository->insert_event($e);
                }
            }
            $data['group_id'] = 0;
        }
        return new WP_REST_Response($data, 201);
    }

    public function rest_update_event($request) {
        $id = (int)$request['id'];
        unset($request['recurrence'], $request['recurrence_end']);
        $data = $request->get_json_params();
        if (array_key_exists('group_id', $data)) {
            $group_id = $data['group_id'] !== '' ? (int)$data['group_id'] : null;
            if ($group_id) {
                $group_event = $this->repository->get_event($group_id);
                if ($group_event) {
                    if ($group_event['group_id'] === null) {
                        $this->repository->update_event($group_id, ['group_id' => 0]);
                    } elseif ((int)$group_event['group_id'] > 0) {
                        $group_id = (int)$group_event['group_id'];
                    }
                }
                $data['group_id'] = $group_id;
            } else {
                $data['group_id'] = null;
            }
        }
        $apply = $request->get_param('apply_group');
        if ($apply) {
            $event = $this->repository->get_event($id);
            if ($event) {
                $gid = null;
                if ($event['group_id'] === 0) {
                    $gid = $id;
                } elseif ($event['group_id']) {
                    $gid = $event['group_id'];
                }
                if ($gid) {
                    $this->repository->update_event($gid, $data);
                    $this->repository->update_events_by_group($gid, $data);
                } else {
                    $this->repository->update_event($id, $data);
                }
            }
        } else {
            $this->repository->update_event($id, $data);
        }
        return rest_ensure_response($this->repository->get_event($id));
    }

    public function rest_delete_event($request) {
        $id = (int)$request['id'];
        $apply = $request->get_param('apply_group');
        if ($apply) {
            $event = $this->repository->get_event($id);
            if ($event) {
                $gid = null;
                if ($event['group_id'] === 0) {
                    $gid = $id;
                } elseif ($event['group_id']) {
                    $gid = $event['group_id'];
                }
                if ($gid) {
                    $this->repository->delete_events_by_group($gid);
                    $this->repository->delete_event($gid);
                } else {
                    $this->repository->delete_event($id);
                }
            }
        } else {
            $this->repository->delete_event($id);
        }
        return new WP_REST_Response(null, 204);
    }

    // Reservation handlers
    public function rest_get_reservations($request) {
        $user_id = $request->get_param('user_id');
        $event_id = $request->get_param('event_id');
        $active_only = $request->get_param('active_only');
        $active_only = is_null($active_only) ? true : (bool)intval($active_only);
        return rest_ensure_response($this->repository->get_reservations($user_id, $event_id, $active_only));
    }

    public function rest_invite_user($request) {
        $id = $request['id'];
        $user = $this->repository->get_user($id);
        if (!$user) {
            return new WP_Error('not_found', 'Utente non trovato', ['status' => 404]);
        }
        $token = Res_Pong_Util::generate_reset_token(MONTH_IN_SECONDS);
        $this->repository->update_user($id, ['reset_token' => $token]);

        $url = $this->configuration->get('app_url') . '/#/first-access?token=' . Res_Pong_Util::base64url_encode($token);
        $params = $request->get_json_params();
        $text = (is_array($params) && isset($params['text']) && $params['text'] !== '') ? $params['text'] : $this->configuration->get('invitation_text');
        $placeholders = ['#email', '#username', '#last_name', '#first_name', '#category'];
        $replacements = [$user['email'], $user['username'], $user['last_name'], $user['first_name'], $user['category']];
        $text = str_replace($placeholders, $replacements, $text);
        $message = $text . "\n\nClicca qui: " . $url;
        $subject = $this->configuration->get('invitation_subject');
        wp_mail($user['email'], $subject, $message);
        return new WP_REST_Response(['success' => true], 200);
    }

    public function rest_reset_password($request) {
        $id = $request['id'];
        $user = $this->repository->get_user($id);
        if (!$user) {
            return new WP_Error('not_found', 'Utente non trovato', ['status' => 404]);
        }
        $params = $request->get_json_params();
        $password = (is_array($params) && isset($params['password'])) ? $params['password'] : '';
        if (!empty($password)) {
            if (strlen($password) < 6) {
                return new WP_Error('invalid_password', 'Password must be at least 6 characters', ['status' => 400]);
            }
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $this->repository->update_user($id, ['password' => $hashed, 'reset_token' => null]);
            return new WP_REST_Response(['success' => true], 200);
        }
        $token = Res_Pong_Util::generate_reset_token();
        $this->repository->update_user($id, ['reset_token' => $token]);
        $url = $this->configuration->get('app_url') . '/#/password-update?token=' . Res_Pong_Util::base64url_encode($token);
        $text = (is_array($params) && isset($params['text']) && $params['text'] !== '') ? $params['text'] : $this->configuration->get('reset_password_text');
        $placeholders = ['#email', '#username', '#last_name', '#first_name', '#category'];
        $replacements = [$user['email'], $user['username'], $user['last_name'], $user['first_name'], $user['category']];
        $text = str_replace($placeholders, $replacements, $text);
        $message = $text . "\n\nClicca qui: " . $url;
        $subject = $this->configuration->get('reset_password_subject');
        wp_mail($user['email'], $subject, $message);
        return new WP_REST_Response(['success' => true], 200);
    }

    public function rest_impersonate_user($request) {
        $id = $request['id'];
        $user = (object) $this->repository->get_user($id);
        if (!$user) {
            return new WP_Error('not_found', 'Utente non trovato', ['status' => 404]);
        }
        $ttl = HOUR_IN_SECONDS;
        $token = Res_Pong_Util::res_pong_token_make((int)$user->id, $ttl);
        Res_Pong_Util::res_pong_clear_cookie();
        Res_Pong_Util::res_pong_set_cookie($token, $ttl);
        Res_Pong_Util::adjust_user($user);
        return new WP_REST_Response(['url' => $this->configuration->get('app_url'), 'user' => $user], 200);
    }

    public function rest_send_email($request) {
        $params = $request->get_json_params();
        $subject = isset($params['subject']) ? sanitize_text_field($params['subject']) : '';
        $text = isset($params['text']) ? sanitize_textarea_field($params['text']) : '';
        $recipients = isset($params['recipients']) && is_array($params['recipients']) ? array_filter(array_map('sanitize_email', $params['recipients'])) : [];
        if ($subject === '' || $text === '' || empty($recipients)) {
            return new WP_Error('invalid_data', 'Oggetto, testo e destinatari sono obbligatori', ['status' => 400]);
        }
        $placeholders = ['#email', '#username', '#last_name', '#first_name', '#category'];
        if (count($recipients) === 1) {
            $email = $recipients[0];
            $user = $this->repository->find_user_by_email($email);
            $message = $text;
            if ($user) {
                $replacements = [$user['email'], $user['username'], $user['last_name'], $user['first_name'], $user['category']];
                $message = str_replace($placeholders, $replacements, $text);
            } else {
                $message = str_replace($placeholders, '', $text);
            }
            wp_mail($email, $subject, $message);
        } else {
            $message = str_replace($placeholders, '', $text);
            $headers = ['Bcc: ' . implode(',', $recipients)];
            $to = $this->configuration->get('default_email_address');
            wp_mail($to, $subject, $message, $headers);
        }
        return rest_ensure_response(['success' => true]);
    }

    public function rest_get_reservation($request) {
        $id = (int)$request['id'];
        $reservation = $this->repository->get_reservation($id);
        if (!$reservation) {
            return new WP_Error('not_found', 'Prenotazione non trovata', ['status' => 404]);
        }
        return rest_ensure_response($reservation);
    }

    public function rest_create_reservation($request) {
        $data = $request->get_json_params();
        if ($this->repository->find_reservation($data['user_id'], $data['event_id'])) {
            return new WP_Error('reservation_exists', 'Prenotazione già esistente', ['status' => 409]);
        }
        $id = $this->repository->insert_reservation($data);
        if ($id === false) {
            return new WP_Error('insert_failed', 'Creazione prenotazione fallita', ['status' => 500]);
        }
        $data['id'] = $id;
        error_log("Inserted reservation: " . print_r($data, true));
        return new WP_REST_Response($data, 201);
    }

    public function rest_update_reservation($request) {
        $id = (int)$request['id'];
        $data = $request->get_json_params();
        $this->repository->update_reservation($id, $data);
        return rest_ensure_response($this->repository->get_reservation($id));
    }

    public function rest_delete_reservation($request) {
        $id = (int)$request['id'];
        $this->repository->delete_reservation($id);
        return new WP_REST_Response(null, 204);
    }

    public function rest_export_users() {
        $csv = $this->repository->export_users_csv();
        nocache_headers();
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="users.csv"');
        echo $csv;
        exit;
    }

    public function rest_import_users($request) {
        $params = $request->get_json_params();
        $csv = $params['csv'] ?? '';
        if ($csv === '') {
            return new WP_Error('no_data', 'Nessun dato ricevuto', ['status' => 400]);
        }
        $result = $this->repository->import_users_csv($csv);
        if (is_wp_error($result)) {
            return $result;
        }
        if ($result === false) {
            return new WP_Error('import_failed', 'Importazione utenti fallita', ['status' => 500]);
        }
        $skipped = is_array($result) ? $result : [];
        return new WP_REST_Response(['success' => true, 'skipped' => $skipped], 200);
    }

    public function rest_export_events() {
        $csv = $this->repository->export_events_csv();
        nocache_headers();
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="events.csv"');
        echo $csv;
        exit;
    }

    public function rest_import_events($request) {
        $params = $request->get_json_params();
        $csv = $params['csv'] ?? '';
        if ($csv === '') {
            return new WP_Error('no_data', 'Nessun dato ricevuto', ['status' => 400]);
        }
        $result = $this->repository->import_events_csv($csv);
        if (is_wp_error($result)) {
            return $result;
        }
        if (!$result) {
            return new WP_Error('import_failed', 'Importazione eventi fallita', ['status' => 500]);
        }
        return new WP_REST_Response(['success' => true], 200);
    }

    public function rest_export_reservations() {
        $csv = $this->repository->export_reservations_csv();
        nocache_headers();
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="reservations.csv"');
        echo $csv;
        exit;
    }

    public function rest_import_reservations($request) {
        $params = $request->get_json_params();
        $csv = $params['csv'] ?? '';
        if ($csv === '') {
            return new WP_Error('no_data', 'Nessun dato ricevuto', ['status' => 400]);
        }
        $result = $this->repository->import_reservations_csv($csv);
        if (is_wp_error($result)) {
            return $result;
        }
        if (!$result) {
            return new WP_Error('import_failed', 'Importazione prenotazioni fallita', ['status' => 500]);
        }
        return new WP_REST_Response(['success' => true], 200);
    }

}
