<?php

class Res_Pong_User_Repository {
    private $table_user;
    private $table_event;
    private $table_reservation;
    private $wpdb;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $prefix = $this->wpdb->prefix;
        $this->table_user = $prefix . 'RP_USER';
        $this->table_event = $prefix . 'RP_EVENT';
        $this->table_reservation = $prefix . 'RP_RESERVATION';
    }


    public function get_enabled_user_by_id($id) {
        return $this->wpdb->get_row($this->wpdb->prepare("SELECT * FROM {$this->table_user} WHERE id = %s AND enabled = 1", $id));
    }

    public function get_user_by_id($id) {
        return $this->wpdb->get_row($this->wpdb->prepare("SELECT * FROM {$this->table_user} WHERE id = %s", $id));
    }

    public function get_user_by_id_with_active_reservations($id, $date, $group_id) {
        $query = "SELECT U.*, COUNT(E.id) AS active_reservations
                    FROM wp_RP_USER AS U
                             LEFT JOIN wp_RP_RESERVATION AS R ON R.user_id = U.id
                             LEFT JOIN wp_RP_EVENT AS E ON E.id = R.event_id AND E.start_datetime > %s AND E.group_id = %s
                    WHERE U.id = %s
                    GROUP BY U.id, U.email, U.username, U.last_name, U.first_name, U.category,
                             U.password, U.timeout, U.reset_token, U.enabled";

        return $this->wpdb->get_row($this->wpdb->prepare($query, $date, $group_id, $id));
    }

    public function get_enabled_user_by_token($token) {
        return $this->wpdb->get_row($this->wpdb->prepare("SELECT * FROM {$this->table_user} WHERE reset_token = %s AND enabled = 1", $token));
    }

    public function find_users() {
        return $this->wpdb->get_results($this->wpdb->prepare("SELECT * FROM {$this->table_user}"));
    }

    public function delete_user($id) {
        return $this->wpdb->delete($this->table_user, ['id' => $id]);
    }

    // ------------------------
    // CRUD: RP_EVENT
    // ------------------------

    public function insert_event($data) {
        return $this->wpdb->insert($this->table_event, $data);
    }

    public function get_event_by_id($id) {
        return $this->wpdb->get_row($this->wpdb->prepare("SELECT * FROM {$this->table_event} WHERE id = %d", $id));
    }

    public function get_all_events() {
        return $this->wpdb->get_results("SELECT * FROM {$this->table_event} ORDER BY start_datetime ASC");
    }

    public function update_event($id, $data) {
        return $this->wpdb->update($this->table_event, $data, ['id' => $id]);
    }

    public function delete_event($id) {
        return $this->wpdb->delete($this->table_event, ['id' => $id]);
    }

    // ------------------------
    // CRUD: RP_RESERVATION
    // ------------------------

    public function insert_reservation($data) {
        return $this->wpdb->insert($this->table_reservation, $data);
    }

    public function get_reservation_by_id($id) {
        return $this->wpdb->get_row($this->wpdb->prepare("SELECT * FROM {$this->table_reservation} WHERE id = %d", $id));
    }

    public function get_reservations_by_user_id_and_event_id($user_id) {
        $query = "
        SELECT 
            r.id,
            r.user_id,
            r.event_id,
            r.created_at,
            r.presence_confirmed,
            rd.group_id,
            rd.name,
            rd.start_datetime,
            rd.end_datetime
        FROM 
            {$this->table_reservation} r
        INNER JOIN 
            {$this->table_event} rd ON r.event_id = rd.id
        WHERE 
            r.user_id = %s
        ORDER BY 
            rd.start_datetime DESC
    ";

        return $this->wpdb->get_results($this->wpdb->prepare($query, $user_id));
    }

    public function get_reservations_by_user_id($user_id) {
        $query = "
        SELECT 
            r.id,
            r.user_id,
            r.event_id,
            r.created_at,
            r.presence_confirmed,
            rd.group_id,
            rd.name,
            rd.start_datetime,
            rd.end_datetime
        FROM 
            {$this->table_reservation} r
        INNER JOIN 
            {$this->table_event} rd ON r.event_id = rd.id
        WHERE 
            r.user_id = %s
        ORDER BY 
            rd.start_datetime DESC
    ";

        return $this->wpdb->get_results($this->wpdb->prepare($query, $user_id));
    }

    public function get_reservations_by_event_id_with_user_data($event_id) {
        $query = "
        SELECT 
            r.id,
            r.created_at,
            r.presence_confirmed,
            r.user_id,
            u.last_name,
            u.first_name
        FROM 
            {$this->table_reservation} r
        INNER JOIN 
            {$this->table_user} u ON r.user_id = u.id
        WHERE 
            r.event_id = %s
        ORDER BY 
            r.created_at DESC
    ";

        return $this->wpdb->get_results($this->wpdb->prepare($query, $event_id));
    }

    public function get_next_and_previous_event($event_id, $start_datetime) {
        $query = "SELECT (SELECT id
        FROM {$this->table_event}
        WHERE concat(start_datetime,'_',id) < %s
        ORDER BY concat(start_datetime,'_',id) DESC
        LIMIT 1) AS prev_id,
       (SELECT id
        FROM {$this->table_event}
        WHERE concat(start_datetime,'_',id) > %s
        ORDER BY concat(start_datetime,'_',id)
        LIMIT 1) AS next_id
    ";

        return $this->wpdb->get_results($this->wpdb->prepare($query, "${start_datetime}_${event_id}", "${start_datetime}_${event_id}"));
    }

    public function get_reservations_by_user($user_id) {
        return $this->wpdb->get_results($this->wpdb->prepare("SELECT * FROM {$this->table_reservation} WHERE user_id = %s", $user_id));
    }

    public function get_reservations_by_date($date_id) {
        return $this->wpdb->get_results($this->wpdb->prepare("SELECT * FROM {$this->table_reservation} WHERE event_id = %d", $date_id));
    }

    public function update_reservation($id, $data) {
        return $this->wpdb->update($this->table_reservation, $data, ['id' => $id]);
    }

    public function delete_reservation($id) {
        return $this->wpdb->delete($this->table_reservation, ['id' => $id]);
    }

    public function get_reservations_summary($start, $end) {
        $sql = "
            SELECT 
                rd.id AS event_id,
                rd.start_datetime,
                rd.end_datetime,
                rd.max_players,
                r.id AS reservation_id,
                r.user_id,
                r.created_at,
                r.presence_confirmed,
                u.email,
                u.username,
                u.last_name,
                u.first_name,
                u.timeout,
                u.id AS uid,
                u.username,
                u.enabled
            FROM $this->table_event rd
            LEFT JOIN $this->table_reservation r ON rd.id = r.event_id
            LEFT JOIN $this->table_user u ON r.user_id = u.id
            WHERE rd.start_datetime BETWEEN %s AND %s
            ORDER BY rd.start_datetime ASC, r.created_at ASC
        ";

        return $this->wpdb->get_results($this->wpdb->prepare($sql, $start, $end));

    }

    public function get_users_with_stats() {

        // Retrieve timeout_minutes from configuration table
        $timeout_minutes = "60";

        // Fallback if config is missing or invalid
        if (!is_numeric($timeout_minutes)) {
            $timeout_minutes = 30;
        }

        // Query with JOINs and computed columns
        $query = "
        SELECT 
            u.id,
            u.username,
            u.email,
            CONCAT(u.last_name, ' ', u.first_name) AS full_name,
            u.first_name,
            u.last_name,
            u.timeout,
            u.enabled,
            COUNT(r.id) AS total_reservations,
            SUM(
                CASE
                    WHEN STR_TO_DATE(rd.end_datetime, '%%Y-%%m-%%d %%H:%%i:%%s') + INTERVAL %d MINUTE > NOW()
                    THEN 1
                    ELSE 0
                END
            ) AS active_reservations,
            MAX(STR_TO_DATE(rd.start_datetime, '%%Y-%%m-%%d %%H:%%i:%%s')) AS last_reservation,
            CASE
                WHEN u.timeout IS NOT NULL AND STR_TO_DATE(u.timeout, '%%Y-%%m-%%d %%H:%%i:%%s') > NOW()
                THEN 1
                ELSE 0
            END AS is_in_timeout
        FROM 
            $this->table_user u
        LEFT JOIN 
            $this->table_reservation r ON r.user_id = u.id
        LEFT JOIN 
            $this->table_event rd ON rd.id = r.event_id
        GROUP BY 
            u.id
    ";


        return $this->wpdb->get_results($this->wpdb->prepare($query, (int)$timeout_minutes));
    }

    public function update_reservation_confirmed($id, $new_value) {
        return $this->wpdb->update($this->table_reservation, ['presence_confirmed' => $new_value], ['id' => $id], ['%d'], ['%d']);
    }

    public function update_user_password($user_id, $hashed_password) {
        return $this->wpdb->update($this->table_user, ['password' => $hashed_password], ['id' => $user_id], ['%s'], ['%s']);

    }

    public function update_user_fields($id, $email, $username, $first_name, $last_name, $enabled) {

        return $this->wpdb->update(
            $this->table_user,
            [
                'username' => $username,
                'email' => $email,
                'first_name' => $first_name,
                'last_name' => $last_name,
                'enabled' => $enabled
            ],
            ['id' => $id],
            ['%s', '%s', '%s', '%s', '%d'],
            ['%s']
        );
    }


    public function insert_update_user($id, $username, $email, $first_name, $last_name) {
        $this->wpdb->query(
            $this->wpdb->prepare(
                "INSERT INTO {$this->table_user} (id, username, email, first_name, last_name)
                     VALUES (%s, %s, %s, %s, %s)
                     ON DUPLICATE KEY UPDATE
                        username = VALUES(username),
                        email = VALUES(email),
                        first_name = VALUES(first_name),
                        last_name = VALUES(last_name)",
                $id, $username, $email, $first_name, $last_name
            )
        );
    }

    public function get_reservations_by_date_with_user_data($id) {
        $sql = "SELECT * FROM $this->table_reservation R
                    LEFT JOIN $this->table_user U ON R.user_id = U.id 
                    WHERE event_id = %d";
        return $this->wpdb->get_results($this->wpdb->prepare($sql, $id));
    }

    public function get_enabled_user_by_username_or_email($username) {
        return $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT * FROM $this->table_user WHERE (LOWER(username) = LOWER(%s) OR LOWER(email) = LOWER(%s)) LIMIT 1",
            $username,
            $username
        ));
    }

    public function delete_reservation_by_user_and_event($user_id, $event_id) {
        return $this->wpdb->query(
            $this->wpdb->prepare(
                "DELETE FROM {$this->table_reservation} WHERE user_id = %s AND event_id = %d",
                $user_id, $event_id)
        );
    }

    public function get_enabled_user_by_email($user_email) {
        return $this->wpdb->get_row($this->wpdb->prepare("SELECT * FROM {$this->table_user} WHERE enabled = 1 AND LOWER(email) = LOWER(%s)", $user_email));
    }

    public function update_user_token($user_id, $token) {
        $this->wpdb->update($this->table_user, ['reset_token' => $token], ['id' => $user_id]);
    }

    public function update_users_state($ids, $enabled) {
        $placeholders = implode(',', array_fill(0, count($ids), '%s'));
        $query = "UPDATE {$this->table_user} SET enabled = %s WHERE id IN ($placeholders)";
        $params = array_merge([$enabled], $ids);
        $this->wpdb->query($this->wpdb->prepare($query, ...$params));
    }

    public function create_user($id, $username, $email, $first_name, $last_name, $enabled, $password) {
        $insert = $this->wpdb->insert($this->table_user, [
            'id' => $id,
            'username' => $username,
            'email' => $email,
            'password' => $password,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'enabled' => $enabled
        ]);
        if (!$insert && $this->wpdb->last_error) {
            throw new Exception("Error creating user - " . $this->wpdb->last_error);
        }
    }

    public function update_user($id, $username, $email, $first_name, $last_name, $enabled) {
        $data = [
            'username' => $username,
            'email' => $email,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'enabled' => $enabled,
        ];
        $update = $this->wpdb->update($this->table_user, $data, ['id' => $id]);
        if (!$update && $this->wpdb->last_error) {
            throw new Exception("Error updating user - " . $this->wpdb->last_error);
        }
    }

    public function get_events($start, $end, $user_id = "") {
        $query = "
        SELECT 
            e.id,
            e.group_id,
            e.category,
            e.name,
            e.note,
            e.start_datetime,
            e.end_datetime,
            e.max_players,
            e.enabled,
            COUNT(r.id) AS players_count,
            CASE 
                WHEN SUM(CASE WHEN r.user_id = %s THEN 1 ELSE 0 END) > 0 
                THEN 1 
                ELSE 0 
            END AS booked 
        FROM {$this->table_event} e
        LEFT JOIN {$this->table_reservation} r
                           ON r.event_id = e.id
        WHERE e.start_datetime >= %s
          AND e.end_datetime <= %s
        GROUP BY e.id, e.group_id, e.category, e.name, e.note,
                 e.start_datetime, e.end_datetime, e.max_players
        ORDER BY e.start_datetime";
        return $this->wpdb->get_results($this->wpdb->prepare($query, $user_id, $start, $end));
    }


}
