<?php

class Res_Pong_User_Repository {
    private $table_user;
    private $table_event;
    private $table_reservation;
    private $table_guard;
    private $wpdb;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $prefix = $this->wpdb->prefix;
        $this->table_user = $prefix . 'RP_USER';
        $this->table_event = $prefix . 'RP_EVENT';
        $this->table_reservation = $prefix . 'RP_RESERVATION';
        $this->table_guard = $prefix . 'RP_GUARD';
    }

    public function get_user_by_id($id) {
        return $this->wpdb->get_row($this->wpdb->prepare("SELECT * FROM {$this->table_user} WHERE id = %s", $id));
    }

    public function get_user_by_id_with_active_reservations($user_id, $date, $group_id) {
        $query = "SELECT U.*, COUNT(E.id) AS active_reservations
                    FROM wp_RP_USER AS U
                             LEFT JOIN wp_RP_RESERVATION AS R ON R.user_id = U.id
                             LEFT JOIN wp_RP_EVENT AS E ON E.id = R.event_id AND E.end_datetime > %s AND (E.group_id = %s OR E.id = %s)
                    WHERE U.id = %s
                    GROUP BY U.id, U.email, U.username, U.last_name, U.first_name, U.category,
                             U.password, U.timeout, U.reset_token, U.enabled";

        return $this->wpdb->get_row($this->wpdb->prepare($query, $date, $group_id, $group_id, $user_id));
    }

    public function get_enabled_user_by_token($token) {
        return $this->wpdb->get_row($this->wpdb->prepare("SELECT * FROM {$this->table_user} WHERE reset_token = %s AND enabled = 1", $token));
    }

    public function get_event_by_id($id) {
        return $this->wpdb->get_row($this->wpdb->prepare("SELECT * FROM {$this->table_event} WHERE id = %d", $id));
    }

    public function insert_reservation($data) {
        return $this->wpdb->insert($this->table_reservation, $data);
    }

    public function acquire_guard($user_id, $group_id) {
        $sql = $this->wpdb->prepare("INSERT INTO {$this->table_guard} (user_id, group_id) VALUES (%s, %d)", $user_id, $group_id);
        $this->wpdb->query($sql);
    }

    public function release_guard($user_id, $group_id) {
        $sql = $this->wpdb->prepare("DELETE FROM {$this->table_guard} WHERE user_id = %s AND group_id = %d", $user_id, $group_id);
        $this->wpdb->query($sql);
    }

    public function acquire_named_lock($group_id, $timeout) {
        $sql = $this->wpdb->prepare('SELECT GET_LOCK(%s, %d)', $group_id, $timeout);
        return (int)$this->wpdb->get_var($sql);
    }

    public function release_named_lock($group_id) {
        $sql = $this->wpdb->prepare('SELECT RELEASE_LOCK(%s)', $group_id);
        $this->wpdb->get_var($sql);
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
            r.created_at
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

    public function update_user_password($user_id, $hashed_password) {
        return $this->wpdb->update($this->table_user, ['password' => $hashed_password], ['id' => $user_id], ['%s'], ['%s']);

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

    public function get_enabled_user_by_id($user_id) {
        return $this->wpdb->get_row($this->wpdb->prepare("SELECT * FROM {$this->table_user} WHERE enabled = 1 AND id = %s", $user_id));
    }

    public function update_user_token($user_id, $token) {
        $this->wpdb->update($this->table_user, ['reset_token' => $token], ['id' => $user_id]);
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

    public function get_fitet_monitor_id($id) {
        $query = "SELECT id FROM {$this->wpdb->prefix}fitet_monitor_players WHERE code = %s";
        return $this->wpdb->get_var($this->wpdb->prepare($query, $id));
    }

    public function transaction(callable $fn)
    {
        $this->wpdb->query('START TRANSACTION');

        try {
            $result = $fn();

            // If any low-level DB error occurred inside the closure, fail the tx
            if (!empty($this->wpdb->last_error)) {
                throw new \RuntimeException($this->wpdb->last_error);
            }

            $this->wpdb->query('COMMIT');
            return $result;
        } catch (\Throwable $e) {
            $this->wpdb->query('ROLLBACK');
            throw $e; // rethrow so caller can decide how to respond
        }
    }

    public function update_flags($user_id, $flags) {
        $this->wpdb->update($this->table_user, ['flags' => $flags], ['id' => $user_id], ['%s'], ['%s']);
    }

    public function empty_event_notification_subscribers($event_id) {
        $this->wpdb->update($this->table_event, ['notification_subscribers' => null], ['id' => $event_id], ['%s'], ['%s']);

    }

    public function update_event_notification_subscribers($event_id, $notification_subscribers) {
        $this->wpdb->update($this->table_event, ['notification_subscribers' => $notification_subscribers], ['id' => $event_id], ['%s'], ['%s']);
    }



}
