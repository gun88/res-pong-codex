<?php

class Res_Pong_Configuration {
    private $option_name = 'res_pong_configuration';
    private $defaults = [
        'almost_closed_minutes' => 30,
        'almost_full_players' => 4,
        'max_active_reservations' => 1,
        'next_reservation_delay' => 300,
        'first_access_page_url' => 'https://localhost/#/first-access',
        'password_update_page_url' => 'https://localhost/#/password-update',
        'invitation_text' => '',
        'reset_password_text' => '',
    ];

    public function get_all() {
        $config = get_option($this->option_name, []);
        return wp_parse_args($config, $this->defaults);
    }

    public function get($key) {
        $config = $this->get_all();
        return isset($config[$key]) ? $config[$key] : null;
    }

    public function update($data) {
        $config = $this->get_all();
        foreach ($data as $key => $value) {
            if (array_key_exists($key, $this->defaults)) {
                $config[$key] = $value;
            }
        }
        update_option($this->option_name, $config);
    }

    public function update_single($key, $value) {
        if (!array_key_exists($key, $this->defaults)) {
            return;
        }
        $config = $this->get_all();
        $config[$key] = $value;
        update_option($this->option_name, $config);
    }
}

