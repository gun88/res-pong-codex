<?php

defined('ABSPATH') || exit;

require_once RES_PONG_PLUGIN_DIR . 'includes/class-res-pong-repository.php';
require_once RES_PONG_PLUGIN_DIR . 'includes/class-res-pong-configuration.php';
require_once RES_PONG_PLUGIN_DIR . 'includes/class-res-pong-rest.php';
require_once RES_PONG_PLUGIN_DIR . 'includes/class-res-pong-admin.php';

class Res_Pong {
    private $repository;
    private $rest;
    private $admin;
    private $configuration;

    public function __construct() {
        $this->repository = new Res_Pong_Repository();
        $this->configuration = new Res_Pong_Configuration();
        $this->rest = new Res_Pong_Rest($this->repository);
        if (is_admin()) {
            $this->admin = new Res_Pong_Admin($this->repository, $this->configuration);
        }
    }

    public function activate() {
        $this->repository->create_tables();
    }

    public function deactivate() {
        error_log('deactivate res-pong');
    }
}
