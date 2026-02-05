<?php
if (!defined('ABSPATH')) die();

class WPFTPConfig {
    private static $instance = null;
    private $config = array();
    private $option_name = 'wpftp_options';
    
    private function __construct() {
        $this->config = get_option($this->option_name);
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function get($key, $default = null) {
        return isset($this->config[$key]) ? $this->config[$key] : $default;
    }
    
    public function set($key, $value) {
        $this->config[$key] = $value;
        return update_option($this->option_name, $this->config);
    }
    
    public function getAll() {
        return $this->config;
    }
    
    public function update($new_config) {
        $this->config = array_merge($this->config, $new_config);
        return update_option($this->option_name, $this->config);
    }
} 