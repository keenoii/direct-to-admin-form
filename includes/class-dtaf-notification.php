<?php
/**
 * Notification class
 * 
 * Handles email notifications and other notification types
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class DTAF_Notification {
    /**
     * Send email notification for new submission
     *
     * @param int $submission_id Submission ID
     * @param array $submission_data Submission data
     * @param array $attachments File attachments
     * @param array $form_settings Form settings
     * @return bool True if email was sent successfully, false otherwise
     */
    public static function send_new_submission_email($submission_id, $submission_data, $attachments = [], $form_settings = []) {
        // Get recipient email
        $recipient = isset($form_settings['recipient_email']) ? $form_settings['recipient_email'] : get_option('admin_email');
        
        // Get email subject prefix
        $subject_prefix = isset($form_settings['email_subject_prefix']) ? $form_settings['email_subject_prefix'] : __('[สายตรงผู้บริหาร]', 'direct-to-admin-form');
        
        // Build email subject
        $subject = $subject_prefix . ' ' . __('เรื่อง', 'direct-to-admin-form') . ': ' . $submission_data['subject'];
        
        // Build email body
        $body = self::build_email_body($submission_id, $submission_data);
        
        // Set email headers
        $headers = ['Content-Type: text/html; charset=UTF-8'];
        
        // Add From header
        $site_name = get_bloginfo('name');
        $site_domain = preg_replace('/^www\./', '', parse_url(home_url(), PHP_URL_HOST));
        if (empty($site_domain)) {
            $site_domain = 'localhost';
        }
        $headers[] = 'From: ' . $site_name . ' <noreply@' . $site_domain . '>';
        
        // Send email
        return wp_mail($recipient, $subject, $body, $headers, $attachments);
    }
    
    /**
     * Build email body for new submission
     *
     * @param int $submission_id Submission ID
     * @param array $submission_data Submission data
     * @return string Email body HTML
     */
    private static function build_email_body($submission_id, $submission_data) {
        $admin_url = admin_url('admin.php?page=dtaf-submissions&action=view&submission_id=' . $submission_id);
        
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
            <title><?php echo esc_html($submission_data['subject']); ?></title>
            <style type="text/css">
                body {
                    font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
                    color: #333;
                    line-height: 1.5;
                }
                .container {
                    max-width: 600px;
                    margin: 0 auto;
                    padding: 20px;
                }
                .header {
                    background-color: #0073aa;
                    color: #fff;
                    padding: 20px;
                    text-align: center;
                    border-radius: 5px 5px 0 0;
                }
                .content {
                    background-color: #f9f9f9;
                    padding: 20px;
                    border: 1px solid #ddd;
                    border-top: none;
                    border-radius: 0 0 5px 5px;
                }
                .footer {
                    margin-top: 20px;
                    text-align: center;
                    font-size: 12px;
                    color: #777;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                }
                table td {
                    padding: 8px;
                    border-bottom: 1px solid #eee;
                }
                table th {
                    text-align: left;
                    padding: 8px;
                    border-bottom: 1px solid #eee;
                    width: 30%;
                }
                .button {
                    display: inline-block;
                    background-color: #0073aa;
                    color: #fff;
                    padding: 10px 20px;
                    text-decoration: none;
                    border-radius: 3px;
                    margin-top: 20px;
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1><?php esc_html_e('เรื่องร้องเรียนใหม่', 'direct-to-admin-form'); ?></h1>
                </div>
                <div class="content">
                    <p>
                        <?php 
                        printf(
                            esc_html__('คุณได้รับเรื่องร้องเรียนใหม่ผ่านระบบ (ID: %s, Form: %s):', 'direct-to-admin-form'),
                            $submission_id,
                            isset($submission_data['form_id']) ? $submission_data['form_id'] : 'default_form'
                        ); 
                        ?>
                    </p>
                    
                    <table>
                        <tr>
                            <th><?php esc_html_e('ชื่อ-นามสกุลผู้ร้อง:', 'direct-to-admin-form'); ?></th>
                            <td><?php echo esc_html($submission_data['name']); ?></td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e('บัตรประชาชน:', 'direct-to-admin-form'); ?></th>
                            <td><?php echo esc_html($submission_data['idcard']); ?></td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e('โทรศัพท์:', 'direct-to-admin-form'); ?></th>
                            <td><?php echo esc_html($submission_data['phone']); ?></td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e('อีเมล:', 'direct-to-admin-form'); ?></th>
                            <td><?php echo esc_html($submission_data['email']); ?></td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e('ที่อยู่:', 'direct-to-admin-form'); ?></th>
                            <td><?php echo esc_html(!empty($submission_data['address']) ? $submission_data['address'] : '-'); ?></td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e('ประเภทเรื่อง:', 'direct-to-admin-form'); ?></th>
                            <td><?php echo esc_html($submission_data['type']); ?></td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e('หัวข้อเรื่อง:', 'direct-to-admin-form'); ?></th>
                            <td><?php echo esc_html($submission_data['subject']); ?></td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e('รายละเอียด:', 'direct-to-admin-form'); ?></th>
                            <td><?php echo nl2br(esc_html($submission_data['detail'])); ?></td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e('วันที่ส่ง:', 'direct-to-admin-form'); ?></th>
                            <td><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($submission_data['created_at']))); ?></td>
                        </tr>
                    </table>
                    
                    <div style="text-align: center;">
                        <a href="<?php echo esc_url($admin_url); ?>" class="button">
                            <?php esc_html_e('ดูรายละเอียดในระบบ Admin', 'direct-to-admin-form'); ?>
                        </a>
                    </div>
                </div>
                <div class="footer">
                    <p>
                        <?php 
                        printf(
                            esc_html__('อีเมลนี้ถูกส่งจากเว็บไซต์ %s', 'direct-to-admin-form'),
                            get_bloginfo('name')
                        ); 
                        ?>
                    </p>
                </div>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Send status update notification to submitter
     *
     * @param int $submission_id Submission ID
     * @param string $new_status New status
     * @param string $admin_notes Admin notes
     * @param array $submission_data Submission data
     * @return bool True if email was sent successfully, false otherwise
     */
    public static function send_status_update_email($submission_id, $new_status, $admin_notes, $submission_data) {
        // Only send if we have the submitter's email
        if (empty($submission_data['email'])) {
            return false;
        }
        
        // Build email subject
        $subject = sprintf(
            __('[%s] อัปเดตสถานะเรื่องร้องเรียน: %s', 'direct-to-admin-form'),
            get_bloginfo('name'),
            $submission_data['subject']
        );
        
        // Build email body
        $body = self::build_status_update_email($submission_id, $new_status, $admin_notes, $submission_data);
        
        // Set email headers
        $headers = ['Content-Type: text/html; charset=UTF-8'];
        
        // Add From header
        $site_name = get_bloginfo('name');
        $site_domain = preg_replace('/^www\./', '', parse_url(home_url(), PHP_URL_HOST));
        if (empty($site_domain)) {
            $site_domain = 'localhost';
        }
        $headers[] = 'From: ' . $site_name . ' <noreply@' . $site_domain . '>';
        
        // Send email
        return wp_mail($submission_data['email'], $subject, $body, $headers);
    }
    
    /**
     * Build email body for status update
     *
     * @param int $submission_id Submission ID
     * @param string $new_status New status
     * @param string $admin_notes Admin notes
     * @param array $submission_data Submission data
     * @return string Email body HTML
     */
    private static function build_status_update_email($submission_id, $new_status, $admin_notes, $submission_data) {
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
            <title><?php echo esc_html($submission_data['subject']); ?></title>
            <style type="text/css">
                body {
                    font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
                    color: #333;
                    line-height: 1.5;
                }
                .container {
                    max-width: 600px;
                    margin: 0 auto;
                    padding: 20px;
                }
                .header {
                    background-color: #0073aa;
                    color: #fff;
                    padding: 20px;
                    text-align: center;
                    border-radius: 5px 5px 0 0;
                }
                .content {
                    background-color: #f9f9f9;
                    padding: 20px;
                    border: 1px solid #ddd;
                    border-top: none;
                    border-radius: 0 0 5px 5px;
                }
                .footer {
                    margin-top: 20px;
                    text-align: center;
                    font-size: 12px;
                    color: #777;
                }
                .status {
                    display: inline-block;
                    padding: 5px 10px;
                    border-radius: 3px;
                    background-color: #f0f0f0;
                    font-weight: bold;
                }
                .status-new {
                    background-color: #cce5ff;
                    color: #004085;
                }
                .status-in-progress {
                    background-color: #fff3cd;
                    color: #856404;
                }
                .status-resolved {
                    background-color: #d4edda;
                    color: #155724;
                }
                .status-closed {
                    background-color: #f8d7da;
                    color: #721c24;
                }
                .notes {
                    margin-top: 20px;
                    padding: 15px;
                    background-color: #f0f0f0;
                    border-radius: 3px;
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1><?php esc_html_e('อัปเดตสถานะเรื่องร้องเรียน', 'direct-to-admin-form'); ?></h1>
                </div>
                <div class="content">
                    <p>
                        <?php 
                        printf(
                            esc_html__('เรียน %s,', 'direct-to-admin-form'),
                            esc_html($submission_data['name'])
                        ); 
                        ?>
                    </p>
                    
                    <p>
                        <?php 
                        printf(
                            esc_html__('เรื่องร้องเรียนของคุณเรื่อง "%s" (ID: %s) ได้รับการอัปเดตสถานะแล้ว', 'direct-to-admin-form'),
                            esc_html($submission_data['subject']),
                            $submission_id
                        ); 
                        ?>
                    </p>
                    
                    <p>
                        <?php esc_html_e('สถานะใหม่:', 'direct-to-admin-form'); ?>
                        <span class="status status-<?php echo esc_attr(sanitize_title($new_status)); ?>">
                            <?php echo esc_html($new_status); ?>
                        </span>
                    </p>
                    
                    <?php if (!empty($admin_notes)): ?>
                    <div class="notes">
                        <h3><?php esc_html_e('หมายเหตุจากผู้ดูแลระบบ:', 'direct-to-admin-form'); ?></h3>
                        <p><?php echo nl2br(esc_html($admin_notes)); ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <p>
                        <?php esc_html_e('ขอบคุณที่ใช้บริการระบบสายตรงผู้บริหารของเรา', 'direct-to-admin-form'); ?>
                    </p>
                </div>
                <div class="footer">
                    <p>
                        <?php 
                        printf(
                            esc_html__('อีเมลนี้ถูกส่งจากเว็บไซต์ %s', 'direct-to-admin-form'),
                            get_bloginfo('name')
                        ); 
                        ?>
                    </p>
                    <p>
                        <?php esc_html_e('โปรดอย่าตอบกลับอีเมลนี้ เนื่องจากส่งจากระบบอัตโนมัติ', 'direct-to-admin-form'); ?>
                    </p>
                </div>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
}
