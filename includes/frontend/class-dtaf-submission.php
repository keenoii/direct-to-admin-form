<?php
/**
 * Submission class
 * 
 * Handles form submissions
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class DTAF_Submission {
    /**
     * Initialize the class
     */
    public function __construct() {
        // Register AJAX handlers
        add_action('wp_ajax_dtaf_submit_form', [$this, 'ajax_submit_form']);
        add_action('wp_ajax_nopriv_dtaf_submit_form', [$this, 'ajax_submit_form']);
        
        // Register form processing for non-AJAX submissions
        add_action('admin_post_dtaf_submit_form', [$this, 'process_form_submission']);
        add_action('admin_post_nopriv_dtaf_submit_form', [$this, 'process_form_submission']);
    }
    
    /**
     * Handle AJAX form submission
     */
    public function ajax_submit_form() {
        // Check nonce
        if (!isset($_POST['dtaf_nonce']) || !wp_verify_nonce($_POST['dtaf_nonce'], 'dtaf_submit_form')) {
            wp_send_json_error([
                'message' => __('การตรวจสอบความปลอดภัยล้มเหลว โปรดลองอีกครั้ง', 'direct-to-admin-form')
            ]);
        }
        
        // Process form
        $form = new DTAF_Form();
        $result = $form->process_submission($_POST);
        
        if (is_wp_error($result)) {
            wp_send_json_error([
                'message' => $result->get_error_message(),
                'errors' => $result->get_error_data()
            ]);
        } else {
            wp_send_json_success([
                'message' => $result['message'],
                'submission_id' => $result['submission_id']
            ]);
        }
    }
    
    /**
     * Process form submission (non-AJAX)
     */
    public function process_form_submission() {
        // Get form ID
        $form_id = isset($_POST['form_id']) ? sanitize_text_field($_POST['form_id']) : 'default_form';
        
        // Process form
        $form = new DTAF_Form();
        $result = $form->process_submission($_POST);
        
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }
        
        // Set flash message
        $_SESSION['dtaf_form_id'] = $form_id;
        
        if (is_wp_error($result)) {
            $_SESSION['dtaf_message_type'] = 'error';
            $_SESSION['dtaf_message'] = $result->get_error_message();
        } else {
            $_SESSION['dtaf_message_type'] = 'success';
            $_SESSION['dtaf_message'] = $result['message'];
        }
        
        // Redirect back to form
        wp_safe_redirect(wp_get_referer() . '#dtaf-form-' . $form_id);
        exit;
    }
    
    /**
     * Set flash message
     *
     * @param string $form_id Form ID
     * @param string $type Message type (success or error)
     * @param string $message Message text
     */
    public static function set_flash_message($form_id, $type, $message) {
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }
        
        $_SESSION['dtaf_form_id'] = $form_id;
        $_SESSION['dtaf_message_type'] = $type;
        $_SESSION['dtaf_message'] = $message;
    }
}

// Initialize the class
new DTAF_Submission();
