<?php
if (!defined('ABSPATH')) die();

// 获取配置实例
$config = WPFTPConfig::getInstance();
$options = $config->getAll();

// 处理表单提交
if (isset($_POST['submit']) && check_admin_referer()) {
    if ($_POST['type'] == 'info_set') {
        $new_options = array(
            'no_local_file' => isset($_POST['no_local_file']),
            'ftp_server' => isset($_POST['ftp_server']) ? sanitize_text_field(trim(stripslashes($_POST['ftp_server']))) : '',
            'ftp_port' => isset($_POST['ftp_port']) ? sanitize_text_field(trim(stripslashes($_POST['ftp_port']))) : 21,
            'ftp_user_name' => isset($_POST['ftp_user_name']) ? sanitize_text_field(trim(stripslashes($_POST['ftp_user_name']))) : '',
            'ftp_user_pass' => isset($_POST['ftp_user_pass']) ? sanitize_text_field(trim(stripslashes($_POST['ftp_user_pass']))) : '',
            'ftp_pasv' => isset($_POST['ftp_pasv']),
            'opt' => array(
                'auto_rename' => isset($_POST['auto_rename']),
            ),
        );

        // 处理FTP存储子目录
        $basedir = isset($_POST['ftp_basedir']) ? sanitize_text_field(trim(stripslashes($_POST['ftp_basedir']))) : '';
        $basedir = '/' . trim($basedir, '/');
        $basedir = str_replace(['../', './'], '', $basedir);
        $new_options['ftp_basedir'] = $basedir;

        // 更新配置
        $config->update($new_options);

        // 更新上传URL路径
        if (isset($_POST['upload_url_path'])) {
            update_option('upload_url_path', esc_url_raw(trim(stripslashes($_POST['upload_url_path']))));
        }

        // 处理缩略图设置
        if (isset($_POST['disable_thumb'])) {
            $thumb_sizes = array(
                'thumbnail_size_w' => get_option('thumbnail_size_w'),
                'thumbnail_size_h' => get_option('thumbnail_size_h'),
                'medium_size_w' => get_option('medium_size_w'),
                'medium_size_h' => get_option('medium_size_h'),
                'large_size_w' => get_option('large_size_w'),
                'large_size_h' => get_option('large_size_h'),
                'medium_large_size_w' => get_option('medium_large_size_w'),
                'medium_large_size_h' => get_option('medium_large_size_h'),
            );
            
            $new_options['opt']['thumbsize'] = $thumb_sizes;
            $config->update($new_options);

            // 禁用缩略图
            update_option('thumbnail_size_w', 0);
            update_option('thumbnail_size_h', 0);
            update_option('medium_size_w', 0);
            update_option('medium_size_h', 0);
            update_option('large_size_w', 0);
            update_option('large_size_h', 0);
            update_option('medium_large_size_w', 0);
            update_option('medium_large_size_h', 0);
        } else if (isset($options['opt']['thumbsize'])) {
            // 恢复缩略图设置
            $thumb_sizes = $options['opt']['thumbsize'];
            update_option('thumbnail_size_w', $thumb_sizes['thumbnail_size_w']);
            update_option('thumbnail_size_h', $thumb_sizes['thumbnail_size_h']);
            update_option('medium_size_w', $thumb_sizes['medium_size_w']);
            update_option('medium_size_h', $thumb_sizes['medium_size_h']);
            update_option('large_size_w', $thumb_sizes['large_size_w']);
            update_option('large_size_h', $thumb_sizes['large_size_h']);
            update_option('medium_large_size_w', $thumb_sizes['medium_large_size_w']);
            update_option('medium_large_size_h', $thumb_sizes['medium_large_size_h']);
            
            unset($new_options['opt']['thumbsize']);
            $config->update($new_options);
        }

        add_settings_error('wpftp_messages', 'wpftp_message', '设置已保存', 'success');
    } else if ($_POST['type'] == 'info_replace') {
        global $wpdb;
        
        $original_content = home_url('/wp-content/uploads');
        $new_content = get_option('upload_url_path');
        
        $wpdb->query(
            "UPDATE {$wpdb->prefix}posts SET `post_content` = REPLACE(`post_content`, '{$original_content}', '{$new_content}');"
        );
        
        $new_options = $options;
        $new_options['opt']['legacy_data_replace'] = 1;
        $config->update($new_options);
        
        add_settings_error('wpftp_messages', 'wpftp_message', '内容替换完成', 'success');
    }
    
    // 刷新配置
    $options = $config->getAll();
}
?>

<link rel='stylesheet'  href='<?php echo plugin_dir_url( __FILE__ );?>layui/css/layui.css' />
<link rel='stylesheet'  href='<?php echo plugin_dir_url( __FILE__ );?>layui/css/laobuluo.css'/>
<script src='<?php echo plugin_dir_url( __FILE__ );?>layui/layui.js'></script>
<style type="text/css">
    .wpftpproform .layui-form-label{width:120px;}
    .wpftpproform .layui-input{width: 350px;}
    .wpftpproform .layui-input_eyes{width: 550px;}
    .wpftpproform .layui-form-mid{margin-left:3.5%;}
    .laobuluo-wp-hidden {position: relative;}
    .laobuluo-wp-hidden .laobuluo-wp-eyes{padding: 5px;position:absolute;top:3px;z-index: 999;display: none;}
    .laobuluo-wp-hidden i{font-size:20px;}
    .laobuluo-wp-hidden i.dashicons-visibility{color:#009688 ;}
</style>

<div class="wrap">
    <h1 class="wp-heading-inline"></h1>
    <?php settings_errors('wpftp_messages'); ?>
</div>

<div class="container-laobuluo-main">
   <div class="laobuluo-wbs-header" style="margin-bottom: 15px;">
        <div class="laobuluo-wbs-logo">
            <a><img src="<?php echo plugin_dir_url(__FILE__); ?>layui/images/logo.png"></a>
            <span class="wbs-span">WPFTP Pro自建FTP空间存储插件</span>
            <span class="wbs-free">Pro V5.0</span>
        </div>
        <div class="laobuluo-wbs-btn">
            <a class="layui-btn layui-btn-primary" href="https://www.lezaiyun.com/?utm_source=wpftppro-setting&utm_media=link&utm_campaign=header" target="_blank">
                <i class="layui-icon layui-icon-home"></i> 乐在云工作室
            </a>
            <a class="layui-btn layui-btn-primary" href="https://www.lezaiyun.com/826.html?utm_source=wpftppro-setting&utm_media=link&utm_campaign=header" target="_blank">
                <i class="layui-icon layui-icon-release"></i> 插件教程
            </a>
        </div>
    </div>
</div>

<!-- 内容部分 -->
<div class="container-laobuluo-main">
    <div class="layui-container container-m">
        <div class="layui-row layui-col-space15">
            <!-- 左边设置部分 -->
            <div class="layui-col-md9">
                <div class="laobuluo-panel">
                    <div class="laobuluo-controw">
                        <fieldset class="layui-elem-field layui-field-title site-title">
                            <legend><a name="get">设置选项</a></legend>
                        </fieldset>
                        
                        <form class="layui-form wpftpproform" action="<?php echo wp_nonce_url(admin_url('options-general.php?page=wpftp-settings')); ?>" method="post">
                            <div class="layui-form-item">
                                <label class="layui-form-label">FTP服务器IP地址</label>
                                <div class="layui-input-block">
                                    <input class="layui-input" type="text" name="ftp_server" value="<?php echo esc_attr($options['ftp_server'] ?? ''); ?>" placeholder="FTP服务器IP地址"/>
                                    <div class="layui-form-mid layui-word-aux">填写虚拟主机IP地址。示例：<code>123.123.123.123</code></div>
                                </div>
                            </div>

                            <div class="layui-form-item">
                                <label class="layui-form-label">FTP端口</label>
                                <div class="layui-input-block">
                                    <input class="layui-input" type="text" name="ftp_port" value="<?php echo esc_attr($options['ftp_port'] ?? '21'); ?>" placeholder="21"/>
                                    <div class="layui-form-mid layui-word-aux">FTP端口配置。默认：<code>21</code></div>
                                </div>
                            </div>

                            <div class="layui-form-item">
                                <label class="layui-form-label">FTP空间绑定域名</label>
                                <div class="layui-input-block">
                                    <input type="text" class="layui-input layui-input_eyes" name="upload_url_path" value="<?php echo esc_url(get_option('upload_url_path')); ?>" placeholder="FTP空间绑定域名"/>
                                    <div class="layui-form-mid layui-word-aux">
                                        <p><b>设置注意事项：</b></p>
                                        <p>1. 一般我们是以：<code>http://{FTP空间绑定域名}</code>，同样不要用"/"结尾。</p>
                                        <p>2. 示范： <code>http(s)://ftp.laobuluo.com</code></p>
                                    </div>
                                </div>
                            </div>

                            <div class="layui-form-item">
                                <label class="layui-form-label">FTP用户名</label>
                                <div class="layui-input-block">
                                    <div class="laobuluo-wp-hidden">
                                        <input class="layui-input layui-input_eyes" type="password" name="ftp_user_name" value="<?php echo esc_attr($options['ftp_user_name'] ?? ''); ?>" placeholder="FTP用户名"/>
                                        <span class="laobuluo-wp-eyes"><i class="dashicons dashicons-hidden"></i></span>
                                    </div>
                                </div>
                            </div>

                            <div class="layui-form-item">
                                <label class="layui-form-label">FTP密码</label>
                                <div class="layui-input-block">
                                    <div class="laobuluo-wp-hidden">
                                        <input class="layui-input layui-input_eyes" type="password" name="ftp_user_pass" value="<?php echo esc_attr($options['ftp_user_pass'] ?? ''); ?>" placeholder="FTP密码"/>
                                        <span class="laobuluo-wp-eyes"><i class="dashicons dashicons-hidden"></i></span>
                                    </div>
                                </div>
                            </div>

                            <div class="layui-form-item">
                                <label class="layui-form-label">FTP被动模式</label>
                                <div class="layui-input-inline" style="width:90px;">
                                    <input type="checkbox" name="ftp_pasv" title="开启" <?php checked(isset($options['ftp_pasv']) && $options['ftp_pasv']); ?>/>
                                </div>
                                <div class="layui-form-mid layui-word-aux">开启被动模式(不确定一定要开启)</div>
                            </div>

                            <div class="layui-form-item">
                                <label class="layui-form-label">FTP存储子目录</label>
                                <div class="layui-input-block">
                                    <input type="text" class="layui-input layui-input_eyes" name="ftp_basedir" value="<?php echo esc_attr($options['ftp_basedir'] ?? '/'); ?>" placeholder="FTP存储子目录(非必填,默认为/)"/>
                                    <div class="layui-form-mid layui-word-aux">
                                        <p>这个是要相对我们FTP空间根目录的，一般云服务器创建的就按照默认，有些虚拟主机是需要单独设置子目录的，比如/wwwroot/</p>
                                    </div>
                                </div>
                            </div>

                            <div class="layui-form-item">
                                <label class="layui-form-label">自动重命名</label>
                                <div class="layui-input-inline" style="width:90px;">
                                    <input type="checkbox" name="auto_rename" title="设置" <?php checked(isset($options['opt']['auto_rename']) && $options['opt']['auto_rename']); ?>/>
                                </div>
                                <div class="layui-form-mid layui-word-aux">上传文件自动重命名，解决中文文件名或者重复文件名问题</div>
                            </div>

                            <div class="layui-form-item">
                                <label class="layui-form-label">不在本地保存</label>
                                <div class="layui-input-inline" style="width:90px;">
                                    <input type="checkbox" name="no_local_file" title="设置" <?php checked(isset($options['no_local_file']) && $options['no_local_file']); ?>/>
                                </div>
                                <div class="layui-form-mid layui-word-aux">如果不想同步在服务器中备份静态文件就 "勾选"。</div>
                            </div>

                            <div class="layui-form-item">
                                <label class="layui-form-label">禁止缩略图</label>
                                <div class="layui-input-inline" style="width:90px;">
                                    <input type="checkbox" name="disable_thumb" title="禁止" <?php checked(isset($options['opt']['thumbsize'])); ?>/>
                                </div>
                                <div class="layui-form-mid layui-word-aux">仅生成和上传主图，禁止缩略图裁剪。</div>
                            </div>

                            <div class="layui-form-item">
                                <label class="layui-form-label"></label>
                                <div class="layui-input-block">
                                    <button class="layui-btn" type="submit" name="submit" value="保存设置">保存设置</button>
                                </div>
                            </div>
                            <input type="hidden" name="type" value="info_set">
                            <?php wp_nonce_field('wpftp_settings_action', 'wpftp_settings_nonce'); ?>
                        </form>

                                              
                    </div>
                </div>
            </div>

            <!-- 右边部分 -->
            <div class="layui-col-md3">
                <div id="nav">
                    <div class="laobuluo-panel">
                        <div class="laobuluo-panel-title">关注公众号</div>
                        <div class="laobuluo-code">
                            <img src="<?php echo plugin_dir_url(__FILE__); ?>layui/images/qrcode.png">
                            <p>微信扫码关注 <span class="layui-badge layui-bg-blue">老蒋朋友圈</span> 公众号</p>
                            <p><span class="layui-badge">优先</span> 获取插件更新 和 更多 <span class="layui-badge layui-bg-green">免费插件</span></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- footer -->
<div class="container-laobuluo-main">
    <div class="layui-container container-m">
        <div class="layui-row layui-col-space15">
            <div class="layui-col-md12">
                <div class="laobuluo-footer-code">
                    <span class="codeshow"></span>
                </div>
                <div class="laobuluo-links">
                    
                    <a href="https://www.lezaiyun.com/?utm_source=lbs-setting&utm_media=link&utm_campaign=footer" target="_blank">乐在云工作室</a>
                    <a href="https://www.lezaiyun.com/826.html?utm_source=lbs-setting&utm_media=link&utm_campaign=footer" target="_blank">使用说明</a>
                    
                </div>
            </div>
        </div>
    </div>
</div>

<script>
layui.use(['form', 'element'], function(){
    var form = layui.form;
    var element = layui.element;
    
    // 显示/隐藏密码
    jQuery('.laobuluo-wp-hidden').hover(function(){
        jQuery(this).find('.laobuluo-wp-eyes').show();
        jQuery(this).find('input').attr('type', 'text');
    }, function(){
        jQuery(this).find('.laobuluo-wp-eyes').hide();
        jQuery(this).find('input').attr('type', 'password');
    });
});
</script>