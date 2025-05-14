<?php
/**
 * Dashboard class
 * 
 * Handles dashboard widgets and statistics
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class DTAF_Dashboard {
    /**
     * Render dashboard widget
     */
    public function render_dashboard_widget() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'dtaf_submissions';
        
        // Get counts
        $total_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        $new_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE status = %s", __('ใหม่', 'direct-to-admin-form')));
        $in_progress_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE status = %s", __('กำลังดำเนินการ', 'direct-to-admin-form')));
        
        // Get recent submissions (last 5)
        $recent_submissions = $wpdb->get_results(
            "SELECT id, name, subject, type, status, created_at FROM $table_name ORDER BY created_at DESC LIMIT 5"
        );
        
        // Get submission counts by type
        $type_counts = $wpdb->get_results(
            "SELECT type, COUNT(*) as count FROM $table_name GROUP BY type ORDER BY count DESC LIMIT 5"
        );
        
        ?>
        <div class="dtaf-dashboard-widget">
            <!-- Stats Overview -->
            <div class="dtaf-widget-section">
                <div class="dtaf-stat-cards" style="display: flex; flex-wrap: wrap; gap: 10px;">
                    <div class="dtaf-stat-card" style="flex: 1; min-width: 100px; text-align: center; padding: 10px; background-color: #f0f6fc; border-radius: 4px;">
                        <div class="dtaf-stat-number"><?php echo esc_html($total_count); ?></div>
                        <div class="dtaf-stat-label"><?php esc_html_e('เรื่องทั้งหมด', 'direct-to-admin-form'); ?></div>
                    </div>
                    
                    <div class="dtaf-stat-card" style="flex: 1; min-width: 100px; text-align: center; padding: 10px; background-color: #fcf8e3; border-radius: 4px;">
                        <div class="dtaf-stat-number"><?php echo esc_html($new_count); ?></div>
                        <div class="dtaf-stat-label"><?php esc_html_e('เรื่องใหม่', 'direct-to-admin-form'); ?></div>
                    </div>
                    
                    <div class="dtaf-stat-card" style="flex: 1; min-width: 100px; text-align: center; padding: 10px; background-color: #f2dede; border-radius: 4px;">
                        <div class="dtaf-stat-number"><?php echo esc_html($in_progress_count); ?></div>
                        <div class="dtaf-stat-label"><?php esc_html_e('กำลังดำเนินการ', 'direct-to-admin-form'); ?></div>
                    </div>
                </div>
            </div>
            
            <!-- Recent Submissions -->
            <div class="dtaf-widget-section" style="margin-top: 15px;">
                <h3 style="margin: 0 0 10px; padding-bottom: 5px; border-bottom: 1px solid #eee;">
                    <?php esc_html_e('เรื่องร้องเรียนล่าสุด', 'direct-to-admin-form'); ?>
                </h3>
                
                <?php if (!empty($recent_submissions)): ?>
                    <ul style="margin: 0; padding: 0; list-style: none;">
                        <?php foreach ($recent_submissions as $submission): ?>
                            <li style="padding: 8px 0; border-bottom: 1px solid #f0f0f1;">
                                <a href="<?php echo esc_url(admin_url('admin.php?page=dtaf-submissions&action=view&submission_id=' . $submission->id)); ?>" style="text-decoration: none; display: block;">
                                    <strong><?php echo esc_html(wp_trim_words($submission->subject, 8)); ?></strong>
                                    <div style="display: flex; justify-content: space-between; font-size: 12px; color: #646970; margin-top: 3px;">
                                        <span><?php echo esc_html($submission->name); ?></span>
                                        <span>
                                            <?php 
                                            $status_class = 'dtaf-status-' . sanitize_title($submission->status);
                                            echo '<span class="dtaf-status-badge ' . esc_attr($status_class) . '" style="font-size: 11px; padding: 1px 6px; border-radius: 10px;">' . esc_html($submission->status) . '</span>';
                                            ?>
                                        </span>
                                    </div>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <p style="margin: 10px 0 0; text-align: right;">
                        <a href="<?php echo esc_url(admin_url('admin.php?page=dtaf-submissions')); ?>" class="button button-small">
                            <?php esc_html_e('ดูทั้งหมด', 'direct-to-admin-form'); ?>
                        </a>
                    </p>
                <?php else: ?>
                    <p><?php esc_html_e('ยังไม่มีเรื่องร้องเรียน', 'direct-to-admin-form'); ?></p>
                <?php endif; ?>
            </div>
            
            <!-- Submission Types -->
            <?php if (!empty($type_counts)): ?>
                <div class="dtaf-widget-section" style="margin-top: 15px;">
                    <h3 style="margin: 0 0 10px; padding-bottom: 5px; border-bottom: 1px solid #eee;">
                        <?php esc_html_e('ประเภทเรื่องร้องเรียน', 'direct-to-admin-form'); ?>
                    </h3>
                    
                    <ul style="margin: 0; padding: 0; list-style: none;">
                        <?php foreach ($type_counts as $type): ?>
                            <li style="display: flex; justify-content: space-between; padding: 5px 0;">
                                <span><?php echo esc_html($type->type); ?></span>
                                <span style="font-weight: bold;"><?php echo esc_html($type->count); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
}
