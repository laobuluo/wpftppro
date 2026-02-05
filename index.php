<?php
/**
 * Plugin Name: WPFTP PRO
 * Plugin URI: https://www.lezaiyun.com/826.html
 * Description: 在原有WPFTP基础上升级至PRO版本，考虑到兼容已有用户问题，不变动原来的WPFTP。之前版本依旧可以使用。
 * Version: 5.0
 * Author: 老蒋和他的伙伴们
 * Author URI: https://www.lezaiyun.com
 */

if (!defined('ABSPATH')) die();

// 加载必要的类文件
require_once(plugin_dir_path(__FILE__) . 'class-wpftp-config.php');
require_once(plugin_dir_path(__FILE__) . 'class-wpftp-logger.php');
require_once(plugin_dir_path(__FILE__) . 'class-wpftp-connection.php');
require_once(plugin_dir_path(__FILE__) . 'class-wpftp-operations.php');

class WPFTP {
    private static $instance = null;
    private $config;
    private $logger;
    private $operations;
    private $menu_title = 'WPFTP PRO设置';
    private $page_title = 'WPFTP PRO设置';
    private $capability = 'manage_options';
    private $version = '4.2';
    
    private function __construct() {
        $this->config = WPFTPConfig::getInstance();
        $this->logger = WPFTPLogger::getInstance();
        $this->operations = WPFTPOperations::getInstance();
        
        $this->init_hooks();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function init_hooks() {
        // 插件激活和停用钩子
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // 避免上传插件/主题被同步到FTP
        if (substr_count($_SERVER['REQUEST_URI'], '/update.php') <= 0) {
            add_filter('wp_handle_upload', array($this, 'handle_upload'));
            
            if (version_compare(get_bloginfo('version'), '5.3', '<')) {
                add_filter('wp_update_attachment_metadata', array($this, 'handle_attachment_metadata'));
            } else {
                add_filter('wp_generate_attachment_metadata', array($this, 'handle_attachment_metadata'));
                add_filter('wp_save_image_editor_file', array($this, 'handle_image_editor_file'));
            }
        }
        
        // 删除文件时同步删除FTP上的文件
        add_action('delete_attachment', array($this, 'handle_delete_attachment'));
        
        // 添加设置菜单
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_filter('plugin_action_links', array($this, 'add_settings_link'), 10, 2);
        
        // 文件名处理
        add_filter('sanitize_file_name', array($this, 'handle_filename'), 10, 1);
    }
    
    public function activate() {
        // 初始化配置
        $default_options = array(
            'version' => $this->version,
            'ftp_server' => '',
            'ftp_port' => 21,
            'ftp_pasv' => false,
            'ftp_user_name' => '',
            'ftp_user_pass' => '',
            'ftp_basedir' => '/',
            'no_local_file' => false,
            'backup_url_path' => '',
            'opt' => array(
                'auto_rename' => false,
            ),
        );
        
        if (!get_option('wpftp_options')) {
            add_option('wpftp_options', $default_options);
        }
    }
    
    public function deactivate() {
        // 保存当前的upload_url_path
        $current_options = get_option('wpftp_options');
        $current_options['backup_url_path'] = get_option('upload_url_path');
        update_option('wpftp_options', $current_options);
        
        // 恢复默认的上传路径
        update_option('upload_url_path', '');
    }
    
    public function handle_upload($upload) {
        try {
            if (isset($upload['file']) && isset($upload['url'])) {
                $file_path = $upload['file'];
                $relative_path = str_replace(wp_upload_dir()['basedir'] . '/', '', $file_path);
                
                // 上传到FTP
                $this->operations->upload($relative_path, $file_path);
                
                // 如果设置了不保留本地文件
                if ($this->config->get('no_local_file')) {
                    @unlink($file_path);
                }
            }
        } catch (Exception $e) {
            $this->logger->log('upload', 'error', $e->getMessage());
        }
        
        return $upload;
    }
    
    public function handle_attachment_metadata($metadata) {
        if (is_array($metadata) && isset($metadata['file'])) {
            try {
                $upload_dir = wp_upload_dir();
                
                // 处理缩略图
                if (isset($metadata['sizes']) && is_array($metadata['sizes'])) {
                    foreach ($metadata['sizes'] as $size) {
                        $file_path = $upload_dir['basedir'] . '/' . dirname($metadata['file']) . '/' . $size['file'];
                        $relative_path = dirname($metadata['file']) . '/' . $size['file'];
                        
                        if (file_exists($file_path)) {
                            $this->operations->upload($relative_path, $file_path);
                            
                            if ($this->config->get('no_local_file')) {
                                @unlink($file_path);
                            }
                        }
                    }
                }
            } catch (Exception $e) {
                $this->logger->log('upload_thumbnails', 'error', $e->getMessage());
            }
        }
        
        return $metadata;
    }
    
    public function handle_image_editor_file($override) {
        try {
            if (is_array($override) && isset($override['path'])) {
                $file_path = $override['path'];
                $relative_path = str_replace(wp_upload_dir()['basedir'] . '/', '', $file_path);
                
                $this->operations->upload($relative_path, $file_path);
                
                if ($this->config->get('no_local_file')) {
                    @unlink($file_path);
                }
            }
        } catch (Exception $e) {
            $this->logger->log('upload_edited', 'error', $e->getMessage());
        }
        
        return $override;
    }
    
    public function handle_delete_attachment($post_id) {
        try {
            $meta = wp_get_attachment_metadata($post_id);
            $keys = array();
            
            // 添加主文件
            if (isset($meta['file'])) {
                $keys[] = $meta['file'];
            }
            
            // 添加缩略图
            if (isset($meta['sizes']) && is_array($meta['sizes'])) {
                foreach ($meta['sizes'] as $size) {
                    $keys[] = dirname($meta['file']) . '/' . $size['file'];
                }
            }
            
            // 删除文件
            if (!empty($keys)) {
                $this->operations->delete($keys);
            }
        } catch (Exception $e) {
            $this->logger->log('delete_attachment', 'error', $e->getMessage());
        }
    }
    
    public function handle_filename($filename) {
        if ($this->config->get('opt')['auto_rename']) {
            // 生成唯一文件名
            $info = pathinfo($filename);
            $ext = empty($info['extension']) ? '' : '.' . $info['extension'];
            $name = basename($filename, $ext);
            
            return sanitize_title($name) . '-' . uniqid() . $ext;
        }
        
        return $filename;
    }
    
    public function add_admin_menu() {
        add_options_page(
            $this->page_title,
            $this->menu_title,
            $this->capability,
            'wpftp-settings',
            array($this, 'render_settings_page')
        );
    }
    
    public function add_settings_link($links, $file) {
        if (plugin_basename(__FILE__) === $file) {
            $settings_link = '<a href="' . admin_url('options-general.php?page=wpftp-settings') . '">' . __('设置') . '</a>';
            array_unshift($links, $settings_link);
        }
        return $links;
    }
    
    public function render_settings_page() {
        // 包含设置页面模板
        require_once(plugin_dir_path(__FILE__) . 'setting.php');
    }
}

// 初始化插件
WPFTP::getInstance();
