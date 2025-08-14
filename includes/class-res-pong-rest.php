<?php

class Res_Pong_Rest {
    private $repository;

    public function __construct($repository) {
        $this->repository = $repository;
        add_action('rest_api_init', [ $this, 'register_routes' ]);
    }

    public function register_routes() {
        $namespace = 'res-pong/v1';

        // Users
        register_rest_route($namespace, '/users', [
            'methods'  => 'GET',
            'callback' => [ $this, 'rest_get_users' ],
            'permission_callback' => function () { return current_user_can('manage_options'); },
        ]);
        register_rest_route($namespace, '/users', [
            'methods'  => 'POST',
            'callback' => [ $this, 'rest_create_user' ],
            'permission_callback' => function () { return current_user_can('manage_options'); },
        ]);
        register_rest_route($namespace, '/users/export', [
            'methods'  => 'GET',
            'callback' => [ $this, 'rest_export_users' ],
            'permission_callback' => function () { return current_user_can('manage_options'); },
        ]);
        register_rest_route($namespace, '/users/import', [
            'methods'  => 'POST',
            'callback' => [ $this, 'rest_import_users' ],
            'permission_callback' => function () { return current_user_can('manage_options'); },
        ]);
        register_rest_route($namespace, '/users/(?P<id>[\w-]+)', [
            'methods'  => 'GET',
            'callback' => [ $this, 'rest_get_user' ],
            'permission_callback' => function () { return current_user_can('manage_options'); },
        ]);
        register_rest_route($namespace, '/users/(?P<id>[\w-]+)', [
            'methods'  => 'PUT',
            'callback' => [ $this, 'rest_update_user' ],
            'permission_callback' => function () { return current_user_can('manage_options'); },
        ]);
        register_rest_route($namespace, '/users/(?P<id>[\w-]+)', [
            'methods'  => 'DELETE',
            'callback' => [ $this, 'rest_delete_user' ],
            'permission_callback' => function () { return current_user_can('manage_options'); },
        ]);
        register_rest_route($namespace, '/users/(?P<id>[\w-]+)/invite', [
            'methods'  => 'POST',
            'callback' => [ $this, 'rest_invite_user' ],
            'permission_callback' => function () { return current_user_can('manage_options'); },
        ]);
        register_rest_route($namespace, '/users/(?P<id>[\w-]+)/reset-password', [
            'methods'  => 'POST',
            'callback' => [ $this, 'rest_reset_password' ],
            'permission_callback' => function () { return current_user_can('manage_options'); },
        ]);

        // Events
        register_rest_route($namespace, '/events', [
            'methods'  => 'GET',
            'callback' => [ $this, 'rest_get_events' ],
            'permission_callback' => function () { return current_user_can('manage_options'); },
        ]);
        register_rest_route($namespace, '/events', [
            'methods'  => 'POST',
            'callback' => [ $this, 'rest_create_event' ],
            'permission_callback' => function () { return current_user_can('manage_options'); },
        ]);
        register_rest_route($namespace, '/events/export', [
            'methods'  => 'GET',
            'callback' => [ $this, 'rest_export_events' ],
            'permission_callback' => function () { return current_user_can('manage_options'); },
        ]);
        register_rest_route($namespace, '/events/import', [
            'methods'  => 'POST',
            'callback' => [ $this, 'rest_import_events' ],
            'permission_callback' => function () { return current_user_can('manage_options'); },
        ]);
        register_rest_route($namespace, '/events/(?P<id>\d+)', [
            'methods'  => 'GET',
            'callback' => [ $this, 'rest_get_event' ],
            'permission_callback' => function () { return current_user_can('manage_options'); },
        ]);
        register_rest_route($namespace, '/events/(?P<id>\d+)', [
            'methods'  => 'PUT',
            'callback' => [ $this, 'rest_update_event' ],
            'permission_callback' => function () { return current_user_can('manage_options'); },
        ]);
        register_rest_route($namespace, '/events/(?P<id>\d+)', [
            'methods'  => 'DELETE',
            'callback' => [ $this, 'rest_delete_event' ],
            'permission_callback' => function () { return current_user_can('manage_options'); },
        ]);

        // Reservations
        register_rest_route($namespace, '/reservations', [
            'methods'  => 'GET',
            'callback' => [ $this, 'rest_get_reservations' ],
            'permission_callback' => function () { return current_user_can('manage_options'); },
        ]);
        register_rest_route($namespace, '/reservations', [
            'methods'  => 'POST',
            'callback' => [ $this, 'rest_create_reservation' ],
            'permission_callback' => function () { return current_user_can('manage_options'); },
        ]);
        register_rest_route($namespace, '/reservations/export', [
            'methods'  => 'GET',
            'callback' => [ $this, 'rest_export_reservations' ],
            'permission_callback' => function () { return current_user_can('manage_options'); },
        ]);
        register_rest_route($namespace, '/reservations/import', [
            'methods'  => 'POST',
            'callback' => [ $this, 'rest_import_reservations' ],
            'permission_callback' => function () { return current_user_can('manage_options'); },
        ]);
        register_rest_route($namespace, '/reservations/(?P<id>\d+)', [
            'methods'  => 'GET',
            'callback' => [ $this, 'rest_get_reservation' ],
            'permission_callback' => function () { return current_user_can('manage_options'); },
        ]);
        register_rest_route($namespace, '/reservations/(?P<id>\d+)', [
            'methods'  => 'PUT',
            'callback' => [ $this, 'rest_update_reservation' ],
            'permission_callback' => function () { return current_user_can('manage_options'); },
        ]);
        register_rest_route($namespace, '/reservations/(?P<id>\d+)', [
            'methods'  => 'DELETE',
            'callback' => [ $this, 'rest_delete_reservation' ],
            'permission_callback' => function () { return current_user_can('manage_options'); },
        ]);
    }

    // User handlers
    public function rest_get_users() {
        return rest_ensure_response($this->repository->get_users());
    }

    public function rest_get_user($request) {
        $id = $request['id'];
        $user = $this->repository->get_user($id);
        if (!$user) {
            return new WP_Error('not_found', 'User not found', [ 'status' => 404 ]);
        }
        return rest_ensure_response($user);
    }

    public function rest_create_user($request) {
        $data = $request->get_json_params();
        $this->repository->insert_user($data);
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
        $open_only = is_null($open_only) ? true : (bool) intval($open_only);
        return rest_ensure_response($this->repository->get_events($open_only));
    }

    public function rest_get_event($request) {
        $id = (int) $request['id'];
        $event = $this->repository->get_event($id);
        if (!$event) {
            return new WP_Error('not_found', 'Event not found', [ 'status' => 404 ]);
        }
        return rest_ensure_response($event);
    }

    public function rest_create_event($request) {
        $data = $request->get_json_params();
        $group_id = isset($data['group_id']) && $data['group_id'] !== '' ? (int) $data['group_id'] : null;
        $recurrence = isset($data['recurrence']) ? $data['recurrence'] : 'none';
        $recurrence_end = isset($data['recurrence_end']) ? $data['recurrence_end'] : null;
        unset($data['recurrence'], $data['recurrence_end']);
        if ($group_id) {
            $data['group_id'] = $group_id;
            $id = $this->repository->insert_event($data);
            $data['id'] = $id;
            return new WP_REST_Response($data, 201);
        }
        $data['group_id'] = null;
        $id = $this->repository->insert_event($data);
        $data['id'] = $id;
        if ($recurrence !== 'none' && $recurrence_end) {
            $this->repository->update_event($id, [ 'group_id' => $id ]);
            $start = new DateTime($data['start_datetime']);
            $end = new DateTime($data['end_datetime']);
            $limit = new DateTime($recurrence_end . ' 23:59:59');
            switch ($recurrence) {
                case 'daily': $interval = new DateInterval('P1D'); break;
                case 'weekly': $interval = new DateInterval('P1W'); break;
                case 'monthly': $interval = new DateInterval('P1M'); break;
                default: $interval = null; break;
            }
            if ($interval) {
                while (true) {
                    $start->add($interval);
                    $end->add($interval);
                    if ($start > $limit) { break; }
                    $e = $data;
                    $e['start_datetime'] = $start->format('Y-m-d H:i:s');
                    $e['end_datetime'] = $end->format('Y-m-d H:i:s');
                    $e['group_id'] = $id;
                    $this->repository->insert_event($e);
                }
            }
            $data['group_id'] = $id;
        }
        return new WP_REST_Response($data, 201);
    }

    public function rest_update_event($request) {
        $id = (int) $request['id'];
        $data = $request->get_json_params();
        $apply = $request->get_param('apply_group');
        if ($apply) {
            $event = $this->repository->get_event($id);
            if ($event && $event['group_id']) {
                $this->repository->update_events_by_group($event['group_id'], $data);
            } else {
                $this->repository->update_event($id, $data);
            }
        } else {
            $this->repository->update_event($id, $data);
        }
        return rest_ensure_response($this->repository->get_event($id));
    }

    public function rest_delete_event($request) {
        $id = (int) $request['id'];
        $apply = $request->get_param('apply_group');
        if ($apply) {
            $event = $this->repository->get_event($id);
            if ($event && $event['group_id']) {
                $this->repository->delete_events_by_group($event['group_id']);
            } else {
                $this->repository->delete_event($id);
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
        $active_only = is_null($active_only) ? true : (bool) intval($active_only);
        return rest_ensure_response($this->repository->get_reservations($user_id, $event_id, $active_only));
    }

    public function rest_invite_user($request) {
        // TODO: implement invite logic
        return new WP_REST_Response(null, 200);
    }

    public function rest_reset_password($request) {
        // TODO: implement reset password logic
        return new WP_REST_Response(null, 200);
    }

    public function rest_get_reservation($request) {
        $id = (int) $request['id'];
        $reservation = $this->repository->get_reservation($id);
        if (!$reservation) {
            return new WP_Error('not_found', 'Reservation not found', [ 'status' => 404 ]);
        }
        return rest_ensure_response($reservation);
    }

    public function rest_create_reservation($request) {
        $data = $request->get_json_params();
        $this->repository->insert_reservation($data);
        return new WP_REST_Response($data, 201);
    }

    public function rest_update_reservation($request) {
        $id = (int) $request['id'];
        $data = $request->get_json_params();
        $this->repository->update_reservation($id, $data);
        return rest_ensure_response($this->repository->get_reservation($id));
    }

    public function rest_delete_reservation($request) {
        $id = (int) $request['id'];
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
        $files = $request->get_file_params();
        if (empty($files['file'])) {
            return new WP_Error('no_file', 'No file uploaded', [ 'status' => 400 ]);
        }
        $this->repository->import_users_csv($files['file']['tmp_name']);
        return new WP_REST_Response(null, 200);
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
        $files = $request->get_file_params();
        if (empty($files['file'])) {
            return new WP_Error('no_file', 'No file uploaded', [ 'status' => 400 ]);
        }
        $this->repository->import_events_csv($files['file']['tmp_name']);
        return new WP_REST_Response(null, 200);
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
        $files = $request->get_file_params();
        if (empty($files['file'])) {
            return new WP_Error('no_file', 'No file uploaded', [ 'status' => 400 ]);
        }
        $this->repository->import_reservations_csv($files['file']['tmp_name']);
        return new WP_REST_Response(null, 200);
    }
}

