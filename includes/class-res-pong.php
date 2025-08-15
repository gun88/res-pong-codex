<?php

defined('ABSPATH') || exit;

require_once RES_PONG_PLUGIN_DIR . 'includes/common/class-res-pong-configuration.php';
require_once RES_PONG_PLUGIN_DIR . 'includes/admin/class-res-pong-admin-repository.php';
require_once RES_PONG_PLUGIN_DIR . 'includes/admin/class-res-pong-admin-service.php';
require_once RES_PONG_PLUGIN_DIR . 'includes/admin/class-res-pong-admin-controller.php';
require_once RES_PONG_PLUGIN_DIR . 'includes/admin/class-res-pong-admin-frontend.php';

class Res_Pong {
    private $configuration;
    private $admin_repository;
    private $admin_service;
    private $admin_controller;
    private $admin_frontend;

    public function __construct() {
        $this->configuration = new Res_Pong_Configuration();
        $this->admin_repository = new Res_Pong_Admin_Repository();
        $this->admin_service = new Res_Pong_Admin_Service($this->admin_repository, $this->configuration);
        $this->admin_controller = new Res_Pong_Admin_Controller($this->admin_service);
        $this->admin_controller->init();
        if (is_admin()) {
            $this->admin_frontend = new Res_Pong_Admin_Frontend($this->configuration);
            $this->admin_frontend->init();
        }
    }

    public function activate() {
        $this->admin_repository->create_tables();
    }

    public function deactivate() {
        error_log('deactivate res-pong');
    }
}
