<?php
/**
 * Admin class
 * 
 * Main class for admin functionality
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class DTAF_Admin {
    /**
     * Initialize the class
     */
    public function __construct() {
        // Add admin menu
        add_action('admin_menu', [$this, 'add_admin_menu']);
        
        // Add admin notices
        add_action('admin_notices', [$this, 'admin_notices']);
        
        // Add dashboard widget
        add_action('wp_dashboard_setup', [$this, 'add_dashboard_widget']);
        
        // Add admin bar menu
        add_action('admin_bar_menu', [$this, 'add_admin_bar_menu'], 99);
        
        // Add help tabs
        add_action('admin_head', [$this, 'add_help_tabs']);
        
        // Add custom footer text
        add_filter('admin_footer_text', [$this, 'custom_footer_text']);
        
        // Initialize other admin classes
        new DTAF_Submissions();
        new DTAF_Form_Manager();
        new DTAF_Settings();
        new DTAF_Export();
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        // Main menu
        add_menu_page(
            __('สายตรงผู้บริหาร', 'direct-to-admin-form'),
            __('สายตรงผู้บริหาร', 'direct-to-admin-form'),
            'manage_options',
            'dtaf-submissions',
            [$this, 'render_submissions_page'],
            'dashicons-email-alt2',
            25
        );
        
        // Submissions submenu
        add_submenu_page(
            'dtaf-submissions',
            __('เรื่องร้องเรียนทั้งหมด', 'direct-to-admin-form'),
            __('เรื่องร้องเรียนทั้งหมด', 'direct-to-admin-form'),
            'manage_options',
            'dtaf-submissions',
            [$this, 'render_submissions_page']
        );
        
        // Form manager submenu
        add_submenu_page(
            'dtaf-submissions',
            __('จัดการแบบฟอร์ม', 'direct-to-admin-form'),
            __('จัดการแบบฟอร์ม', 'direct-to-admin-form'),
            'manage_options',
            'dtaf-form-manager',
            [$this, 'render_form_manager_page']
        );
        
        // Reports submenu
        add_submenu_page(
            'dtaf-submissions',
            __('รายงานและสถิติ', 'direct-to-admin-form'),
            __('รายงานและสถิติ', 'direct-to-admin-form'),
            'manage_options',
            'dtaf-reports',
            [$this, 'render_reports_page']
        );
        
        // Settings submenu
        add_submenu_page(
            'dtaf-submissions',
            __('ตั้งค่าปลั๊กอิน', 'direct-to-admin-form'),
            __('ตั้งค่าปลั๊กอิน', 'direct-to-admin-form'),
            'manage_options',
            'dtaf-settings',
            [$this, 'render_settings_page']
        );
    }
    
    /**
     * Render submissions page
     */
    public function render_submissions_page() {
        // Check if viewing a single submission
        if (isset($_GET['action']) && $_GET['action'] === 'view' && isset($_GET['submission_id'])) {
            $submission_id = intval($_GET['submission_id']);
            $submission = DTAF_Database::get_submission($submission_id);
            
            if ($submission) {
                // Get status history
                $history = DTAF_Database::get_status_history($submission_id);
                
                // Render submission detail template
                include DTAF_PLUGIN_DIR . 'templates/admin/submission-detail.php';
                return;
            }
        }
        
        // Default: show submissions list
        include DTAF_PLUGIN_DIR . 'templates/admin/submissions-list.php';
    }
    
    /**
     * Render form manager page
     */
    public function render_form_manager_page() {
        include DTAF_PLUGIN_DIR . 'templates/admin/form-manager.php';
    }
    
    /**
     * Render reports page
     */
    public function render_reports_page() {
        include DTAF_PLUGIN_DIR . 'templates/admin/reports.php';
    }
    
    /**
     * Render settings page
     */
    public function render_settings_page() {
        include DTAF_PLUGIN_DIR . 'templates/admin/settings.php';
    }
    
    /**
     * Add admin notices
     */
    public function admin_notices() {
        // Only show on our plugin pages
        $screen = get_current_screen();
        if (!$screen || strpos($screen->id, 'dtaf-') === false) {
            return;
        }
        
        // Check if email settings are configured
        $options = get_option('dtaf_settings', []);
        if (empty($options['recipient_email'])) {
            echo '<div class="notice notice-warning is-dismissible"><p>';
            printf(
                __('คุณยังไม่ได้กำหนดอีเมลผู้รับการแจ้งเตือน <a href="%s">คลิกที่นี่เพื่อตั้งค่า</a>', 'direct-to-admin-form'),
                admin_url('admin.php?page=dtaf-settings')
            );
            echo '</p></div>';
        }
    }
    
    /**
     * Add dashboard widget
     */
    public function add_dashboard_widget() {
        wp_add_dashboard_widget(
            'dtaf_dashboard_widget',
            __('สายตรงผู้บริหาร - ภาพรวม', 'direct-to-admin-form'),
            [new DTAF_Dashboard(), 'render_dashboard_widget']
        );
    }
    
    /**
     * Add admin bar menu
     *
     * @param WP_Admin_Bar $wp_admin_bar Admin bar object
     */
    public function add_admin_bar_menu($wp_admin_bar) {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Get count of new submissions
        global $wpdb;
        $table_name = $wpdb->prefix . 'dtaf_submissions';
        $new_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE status = %s",
            __('ใหม่', 'direct-to-admin-form')
        ));
        
        // Add main menu item
        $wp_admin_bar->add_node([
            'id'    => 'dtaf-menu',
            'title' => '<span class="ab-icon dashicons dashicons-email-alt2"></span>' . __('สายตรงผู้บริหาร', 'direct-to-admin-form') . ($new_count > 0 ? ' <span class="dtaf-count">' . $new_count . '</span>' : ''),
            'href'  => admin_url('admin.php?page=dtaf-submissions'),
            'meta'  => [
                'class' => 'dtaf-admin-bar-menu',
            ],
        ]);
        
        // Add submissions submenu
        $wp_admin_bar->add_node([
            'parent' => 'dtaf-menu',
            'id'     => 'dtaf-submissions',
            'title'  => __('เรื่องร้องเรียนทั้งหมด', 'direct-to-admin-form'),
            'href'   => admin_url('admin.php?page=dtaf-submissions'),
        ]);
        
        // Add new submissions submenu if there are any
        if ($new_count > 0) {
            $wp_admin_bar->add_node([
                'parent' => 'dtaf-menu',
                'id'     => 'dtaf-new-submissions',
                'title'  => __('เรื่องร้องเรียนใหม่', 'direct-to-admin-form') . ' <span class="dtaf-count">' . $new_count . '</span>',
                'href'   => admin_url('admin.php?page=dtaf-submissions&status=' . urlencode(__('ใหม่', 'direct-to-admin-form'))),
            ]);
        }
        
        // Add form manager submenu
        $wp_admin_bar->add_node([
            'parent' => 'dtaf-menu',
            'id'     => 'dtaf-form-manager',
            'title'  => __('จัดการแบบฟอร์ม', 'direct-to-admin-form'),
            'href'   => admin_url('admin.php?page=dtaf-form-manager'),
        ]);
        
        // Add reports submenu
        $wp_admin_bar->add_node([
            'parent' => 'dtaf-menu',
            'id'     => 'dtaf-reports',
            'title'  => __('รายงานและสถิติ', 'direct-to-admin-form'),
            'href'   => admin_url('admin.php?page=dtaf-reports'),
        ]);
        
        // Add settings submenu
        $wp_admin_bar->add_node([
            'parent' => 'dtaf-menu',
            'id'     => 'dtaf-settings',
            'title'  => __('ตั้งค่าปลั๊กอิน', 'direct-to-admin-form'),
            'href'   => admin_url('admin.php?page=dtaf-settings'),
        ]);
    }
    
    /**
     * Add help tabs
     */
    public function add_help_tabs() {
        $screen = get_current_screen();
        
        // Only add help tabs on our plugin pages
        if (!$screen || strpos($screen->id, 'dtaf-') === false) {
            return;
        }
        
        // Overview tab
        $screen->add_help_tab([
            'id'      => 'dtaf-help-overview',
            'title'   => __('ภาพรวม', 'direct-to-admin-form'),
            'content' => '<p>' . __('ปลั๊กอินสายตรงผู้บริหารช่วยให้คุณสามารถรับเรื่องร้องเรียนและข้อเสนอแนะจากผู้ใช้เว็บไซต์ได้อย่างง่ายดาย', 'direct-to-admin-form') . '</p>'
        ]);
        
        // Shortcode tab
        $screen->add_help_tab([
            'id'      => 'dtaf-help-shortcode',
            'title'   => __('Shortcode', 'direct-to-admin-form'),
            'content' => '<p>' . __('ใช้ shortcode ต่อไปนี้เพื่อแสดงแบบฟอร์มบนหน้าเว็บไซต์ของคุณ:', 'direct-to-admin-form') . '</p>' .
                        '<p><code>[direct_to_admin_form]</code> - ' . __('แสดงแบบฟอร์มค่าเริ่มต้น', 'direct-to-admin-form') . '</p>' .
                        '<p><code>[direct_to_admin_form form_id="custom-form"]</code> - ' . __('แสดงแบบฟอร์มที่กำหนดเอง', 'direct-to-admin-form') . '</p>'
        ]);
        
        // Page-specific help tabs
        if ($screen->id === 'toplevel_page_dtaf-submissions') {
            $screen->add_help_tab([
                'id'      => 'dtaf-help-submissions',
                'title'   => __('เรื่องร้องเรียน', 'direct-to-admin-form'),
                'content' => '<p>' . __('หน้านี้แสดงรายการเรื่องร้องเรียนทั้งหมดที่ส่งเข้ามาผ่านแบบฟอร์ม คุณสามารถดูรายละเอียด อัปเดตสถานะ และจัดการเรื่องร้องเรียนได้', 'direct-to-admin-form') . '</p>'
            ]);
        } elseif ($screen->id === 'สายตรงผู้บริหาร_page_dtaf-form-manager') {
            $screen->add_help_tab([
                'id'      => 'dtaf-help-forms',
                'title'   => __('จัดการแบบฟอร์ม', 'direct-to-admin-form'),
                'content' => '<p>' . __('หน้านี้ช่วยให้คุณสามารถสร้างและจัดการแบบฟอร์มหลายรูปแบบสำหรับการรับเรื่องร้องเรียนที่แตกต่างกัน', 'direct-to-admin-form') . '</p>'
            ]);
        } elseif ($screen->id === 'สายตรงผู้บริหาร_page_dtaf-reports') {
            $screen->add_help_tab([
                'id'      => 'dtaf-help-reports',
                'title'   => __('รายงานและสถิติ', 'direct-to-admin-form'),
                'content' => '<p>' . __('หน้านี้แสดงรายงานและสถิติเกี่ยวกับเรื่องร้องเรียนที่ได้รับ', 'direct-to-admin-form') . '</p>'
            ]);
        } elseif ($screen->id === 'สายตรงผู้บริหาร_page_dtaf-settings') {
            $screen->add_help_tab([
                'id'      => 'dtaf-help-settings',
                'title'   => __('ตั้งค่า', 'direct-to-admin-form'),
                'content' => '<p>' . __('หน้านี้ช่วยให้คุณสามารถกำหนดค่าต่างๆ ของปลั๊กอิน เช่น อีเมลผู้รับ ข้อความแจ้งเตือน และการตั้งค่าการอัปโหลดไฟล์', 'direct-to-admin-form') . '</p>'
            ]);
        }
        
        // Set help sidebar
        $screen->set_help_sidebar(
            '<p><strong>' . __('สำหรับความช่วยเหลือเพิ่มเติม:', 'direct-to-admin-form') . '</strong></p>' .
            '<p><a href="#" target="_blank">' . __('เอกสารออนไลน์', 'direct-to-admin-form') . '</a></p>' .
            '<p><a href="mailto:support@example.com">' . __('ติดต่อฝ่ายสนับสนุน', 'direct-to-admin-form') . '</a></p>'
        );
    }
    
    /**
     * Custom footer text
     *
     * @param string $text Footer text
     * @return string Modified footer text
     */
    public function custom_footer_text($text) {
        $screen = get_current_screen();
        
        // Only modify on our plugin pages
        if (isset($screen->id) && strpos($screen->id, 'dtaf-') !== false) {
            $text = sprintf(
                __('ขอบคุณที่ใช้ปลั๊กอิน %1$sสายตรงผู้บริหาร%2$s | เวอร์ชัน %3$s', 'direct-to-admin-form'),
                '<strong>',
                '</strong>',
                DTAF_VERSION
            );
        }
        
        return $text;
    }
}

// Initialize the class
new DTAF_Admin();
