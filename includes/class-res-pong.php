<?php

defined('ABSPATH') || exit;

require_once RES_PONG_PLUGIN_DIR . 'includes/class-res-pong-repository.php';
require_once RES_PONG_PLUGIN_DIR . 'includes/class-res-pong-rest.php';
require_once RES_PONG_PLUGIN_DIR . 'includes/class-res-pong-admin.php';

class Res_Pong {
    private $repository;
    private $rest;
    private $admin;

    public function __construct() {
        $this->repository = new Res_Pong_Repository();
        $this->rest = new Res_Pong_Rest($this->repository);
        if (is_admin()) {
            $this->admin = new Res_Pong_Admin($this->repository);
        }
    }

    public function activate() {
        $this->repository->create_tables();
    }

    public function deactivate() {
        error_log('deactivate res-pong');
    }
}
