<?php

defined('ABSPATH') || exit;

require_once RES_PONG_PLUGIN_DIR . 'includes/common/class-res-pong-configuration.php';
require_once RES_PONG_PLUGIN_DIR . 'includes/common/class-res-pong-util.php';

require_once RES_PONG_PLUGIN_DIR . 'includes/admin/class-res-pong-admin-repository.php';
require_once RES_PONG_PLUGIN_DIR . 'includes/admin/class-res-pong-admin-service.php';
require_once RES_PONG_PLUGIN_DIR . 'includes/admin/class-res-pong-admin-controller.php';
require_once RES_PONG_PLUGIN_DIR . 'includes/admin/class-res-pong-admin-frontend.php';

require_once RES_PONG_PLUGIN_DIR . 'includes/user/class-res-pong-user-repository.php';
require_once RES_PONG_PLUGIN_DIR . 'includes/user/class-res-pong-user-service.php';
require_once RES_PONG_PLUGIN_DIR . 'includes/user/class-res-pong-user-controller.php';

class Res_Pong {
    private $configuration;
    private $admin_repository;
    private $admin_service;
    private $admin_controller;
    private $admin_frontend;
    private $user_repository;
    private $user_service;
    private $user_controller;

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

        $this->user_repository = new Res_Pong_User_Repository();
        $this->user_service = new Res_Pong_User_Service($this->user_repository, $this->configuration);
        $this->user_controller = new Res_Pong_User_Controller($this->user_service);
        $this->user_controller->init();
    }

    public function activate() {
        $this->admin_repository->create_tables();
        $this->copy_app_folder();
    }

    public function deactivate() {
        error_log('deactivate res-pong');
    }

    public function copy_app_folder() {
        $src = RES_PONG_PLUGIN_DIR . 'app';
        if (RES_PONG_DEV) {
            $dev_path = RES_PONG_PLUGIN_DIR . 'app/dist/browser';
            if (!is_dir($dev_path)) {
                return;
            }
            $src = $dev_path;
        }
        $dest = ABSPATH . 'prenotazioni';

        if (!file_exists($src)) {
            return;
        }

        if (file_exists($dest)) {
            $this->res_pong_recursive_delete($dest);
        }

        mkdir($dest, 0755, true);

        $this->res_pong_recursive_copy($src, $dest);
    }

    private function res_pong_recursive_copy($src, $dest) {
        $dir = opendir($src);
        @mkdir($dest, 0755, true);
        while (false !== ($file = readdir($dir))) {
            if ($file != '.' && $file != '..') {
                if (is_dir($src . '/' . $file)) {
                    $this->res_pong_recursive_copy($src . '/' . $file, $dest . '/' . $file);
                } else {
                    copy($src . '/' . $file, $dest . '/' . $file);
                }
            }
        }
        closedir($dir);
    }

    private function res_pong_recursive_delete($dir) {
        if (!is_dir($dir)) {
            return;
        }
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = "$dir/$file";
            if (is_dir($path)) {
                $this->res_pong_recursive_delete($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }

    public function send_notification_messages($event, $notification_subscribers) {
        $this->user_service->send_notification_messages($event, $notification_subscribers);
    }

}
