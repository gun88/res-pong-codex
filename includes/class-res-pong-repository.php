<?php

class Res_Pong_Repository {

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

}
