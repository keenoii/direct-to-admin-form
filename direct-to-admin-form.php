<?php

/**
 * Plugin Name: Direct to Admin Form
 * Plugin URI: https://github.com/keenoii/direct-to-admin-form.git
 * Description: แบบฟอร์มสายตรงผู้บริหาร พร้อมระบบจัดการเรื่องร้องเรียน การแจ้งเตือน และการวิเคราะห์ข้อมูล
 * Version: 3.0.0
 * Author: SirKEE
 * Author URI: https://github.com/keenoii
 * Text Domain: direct-to-admin-form
 * Domain Path: /languages
 * Requires at least: 5.6
 * Requires PHP: 7.2
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('DTAF_VERSION', '3.0.0');
define('DTAF_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('DTAF_PLUGIN_URL', plugin_dir_url(__FILE__));
define('DTAF_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main plugin class
 */
final class Direct_To_Admin_Form
{
    /**
     * Singleton instance
     *
     * @var Direct_To_Admin_Form
     */
    private static $instance = null;

    /**
     * Get singleton instance
     *
     * @return Direct_To_Admin_Form
     */
    public static function instance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct()
    {
        $this->includes();
        $this->init_hooks();
    }

    /**
     * Include required files
     */
    private function includes()
    {
        // Core classes
        require_once DTAF_PLUGIN_DIR . 'includes/class-dtaf-database.php';
        require_once DTAF_PLUGIN_DIR . 'includes/class-dtaf-notification.php';
        require_once DTAF_PLUGIN_DIR . 'includes/class-dtaf-security.php';

        // Frontend classes
        require_once DTAF_PLUGIN_DIR . 'includes/frontend/class-dtaf-form.php';
        require_once DTAF_PLUGIN_DIR . 'includes/frontend/class-dtaf-submission.php';

        // Admin classes
        if (is_admin()) {
            // โหลด dependencies ของ DTAF_Admin ก่อน
            require_once DTAF_PLUGIN_DIR . 'includes/admin/class-dtaf-submissions.php';
            require_once DTAF_PLUGIN_DIR . 'includes/admin/class-dtaf-form-manager.php';
            require_once DTAF_PLUGIN_DIR . 'includes/admin/class-dtaf-settings.php';
            require_once DTAF_PLUGIN_DIR . 'includes/admin/class-dtaf-dashboard.php'; // DTAF_Admin เรียกใช้ DTAF_Dashboard ด้วย
            require_once DTAF_PLUGIN_DIR . 'includes/admin/class-dtaf-export.php';

            // จากนั้นค่อยโหลด DTAF_Admin
            require_once DTAF_PLUGIN_DIR . 'includes/admin/class-dtaf-admin.php';
        }
    }



    /**
     * Initialize hooks
     */
    private function init_hooks()
    {
        // Activation and deactivation hooks
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);

        // Load text domain
        add_action('plugins_loaded', [$this, 'load_textdomain']);

        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);

        // Register shortcode
        add_shortcode('direct_to_admin_form', [$this, 'render_form_shortcode']);
    }

    /**
     * Plugin activation
     */
    public function activate()
    {
        // Create database tables
        DTAF_Database::create_tables();

        // Set default options
        $this->set_default_options();

        // Clear permalinks
        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation
     */
    public function deactivate()
    {
        flush_rewrite_rules();
    }

    /**
     * Set default options
     */
    private function set_default_options()
    {
        $default_options = [
            'recipient_email' => get_option('admin_email'),
            'email_subject_prefix' => __('[สายตรงผู้บริหาร]', 'direct-to-admin-form'),
            'success_message' => __('ส่งข้อมูลร้องเรียนเรียบร้อยแล้ว ขอบคุณสำหรับข้อมูลของท่าน', 'direct-to-admin-form'),
            'error_message' => __('เกิดข้อผิดพลาดในการส่งข้อมูล โปรดลองอีกครั้ง', 'direct-to-admin-form'),
            'allowed_file_types' => 'jpg,jpeg,png,pdf,doc,docx',
            'max_file_size' => 5, // MB
            'enable_honeypot' => true,
            'complaint_types' => "ร้องเรียนทุจริต\nพฤติกรรมไม่เหมาะสม\nข้อเสนอแนะ/ร้องเรียนอื่นๆ",
        ];

        // Only set options if they don't exist
        if (!get_option('dtaf_settings')) {
            update_option('dtaf_settings', $default_options);
        }
    }

    /**
     * Load plugin text domain
     */
    public function load_textdomain()
    {
        load_plugin_textdomain('direct-to-admin-form', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets()
    {
        // CSS
        wp_enqueue_style(
            'dtaf-frontend',
            DTAF_PLUGIN_URL . 'assets/css/dtaf-frontend.css',
            [],
            DTAF_VERSION
        );

        // JavaScript
        wp_enqueue_script(
            'dtaf-frontend',
            DTAF_PLUGIN_URL . 'assets/js/dtaf-frontend.js',
            ['jquery'],
            DTAF_VERSION,
            true
        );

        // Localize script
        wp_localize_script('dtaf-frontend', 'dtaf_frontend', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('dtaf_frontend_nonce'),
            'messages' => [
                'required_field' => __('กรุณากรอกข้อมูลในช่องนี้', 'direct-to-admin-form'),
                'invalid_email' => __('กรุณากรอกอีเมลให้ถูกต้อง', 'direct-to-admin-form'),
                'invalid_phone' => __('กรุณากรอกเบอร์โทรศัพท์ให้ถูกต้อง', 'direct-to-admin-form'),
                'invalid_idcard' => __('กรุณากรอกเลขบัตรประชาชนให้ถูกต้อง', 'direct-to-admin-form'),
                'file_too_large' => __('ไฟล์มีขนาดใหญ่เกินไป', 'direct-to-admin-form'),
                'invalid_file_type' => __('ประเภทไฟล์ไม่ได้รับอนุญาต', 'direct-to-admin-form'),
            ],
        ]);
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook)
    {
        // Check if we are on a DTAF admin page
        // Adjust this condition based on your admin page slugs and structure
        $is_dtaf_page = false;
        if (strpos($hook, 'dtaf-') !== false) { // Checks if 'dtaf-' is in the hook string
            $is_dtaf_page = true;
        } else {
            // Example: If your top-level menu slug is 'dtaf_main_menu'
            // and submenu pages are 'dtaf_main_menu_page_dtaf-submissions', etc.
            // Or if hook is 'toplevel_page_dtaf-submissions' for the main submissions page.
            $dtaf_pages_hooks = [
                'toplevel_page_dtaf-submissions', // Main submissions page
                'สายตรงผู้บริหาร_page_dtaf-submissions', // If main menu is 'สายตรงผู้บริหาร'
                'สายตรงผู้บริหาร_page_dtaf-form-manager',
                'สายตรงผู้บริหาร_page_dtaf-reports',
                'สายตรงผู้บริหาร_page_dtaf-settings',
                // Add other DTAF specific page hooks here
            ];
            if (in_array($hook, $dtaf_pages_hooks, true)) {
                $is_dtaf_page = true;
            }
        }

        if (!$is_dtaf_page) {
            return;
        }

        // Enqueue CSS
        wp_enqueue_style(
            'dtaf-admin-style', // Unique handle for your admin CSS
            DTAF_PLUGIN_URL . 'assets/css/dtaf-admin.css', // Path to your admin CSS
            [], // Dependencies
            DTAF_VERSION // Version number
        );

        // Enqueue Chart.js (only on reports page, for example)
        if ($hook === 'สายตรงผู้บริหาร_page_dtaf-reports' || strpos($hook, 'dtaf-reports') !== false) { // Adjust hook check
            wp_enqueue_script(
                'chart-js',
                'https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js', // Or a local copy
                [],
                '3.9.1',
                true // In footer
            );
        }

        // Enqueue WordPress color picker if needed (e.g., for form settings)
        if (strpos($hook, 'dtaf-form-manager') !== false || strpos($hook, 'dtaf-settings') !== false) {
            wp_enqueue_style('wp-color-picker');
        }

        // Enqueue your main admin JavaScript
        $dependencies = ['jquery', 'jquery-ui-datepicker', 'wp-color-picker'];
        if ($hook === 'สายตรงผู้บริหาร_page_dtaf-reports' || strpos($hook, 'dtaf-reports') !== false) {
            $dependencies[] = 'chart-js';
        }

        wp_enqueue_script(
            'dtaf-admin-script', // Unique handle for your admin JS
            DTAF_PLUGIN_URL . 'assets/js/dtaf-admin.js', // Path to your dtaf-admin.js
            $dependencies,
            DTAF_VERSION,
            true // In footer
        );

        // Prepare data for JavaScript localization
        $available_statuses = [ // Define or fetch your statuses
            __('ใหม่', 'direct-to-admin-form'),
            __('กำลังดำเนินการ', 'direct-to-admin-form'),
            __('รอข้อมูลเพิ่มเติม', 'direct-to-admin-form'),
            __('แก้ไขแล้ว', 'direct-to-admin-form'),
            __('ปิดเรื่อง', 'direct-to-admin-form'),
        ];

        $localized_data = [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('dtaf_admin_nonce'), // General admin nonce, e.g., for get_submission_details
            'update_status_nonce' => wp_create_nonce('dtaf_update_status'), // For updating status
            'delete_form_nonce_base' => 'dtaf_delete_form_', // Base for form deletion nonces (if building dynamically in JS)
            'test_line_notify_nonce' => wp_create_nonce('dtaf_test_line_notify_action'), // For Line Notify test
            'statuses' => $available_statuses,
            'text' => [
                'loading' => esc_js(__('กำลังโหลด...', 'direct-to-admin-form')),
                'error' => esc_js(__('เกิดข้อผิดพลาด โปรดลองอีกครั้ง', 'direct-to-admin-form')),
                'confirm_delete' => esc_js(__('คุณแน่ใจหรือไม่ว่าต้องการลบรายการนี้?', 'direct-to-admin-form')),
                'confirm_bulk_delete' => esc_js(__('คุณแน่ใจหรือไม่ว่าต้องการลบรายการที่เลือกทั้งหมด? การดำเนินการนี้ไม่สามารถย้อนกลับได้', 'direct-to-admin-form')),
                'status_update_success' => esc_js(__('อัปเดตสถานะเรียบร้อยแล้ว', 'direct-to-admin-form')),
                'status_update_error' => esc_js(__('เกิดข้อผิดพลาดในการอัปเดตสถานะ', 'direct-to-admin-form')),
                'delete_success' => esc_js(__('ลบรายการเรียบร้อยแล้ว', 'direct-to-admin-form')),
                'delete_error' => esc_js(__('เกิดข้อผิดพลาดในการลบรายการ', 'direct-to-admin-form')),
                'select_action' => esc_js(__('กรุณาเลือกการกระทำ', 'direct-to-admin-form')),
                'select_items' => esc_js(__('กรุณาเลือกรายการที่ต้องการดำเนินการ', 'direct-to-admin-form')),
                'copied' => esc_js(__('คัดลอกแล้ว!', 'direct-to-admin-form')),
                'save' => esc_js(__('บันทึก', 'direct-to-admin-form')),
                'cancel' => esc_js(__('ยกเลิก', 'direct-to-admin-form')),
                'notes_placeholder' => esc_js(__('หมายเหตุ (ไม่บังคับ)', 'direct-to-admin-form')),
                'test_line_success' => esc_js(__('ทดสอบสำเร็จ! ตรวจสอบการแจ้งเตือนใน Line ของคุณ', 'direct-to-admin-form')),
                'test_line_error' => esc_js(__('เกิดข้อผิดพลาดในการทดสอบ', 'direct-to-admin-form')),
                'test_line_enter_token' => esc_js(__('กรุณากรอก Line Notify Token ก่อนทดสอบ', 'direct-to-admin-form')),
            ],
            // Add any other data your JS might need
        ];

        wp_localize_script('dtaf-admin-script', 'dtaf_admin', $localized_data);
    }

    /**
     * Render form shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string Form HTML
     */
    public function render_form_shortcode($atts)
    {
        $form = new DTAF_Form();
        return $form->render($atts);
    }
}

/**
 * Start the plugin
 */
function dtaf()
{
    return Direct_To_Admin_Form::instance();
}

// Initialize the plugin
dtaf();
