<?php

defined('ABSPATH') || exit;

require_once RES_PONG_PLUGIN_DIR . 'includes/class-res-pong-repository.php';

class Res_Pong {
    private $repository;

    public function __construct() {
        $this->repository = new Res_Pong_Repository();
    }

    public function activate() {
        $this->repository->create_tables();
    }

    public function deactivate() {
        error_log('deactivate res-pong');
    }

}
