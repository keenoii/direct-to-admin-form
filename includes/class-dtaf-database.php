<?php
/**
 * Database class
 * 
 * Handles database operations for the plugin
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class DTAF_Database {
    /**
     * Create database tables
     */
    public static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        // Submissions table
        $table_name = $wpdb->prefix . 'dtaf_submissions';
        $sql = "CREATE TABLE $table_name (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            form_id VARCHAR(100) DEFAULT 'default_form',
            name VARCHAR(255) NOT NULL,
            idcard VARCHAR(20) NOT NULL,
            phone VARCHAR(30) NOT NULL,
            email VARCHAR(100) NOT NULL,
            address TEXT,
            type VARCHAR(255) NOT NULL,
            subject TEXT NOT NULL,
            detail LONGTEXT NOT NULL,
            file_paths TEXT,
            created_at DATETIME NOT NULL,
            status VARCHAR(50) DEFAULT 'ใหม่',
            admin_notes TEXT,
            ip_address VARCHAR(100),
            user_agent VARCHAR(255),
            PRIMARY KEY  (id),
            INDEX form_id_idx (form_id),
            INDEX status_idx (status),
            INDEX type_idx (type)
        ) $charset_collate;";
        dbDelta($sql);

        // Forms table
        $table_name = $wpdb->prefix . 'dtaf_forms';
        $sql = "CREATE TABLE $table_name (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            form_slug VARCHAR(100) NOT NULL UNIQUE,
            form_name VARCHAR(255) NOT NULL,
            form_settings LONGTEXT, 
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";
        dbDelta($sql);

        // Status history table
        $table_name = $wpdb->prefix . 'dtaf_status_history';
        $sql = "CREATE TABLE $table_name (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            submission_id BIGINT(20) UNSIGNED NOT NULL,
            status VARCHAR(50) NOT NULL,
            notes TEXT,
            created_at DATETIME NOT NULL,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            PRIMARY KEY  (id),
            INDEX submission_id_idx (submission_id)
        ) $charset_collate;";
        dbDelta($sql);

        // Create default form if it doesn't exist
        self::create_default_form();
    }

    /**
     * Create default form
     */
    private static function create_default_form() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'dtaf_forms';
        
        // Check if default form exists
        $form_exists = $wpdb->get_var(
            $wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE form_slug = %s", 'default_form')
        );
        
        if (!$form_exists) {
            $settings = get_option('dtaf_settings', []);
            $complaint_types = isset($settings['complaint_types']) ? $settings['complaint_types'] : "ร้องเรียนทุจริต\nพฤติกรรมไม่เหมาะสม\nข้อเสนอแนะ/ร้องเรียนอื่นๆ";
            $complaint_types_array = array_map('trim', explode("\n", $complaint_types));
            
            $form_settings = [
                'recipient_email' => isset($settings['recipient_email']) ? $settings['recipient_email'] : get_option('admin_email'),
                'email_subject_prefix' => isset($settings['email_subject_prefix']) ? $settings['email_subject_prefix'] : __('[สายตรงผู้บริหาร]', 'direct-to-admin-form'),
                'success_message' => isset($settings['success_message']) ? $settings['success_message'] : __('ส่งข้อมูลร้องเรียนเรียบร้อยแล้ว ขอบคุณสำหรับข้อมูลของท่าน', 'direct-to-admin-form'),
                'complaint_types' => $complaint_types_array,
                'form_color' => '#0073aa',
                'button_color' => '#0073aa',
                'show_address_field' => true,
                'required_fields' => ['name', 'idcard', 'phone', 'email', 'type', 'subject', 'detail'],
            ];
            
            $wpdb->insert(
                $table_name,
                [
                    'form_slug' => 'default_form',
                    'form_name' => __('แบบฟอร์มสายตรงผู้บริหาร (ค่าเริ่มต้น)', 'direct-to-admin-form'),
                    'form_settings' => json_encode($form_settings),
                    'created_at' => current_time('mysql'),
                    'updated_at' => current_time('mysql'),
                ]
            );
        }
    }

    /**
     * Get submissions with optional filtering
     *
     * @param array $args Query arguments
     * @return array Submissions
     */
    public static function get_submissions($args = []) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'dtaf_submissions';
        
        $defaults = [
            'per_page' => 20,
            'page' => 1,
            'orderby' => 'created_at',
            'order' => 'DESC',
            'status' => '',
            'form_id' => '',
            'type' => '',
            'search' => '',
            'date_from' => '',
            'date_to' => '',
        ];
        
        $args = wp_parse_args($args, $defaults);
        $where = [];
        $values = [];
        
        // Status filter
        if (!empty($args['status'])) {
            $where[] = 'status = %s';
            $values[] = $args['status'];
        }
        
        // Form filter
        if (!empty($args['form_id'])) {
            $where[] = 'form_id = %s';
            $values[] = $args['form_id'];
        }
        
        // Type filter
        if (!empty($args['type'])) {
            $where[] = 'type = %s';
            $values[] = $args['type'];
        }
        
        // Date range filter
        if (!empty($args['date_from'])) {
            $where[] = 'created_at >= %s';
            $values[] = $args['date_from'] . ' 00:00:00';
        }
        
        if (!empty($args['date_to'])) {
            $where[] = 'created_at <= %s';
            $values[] = $args['date_to'] . ' 23:59:59';
        }
        
        // Search
        if (!empty($args['search'])) {
            $search_term = '%' . $wpdb->esc_like($args['search']) . '%';
            $where[] = '(name LIKE %s OR subject LIKE %s OR email LIKE %s OR idcard LIKE %s OR id = %s)';
            $values[] = $search_term;
            $values[] = $search_term;
            $values[] = $search_term;
            $values[] = $search_term;
            $values[] = $args['search']; // For exact ID match
        }
        
        // Build WHERE clause
        $where_clause = '';
        if (!empty($where)) {
            $where_clause = 'WHERE ' . implode(' AND ', $where);
        }
        
        // Build ORDER BY clause
        $orderby = sanitize_sql_orderby($args['orderby'] . ' ' . $args['order']);
        if (!$orderby) {
            $orderby = 'created_at DESC';
        }
        
        // Calculate offset
        $offset = ($args['page'] - 1) * $args['per_page'];
        
        // Get total count
        $count_query = "SELECT COUNT(*) FROM $table_name $where_clause";
        $total_items = $wpdb->get_var($wpdb->prepare($count_query, $values));
        
        // Get submissions
        $query = "SELECT * FROM $table_name $where_clause ORDER BY $orderby LIMIT %d OFFSET %d";
        $prepared_values = array_merge($values, [$args['per_page'], $offset]);
        $submissions = $wpdb->get_results($wpdb->prepare($query, $prepared_values));
        
        return [
            'items' => $submissions,
            'total_items' => $total_items,
            'total_pages' => ceil($total_items / $args['per_page']),
        ];
    }

    /**
     * Get a single submission
     *
     * @param int $id Submission ID
     * @return object|null Submission object or null if not found
     */
    public static function get_submission($id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'dtaf_submissions';
        
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id));
    }

    /**
     * Insert a new submission
     *
     * @param array $data Submission data
     * @return int|false The submission ID on success, false on failure
     */
    public static function insert_submission($data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'dtaf_submissions';
        
        $result = $wpdb->insert($table_name, $data);
        
        if ($result) {
            $submission_id = $wpdb->insert_id;
            
            // Add to status history
            self::add_status_history($submission_id, $data['status'], '', get_current_user_id());
            
            return $submission_id;
        }
        
        return false;
    }

    /**
     * Update a submission
     *
     * @param int $id Submission ID
     * @param array $data Data to update
     * @return bool True on success, false on failure
     */
    public static function update_submission($id, $data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'dtaf_submissions';
        
        // Get current status
        $current_status = $wpdb->get_var($wpdb->prepare("SELECT status FROM $table_name WHERE id = %d", $id));
        
        $result = $wpdb->update(
            $table_name,
            $data,
            ['id' => $id]
        );
        
        if ($result !== false) {
            // Add to status history if status changed
            if (isset($data['status']) && $data['status'] !== $current_status) {
                $notes = isset($data['admin_notes']) ? $data['admin_notes'] : '';
                self::add_status_history($id, $data['status'], $notes, get_current_user_id());
            }
            
            return true;
        }
        
        return false;
    }

    /**
     * Delete a submission
     *
     * @param int $id Submission ID
     * @return bool True on success, false on failure
     */
    public static function delete_submission($id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'dtaf_submissions';
        
        // Delete status history first
        $history_table = $wpdb->prefix . 'dtaf_status_history';
        $wpdb->delete($history_table, ['submission_id' => $id]);
        
        // Then delete the submission
        return $wpdb->delete($table_name, ['id' => $id]);
    }

    /**
     * Add status history entry
     *
     * @param int $submission_id Submission ID
     * @param string $status Status
     * @param string $notes Notes
     * @param int $user_id User ID
     * @return int|false The status history ID on success, false on failure
     */
    public static function add_status_history($submission_id, $status, $notes, $user_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'dtaf_status_history';
        
        $result = $wpdb->insert(
            $table_name,
            [
                'submission_id' => $submission_id,
                'status' => $status,
                'notes' => $notes,
                'created_at' => current_time('mysql'),
                'user_id' => $user_id,
            ]
        );
        
        if ($result) {
            return $wpdb->insert_id;
        }
        
        return false;
    }

    /**
     * Get status history for a submission
     *
     * @param int $submission_id Submission ID
     * @return array Status history entries
     */
    public static function get_status_history($submission_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'dtaf_status_history';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT h.*, u.display_name as user_name 
            FROM $table_name h 
            LEFT JOIN {$wpdb->users} u ON h.user_id = u.ID 
            WHERE h.submission_id = %d 
            ORDER BY h.created_at DESC",
            $submission_id
        ));
    }

    /**
     * Get all forms
     *
     * @return array Forms
     */
    public static function get_forms() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'dtaf_forms';
        
        return $wpdb->get_results("SELECT * FROM $table_name ORDER BY form_name ASC");
    }

    /**
     * Get a single form
     *
     * @param int|string $id_or_slug Form ID or slug
     * @return object|null Form object or null if not found
     */
    public static function get_form($id_or_slug) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'dtaf_forms';
        
        if (is_numeric($id_or_slug)) {
            return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id_or_slug));
        } else {
            return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE form_slug = %s", $id_or_slug));
        }
    }

    /**
     * Insert a new form
     *
     * @param array $data Form data
     * @return int|false The form ID on success, false on failure
     */
    public static function insert_form($data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'dtaf_forms';
        
        $data['created_at'] = current_time('mysql');
        $data['updated_at'] = current_time('mysql');
        
        $result = $wpdb->insert($table_name, $data);
        
        if ($result) {
            return $wpdb->insert_id;
        }
        
        return false;
    }

    /**
     * Update a form
     *
     * @param int $id Form ID
     * @param array $data Data to update
     * @return bool True on success, false on failure
     */
    public static function update_form($id, $data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'dtaf_forms';
        
        $data['updated_at'] = current_time('mysql');
        
        return $wpdb->update(
            $table_name,
            $data,
            ['id' => $id]
        ) !== false;
    }

    /**
     * Delete a form
     *
     * @param int $id Form ID
     * @return bool True on success, false on failure
     */
    public static function delete_form($id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'dtaf_forms';
        
        // Get form slug
        $form_slug = $wpdb->get_var($wpdb->prepare("SELECT form_slug FROM $table_name WHERE id = %d", $id));
        
        // Don't delete default form
        if ($form_slug === 'default_form') {
            return false;
        }
        
        // Check if there are submissions using this form
        $submissions_table = $wpdb->prefix . 'dtaf_submissions';
        $submission_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $submissions_table WHERE form_id = %s",
            $form_slug
        ));
        
        if ($submission_count > 0) {
            return false;
        }
        
        return $wpdb->delete($table_name, ['id' => $id]) !== false;
    }

    /**
       /**
     * Get submission statistics
     *
     * @return array Statistics
     */
    public static function get_statistics() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'dtaf_submissions';
        
        // Total submissions
        $total = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        
        // Submissions by status
        $status_stats = $wpdb->get_results(
            "SELECT status, COUNT(*) as count FROM $table_name GROUP BY status ORDER BY count DESC"
        );
        
        // Submissions by type
        $type_stats = $wpdb->get_results(
            "SELECT type, COUNT(*) as count FROM $table_name GROUP BY type ORDER BY count DESC"
        );
        
        // Submissions by form
        $form_stats = $wpdb->get_results(
            "SELECT form_id, COUNT(*) as count FROM $table_name GROUP BY form_id ORDER BY count DESC"
        );
        
        // Submissions by month (last 12 months)
        $month_stats = $wpdb->get_results(
            "SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count 
            FROM $table_name 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH) 
            GROUP BY month 
            ORDER BY month ASC"
        );
        
        return [
            'total' => (int) $total, // Ensure total is integer
            'by_status' => $status_stats,
            'by_type' => $type_stats,
            'by_form' => $form_stats,
            'by_month' => $month_stats,
        ];
    }

    /**
     * Get filtered statistics based on a date range.
     *
     * @param string $start_date Start date in YYYY-MM-DD format.
     * @param string $end_date End date in YYYY-MM-DD format.
     * @return array Associative array of statistics.
     */
    public static function get_filtered_statistics($start_date, $end_date) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'dtaf_submissions'; // ชื่อตาราง submissions
        // คอลัมน์วันที่คือ 'created_at' และเป็น DATETIME

        $stats = [
            'total' => 0,
            'by_status' => [],
            'by_type' => [],
            'by_form' => [],
            'by_month' => [],
        ];

        // Sanitize and prepare date conditions
        // $start_date and $end_date are assumed to be 'Y-m-d'
        $date_where_clause = $wpdb->prepare(
            " AND created_at BETWEEN %s AND %s",
            $start_date . ' 00:00:00', // Start of the day
            $end_date . ' 23:59:59'    // End of the day
        );

        // Total submissions in range
        $stats['total'] = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$table_name} WHERE 1=1 {$date_where_clause}"
        );

        if ($stats['total'] > 0) {
            // Submissions by status in range
            $stats['by_status'] = $wpdb->get_results(
                "SELECT status, COUNT(*) as count FROM {$table_name} WHERE 1=1 {$date_where_clause} GROUP BY status ORDER BY count DESC"
            );

            // Submissions by type in range
            $stats['by_type'] = $wpdb->get_results(
                "SELECT type, COUNT(*) as count FROM {$table_name} WHERE 1=1 {$date_where_clause} GROUP BY type ORDER BY count DESC"
            );

            // Submissions by form in range
            $stats['by_form'] = $wpdb->get_results(
                "SELECT form_id, COUNT(*) as count FROM {$table_name} WHERE 1=1 {$date_where_clause} GROUP BY form_id ORDER BY count DESC"
            );

            // Submissions by month in range
            $stats['by_month'] = $wpdb->get_results(
                 $wpdb->prepare("
                    SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count
                    FROM {$table_name}
                    WHERE created_at BETWEEN %s AND %s
                    GROUP BY month
                    ORDER BY month ASC
                ", $start_date . ' 00:00:00', $end_date . ' 23:59:59')
            );
        }

        return $stats;
    }

}