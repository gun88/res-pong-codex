<?php

define('RES_PONG_CONFIGURATION_SITE_URL', get_site_url());
define('RES_PONG_CONFIGURATION_SITE_NAME', get_bloginfo('name'));

class Res_Pong_Configuration {
    private $option_name = 'res_pong_configuration';
    private $defaults = [
        'almost_closed_minutes' => 30,
        'almost_full_players' => 4,
        'max_active_reservations' => 1,
        'next_reservation_delay' => 300,
        'avatar_management' => 'none',
        'default_email_address' => 'prenotazioni@my-site.com',

        'site_url' => RES_PONG_CONFIGURATION_SITE_URL,
        'site_name' => RES_PONG_CONFIGURATION_SITE_NAME,

        'app_url' => RES_PONG_CONFIGURATION_SITE_URL . "/prenotazioni",
        'app_name' => "Portale Prenotazioni",

        'invitation_subject' => "Effettua il tuo primo accesso - #app_name",
        'invitation_text' => "Ciao #first_name,\n\nStai per entrare nel #app_name!\n\nPer per completare la configurazione del tuo profilo, clicca su questo link:\n\n<strong><a href=\"#link\">Completa configurazione</a></strong>\n\n&nbsp;\n\nSe il collegamento non dovesse funzionare, copia e incolla il seguente indirizzo nel tuo browser:\n#link\n\n&nbsp;",
      
        'reset_password_subject' => "Reset password - #app_name - #now_date_only",
        'reset_password_text' => "Ciao #first_name,\n\nAbbiamo ricevuto una richiesta di reset della tua password in data: #now_date_and_time.\n\nSe non sei stato tu a richiedere il reset, ignora questa email, altrimenti clicca sul seguente link per reimpostare la tua password:\n\n<a href=\"#link\"><strong>Reimposta password</strong></a>\n\n&nbsp;\n\nSe il collegamento non dovesse funzionare, copia e incolla il seguente indirizzo nel tuo browser:\n#link\n\n&nbsp;",
      
        'update_password_subject' => "Password aggiornata - #app_name - #now_date_only",
        'update_password_text' => "Ciao #first_name,\n\nLa tua password è stata aggiornata correttamente.\n\nOra puoi accedere al #app_name usando le tue nuove credenziali.\n\nPer autenticarti, inserisci:\n<ul>\n \t<li>Il tuo username (<em>#username</em>) o la tua email (<em>#email</em>)</li>\n \t<li>La password che hai appena creato</li>\n</ul>\n<a href=\"#app_url\"><strong>Accedi al portale</strong></a>\n\nOppure visita il nostro sito <a href=\"#site_url\">#site_name</a> e clicca sul collegamento al #app_name.\n\n&nbsp;",

        'reservation_confirmed_subject' => 'Prenotazione Confermata - #event_name - #event_date_short - #app_name',
        'reservation_confirmed_text' => "Ciao #first_name,\n\nLa tua prenotazione per <a href=\"#app_url/#/events/#event_id\">#event_name di #event_date_full</a> è confermata.\n\nSeguono i dettagli:\n\n<b>Evento:</b> #event_name\n<b>Categoria:</b> #event_category\n<b>Data:</b> #event_date_only\n<b>Ora inizio:</b> #event_time_start\n<b>Ora fine:</b> #event_time_end\n<b>Durata:</b> #event_duration\n<b>Limite partecipanti:</b> #event_max_players\n<b>Note:</b> <i>#event_note</i>\n\nTi aspettiamo in palestra!\n\n&nbsp;",

        'reservation_deleted_subject' => 'Prenotazione Cancellata - #event_name - #event_date_short - #app_name',
        'reservation_deleted_text' => "Ciao #first_name,\n\nLa tua prenotazione per <a href=\"#app_url/#/events/#event_id\">#event_name di #event_date_full</a> è stata cancellata correttamente.\n\n&nbsp;",

        'notify_availability_subject' => 'Posti Disponibili - #event_name - #event_date_short - #app_name',
        'notify_availability_text' => "Ciao #first_name,\n\nCi sono posti disponibili per <a href=\"#app_url/#/events/#event_id\">#event_name di #event_date_full</a>.\n\n<i>NOTA: questa notifica è stata inviata a <b>#event_notified_count utenti</b> della piattaforma.</i>\n\n&nbsp;",

        'mail_signature' => "<strong>#site_name</strong>\n<em>#app_name</em>\n#app_url\n",
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

