<?php

class LaobuluoFtpMod
{
    private $conn_id;
    private $ftp_basedir = "/";  # 必须以/开头，默认为/

    public function __construct($option)
    {
        // 建立基础连接
        $this->conn_id = ftp_connect($option['ftp_server'], $option['ftp_port']);
        // 使用用户名和口令登录
        $login_result = ftp_login($this->conn_id, $option['ftp_user_name'], $option['ftp_user_pass']);
        // 检查是否成功
        if (!$this->conn_id) {
            error_log("FTP连接失败: 无法连接到服务器 " . $option['ftp_server']);
            throw new Exception("FTP连接失败: 无法连接到FTP服务器");
        }
        if (!$login_result) {
            error_log("FTP登录失败: 用户名或密码错误 " . $option['ftp_user_name']);
            throw new Exception("FTP登录失败: 请检查用户名和密码");
        }
        if ($option['ftp_basedir'] != $this->ftp_basedir) {
            $this->ftp_basedir = $option['ftp_basedir'];
        }
        if (!@ftp_chdir($this->conn_id, $this->ftp_basedir)) {
            if (!@ftp_mkdir($this->conn_id, $this->ftp_basedir)) {
                throw new Exception("FTP错误: 无法创建基础目录 " . $this->ftp_basedir);
            }
            if (!@ftp_chdir($this->conn_id, $this->ftp_basedir)) {
                throw new Exception("FTP错误: 无法切换到基础目录 " . $this->ftp_basedir);
            }
        }
        // 设置FTP被动模式
        if (!@ftp_pasv($this->conn_id, $option['ftp_pasv'])) {
            error_log("FTP被动模式设置失败");
            // 不抛出异常，因为某些服务器可能不支持被动模式
        }
    }


    protected function ftp_mksubdirs($ftpcon, $ftpbasedir, $ftpath)
    {
        @ftp_chdir($ftpcon, $ftpbasedir); // /var/www/uploads
        $parts = explode('/', $ftpath); // 2013/06/11/username
        foreach ($parts as $part) {
            if (!@ftp_chdir($ftpcon, $part)) {
                ftp_mkdir($ftpcon, $part);
                ftp_chdir($ftpcon, $part);
                //ftp_chmod($ftpcon, 0777, $part);
            }
        }
    }

    # key 不能以/开头，否则要去掉ftp_put中连接的/
    public function Upload($key, $localFilePath)
    {
        // 1. 检测路径是否存在
        if (!@ftp_chdir($this->conn_id, dirname($key))) {
            $this->ftp_mksubdirs($this->conn_id, $this->ftp_basedir, dirname($key));
        }
        // 上传文件
        return ftp_put($this->conn_id, $this->ftp_basedir . '/' . $key, $localFilePath, FTP_BINARY);  // 成功时返回 TRUE， 或者在失败时返回 FALSE。
    }

    public function Delete($keys)
    {
        # 在连接内循环比在调用时循环节省连接次数。在外面循环需要重复创建数组个数的连接次数
        foreach ($keys as $key) {
            ftp_delete($this->conn_id, $this->ftp_basedir . '/' . $key);
        }
    }

    public function hasExist($key)
    {
        // 文件夹会返回-1,
        // 执行成功返回文件大小，失败返回 -1。有些 FTP 服务器可能不支持此特性。
        $res = ftp_size($this->conn_id, $this->ftp_basedir . '/' . $key);
        if ($res != -1) {
            return True;
        } else {
            return False;
        }
    }


    public function __destruct()
    {
        // TODO: Implement __destruct() method.
        ftp_close($this->conn_id);  // 关闭 FTP 流
    }
}


class LaobuluoFtpApi
{
    private $options;

    public function __construct($option)
    {
        $this->options = $option;
    }
    # key 不能以/开头，否则要去掉ftp_put中连接的/
    public function Upload($key, $localFilePath)
    {
        $ftp = new LaobuluoFtpMod($this->options);
        return $ftp->Upload($key, $localFilePath);  // 成功时返回 TRUE， 或者在失败时返回 FALSE。
    }

    public function Delete($keys)
    {
        $ftp = new LaobuluoFtpMod($this->options);
        $ftp->Delete($keys);
    }

    public function hasExist($key)
    {
        $ftp = new LaobuluoFtpMod($this->options);
        return $ftp->hasExist($key);
    }
}
