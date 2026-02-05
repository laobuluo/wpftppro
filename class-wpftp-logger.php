<?php
if (!defined('ABSPATH')) die();

class WPFTPLogger {
    private static $instance = null;
    private $table_name;
    
    private function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'wpftp_logs';
        $this->maybe_create_table();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function maybe_create_table() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table_name} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            time datetime DEFAULT CURRENT_TIMESTAMP,
            operation varchar(50) NOT NULL,
            status varchar(20) NOT NULL,
            details text,
            file_path varchar(255),
            file_size bigint(20),
            user_id bigint(20),
            PRIMARY KEY  (id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    public function log($operation, $status, $details = '', $file_path = '', $file_size = 0) {
        global $wpdb;
        
        $data = array(
            'operation' => $operation,
            'status' => $status,
            'details' => $details,
            'file_path' => $file_path,
            'file_size' => $file_size,
            'user_id' => get_current_user_id()
        );
        
        $wpdb->insert($this->table_name, $data);
        
        if ($status === 'error') {
            error_log("WPFTP Error: {$operation} - {$details}");
        }
    }
    
    public function get_logs($limit = 100, $offset = 0) {
        global $wpdb;
        
        $sql = $wpdb->prepare("SELECT * FROM {$this->table_name} ORDER BY time DESC LIMIT %d OFFSET %d", 
            $limit, $offset);
            
        return $wpdb->get_results($sql);
    }
    
    public function clear_logs($days = 30) {
        global $wpdb;
        
        $sql = $wpdb->prepare("DELETE FROM {$this->table_name} WHERE time < DATE_SUB(NOW(), INTERVAL %d DAY)", 
            $days);
            
        return $wpdb->query($sql);
    }
} 