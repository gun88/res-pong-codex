<?php

class Res_Pong_Configuration {
    private $option_name = 'res_pong_configuration';
    private $defaults = [
        'almost_closed_minutes' => 30,
        'almost_full_players' => 4,
        'max_active_reservations' => 1,
        'next_reservation_delay' => 300,
        'default_email_address' => '',
        'app_url' => 'https://my-site/prenotazioni',
        'invitation_subject' => 'Portale Prenotazioni - Effettua il tuo primo accesso',
        'invitation_text' => "Ciao #first_name,\n\nStai per entrare nel portale prenotazioni!\nClicca sul link per effettuare il primo accesso.",
        'reset_password_subject' => 'Portale Prenotazioni - Reset password',
        'reset_password_text' => "Ciao #first_name,\n\nAbbiamo ricevuto una richiesta di reset della tua password. Se non sei stato tu a richiedere il reset, ignora questa email, altrimenti clicca sul seguente link per reimpostare la tua password.",
    ];

    public function get_all() {
        $config = get_option($this->option_name, []);
        $config = wp_parse_args($config, $this->defaults);
        if ($config['default_email_address'] === '') {
            $config['default_email_address'] = get_option('admin_email');
        }
        return $config;
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

