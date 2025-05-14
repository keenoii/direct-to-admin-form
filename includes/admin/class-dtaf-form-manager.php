<?php
/**
 * Form Manager class
 * 
 * Handles form management functionality
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class DTAF_Form_Manager {
    /**
     * Initialize the class
     */
    public function __construct() {
        // Register AJAX handlers
        add_action('admin_post_dtaf_save_form', [$this, 'save_form']);
        add_action('wp_ajax_dtaf_delete_form', [$this, 'ajax_delete_form']);
        add_action('wp_ajax_dtaf_load_form_preview', [$this, 'ajax_load_form_preview']);
    }
    
    /**
     * Save form
     */
    public function save_form() {
        // Check nonce
        if (!isset($_POST['dtaf_form_nonce']) || !wp_verify_nonce($_POST['dtaf_form_nonce'], 'dtaf_save_form')) {
            wp_die(__('การตรวจสอบความปลอดภัยล้มเหลว', 'direct-to-admin-form'));
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('คุณไม่มีสิทธิ์เข้าถึงหน้านี้', 'direct-to-admin-form'));
        }
        
        // Get form data
        $form_id = isset($_POST['form_id']) ? intval($_POST['form_id']) : 0;
        $form_name = isset($_POST['form_name']) ? sanitize_text_field($_POST['form_name']) : '';
        $form_slug = isset($_POST['form_slug']) ? sanitize_title($_POST['form_slug']) : '';
        
        // Validate form name
        if (empty($form_name)) {
            wp_die(__('กรุณากรอกชื่อแบบฟอร์ม', 'direct-to-admin-form'));
        }
        
        // Validate form slug for new forms
        if ($form_id === 0 && empty($form_slug)) {
            wp_die(__('กรุณากรอกรหัสแบบฟอร์ม', 'direct-to-admin-form'));
        }
        
        // Get form settings
        $settings = isset($_POST['settings']) ? $_POST['settings'] : [];
        
        // Process complaint types
        if (isset($settings['complaint_types'])) {
            $complaint_types = explode("\n", $settings['complaint_types']);
            $complaint_types = array_map('trim', $complaint_types);
            $complaint_types = array_filter($complaint_types);
            $settings['complaint_types'] = $complaint_types;
        }
        
        // Ensure required fields is an array
        if (!isset($settings['required_fields']) || !is_array($settings['required_fields'])) {
            $settings['required_fields'] = ['name', 'idcard', 'phone', 'email', 'type', 'subject', 'detail'];
        }
        
        // Convert show_address_field to boolean
        $settings['show_address_field'] = isset($settings['show_address_field']) ? true : false;
        
        // Prepare form data
        $form_data = [
            'form_name' => $form_name,
            'form_settings' => json_encode($settings),
        ];
        
        // Add form slug for new forms
        if ($form_id === 0) {
            $form_data['form_slug'] = $form_slug;
            $form_data['created_at'] = current_time('mysql');
        }
        
        $form_data['updated_at'] = current_time('mysql');
        
        // Save form
        if ($form_id > 0) {
            // Update existing form
            $result = DTAF_Database::update_form($form_id, $form_data);
            $redirect_url = admin_url('admin.php?page=dtaf-form-manager&action=edit&form_id=' . $form_id . '&updated=1');
        } else {
            // Insert new form
            $result = DTAF_Database::insert_form($form_data);
            $redirect_url = admin_url('admin.php?page=dtaf-form-manager&updated=1');
        }
        
        // Check result
        if ($result === false) {
            wp_die(__('เกิดข้อผิดพลาดในการบันทึกแบบฟอร์ม', 'direct-to-admin-form'));
        }
        
        // Redirect
        wp_redirect($redirect_url);
        exit;
    }
    
    /**
     * Delete form via AJAX
     */
    public function ajax_delete_form() {
        // Check nonce
        $form_id = isset($_POST['form_id']) ? intval($_POST['form_id']) : 0;
        
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'dtaf_delete_form_' . $form_id)) {
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
        
        // Delete form
        $result = DTAF_Database::delete_form($form_id);
        
        if ($result) {
            wp_send_json_success([
                'message' => __('ลบแบบฟอร์มเรียบร้อยแล้ว', 'direct-to-admin-form')
            ]);
        } else {
            wp_send_json_error([
                'message' => __('ไม่สามารถลบแบบฟอร์มได้ อาจเป็นเพราะเป็นแบบฟอร์มค่าเริ่มต้นหรือมีการส่งข้อมูลผ่านแบบฟอร์มนี้แล้ว', 'direct-to-admin-form')
            ]);
        }
    }
    
    /**
     * Load form preview via AJAX
     */
    public function ajax_load_form_preview() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'dtaf_form_preview')) {
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
        
        // Get form data
        $form_data = isset($_POST['form_data']) ? $_POST['form_data'] : [];
        
        // Validate form data
        if (empty($form_data) || !isset($form_data['form_name']) || !isset($form_data['form_slug'])) {
            wp_send_json_error([
                'message' => __('ข้อมูลแบบฟอร์มไม่ถูกต้อง', 'direct-to-admin-form')
            ]);
        }
        
        // Process complaint types
        if (isset($form_data['complaint_types'])) {
            $complaint_types = explode("\n", $form_data['complaint_types']);
            $complaint_types = array_map('trim', $complaint_types);
            $complaint_types = array_filter($complaint_types);
        } else {
            $complaint_types = [
                __('ร้องเรียนทุจริต', 'direct-to-admin-form'),
                __('พฤติกรรมไม่เหมาะสม', 'direct-to-admin-form'),
                __('ข้อเสนอแนะ/ร้องเรียนอื่นๆ', 'direct-to-admin-form')
            ];
        }
        
        // Prepare form settings
        $form_settings = [
            'complaint_types' => $complaint_types,
            'form_color' => isset($form_data['form_color']) ? sanitize_text_field($form_data['form_color']) : '#0073aa',
            'button_color' => isset($form_data['button_color']) ? sanitize_text_field($form_data['button_color']) : '#0073aa',
            'show_address_field' => isset($form_data['show_address_field']) ? (bool)$form_data['show_address_field'] : true,
            'required_fields' => isset($form_data['required_fields']) ? array_map('sanitize_text_field', $form_data['required_fields']) : ['name', 'idcard', 'phone', 'email', 'type', 'subject', 'detail'],
        ];
        
        // Get form preview HTML
        ob_start();
        include DTAF_PLUGIN_DIR . 'includes/admin/dtaf-form-preview.php';
        $html = ob_get_clean();
        
        wp_send_json_success([
            'html' => $html
        ]);
    }
    
    /**
     * Get form effective settings
     *
     * @param string $form_slug Form slug
     * @return array Form settings
     */
    public static function get_form_effective_settings($form_slug) {
        // Get form data
        $form = DTAF_Database::get_form($form_slug);
        
        // Default settings
        $default_settings = [
            'recipient_email' => get_option('admin_email'),
            'email_subject_prefix' => __('[สายตรงผู้บริหาร]', 'direct-to-admin-form'),
            'success_message' => __('ส่งข้อมูลร้องเรียนเรียบร้อยแล้ว ขอบคุณสำหรับข้อมูลของท่าน', 'direct-to-admin-form'),
            'complaint_types' => [
                __('ร้องเรียนทุจริต', 'direct-to-admin-form'),
                __('พฤติกรรมไม่เหมาะสม', 'direct-to-admin-form'),
                __('ข้อเสนอแนะ/ร้องเรียนอื่นๆ', 'direct-to-admin-form')
            ],
            'form_color' => '#0073aa',
            'button_color' => '#0073aa',
            'show_address_field' => true,
            'required_fields' => ['name', 'idcard', 'phone', 'email', 'type', 'subject', 'detail'],
        ];
        
        // Get global settings
        $global_settings = get_option('dtaf_settings', []);
        
        // Merge with global settings
        $settings = wp_parse_args($global_settings, $default_settings);
        
        // If form exists, merge with form settings
        if ($form) {
            $form_settings = json_decode($form->form_settings, true);
            if (is_array($form_settings)) {
                $settings = wp_parse_args($form_settings, $settings);
            }
        }
        
        // Ensure complaint types is an array
        if (isset($settings['complaint_types']) && !is_array($settings['complaint_types'])) {
            $complaint_types = explode("\n", $settings['complaint_types']);
            $complaint_types = array_map('trim', $complaint_types);
            $complaint_types = array_filter($complaint_types);
            $settings['complaint_types'] = $complaint_types;
        }
        
        return $settings;
    }
}

// Initialize the class
new DTAF_Form_Manager();
