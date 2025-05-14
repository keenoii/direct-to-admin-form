<?php
/**
 * Form class
 * 
 * Handles form rendering and processing
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class DTAF_Form {
    /**
     * Render form
     *
     * @param array $atts Shortcode attributes
     * @return string Form HTML
     */
    public function render($atts) {
        // Parse shortcode attributes
        $atts = shortcode_atts([
            'form_id' => 'default_form',
        ], $atts, 'direct_to_admin_form');
        
        $form_id = sanitize_text_field($atts['form_id']);
        
        // Get form data
        $form = DTAF_Database::get_form($form_id);
        if (!$form) {
            return '<p class="dtaf-error">' . esc_html__('แบบฟอร์มไม่ถูกต้องหรือไม่พบ', 'direct-to-admin-form') . '</p>';
        }
        
        // Parse form settings
        $form_settings = json_decode($form->form_settings, true);
        if (!$form_settings) {
            $form_settings = [];
        }
        
        // Get global settings for fallbacks
        $global_settings = get_option('dtaf_settings', []);
        
        // Merge settings with defaults
        $settings = wp_parse_args($form_settings, [
            'recipient_email' => isset($global_settings['recipient_email']) ? $global_settings['recipient_email'] : get_option('admin_email'),
            'email_subject_prefix' => isset($global_settings['email_subject_prefix']) ? $global_settings['email_subject_prefix'] : __('[สายตรงผู้บริหาร]', 'direct-to-admin-form'),
            'success_message' => isset($global_settings['success_message']) ? $global_settings['success_message'] : __('ส่งข้อมูลร้องเรียนเรียบร้อยแล้ว ขอบคุณสำหรับข้อมูลของท่าน', 'direct-to-admin-form'),
            'complaint_types' => isset($global_settings['complaint_types']) ? array_map('trim', explode("\n", $global_settings['complaint_types'])) : [],
            'form_color' => '#0073aa',
            'button_color' => '#0073aa',
            'show_address_field' => true,
            'required_fields' => ['name', 'idcard', 'phone', 'email', 'type', 'subject', 'detail'],
        ]);
        
        // Check for flash messages
        $message = '';
        $message_type = '';
        
        if (isset($_SESSION['dtaf_message']) && isset($_SESSION['dtaf_form_id']) && $_SESSION['dtaf_form_id'] === $form_id) {
            $message_type = $_SESSION['dtaf_message_type'];
            $message = $_SESSION['dtaf_message'];
            
            // Clear session
            unset($_SESSION['dtaf_message']);
            unset($_SESSION['dtaf_message_type']);
            unset($_SESSION['dtaf_form_id']);
        }
        
        // Start output buffer
        ob_start();
        
        // Include form template
        include DTAF_PLUGIN_DIR . 'templates/frontend/form-template.php';
        
        return ob_get_clean();
    }
    
    /**
     * Process form submission
     *
     * @param array $data Form data
     * @return array|WP_Error Result array or error
     */
    public function process_submission($data) {
        // Verify nonce
        if (!isset($data['dtaf_nonce']) || !DTAF_Security::verify_nonce($data['dtaf_nonce'], 'dtaf_submit_form')) {
            return new WP_Error('invalid_nonce', __('การตรวจสอบความปลอดภัยล้มเหลว โปรดลองอีกครั้ง', 'direct-to-admin-form'));
        }
        
        // Get form ID
        $form_id = isset($data['form_id']) ? sanitize_text_field($data['form_id']) : 'default_form';
        
        // Get form data
        $form = DTAF_Database::get_form($form_id);
        if (!$form) {
            return new WP_Error('invalid_form', __('แบบฟอร์มไม่ถูกต้องหรือไม่พบ', 'direct-to-admin-form'));
        }
        
        // Parse form settings
        $form_settings = json_decode($form->form_settings, true);
        if (!$form_settings) {
            $form_settings = [];
        }
        
        // Get global settings
        $global_settings = get_option('dtaf_settings', []);
        
        // Check honeypot if enabled
        if (isset($global_settings['enable_honeypot']) && $global_settings['enable_honeypot']) {
            if (!DTAF_Security::verify_honeypot()) {
                // Log potential spam attempt
                error_log('DTAF: Honeypot triggered for form ' . $form_id . ' from IP ' . $_SERVER['REMOTE_ADDR']);
                
                // Return generic error to avoid revealing the honeypot
                return new WP_Error('submission_error', __('เกิดข้อผิดพลาดในการส่งข้อมูล โปรดลองอีกครั้ง', 'direct-to-admin-form'));
            }
        }
        
        // Get required fields
        $required_fields = isset($form_settings['required_fields']) ? $form_settings['required_fields'] : ['name', 'idcard', 'phone', 'email', 'type', 'subject', 'detail'];
        
        // Sanitize and validate form data
        $sanitized_data = DTAF_Security::sanitize_form_data($data, $required_fields);
        if (is_wp_error($sanitized_data)) {
            return $sanitized_data;
        }
        
        // Prepare submission data
        $submission_data = [
            'form_id' => $form_id,
            'name' => $sanitized_data['name'],
            'idcard' => $sanitized_data['idcard'],
            'phone' => $sanitized_data['phone'],
            'email' => $sanitized_data['email'],
            'address' => isset($sanitized_data['address']) ? $sanitized_data['address'] : '',
            'type' => $sanitized_data['type'],
            'subject' => $sanitized_data['subject'],
            'detail' => $sanitized_data['detail'],
            'created_at' => current_time('mysql'),
            'status' => __('ใหม่', 'direct-to-admin-form'),
            'ip_address' => $_SERVER['REMOTE_ADDR'],
            'user_agent' => $_SERVER['HTTP_USER_AGENT'],
        ];
        
        // Handle file uploads
        $uploaded_files = [];
        $file_paths = [];
        $allowed_types = isset($global_settings['allowed_file_types']) ? $global_settings['allowed_file_types'] : 'jpg,jpeg,png,pdf,doc,docx';
        $max_file_size = isset($global_settings['max_file_size']) ? (int)$global_settings['max_file_size'] : 5;
        
        // Process up to 3 file uploads
        for ($i = 1; $i <= 3; $i++) {
            $file_key = 'dtaf_file_' . $i;
            
            if (isset($_FILES[$file_key]) && !empty($_FILES[$file_key]['name'])) {
                $file_result = DTAF_Security::validate_file_upload($_FILES[$file_key], $allowed_types, $max_file_size);
                
                if (is_wp_error($file_result)) {
                    return $file_result;
                }
                
                // Upload the file
                if (!function_exists('wp_handle_upload')) {
                    require_once(ABSPATH . 'wp-admin/includes/file.php');
                }
                
                $upload_overrides = ['test_form' => false];
                $uploaded_file = wp_handle_upload($_FILES[$file_key], $upload_overrides);
                
                if (isset($uploaded_file['error'])) {
                    return new WP_Error('upload_error', $uploaded_file['error']);
                }
                
                $uploaded_files[] = $uploaded_file['file']; // Path for email attachments
                $file_paths[] = $uploaded_file['url']; // URL for database storage
            }
        }
        
        // Store file paths in submission data
        if (!empty($file_paths)) {
            $submission_data['file_paths'] = serialize($file_paths);
        }
        
        // Insert submission into database
        $submission_id = DTAF_Database::insert_submission($submission_data);
        
        if (!$submission_id) {
            return new WP_Error('db_error', __('เกิดข้อผิดพลาดในการบันทึกข้อมูล โปรดลองอีกครั้ง', 'direct-to-admin-form'));
        }
        
        // Send email notification
        $email_sent = DTAF_Notification::send_new_submission_email($submission_id, $submission_data, $uploaded_files, $form_settings);
        
        return [
            'success' => true,
            'submission_id' => $submission_id,
            'email_sent' => $email_sent,
            'message' => isset($form_settings['success_message']) ? $form_settings['success_message'] : __('ส่งข้อมูลร้องเรียนเรียบร้อยแล้ว ขอบคุณสำหรับข้อมูลของท่าน', 'direct-to-admin-form'),
        ];
    }
}
