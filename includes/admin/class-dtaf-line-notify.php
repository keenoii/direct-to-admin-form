<?php
/**
 * Line Notify class
 * 
 * Handles Line Notify integration
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class DTAF_Line_Notify {
    /**
     * Send Line Notify message for new submission
     *
     * @param int $submission_id Submission ID
     * @param array $submission_data Submission data
     * @return bool True if message was sent successfully, false otherwise
     */
    public static function send_new_submission_notification($submission_id, $submission_data) {
        // Get settings
        $options = get_option('dtaf_settings', []);
        
        // Check if Line Notify is enabled
        if (!isset($options['enable_line_notify']) || !$options['enable_line_notify']) {
            return false;
        }
        
        // Check if token is set
        if (!isset($options['line_notify_token']) || empty($options['line_notify_token'])) {
            return false;
        }
        
        // Get message template
        $message_template = isset($options['line_notify_message']) ? $options['line_notify_message'] : "มีเรื่องร้องเรียนใหม่\nเรื่อง: {subject}\nจาก: {name}\nประเภท: {type}\nวันที่: {date}";
        
        // Replace placeholders
        $message = str_replace(
            ['{id}', '{name}', '{subject}', '{type}', '{date}', '{email}', '{phone}'],
            [
                $submission_id,
                $submission_data['name'],
                $submission_data['subject'],
                $submission_data['type'],
                date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($submission_data['created_at'])),
                $submission_data['email'],
                $submission_data['phone']
            ],
            $message_template
        );
        
        // Send notification
        return self::send_notification($options['line_notify_token'], $message);
    }
    
    /**
     * Send Line Notify message for status update
     *
     * @param int $submission_id Submission ID
     * @param string $new_status New status
     * @param string $admin_notes Admin notes
     * @param array $submission_data Submission data
     * @return bool True if message was sent successfully, false otherwise
     */
    public static function send_status_update_notification($submission_id, $new_status, $admin_notes, $submission_data) {
        // Get settings
        $options = get_option('dtaf_settings', []);
        
        // Check if Line Notify is enabled
        if (!isset($options['enable_line_notify']) || !$options['enable_line_notify']) {
            return false;
        }
        
        // Check if token is set
        if (!isset($options['line_notify_token']) || empty($options['line_notify_token'])) {
            return false;
        }
        
        // Build message
        $message = sprintf(
            __("มีการอัปเดตสถานะเรื่องร้องเรียน\nID: %d\nเรื่อง: %s\nสถานะใหม่: %s\nหมายเหตุ: %s", 'direct-to-admin-form'),
            $submission_id,
            $submission_data['subject'],
            $new_status,
            !empty($admin_notes) ? $admin_notes : '-'
        );
        
        // Send notification
        return self::send_notification($options['line_notify_token'], $message);
    }
    
    /**
     * Send Line Notify notification
     *
     * @param string $token Line Notify token
     * @param string $message Message to send
     * @return bool True if message was sent successfully, false otherwise
     */
    private static function send_notification($token, $message) {
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
            error_log('Line Notify Error: ' . $response->get_error_message());
            return false;
        }
        
        // Check response code
        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code !== 200) {
            error_log('Line Notify Error: HTTP ' . $response_code);
            return false;
        }
        
        return true;
    }
}
