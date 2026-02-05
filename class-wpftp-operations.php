<?php
if (!defined('ABSPATH')) die();

class WPFTPOperations {
    private static $instance = null;
    private $connection;
    private $config;
    private $logger;
    
    // 允许的文件类型
    private $allowed_types = array(
        'jpg', 'jpeg', 'png', 'gif', 'webp',  // 图片
        'pdf', 'doc', 'docx', 'xls', 'xlsx',  // 文档
        'zip', 'rar', 'tar', 'gz',            // 压缩文件
        'mp4', 'webm', 'ogg',                 // 视频
        'mp3', 'wav'                          // 音频
    );
    
    // 默认最大文件大小 (50MB)
    private $max_file_size = 52428800;
    
    private function __construct() {
        $this->connection = WPFTPConnection::getInstance();
        $this->config = WPFTPConfig::getInstance();
        $this->logger = WPFTPLogger::getInstance();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function upload($key, $local_file_path) {
        // 验证文件
        $this->validateFile($local_file_path);
        
        // 开始上传
        try {
            $file_size = filesize($local_file_path);
            $start_time = microtime(true);
            
            $result = $this->connection->withRetry(function($conn) use ($key, $local_file_path) {
                // 确保目录存在
                $this->ensureDirectoryExists(dirname($key), $conn);
                
                // 上传文件
                $upload_result = ftp_put(
                    $conn,
                    $this->config->get('ftp_basedir', '/') . '/' . $key,
                    $local_file_path,
                    FTP_BINARY
                );
                
                if (!$upload_result) {
                    throw new Exception("文件上传失败");
                }
                
                return true;
            });
            
            $end_time = microtime(true);
            $duration = round($end_time - $start_time, 2);
            
            // 记录成功日志
            $this->logger->log(
                'upload',
                'success',
                "文件上传成功，耗时: {$duration}秒",
                $key,
                $file_size
            );
            
            return true;
            
        } catch (Exception $e) {
            $this->logger->log(
                'upload',
                'error',
                $e->getMessage(),
                $key,
                $file_size ?? 0
            );
            throw $e;
        }
    }
    
    public function delete($keys) {
        if (!is_array($keys)) {
            $keys = array($keys);
        }
        
        $results = array();
        
        foreach ($keys as $key) {
            try {
                $result = $this->connection->withRetry(function($conn) use ($key) {
                    return @ftp_delete(
                        $conn,
                        $this->config->get('ftp_basedir', '/') . '/' . $key
                    );
                });
                
                $results[$key] = $result;
                
                $this->logger->log(
                    'delete',
                    $result ? 'success' : 'error',
                    $result ? '文件删除成功' : '文件删除失败',
                    $key
                );
                
            } catch (Exception $e) {
                $results[$key] = false;
                $this->logger->log('delete', 'error', $e->getMessage(), $key);
            }
        }
        
        return $results;
    }
    
    public function exists($key) {
        try {
            return $this->connection->withRetry(function($conn) use ($key) {
                $size = ftp_size($conn, $this->config->get('ftp_basedir', '/') . '/' . $key);
                return $size !== -1;
            });
        } catch (Exception $e) {
            $this->logger->log('exists', 'error', $e->getMessage(), $key);
            return false;
        }
    }
    
    private function validateFile($file_path) {
        // 检查文件是否存在
        if (!file_exists($file_path)) {
            throw new Exception("文件不存在: {$file_path}");
        }
        
        // 检查文件大小
        $file_size = filesize($file_path);
        if ($file_size > $this->max_file_size) {
            throw new Exception("文件大小超过限制: " . size_format($file_size));
        }
        
        // 检查文件类型
        $ext = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
        if (!in_array($ext, $this->allowed_types)) {
            throw new Exception("不支持的文件类型: {$ext}");
        }
        
        // 检查文件是否可读
        if (!is_readable($file_path)) {
            throw new Exception("文件不可读: {$file_path}");
        }
    }
    
    private function ensureDirectoryExists($path, $conn) {
        $path = trim($path, '/');
        $parts = explode('/', $path);
        $current = '';
        
        foreach ($parts as $part) {
            $current .= '/' . $part;
            if (!@ftp_chdir($conn, $this->config->get('ftp_basedir', '/') . $current)) {
                if (!@ftp_mkdir($conn, $this->config->get('ftp_basedir', '/') . $current)) {
                    throw new Exception("无法创建目录: {$current}");
                }
            }
        }
        
        // 返回到根目录
        @ftp_chdir($conn, $this->config->get('ftp_basedir', '/'));
    }
    
    public function setAllowedTypes($types) {
        if (is_array($types)) {
            $this->allowed_types = array_map('strtolower', $types);
        }
    }
    
    public function setMaxFileSize($size) {
        $this->max_file_size = (int)$size;
    }
} 