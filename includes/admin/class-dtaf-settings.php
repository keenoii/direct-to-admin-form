<?php
/**
 * Settings class
 * 
 * Handles plugin settings
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class DTAF_Settings {
    /**
     * Initialize the class
     */
    public function __construct() {
        // Register settings
        add_action('admin_init', [$this, 'register_settings']);
        
        // Register AJAX handlers
        add_action('wp_ajax_dtaf_test_line_notify', [$this, 'ajax_test_line_notify']);
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        // Register setting
        register_setting(
            'dtaf_settings_group',
            'dtaf_settings',
            [$this, 'sanitize_settings']
        );
    }
    
    /**
     * Sanitize settings
     *
     * @param array $input Settings input
     * @return array Sanitized settings
     */
    public function sanitize_settings($input) {
        $sanitized = [];
        
        // Recipient email
        if (isset($input['recipient_email'])) {
            $emails = explode(',', $input['recipient_email']);
            $sanitized_emails = [];
            
            foreach ($emails as $email) {
                $email = sanitize_email(trim($email));
                if (!empty($email)) {
                    $sanitized_emails[] = $email;
                }
            }
            
            $sanitized['recipient_email'] = implode(', ', $sanitized_emails);
        }
        
        // Email subject prefix
        if (isset($input['email_subject_prefix'])) {
            $sanitized['email_subject_prefix'] = sanitize_text_field($input['email_subject_prefix']);
        }
        
        // Success message
        if (isset($input['success_message'])) {
            $sanitized['success_message'] = sanitize_textarea_field($input['success_message']);
        }
        
        // Error message
        if (isset($input['error_message'])) {
            $sanitized['error_message'] = sanitize_textarea_field($input['error_message']);
        }
        
        // Complaint types
        if (isset($input['complaint_types'])) {
            $sanitized['complaint_types'] = sanitize_textarea_field($input['complaint_types']);
        }
        
        // Allowed file types
        if (isset($input['allowed_file_types'])) {
            $file_types = explode(',', $input['allowed_file_types']);
            $sanitized_types = [];
            
            foreach ($file_types as $type) {
                $type = sanitize_text_field(trim($type));
                if (!empty($type)) {
                    $sanitized_types[] = $type;
                }
            }
            
            $sanitized['allowed_file_types'] = implode(',', $sanitized_types);
        }
        
        // Max file size
        if (isset($input['max_file_size'])) {
            $max_size = intval($input['max_file_size']);
            $server_max = min((int)(ini_get('upload_max_filesize')), (int)(ini_get('post_max_size')));
            
            $sanitized['max_file_size'] = min($max_size, $server_max);
        }
        
        // Enable honeypot
        $sanitized['enable_honeypot'] = isset($input['enable_honeypot']) ? 1 : 0;
        
        // Line Notify token
        if (isset($input['line_notify_token'])) {
            $sanitized['line_notify_token'] = sanitize_text_field($input['line_notify_token']);
        }
        
        // Enable Line Notify
        $sanitized['enable_line_notify'] = isset($input['enable_line_notify']) ? 1 : 0;
        
        // Line Notify message
        if (isset($input['line_notify_message'])) {
            $sanitized['line_notify_message'] = sanitize_textarea_field($input['line_notify_message']);
        }
        
        // Delete data on uninstall
        $sanitized['delete_data'] = isset($input['delete_data']) ? 1 : 0;
        
        return $sanitized;
    }
    
    /**
     * Test Line Notify via AJAX
     */
    public function ajax_test_line_notify() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'dtaf_admin_nonce')) {
            wp_send_json_error([
                'message' => __('การตรวจสอบความปลอดภัยล้มเหลว', 'direct-to-admin-form')
            ]);
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error([
                'message' => __('คุณไม่มีสิทธิ์ดำเนินการนี้', 'direct-to-admin-form')
            ]);
        }
        
        // Get token
        $token = isset($_POST['token']) ? sanitize_text_field($_POST['token']) : '';
        
        if (empty($token)) {
            wp_send_json_error([
                'message' => __('กรุณากรอก Line Notify Token', 'direct-to-admin-form')
            ]);
        }
        
        // Test message
        $message = sprintf(
            __("ทดสอบการแจ้งเตือน Line Notify จากปลั๊กอิน Direct to Admin Form\nเวลา: %s\nเว็บไซต์: %s", 'direct-to-admin-form'),
            date_i18n(get_option('date_format') . ' ' . get_option('time_format')),
            get_bloginfo('name')
        );
        
        // Send test notification
        $result = $this->send_line_notify($token, $message);
        
        if ($result) {
            wp_send_json_success([
                'message' => __('ส่งการแจ้งเตือนทดสอบสำเร็จ', 'direct-to-admin-form')
            ]);
        } else {
            wp_send_json_error([
                'message' => __('เกิดข้อผิดพลาดในการส่งการแจ้งเตือน โปรดตรวจสอบ Token ของคุณ', 'direct-to-admin-form')
            ]);
        }
    }
    
    /**
     * Send Line Notify notification
     *
     * @param string $token Line Notify token
     * @param string $message Message to send
     * @return bool True if message was sent successfully, false otherwise
     */
    private function send_line_notify($token, $message) {
        // Line Notify API endpoint
        $api_url = 'https://notify-api.line.me/api/notify';
        
        // Prepare request
        $headers = [
            'Content-Type: application/x-www-form-urlencoded',
            'Authorization: Bearer ' . $token
        ];
        
        $data = ['message' => $message];
        
        // Use WordPress HTTP API
        $response = wp_remote_post($api_url, [
            'headers' => $headers,
            'body' => $data,
            'timeout' => 30
        ]);
        
        // Check for errors
        if (is_wp_error($response)) {
            return false;
        }
        
        // Check response code
        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code !== 200) {
            return false;
        }
        
        return true;
    }
}

// Initialize the class
new DTAF_Settings();
