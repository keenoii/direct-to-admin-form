<?php
/**
 * Submissions class
 * 
 * Handles submissions management functionality
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class DTAF_Submissions {
    /**
     * Initialize the class
     */
    public function __construct() {
        // Register AJAX handlers
        add_action('wp_ajax_dtaf_ajax_get_submission_details', [$this, 'ajax_get_submission_details']);
        add_action('wp_ajax_dtaf_ajax_update_submission_status', [$this, 'ajax_update_submission_status']);
        add_action('wp_ajax_dtaf_ajax_delete_submission', [$this, 'ajax_delete_submission']);
        
        // Register bulk action handler
        add_action('admin_post_dtaf_bulk_action', [$this, 'handle_bulk_action']);
    }
    
    /**
     * Get submission details via AJAX
     */
    public function ajax_get_submission_details() {
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
        
        // Get submission ID
        $submission_id = isset($_POST['submission_id']) ? intval($_POST['submission_id']) : 0;
        
        // Get submission
        $submission = DTAF_Database::get_submission($submission_id);
        
        if (!$submission) {
            wp_send_json_error([
                'message' => __('ไม่พบข้อมูลเรื่องร้องเรียน', 'direct-to-admin-form')
            ]);
        }
        
        // Get form name
        $form = DTAF_Database::get_form($submission->form_id);
        $form_name = $form ? $form->form_name : __('แบบฟอร์มค่าเริ่มต้น', 'direct-to-admin-form');
        
        // Format date
        $date = date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($submission->created_at));
        
        // Get file attachments
        $file_paths = [];
        if (!empty($submission->file_paths)) {
            $file_paths = unserialize($submission->file_paths);
        }
        
        // Build HTML
        ob_start();
        ?>
        <h3><?php echo esc_html($submission->subject); ?></h3>
        
        <table class="form-table">
            <tr>
                <th><?php esc_html_e('ชื่อ-นามสกุล:', 'direct-to-admin-form'); ?></th>
                <td><?php echo esc_html($submission->name); ?></td>
            </tr>
            <tr>
                <th><?php esc_html_e('บัตรประชาชน:', 'direct-to-admin-form'); ?></th>
                <td><?php echo esc_html($submission->idcard); ?></td>
            </tr>
            <tr>
                <th><?php esc_html_e('โทรศัพท์:', 'direct-to-admin-form'); ?></th>
                <td><?php echo esc_html($submission->phone); ?></td>
            </tr>
            <tr>
                <th><?php esc_html_e('อีเมล:', 'direct-to-admin-form'); ?></th>
                <td>
                    <a href="mailto:<?php echo esc_attr($submission->email); ?>">
                        <?php echo esc_html($submission->email); ?>
                    </a>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('ที่อยู่:', 'direct-to-admin-form'); ?></th>
                <td><?php echo nl2br(esc_html($submission->address)); ?></td>
            </tr>
            <tr>
                <th><?php esc_html_e('ประเภท:', 'direct-to-admin-form'); ?></th>
                <td><?php echo esc_html($submission->type); ?></td>
            </tr>
            <tr>
                <th><?php esc_html_e('รายละเอียด:', 'direct-to-admin-form'); ?></th>
                <td><?php echo nl2br(esc_html($submission->detail)); ?></td>
            </tr>
            <tr>
                <th><?php esc_html_e('แบบฟอร์ม:', 'direct-to-admin-form'); ?></th>
                <td><?php echo esc_html($form_name); ?> (<?php echo esc_html($submission->form_id); ?>)</td>
            </tr>
            <tr>
                <th><?php esc_html_e('วันที่ส่ง:', 'direct-to-admin-form'); ?></th>
                <td><?php echo esc_html($date); ?></td>
            </tr>
            <tr>
                <th><?php esc_html_e('สถานะ:', 'direct-to-admin-form'); ?></th>
                <td>
                    <span class="dtaf-status-badge dtaf-status-<?php echo esc_attr(sanitize_title($submission->status)); ?>">
                        <?php echo esc_html($submission->status); ?>
                    </span>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('หมายเหตุ:', 'direct-to-admin-form'); ?></th>
                <td><?php echo nl2br(esc_html($submission->admin_notes)); ?></td>
            </tr>
        </table>
        
        <?php if (!empty($file_paths)): ?>
            <h4><?php esc_html_e('ไฟล์แนบ:', 'direct-to-admin-form'); ?></h4>
            <ul>
                <?php foreach ($file_paths as $file_path): ?>
                    <li>
                        <a href="<?php echo esc_url($file_path); ?>" target="_blank">
                            <?php echo esc_html(basename($file_path)); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        
        <p>
            <a href="<?php echo esc_url(admin_url('admin.php?page=dtaf-submissions&action=view&submission_id=' . $submission_id)); ?>" class="button">
                <?php esc_html_e('ดูรายละเอียดเพิ่มเติม', 'direct-to-admin-form'); ?>
            </a>
        </p>
        <?php
        $html = ob_get_clean();
        
        wp_send_json_success([
            'html' => $html
        ]);
    }
    
    /**
     * Update submission status via AJAX
     */
    public function ajax_update_submission_status() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'dtaf_update_status')) {
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
        
        // Get submission data
        $submission_id = isset($_POST['submission_id']) ? intval($_POST['submission_id']) : 0;
        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';
        $admin_notes = isset($_POST['admin_notes']) ? sanitize_textarea_field($_POST['admin_notes']) : '';
        $notify_submitter = isset($_POST['notify_submitter']) ? (bool)$_POST['notify_submitter'] : false;
        
        // Validate submission ID
        if ($submission_id <= 0) {
            wp_send_json_error([
                'message' => __('รหัสเรื่องร้องเรียนไม่ถูกต้อง', 'direct-to-admin-form')
            ]);
        }
        
        // Validate status
        if (empty($status)) {
            wp_send_json_error([
                'message' => __('กรุณาเลือกสถานะ', 'direct-to-admin-form')
            ]);
        }
        
        // Get submission
        $submission = DTAF_Database::get_submission($submission_id);
        
        if (!$submission) {
            wp_send_json_error([
                'message' => __('ไม่พบข้อมูลเรื่องร้องเรียน', 'direct-to-admin-form')
            ]);
        }
        
        // Update submission
        $update_data = [
            'status' => $status,
            'admin_notes' => $admin_notes
        ];
        
        $result = DTAF_Database::update_submission($submission_id, $update_data);
        
        if (!$result) {
            wp_send_json_error([
                'message' => __('เกิดข้อผิดพลาดในการอัปเดตสถานะ', 'direct-to-admin-form')
            ]);
        }
        
        // Send notification to submitter if requested
        if ($notify_submitter) {
            DTAF_Notification::send_status_update_email($submission_id, $status, $admin_notes, (array)$submission);
        }
        
        // Send Line Notify if enabled
        DTAF_Line_Notify::send_status_update_notification($submission_id, $status, $admin_notes, (array)$submission);
        
        // Get updated status history
        $history = DTAF_Database::get_status_history($submission_id);
        
        // Build history HTML
        ob_start();
        if (!empty($history)) {
            ?>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('วันที่', 'direct-to-admin-form'); ?></th>
                        <th><?php esc_html_e('สถานะ', 'direct-to-admin-form'); ?></th>
                        <th><?php esc_html_e('หมายเหตุ', 'direct-to-admin-form'); ?></th>
                        <th><?php esc_html_e('ผู้ดำเนินการ', 'direct-to-admin-form'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($history as $entry): ?>
                        <tr>
                            <td>
                                <?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($entry->created_at))); ?>
                            </td>
                            <td>
                                <span class="dtaf-status-badge dtaf-status-<?php echo esc_attr(sanitize_title($entry->status)); ?>">
                                    <?php echo esc_html($entry->status); ?>
                                </span>
                            </td>
                            <td><?php echo nl2br(esc_html($entry->notes)); ?></td>
                            <td><?php echo esc_html($entry->user_name); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php
        } else {
            ?>
            <p><?php esc_html_e('ยังไม่มีประวัติการเปลี่ยนแปลงสถานะ', 'direct-to-admin-form'); ?></p>
            <?php
        }
        $history_html = ob_get_clean();
        
        wp_send_json_success([
            'message' => __('อัปเดตสถานะเรียบร้อยแล้ว', 'direct-to-admin-form'),
            'new_status' => $status,
            'new_status_class' => 'dtaf-status-' . sanitize_title($status),
            'history_html' => $history_html
        ]);
    }
    
    /**
     * Delete submission via AJAX
     */
    public function ajax_delete_submission() {
        // Check nonce
        $submission_id = isset($_POST['submission_id']) ? intval($_POST['submission_id']) : 0;
        
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'dtaf_delete_submission_' . $submission_id)) {
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
        
        // Delete submission
        $result = DTAF_Database::delete_submission($submission_id);
        
        if ($result) {
            wp_send_json_success([
                'message' => __('ลบเรื่องร้องเรียนเรียบร้อยแล้ว', 'direct-to-admin-form'),
                'redirect_url' => admin_url('admin.php?page=dtaf-submissions&deleted=1')
            ]);
        } else {
            wp_send_json_error([
                'message' => __('เกิดข้อผิดพลาดในการลบเรื่องร้องเรียน', 'direct-to-admin-form')
            ]);
        }
    }
    
    /**
     * Handle bulk actions
     */
    public function handle_bulk_action() {
        // Check nonce
        if (!isset($_POST['dtaf_bulk_action_nonce']) || !wp_verify_nonce($_POST['dtaf_bulk_action_nonce'], 'dtaf_bulk_action')) {
            wp_die(__('การตรวจสอบความปลอดภัยล้มเหลว', 'direct-to-admin-form'));
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('คุณไม่มีสิทธิ์ดำเนินการนี้', 'direct-to-admin-form'));
        }
        
        // Get action and submission IDs
        $action = isset($_POST['bulk_action']) ? sanitize_text_field($_POST['bulk_action']) : '';
        $submission_ids = isset($_POST['submission_ids']) ? array_map('intval', $_POST['submission_ids']) : [];
        
        // Validate action and submission IDs
        if (empty($action) || $action === '-1' || empty($submission_ids)) {
            wp_redirect(admin_url('admin.php?page=dtaf-submissions&error=1'));
            exit;
        }
        
        // Process action
        $processed = 0;
        
        switch ($action) {
            case 'mark_as_new':
                foreach ($submission_ids as $submission_id) {
                    $result = DTAF_Database::update_submission($submission_id, ['status' => __('ใหม่', 'direct-to-admin-form')]);
                    if ($result) {
                        $processed++;
                    }
                }
                break;
                
            case 'mark_as_in_progress':
                foreach ($submission_ids as $submission_id) {
                    $result = DTAF_Database::update_submission($submission_id, ['status' => __('กำลังดำเนินการ', 'direct-to-admin-form')]);
                    if ($result) {
                        $processed++;
                    }
                }
                break;
                
            case 'mark_as_resolved':
                foreach ($submission_ids as $submission_id) {
                    $result = DTAF_Database::update_submission($submission_id, ['status' => __('แก้ไขแล้ว', 'direct-to-admin-form')]);
                    if ($result) {
                        $processed++;
                    }
                }
                break;
                
            case 'mark_as_closed':
                foreach ($submission_ids as $submission_id) {
                    $result = DTAF_Database::update_submission($submission_id, ['status' => __('ปิดเรื่อง', 'direct-to-admin-form')]);
                    if ($result) {
                        $processed++;
                    }
                }
                break;
                
            case 'bulk_delete':
                foreach ($submission_ids as $submission_id) {
                    $result = DTAF_Database::delete_submission($submission_id);
                    if ($result) {
                        $processed++;
                    }
                }
                break;
        }
        
        // Redirect with result
        wp_redirect(admin_url('admin.php?page=dtaf-submissions&bulk_processed=' . $processed));
        exit;
    }
}

// Initialize the class
new DTAF_Submissions();
