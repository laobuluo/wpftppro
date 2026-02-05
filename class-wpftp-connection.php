<?php
if (!defined('ABSPATH')) die();

class WPFTPConnection {
    private static $instance = null;
    private $conn_id = null;
    private $config;
    private $logger;
    private $last_used;
    private $connection_timeout = 300; // 5分钟超时
    private $max_retries = 3;
    private $retry_delay = 1; // 1秒
    
    private function __construct() {
        $this->config = WPFTPConfig::getInstance();
        $this->logger = WPFTPLogger::getInstance();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function connect() {
        if ($this->conn_id && $this->isConnectionValid()) {
            return true;
        }
        
        $this->disconnect();
        
        try {
            $this->conn_id = ftp_connect(
                $this->config->get('ftp_server'),
                $this->config->get('ftp_port', 21),
                5 // 连接超时5秒
            );
            
            if (!$this->conn_id) {
                throw new Exception("无法连接到FTP服务器");
            }
            
            $login_result = ftp_login(
                $this->conn_id,
                $this->config->get('ftp_user_name'),
                $this->config->get('ftp_user_pass')
            );
            
            if (!$login_result) {
                throw new Exception("FTP登录失败");
            }
            
            // 设置被动模式
            ftp_pasv($this->conn_id, $this->config->get('ftp_pasv', false));
            
            // 切换到基础目录
            $basedir = $this->config->get('ftp_basedir', '/');
            if (!@ftp_chdir($this->conn_id, $basedir)) {
                if (!@ftp_mkdir($this->conn_id, $basedir)) {
                    throw new Exception("无法创建基础目录: " . $basedir);
                }
                if (!@ftp_chdir($this->conn_id, $basedir)) {
                    throw new Exception("无法切换到基础目录: " . $basedir);
                }
            }
            
            $this->last_used = time();
            return true;
            
        } catch (Exception $e) {
            $this->logger->log('connect', 'error', $e->getMessage());
            $this->disconnect();
            throw $e;
        }
    }
    
    private function isConnectionValid() {
        if (!$this->conn_id) {
            return false;
        }
        
        // 检查连接是否超时
        if (time() - $this->last_used > $this->connection_timeout) {
            return false;
        }
        
        // 尝试执行一个简单的FTP命令来测试连接
        try {
            ftp_pwd($this->conn_id);
            $this->last_used = time();
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    public function disconnect() {
        if ($this->conn_id) {
            @ftp_close($this->conn_id);
            $this->conn_id = null;
        }
    }
    
    public function withRetry($callback) {
        $attempt = 0;
        $last_error = null;
        
        while ($attempt < $this->max_retries) {
            try {
                $this->connect();
                return $callback($this->conn_id);
            } catch (Exception $e) {
                $last_error = $e;
                $attempt++;
                
                if ($attempt < $this->max_retries) {
                    sleep($this->retry_delay);
                    $this->disconnect();
                }
            }
        }
        
        throw $last_error;
    }
    
    public function __destruct() {
        $this->disconnect();
    }
} 