<?php
/**
 * Admin reports template
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get date range from GET parameters for filter inputs and export
$filter_start_date = isset($_GET['start_date']) && !empty($_GET['start_date']) ? sanitize_text_field($_GET['start_date']) : date('Y-m-d', strtotime('-30 days'));
$filter_end_date = isset($_GET['end_date']) && !empty($_GET['end_date']) ? sanitize_text_field($_GET['end_date']) : date('Y-m-d');

// Determine which statistics to use for display
$display_stats = [];
$is_filtered = (isset($_GET['start_date']) && !empty($_GET['start_date'])) && (isset($_GET['end_date']) && !empty($_GET['end_date']));

if ($is_filtered) {
    // If date range is actively provided via GET, use filtered statistics
    $display_stats = DTAF_Database::get_filtered_statistics($filter_start_date, $filter_end_date);
} else {
    // Otherwise, use general statistics
    $display_stats = DTAF_Database::get_statistics();
}

// Ensure $display_stats has a 'total' key, even if 0
$display_stats['total'] = isset($display_stats['total']) ? (int) $display_stats['total'] : 0;


// Get form names for display (used by both filtered and non-filtered stats)
$forms_data = DTAF_Database::get_forms();
$form_names = [];
if (!empty($forms_data)) {
    foreach ($forms_data as $form_item) {
        if (isset($form_item->form_slug) && isset($form_item->form_name)) {
            $form_names[$form_item->form_slug] = $form_item->form_name;
        }
    }
}
?>

<div class="wrap dtaf-admin-page">
    <h1 class="wp-heading-inline"><?php esc_html_e('รายงานและสถิติ', 'direct-to-admin-form'); ?></h1>
    
    <!-- Date Range Filter -->
    <div class="tablenav top">
        <form method="get" class="alignleft actions">
            <input type="hidden" name="page" value="dtaf-reports">
            
            <label for="start_date" class="screen-reader-text"><?php esc_html_e('วันที่เริ่มต้น', 'direct-to-admin-form'); ?></label>
            <input type="date" id="start_date" name="start_date" value="<?php echo esc_attr($filter_start_date); ?>">
            
            <label for="end_date" class="screen-reader-text"><?php esc_html_e('วันที่สิ้นสุด', 'direct-to-admin-form'); ?></label>
            <input type="date" id="end_date" name="end_date" value="<?php echo esc_attr($filter_end_date); ?>">
            
            <input type="submit" class="button" value="<?php esc_attr_e('กรอง', 'direct-to-admin-form'); ?>">
        </form>
        
        <!-- Export Form -->
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="alignright">
            <input type="hidden" name="action" value="dtaf_export_report">
            <input type="hidden" name="start_date" value="<?php echo esc_attr($filter_start_date); ?>">
            <input type="hidden" name="end_date" value="<?php echo esc_attr($filter_end_date); ?>">
            <?php wp_nonce_field('dtaf_export_report', 'dtaf_export_nonce'); ?>
            
            <select name="export_format">
                <option value="csv"><?php esc_html_e('CSV', 'direct-to-admin-form'); ?></option>
                <option value="excel"><?php esc_html_e('Excel', 'direct-to-admin-form'); ?></option>
                <option value="pdf"><?php esc_html_e('PDF', 'direct-to-admin-form'); ?></option>
            </select>
            
            <input type="submit" class="button" value="<?php esc_attr_e('ส่งออกรายงาน', 'direct-to-admin-form'); ?>">
        </form>
    </div>
    
    <!-- Summary Cards -->
    <div class="dtaf-admin-stats">
        <div style="display: flex; flex-wrap: wrap; gap: 20px; margin-bottom: 20px;">
            <div style="flex: 1; min-width: 200px; background-color: #fff; border: 1px solid #c3c4c7; border-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.13); padding: 20px; text-align: center;">
                <h2 style="margin: 0; font-size: 36px; color: #2271b1;"><?php echo esc_html($display_stats['total']); ?></h2>
                <p style="margin: 10px 0 0; color: #646970;"><?php esc_html_e('เรื่องร้องเรียนทั้งหมด', 'direct-to-admin-form'); ?><?php if ($is_filtered) echo ' (ตามช่วงวันที่)'; ?></p>
            </div>
            
            <div style="flex: 1; min-width: 200px; background-color: #fff; border: 1px solid #c3c4c7; border-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.13); padding: 20px; text-align: center;">
                <h2 style="margin: 0; font-size: 36px; color: #2271b1;">
                    <?php 
                    $new_count = 0;
                    if (!empty($display_stats['by_status'])) {
                        foreach ($display_stats['by_status'] as $status_item) {
                            if ($status_item->status === __('ใหม่', 'direct-to-admin-form')) {
                                $new_count = $status_item->count;
                                break;
                            }
                        }
                    }
                    echo esc_html($new_count);
                    ?>
                </h2>
                <p style="margin: 10px 0 0; color: #646970;"><?php esc_html_e('เรื่องร้องเรียนใหม่', 'direct-to-admin-form'); ?></p>
            </div>
            
            <div style="flex: 1; min-width: 200px; background-color: #fff; border: 1px solid #c3c4c7; border-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.13); padding: 20px; text-align: center;">
                <h2 style="margin: 0; font-size: 36px; color: #2271b1;">
                    <?php 
                    $resolved_count = 0;
                     if (!empty($display_stats['by_status'])) {
                        foreach ($display_stats['by_status'] as $status_item) {
                            if ($status_item->status === __('แก้ไขแล้ว', 'direct-to-admin-form')) {
                                $resolved_count = $status_item->count;
                                break;
                            }
                        }
                    }
                    echo esc_html($resolved_count);
                    ?>
                </h2>
                <p style="margin: 10px 0 0; color: #646970;"><?php esc_html_e('เรื่องที่แก้ไขแล้ว', 'direct-to-admin-form'); ?></p>
            </div>
            
            <div style="flex: 1; min-width: 200px; background-color: #fff; border: 1px solid #c3c4c7; border-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.13); padding: 20px; text-align: center;">
                <h2 style="margin: 0; font-size: 36px; color: #2271b1;">
                    <?php 
                    $in_progress_count = 0;
                    if (!empty($display_stats['by_status'])) {
                        foreach ($display_stats['by_status'] as $status_item) {
                            if ($status_item->status === __('กำลังดำเนินการ', 'direct-to-admin-form')) {
                                $in_progress_count = $status_item->count;
                                break;
                            }
                        }
                    }
                    echo esc_html($in_progress_count);
                    ?>
                </h2>
                <p style="margin: 10px 0 0; color: #646970;"><?php esc_html_e('เรื่องที่กำลังดำเนินการ', 'direct-to-admin-form'); ?></p>
            </div>
        </div>
    </div>
    
    <div class="metabox-holder columns-2">
        <!-- Chart: Submissions by Month -->
        <div class="postbox">
            <h2 class="hndle"><span><?php esc_html_e('เรื่องร้องเรียนตามเดือน', 'direct-to-admin-form'); ?></span></h2>
            <div class="inside">
                <canvas id="dtaf-submissions-chart" width="400" height="200"></canvas>
            </div>
        </div>
        
        <!-- Chart: Submissions by Status -->
        <div class="postbox">
            <h2 class="hndle"><span><?php esc_html_e('เรื่องร้องเรียนตามสถานะ', 'direct-to-admin-form'); ?></span></h2>
            <div class="inside">
                <canvas id="dtaf-status-chart" width="400" height="200"></canvas>
            </div>
        </div>
        
        <!-- Chart: Submissions by Type -->
        <div class="postbox">
            <h2 class="hndle"><span><?php esc_html_e('เรื่องร้องเรียนตามประเภท', 'direct-to-admin-form'); ?></span></h2>
            <div class="inside">
                <canvas id="dtaf-type-chart" width="400" height="200"></canvas>
            </div>
        </div>
        
        <!-- Chart: Submissions by Form -->
        <div class="postbox">
            <h2 class="hndle"><span><?php esc_html_e('เรื่องร้องเรียนตามแบบฟอร์ม', 'direct-to-admin-form'); ?></span></h2>
            <div class="inside">
                <canvas id="dtaf-form-chart" width="400" height="200"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Detailed Statistics -->
    <div class="metabox-holder columns-2">
        <!-- Submissions by Status -->
        <div class="postbox">
            <h2 class="hndle"><span><?php esc_html_e('เรื่องร้องเรียนตามสถานะ', 'direct-to-admin-form'); ?></span></h2>
            <div class="inside">
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('สถานะ', 'direct-to-admin-form'); ?></th>
                            <th><?php esc_html_e('จำนวน', 'direct-to-admin-form'); ?></th>
                            <th><?php esc_html_e('เปอร์เซ็นต์', 'direct-to-admin-form'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($display_stats['by_status'])): ?>
                            <?php foreach ($display_stats['by_status'] as $status_item): ?>
                                <tr>
                                    <td>
                                        <span class="dtaf-status-badge dtaf-status-<?php echo esc_attr(sanitize_title($status_item->status)); ?>">
                                            <?php echo esc_html($status_item->status); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="<?php echo esc_url(admin_url('admin.php?page=dtaf-submissions&status=' . urlencode($status_item->status))); ?>">
                                            <?php echo esc_html($status_item->count); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <?php 
                                        if ($display_stats['total'] > 0) {
                                            echo esc_html(round(($status_item->count / $display_stats['total']) * 100, 1)); 
                                        } else {
                                            echo '0';
                                        }
                                        ?>%
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3"><?php esc_html_e('ไม่พบข้อมูล', 'direct-to-admin-form'); ?></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Submissions by Type -->
        <div class="postbox">
            <h2 class="hndle"><span><?php esc_html_e('เรื่องร้องเรียนตามประเภท', 'direct-to-admin-form'); ?></span></h2>
            <div class="inside">
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('ประเภท', 'direct-to-admin-form'); ?></th>
                            <th><?php esc_html_e('จำนวน', 'direct-to-admin-form'); ?></th>
                            <th><?php esc_html_e('เปอร์เซ็นต์', 'direct-to-admin-form'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($display_stats['by_type'])): ?>
                            <?php foreach ($display_stats['by_type'] as $type_item): ?>
                                <tr>
                                    <td><?php echo esc_html($type_item->type); ?></td>
                                    <td>
                                        <a href="<?php echo esc_url(admin_url('admin.php?page=dtaf-submissions&type=' . urlencode($type_item->type))); ?>">
                                            <?php echo esc_html($type_item->count); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <?php 
                                        if ($display_stats['total'] > 0) {
                                            echo esc_html(round(($type_item->count / $display_stats['total']) * 100, 1));
                                        } else {
                                            echo '0';
                                        }
                                        ?>%
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3"><?php esc_html_e('ไม่พบข้อมูล', 'direct-to-admin-form'); ?></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Submissions by Form -->
        <div class="postbox">
            <h2 class="hndle"><span><?php esc_html_e('เรื่องร้องเรียนตามแบบฟอร์ม', 'direct-to-admin-form'); ?></span></h2>
            <div class="inside">
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('แบบฟอร์ม', 'direct-to-admin-form'); ?></th>
                            <th><?php esc_html_e('จำนวน', 'direct-to-admin-form'); ?></th>
                            <th><?php esc_html_e('เปอร์เซ็นต์', 'direct-to-admin-form'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($display_stats['by_form'])): ?>
                            <?php foreach ($display_stats['by_form'] as $form_item): ?>
                                <tr>
                                    <td>
                                        <?php 
                                        $form_display_name = isset($form_names[$form_item->form_id]) ? $form_names[$form_item->form_id] : $form_item->form_id;
                                        echo esc_html($form_display_name);
                                        ?>
                                    </td>
                                    <td>
                                        <a href="<?php echo esc_url(admin_url('admin.php?page=dtaf-submissions&form_id=' . urlencode($form_item->form_id))); ?>">
                                            <?php echo esc_html($form_item->count); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <?php 
                                        if ($display_stats['total'] > 0) {
                                            echo esc_html(round(($form_item->count / $display_stats['total']) * 100, 1));
                                        } else {
                                            echo '0';
                                        }
                                        ?>%
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3"><?php esc_html_e('ไม่พบข้อมูล', 'direct-to-admin-form'); ?></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Submissions by Month -->
        <div class="postbox">
            <h2 class="hndle"><span><?php esc_html_e('เรื่องร้องเรียนตามเดือน', 'direct-to-admin-form'); ?></span></h2>
            <div class="inside">
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('เดือน', 'direct-to-admin-form'); ?></th>
                            <th><?php esc_html_e('จำนวน', 'direct-to-admin-form'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($display_stats['by_month'])): ?>
                            <?php foreach ($display_stats['by_month'] as $month_item): ?>
                                <tr>
                                    <td>
                                        <?php 
                                        try {
                                            $month_date = new DateTime($month_item->month . '-01');
                                            echo esc_html($month_date->format('F Y'));
                                        } catch (Exception $e) {
                                            echo esc_html($month_item->month); // Fallback
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php echo esc_html($month_item->count); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="2"><?php esc_html_e('ไม่พบข้อมูล', 'direct-to-admin-form'); ?></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    jQuery(document).ready(function($) {
        // Load Chart.js
        if (typeof Chart !== 'undefined') {
            // Prepare data for charts using $display_stats
            var monthsData = <?php 
                $months_labels = [];
                $months_counts = [];
                
                if (!empty($display_stats['by_month'])) {
                    foreach ($display_stats['by_month'] as $month_item) {
                        try {
                            $month_date = new DateTime($month_item->month . '-01');
                            $months_labels[] = $month_date->format('M Y');
                        } catch (Exception $e) {
                            $months_labels[] = $month_item->month; // Fallback
                        }
                        $months_counts[] = $month_item->count;
                    }
                }
                
                echo json_encode([
                    'labels' => $months_labels,
                    'data' => $months_counts
                ]);
            ?>;
            
            var statusData = <?php 
                $statuses_labels = [];
                $status_counts = [];
                $status_colors = [];
                
                if (!empty($display_stats['by_status'])) {
                    $color_map = [
                        __('ใหม่', 'direct-to-admin-form') => 'rgba(0, 123, 255, 0.7)',
                        __('กำลังดำเนินการ', 'direct-to-admin-form') => 'rgba(255, 193, 7, 0.7)',
                        __('รอข้อมูลเพิ่มเติม', 'direct-to-admin-form') => 'rgba(108, 117, 125, 0.7)',
                        __('แก้ไขแล้ว', 'direct-to-admin-form') => 'rgba(40, 167, 69, 0.7)',
                        __('ปิดเรื่อง', 'direct-to-admin-form') => 'rgba(220, 53, 69, 0.7)',
                        // Add more status-color mappings if needed
                    ];
                    
                    foreach ($display_stats['by_status'] as $status_item) {
                        $statuses_labels[] = $status_item->status;
                        $status_counts[] = $status_item->count;
                        $status_colors[] = isset($color_map[$status_item->status]) ? $color_map[$status_item->status] : 'rgba(0, 123, 255, 0.7)'; // Default color
                    }
                }
                
                echo json_encode([
                    'labels' => $statuses_labels,
                    'data' => $status_counts,
                    'colors' => $status_colors
                ]);
            ?>;
            
            var typeData = <?php 
                $types_labels = [];
                $type_counts = [];
                
                if (!empty($display_stats['by_type'])) {
                    foreach ($display_stats['by_type'] as $type_item) {
                        $types_labels[] = $type_item->type;
                        $type_counts[] = $type_item->count;
                    }
                }
                
                echo json_encode([
                    'labels' => $types_labels,
                    'data' => $type_counts
                ]);
            ?>;
            
            var formData = <?php 
                $forms_labels = [];
                $form_counts = [];
                
                if (!empty($display_stats['by_form'])) {
                    foreach ($display_stats['by_form'] as $form_item) {
                        $form_display_name = isset($form_names[$form_item->form_id]) ? $form_names[$form_item->form_id] : $form_item->form_id;
                        $forms_labels[] = $form_display_name;
                        $form_counts[] = $form_item->count;
                    }
                }
                
                echo json_encode([
                    'labels' => $forms_labels,
                    'data' => $form_counts
                ]);
            ?>;
            
            // Generate random colors for charts that don't have predefined ones
            function generateColors(count) {
                var colors = [];
                for (var i = 0; i < count; i++) {
                    var hue = (i * (360 / (count > 0 ? count : 1) * 0.61803398875)) % 360; // Golden angle
                    colors.push('hsla(' + hue + ', 70%, 60%, 0.7)');
                }
                return colors;
            }
            
            // Submissions by Month Chart
            var submissionsCtx = document.getElementById('dtaf-submissions-chart').getContext('2d');
            new Chart(submissionsCtx, {
                type: 'bar',
                data: {
                    labels: monthsData.labels,
                    datasets: [{
                        label: '<?php echo esc_js(__('จำนวนเรื่องร้องเรียน', 'direct-to-admin-form')); ?>',
                        data: monthsData.data,
                        backgroundColor: 'rgba(54, 162, 235, 0.5)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });
            
            // Status Chart
            var statusCtx = document.getElementById('dtaf-status-chart').getContext('2d');
            new Chart(statusCtx, {
                type: 'pie',
                data: {
                    labels: statusData.labels,
                    datasets: [{
                        data: statusData.data,
                        backgroundColor: statusData.colors,
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right'
                        }
                    }
                }
            });
            
            // Type Chart
            var typeCtx = document.getElementById('dtaf-type-chart').getContext('2d');
            new Chart(typeCtx, {
                type: 'doughnut', // Changed to doughnut for variety, can be pie
                data: {
                    labels: typeData.labels,
                    datasets: [{
                        data: typeData.data,
                        backgroundColor: generateColors(typeData.labels.length),
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right'
                        }
                    }
                }
            });
            
            // Form Chart
            var formCtx = document.getElementById('dtaf-form-chart').getContext('2d');
            new Chart(formCtx, {
                type: 'pie',
                data: {
                    labels: formData.labels,
                    datasets: [{
                        data: formData.data,
                        backgroundColor: generateColors(formData.labels.length),
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right'
                        }
                    }
                }
            });
        } else {
            console.warn('Chart.js is not loaded. Charts will not be displayed.');
            // You might want to display a message to the user in the chart canvas areas
            $('#dtaf-submissions-chart, #dtaf-status-chart, #dtaf-type-chart, #dtaf-form-chart')
                .parent().html('<p><?php echo esc_js(__("Chart library not loaded. Cannot display chart.", "direct-to-admin-form")); ?></p>');
        }
    });
</script>