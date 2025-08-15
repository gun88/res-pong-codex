<?php


class Res_Pong_Admin_Repository {

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

    // ------------------------
    // CRUD: RP_USER
    // ------------------------

    public function create_tables() {

        $charset_collate = $this->wpdb->get_charset_collate();

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $sql = "
        CREATE TABLE {$this->table_user} (
            id VARCHAR(20) PRIMARY KEY,
            email VARCHAR(255) NOT NULL UNIQUE,
            username VARCHAR(100) NOT NULL UNIQUE,
            last_name VARCHAR(100) NOT NULL,
            first_name VARCHAR(100) NOT NULL,
            category VARCHAR(25),
            password VARCHAR(255) NOT NULL,
            timeout VARCHAR(25) DEFAULT NULL,
            reset_token VARCHAR(255) DEFAULT NULL,
            enabled TINYINT DEFAULT 1 NOT NULL
        ) $charset_collate;

        CREATE TABLE {$this->table_event} (
            id INT AUTO_INCREMENT PRIMARY KEY,
            group_id INT,
            category VARCHAR(255),
            name VARCHAR(255) NOT NULL,
            note TEXT,
            start_datetime VARCHAR(25) NOT NULL,
            end_datetime VARCHAR(25) NOT NULL,
            max_players INT,
            enabled TINYINT DEFAULT 1 NOT NULL,
            INDEX idx_start_datetime (start_datetime),
            INDEX idx_group_id (group_id)
        ) $charset_collate;

        CREATE TABLE {$this->table_reservation} (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id VARCHAR(20),
            event_id INT,
            created_at VARCHAR(25) NOT NULL,
            presence_confirmed TINYINT DEFAULT 0,
            UNIQUE (user_id, event_id),
            FOREIGN KEY (user_id) REFERENCES {$this->table_user}(id) ON DELETE CASCADE,
            FOREIGN KEY (event_id) REFERENCES {$this->table_event}(id) ON DELETE CASCADE,
            INDEX idx_event_id (event_id),
            INDEX idx_user_id (user_id)
        ) $charset_collate;
        ";

        dbDelta($sql);
    }

    // ------------------------
    // RP_USER methods
    // ------------------------

    public function get_users() {
        $sql = "SELECT *, CONCAT(last_name, ' ', first_name) AS name FROM {$this->table_user}";
        return $this->wpdb->get_results($sql, ARRAY_A);
    }

    public function get_user($id) {
        $sql = "SELECT *, CONCAT(last_name, ' ', first_name) AS name FROM {$this->table_user} WHERE id = %s";
        return $this->wpdb->get_row($this->wpdb->prepare($sql, $id), ARRAY_A);
    }

    public function find_user_by_email($email) {
        $sql = "SELECT *, CONCAT(last_name, ' ', first_name) AS name FROM {$this->table_user} WHERE email = %s";
        return $this->wpdb->get_row($this->wpdb->prepare($sql, $email), ARRAY_A);
    }

    public function insert_user($data) {
        return $this->wpdb->insert($this->table_user, $data);
    }

    public function update_user($id, $data) {
        return $this->wpdb->update($this->table_user, $data, ['id' => $id]);
    }

    public function delete_user($id) {
        return $this->wpdb->delete($this->table_user, ['id' => $id]);
    }

    // ------------------------
    // RP_EVENT methods
    // ------------------------

    public function get_events($open_only = true) {
        $where = '';
        if ($open_only) {
            $now = current_time('mysql');
            $where = $this->wpdb->prepare('WHERE e.start_datetime > %s', $now);
        }
        $sql = "SELECT e.*, g.name AS group_name, COUNT(r.id) AS players_count FROM {$this->table_event} e LEFT JOIN {$this->table_event} g ON e.group_id = g.id LEFT JOIN {$this->table_reservation} r ON e.id = r.event_id {$where} GROUP BY e.id";
        return $this->wpdb->get_results($sql, ARRAY_A);
    }

    public function get_event($id) {
        return $this->wpdb->get_row($this->wpdb->prepare("SELECT * FROM {$this->table_event} WHERE id = %d", $id), ARRAY_A);
    }

    public function insert_event($data) {
        $this->wpdb->insert($this->table_event, $data);
        return $this->wpdb->insert_id;
    }

    public function update_event($id, $data) {
        return $this->wpdb->update($this->table_event, $data, ['id' => $id]);
    }

    public function delete_event($id) {
        $this->wpdb->update($this->table_event, ['group_id' => null], ['group_id' => $id]);
        return $this->wpdb->delete($this->table_event, ['id' => $id]);
    }

    public function update_events_by_group($group_id, $data) {
        return $this->wpdb->update($this->table_event, $data, ['group_id' => $group_id]);
    }

    public function delete_events_by_group($group_id) {
        return $this->wpdb->delete($this->table_event, ['group_id' => $group_id]);
    }

    // ------------------------
    // RP_RESERVATION methods
    // ------------------------

    public function get_reservations($user_id = null, $event_id = null, $active_only = true) {
        $where = [];
        $params = [];
        if ($user_id !== null) {
            $where[] = 'r.user_id = %s';
            $params[] = $user_id;
        }
        if ($event_id !== null) {
            $where[] = 'r.event_id = %d';
            $params[] = $event_id;
        }
        if ($active_only) {
            $where[] = 'e.start_datetime > %s';
            $params[] = current_time('mysql');
        }
        $where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $sql = "SELECT r.*, u.username, u.email, CONCAT(u.last_name, ' ', u.first_name) AS name, e.name AS event_name, e.start_datetime AS event_start_datetime FROM {$this->table_reservation} r JOIN {$this->table_user} u ON r.user_id = u.id JOIN {$this->table_event} e ON r.event_id = e.id {$where_sql} ORDER BY r.created_at DESC";
        if ($params) {
            $sql = $this->wpdb->prepare($sql, $params);
        }
        return $this->wpdb->get_results($sql, ARRAY_A);
    }

    public function get_reservation($id) {
        return $this->wpdb->get_row($this->wpdb->prepare("SELECT * FROM {$this->table_reservation} WHERE id = %d", $id), ARRAY_A);
    }

    public function insert_reservation($data) {
        return $this->wpdb->insert($this->table_reservation, $data);
    }

    public function update_reservation($id, $data) {
        return $this->wpdb->update($this->table_reservation, $data, ['id' => $id]);
    }

    public function delete_reservation($id) {
        return $this->wpdb->delete($this->table_reservation, ['id' => $id]);
    }

    private function rows_to_csv($rows) {
        $fh = fopen('php://temp', 'r+');
        if (!empty($rows)) {
            fputcsv($fh, array_keys($rows[0]));
            foreach ($rows as $row) {
                fputcsv($fh, $row);
            }
        }
        rewind($fh);
        $csv = stream_get_contents($fh);
        fclose($fh);
        return $csv;
    }

    private function import_csv($file, $table) {
        $handle = fopen($file, 'r');
        if (!$handle) {
            return false;
        }
        $columns = $this->wpdb->get_col("DESCRIBE {$table}");
        $allowed = array_fill_keys($columns, true);
        $header = fgetcsv($handle);
        $header = array_values(array_intersect($header, $columns));

        while (($data = fgetcsv($handle)) !== false) {
            if (!array_filter($data, function ($v) { return trim($v) !== ''; })) {
                continue;
            }
            $data = array_slice($data, 0, count($header));
            $row = array_combine($header, $data);
            $row = array_intersect_key($row, $allowed);
            $this->wpdb->replace($table, $row);
        }
        fclose($handle);
        return true;
    }

    public function export_users_csv() {
        return $this->rows_to_csv($this->get_users());
    }

    public function import_users_csv($file) {
        $handle = fopen($file, 'r');
        if (!$handle) {
            return false;
        }
        $first = fgetcsv($handle, 0, ';');
        if ($first && in_array('Cognome', $first) && in_array('Nome', $first) && in_array('Tessera', $first)) {
            $map = array_flip($first);
            while (($data = fgetcsv($handle, 0, ';')) !== false) {
                if (!array_filter($data, function ($v) { return trim($v) !== ''; })) {
                    continue;
                }
                $last = $this->normalize_name($data[$map['Cognome']] ?? '');
                $first_name = $this->normalize_name($data[$map['Nome']] ?? '');
                $email = strtolower(trim($data[$map['Email']] ?? ''));
                $category = $data[$map['Categoria']] ?? '';
                $id = trim($data[$map['Tessera']] ?? '');
                if ($id === '' || $email === '') {
                    continue;
                }
                $username = $this->generate_username($first_name, $last);
                $row = [
                    'id'          => $id,
                    'email'       => $email,
                    'username'    => $username,
                    'last_name'   => $last,
                    'first_name'  => $first_name,
                    'category'    => $category,
                    'password'    => '',
                    'timeout'     => null,
                    'reset_token' => null,
                    'enabled'     => 1,
                ];
                $this->wpdb->replace($this->table_user, $row);
            }
            fclose($handle);
            return true;
        }
        fclose($handle);
        return $this->import_csv($file, $this->table_user);
    }

    public function export_events_csv() {
        $sql = "SELECT * FROM {$this->table_event}";
        return $this->rows_to_csv($this->wpdb->get_results($sql, ARRAY_A));
    }

    public function import_events_csv($file) {
        return $this->import_csv($file, $this->table_event);
    }

    public function export_reservations_csv() {
        return $this->rows_to_csv($this->get_reservations(null, null, false));
    }

    public function import_reservations_csv($file) {
        return $this->import_csv($file, $this->table_reservation);
    }

    private function normalize_name($name) {
        $name = strtolower(trim($name));
        return mb_convert_case($name, MB_CASE_TITLE, 'UTF-8');
    }

    private function generate_username($first_name, $last_name) {
        $base = strtolower(substr($first_name, 0, 1) . preg_replace('/[^a-z0-9]/i', '', $last_name));
        $username = $base;
        $i = 2;
        while ($this->username_exists($username)) {
            $username = $base . $i;
            $i++;
        }
        return $username;
    }

    private function username_exists($username) {
        $sql = $this->wpdb->prepare("SELECT COUNT(*) FROM {$this->table_user} WHERE username = %s", $username);
        return (int) $this->wpdb->get_var($sql) > 0;
    }

}
