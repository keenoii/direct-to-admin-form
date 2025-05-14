<?php

/**
 * Admin submissions list template
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get submissions with pagination
$current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$per_page = 20; // Items per page

// Get filter parameters
$status_filter = isset($_GET['status']) ? sanitize_text_field(wp_unslash($_GET['status'])) : '';
$form_filter = isset($_GET['form_id']) ? sanitize_text_field(wp_unslash($_GET['form_id'])) : '';
$type_filter = isset($_GET['type']) ? sanitize_text_field(wp_unslash($_GET['type'])) : '';
$search_query = isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : ''; // Renamed for clarity
$date_from = isset($_GET['date_from']) ? sanitize_text_field(wp_unslash($_GET['date_from'])) : '';
$date_to = isset($_GET['date_to']) ? sanitize_text_field(wp_unslash($_GET['date_to'])) : '';
$orderby = isset($_GET['orderby']) ? sanitize_key($_GET['orderby']) : 'created_at';
$order = isset($_GET['order']) ? strtoupper(sanitize_key($_GET['order'])) : 'DESC';
if (!in_array($order, ['ASC', 'DESC'])) {
    $order = 'DESC';
}

// Prepare arguments for get_submissions
$args = [
    'per_page' => $per_page,
    'page' => $current_page,
    'status' => $status_filter,
    'form_id' => $form_filter,
    'type' => $type_filter,
    'search' => $search_query,
    'date_from' => $date_from,
    'date_to' => $date_to,
    'orderby' => $orderby,
    'order' => $order,
];

// Get submissions data
$submissions_data = DTAF_Database::get_submissions($args);

// Ensure all expected keys are present with default values
$submissions = isset($submissions_data['items']) && is_array($submissions_data['items']) ? $submissions_data['items'] : [];
$total_items = isset($submissions_data['total_items']) ? (int) $submissions_data['total_items'] : 0;
$total_pages = isset($submissions_data['total_pages']) ? (int) $submissions_data['total_pages'] : 0; // Default to 0 if not set

// Get forms for filter dropdown
$forms = DTAF_Database::get_forms(); // Assuming this returns an array of objects

// Get unique types for filter dropdown
global $wpdb;
$submissions_table_name = $wpdb->prefix . 'dtaf_submissions';
$types = $wpdb->get_col("SELECT DISTINCT type FROM $submissions_table_name WHERE type IS NOT NULL AND type != '' ORDER BY type ASC");

// Get unique statuses for filter dropdown (or use a predefined list)
$statuses = $wpdb->get_col("SELECT DISTINCT status FROM $submissions_table_name WHERE status IS NOT NULL AND status != '' ORDER BY status ASC");
// Predefined statuses might be better for consistency:
// $statuses = [__('ใหม่', 'direct-to-admin-form'), __('กำลังดำเนินการ', 'direct-to-admin-form'), /* ... */];

?>

<div class="wrap dtaf-admin-page">
    <h1 class="wp-heading-inline"><?php esc_html_e('เรื่องร้องเรียนทั้งหมด', 'direct-to-admin-form'); ?></h1>

    <div id="dtaf-ajax-message" class="notice" style="display:none;"></div>

    <form method="get" id="dtaf-filter-form">
        <input type="hidden" name="page" value="dtaf-submissions">
        <?php // Keep existing orderby and order parameters if set for filters
        if (!empty($orderby)) {
            echo '<input type="hidden" name="orderby" value="' . esc_attr($orderby) . '">';
        }
        if (!empty($order)) {
            echo '<input type="hidden" name="order" value="' . esc_attr($order) . '">';
        }
        ?>
        <div class="tablenav top">
            <div class="alignleft actions bulkactions"> <?php // Filters first 
                                                        ?>
                <label for="filter-by-status" class="screen-reader-text"><?php esc_html_e('กรองตามสถานะ', 'direct-to-admin-form'); ?></label>
                <select name="status" id="filter-by-status">
                    <option value=""><?php esc_html_e('ทุกสถานะ', 'direct-to-admin-form'); ?></option>
                    <?php if (!empty($statuses)): foreach ($statuses as $status_val): ?>
                            <option value="<?php echo esc_attr($status_val); ?>" <?php selected($status_filter, $status_val); ?>>
                                <?php echo esc_html($status_val); ?>
                            </option>
                    <?php endforeach;
                    endif; ?>
                </select>

                <label for="filter-by-form" class="screen-reader-text"><?php esc_html_e('กรองตามแบบฟอร์ม', 'direct-to-admin-form'); ?></label>
                <select name="form_id" id="filter-by-form">
                    <option value=""><?php esc_html_e('ทุกแบบฟอร์ม', 'direct-to-admin-form'); ?></option>
                    <?php if (!empty($forms)): foreach ($forms as $form_item): ?>
                            <?php if (is_object($form_item) && isset($form_item->form_slug) && isset($form_item->form_name)): ?>
                                <option value="<?php echo esc_attr($form_item->form_slug); ?>" <?php selected($form_filter, $form_item->form_slug); ?>>
                                    <?php echo esc_html($form_item->form_name); ?>
                                </option>
                            <?php endif; ?>
                    <?php endforeach;
                    endif; ?>
                </select>

                <label for="filter-by-type" class="screen-reader-text"><?php esc_html_e('กรองตามประเภท', 'direct-to-admin-form'); ?></label>
                <select name="type" id="filter-by-type">
                    <option value=""><?php esc_html_e('ทุกประเภท', 'direct-to-admin-form'); ?></option>
                    <?php if (!empty($types)): foreach ($types as $type_val): ?>
                            <option value="<?php echo esc_attr($type_val); ?>" <?php selected($type_filter, $type_val); ?>>
                                <?php echo esc_html($type_val); ?>
                            </option>
                    <?php endforeach;
                    endif; ?>
                </select>
                <input id="post-query-submit" type="submit" class="button" value="<?php esc_attr_e('กรอง', 'direct-to-admin-form'); ?>">
            </div>

            <div class="alignleft actions">
                <label for="date_from" class="screen-reader-text"><?php esc_html_e('วันที่เริ่มต้น', 'direct-to-admin-form'); ?></label>
                <input type="date" id="date_from" name="date_from" value="<?php echo esc_attr($date_from); ?>" style="margin-left: 5px;">
                <span class="date-range-separator" style="margin: 0 5px;">-</span>
                <label for="date_to" class="screen-reader-text"><?php esc_html_e('วันที่สิ้นสุด', 'direct-to-admin-form'); ?></label>
                <input type="date" id="date_to" name="date_to" value="<?php echo esc_attr($date_to); ?>">
            </div>

            <div class="tablenav-pages">
                <?php if ($total_pages > 1): // Display pagination info if there's more than one page 
                ?>
                    <span class="displaying-num">
                        <?php printf(
                            _n('%s item', '%s items', $total_items, 'direct-to-admin-form'),
                            number_format_i18n($total_items)
                        ); ?>
                    </span>
                <?php endif; ?>
                <div class="alignright actions" style="display:inline-block; float:right;"> <!-- Export Form Container -->
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display: inline-block;">
                        <input type="hidden" name="action" value="dtaf_export_submissions">
                        <?php wp_nonce_field('dtaf_export_submissions', 'dtaf_export_nonce'); ?>
                        <input type="hidden" name="status" value="<?php echo esc_attr($status_filter); ?>">
                        <input type="hidden" name="form_id" value="<?php echo esc_attr($form_filter); ?>">
                        <input type="hidden" name="type" value="<?php echo esc_attr($type_filter); ?>">
                        <input type="hidden" name="search" value="<?php echo esc_attr($search_query); ?>">
                        <input type="hidden" name="date_from" value="<?php echo esc_attr($date_from); ?>">
                        <input type="hidden" name="date_to" value="<?php echo esc_attr($date_to); ?>">
                        <label for="export_format" class="screen-reader-text"><?php esc_html_e('รูปแบบการส่งออก', 'direct-to-admin-form'); ?></label>
                        <select name="export_format" id="export_format">
                            <option value="csv"><?php esc_html_e('CSV', 'direct-to-admin-form'); ?></option>
                            <option value="excel"><?php esc_html_e('Excel', 'direct-to-admin-form'); ?></option>
                        </select>
                        <input type="submit" class="button" value="<?php esc_attr_e('ส่งออก', 'direct-to-admin-form'); ?>">
                    </form>
                </div>
                <p class="search-box" style="float:right; margin-right:10px;">
                    <label class="screen-reader-text" for="dtaf-search-input"><?php esc_html_e('ค้นหาเรื่องร้องเรียน:', 'direct-to-admin-form'); ?></label>
                    <input type="search" id="dtaf-search-input" name="s" value="<?php echo esc_attr($search_query); ?>" placeholder="<?php esc_attr_e('ค้นหา...', 'direct-to-admin-form'); ?>">
                    <input type="submit" id="search-submit" class="button" value="<?php esc_attr_e('ค้นหา', 'direct-to-admin-form'); ?>">
                </p>
            </div>
            <br class="clear">
        </div>
    </form> <!-- End Filter Form -->

    <form method="post" id="dtaf-bulk-action-form" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <input type="hidden" name="action" value="dtaf_bulk_action">
        <?php wp_nonce_field('dtaf_bulk_action', 'dtaf_bulk_action_nonce'); ?>

        <div class="tablenav top"> <?php // This tablenav is for bulk actions and potentially top pagination 
                                    ?>
            <div class="alignleft actions bulkactions">
                <label for="bulk-action-selector-top" class="screen-reader-text"><?php esc_html_e('Select bulk action'); ?></label>
                <select name="bulk_action" id="bulk-action-selector-top">
                    <option value="-1"><?php esc_html_e('การกระทำเป็นกลุ่ม', 'direct-to-admin-form'); ?></option>
                    <option value="mark_as_new"><?php esc_html_e('สถานะ: ใหม่', 'direct-to-admin-form'); ?></option>
                    <option value="mark_as_in_progress"><?php esc_html_e('สถานะ: กำลังดำเนินการ', 'direct-to-admin-form'); ?></option>
                    <option value="mark_as_resolved"><?php esc_html_e('สถานะ: แก้ไขแล้ว', 'direct-to-admin-form'); ?></option>
                    <option value="mark_as_closed"><?php esc_html_e('สถานะ: ปิดเรื่อง', 'direct-to-admin-form'); ?></option>
                    <option value="bulk_delete"><?php esc_html_e('ลบที่เลือก', 'direct-to-admin-form'); ?></option>
                </select>
                <input type="submit" id="doaction" class="button action" value="<?php esc_attr_e('ดำเนินการ', 'direct-to-admin-form'); ?>">
            </div>
            <?php // Top Pagination (identical to bottom one)
            if ($total_pages > 1) : ?>
                <div class="tablenav-pages">
                    <span class="displaying-num"><?php printf(_n('%s item', '%s items', $total_items, 'direct-to-admin-form'), number_format_i18n($total_items)); ?></span>
                    <span class="pagination-links">
                        <?php
                        $page_links = paginate_links([
                            'base' => add_query_arg('paged', '%#%'),
                            'format' => '',
                            'prev_text' => __('«'),
                            'next_text' => __('»'),
                            'total' => $total_pages,
                            'current' => $current_page,
                            'type' => 'array', // Get an array of paginated page links
                        ]);

                        if ($page_links) {
                            echo '<span class="pagination-links">';
                            foreach ($page_links as $link) {
                                echo $link;
                            }
                            echo '</span>';
                        }
                        ?>
                    </span>
                </div>
            <?php endif; ?>
            <br class="clear" />
        </div>


        <table class="wp-list-table widefat fixed striped dtaf-submissions-table">
            <thead>
                <tr>
                    <td id="cb" class="manage-column column-cb check-column">
                        <label class="screen-reader-text" for="cb-select-all-1"><?php esc_html_e('Select all'); ?></label>
                        <input id="cb-select-all-1" type="checkbox" />
                    </td>
                    <?php
                    $columns = [
                        'id' => __('ID', 'direct-to-admin-form'),
                        'name' => __('ชื่อผู้ร้อง', 'direct-to-admin-form'),
                        'subject' => __('เรื่อง', 'direct-to-admin-form'),
                        'type' => __('ประเภท', 'direct-to-admin-form'),
                        'form_id' => __('แบบฟอร์ม', 'direct-to-admin-form'),
                        'created_at' => __('วันที่', 'direct-to-admin-form'),
                        'status' => __('สถานะ', 'direct-to-admin-form'),
                    ];
                    $default_sort_column = 'created_at';
                    $default_sort_order = 'DESC';

                    foreach ($columns as $column_slug => $column_display_name) {
                        $current_order = ($orderby === $column_slug && $order === 'ASC') ? 'DESC' : 'ASC';
                        $sort_indicator = '';
                        if ($orderby === $column_slug) {
                            $sort_indicator = ($order === 'ASC') ? '<span class="dashicons dashicons-arrow-up"></span>' : '<span class="dashicons dashicons-arrow-down"></span>';
                        }
                        $is_sorted = ($orderby === $column_slug) ? 'sorted ' . strtolower($order) : 'sortable ' . (($order === 'ASC') ? 'asc' : 'desc');
                        $column_class = "manage-column column-{$column_slug} {$is_sorted}";
                        if (in_array($column_slug, ['id', 'created_at'])) { // Example sortable columns
                            echo '<th scope="col" id="' . esc_attr($column_slug) . '" class="' . esc_attr($column_class) . '">';
                            echo '<a href="' . esc_url(add_query_arg(['orderby' => $column_slug, 'order' => $current_order])) . '">';
                            echo '<span>' . esc_html($column_display_name) . '</span><span class="sorting-indicator">' . $sort_indicator . '</span>';
                            echo '</a></th>';
                        } else {
                            echo '<th scope="col" id="' . esc_attr($column_slug) . '" class="manage-column column-' . esc_attr($column_slug) . '">' . esc_html($column_display_name) . '</th>';
                        }
                    }
                    ?>
                </tr>
            </thead>

            <tbody id="the-list">
                <?php if (!empty($submissions)): ?>
                    <?php foreach ($submissions as $submission): ?>
                        <?php
                        $form_display_name = __('ไม่ระบุ', 'direct-to-admin-form');
                        if (!empty($forms) && isset($submission->form_id)) {
                            foreach ($forms as $form_item) {
                                if (is_object($form_item) && $form_item->form_slug === $submission->form_id) {
                                    $form_display_name = $form_item->form_name;
                                    break;
                                }
                            }
                        }
                        $submission_date = date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($submission->created_at));
                        $status_class_wp = 'status-' . sanitize_title($submission->status); // For WP list table general styling
                        $status_class_dtaf = 'dtaf-status-' . sanitize_title($submission->status); // For your specific DTAF styling
                        ?>
                        <tr id="submission-<?php echo esc_attr($submission->id); ?>" class="<?php echo esc_attr($status_class_wp); ?>">
                            <th scope="row" class="check-column">
                                <label class="screen-reader-text" for="cb-select-<?php echo esc_attr($submission->id); ?>">
                                    <?php printf(esc_html__('Select submission %d'), $submission->id); ?>
                                </label>
                                <input type="checkbox" name="submission_ids[]" id="cb-select-<?php echo esc_attr($submission->id); ?>" value="<?php echo esc_attr($submission->id); ?>">
                            </th>
                            <td class="id column-id" data-colname="<?php esc_attr_e('ID', 'direct-to-admin-form'); ?>">
                                <?php echo esc_html($submission->id); ?>
                                <div class="row-actions">
                                    <span class="view">
                                        <a href="<?php echo esc_url(admin_url('admin.php?page=dtaf-submissions&action=view&submission_id=' . $submission->id)); ?>" aria-label="<?php printf(esc_attr__('View details for submission %s', 'direct-to-admin-form'), $submission->id); ?>">
                                            <?php esc_html_e('ดูรายละเอียด', 'direct-to-admin-form'); ?>
                                        </a> |
                                    </span>
                                    <span class="view-modal">
                                        <a href="#" class="dtaf-view-details-modal" data-id="<?php echo esc_attr($submission->id); ?>"> <!-- ตรวจสอบ class นี้ -->
                                            <?php esc_html_e('ดูในป๊อปอัพ', 'direct-to-admin-form'); ?>
                                        </a> |
                                    </span>
                                    <span class="delete">
                                        <a href="#" class="dtaf-delete-submission" data-id="<?php echo esc_attr($submission->id); ?>" data-nonce="<?php echo esc_attr(wp_create_nonce('dtaf_delete_submission_' . $submission->id)); ?>" aria-label="<?php printf(esc_attr__('Delete submission %s', 'direct-to-admin-form'), $submission->id); ?>" style="color:#b32d2e;">
                                            <?php esc_html_e('ลบ', 'direct-to-admin-form'); ?>
                                        </a>
                                    </span>
                                </div>
                            </td>
                            <td class="name column-name" data-colname="<?php esc_attr_e('ชื่อผู้ร้อง', 'direct-to-admin-form'); ?>">
                                <?php echo esc_html($submission->name); ?>
                                <?php if (!empty($submission->email)): ?>
                                    <div class="row-actions">
                                        <span class="email">
                                            <a href="mailto:<?php echo esc_attr($submission->email); ?>">
                                                <?php echo esc_html($submission->email); ?>
                                            </a>
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="subject column-subject" data-colname="<?php esc_attr_e('เรื่อง', 'direct-to-admin-form'); ?>">
                                <?php echo esc_html(wp_trim_words($submission->subject, 10, '...')); ?>
                            </td>
                            <td class="type column-type" data-colname="<?php esc_attr_e('ประเภท', 'direct-to-admin-form'); ?>">
                                <?php echo esc_html($submission->type); ?>
                            </td>
                            <td class="form_id column-form_id" data-colname="<?php esc_attr_e('แบบฟอร์ม', 'direct-to-admin-form'); ?>">
                                <?php echo esc_html($form_display_name); ?>
                            </td>
                            <td class="created_at column-created_at" data-colname="<?php esc_attr_e('วันที่', 'direct-to-admin-form'); ?>">
                                <abbr title="<?php echo esc_attr($submission->created_at); ?>"><?php echo esc_html($submission_date); ?></abbr>
                            </td>
                            <td class="status column-status <?php echo esc_attr($status_class_dtaf); // Use your specific class for styling if needed 
                                                            ?>" data-colname="<?php esc_attr_e('สถานะ', 'direct-to-admin-form'); ?>">
                                <span class="dtaf-status-text"><?php echo esc_html($submission->status); ?></span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr class="no-items">
                        <td class="colspanchange" colspan="<?php echo count($columns) + 1; // +1 for checkbox column 
                                                            ?>"><?php esc_html_e('ไม่พบข้อมูลการร้องเรียนที่ตรงกับเงื่อนไข', 'direct-to-admin-form'); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>


        </table>
    </form> <!-- End Bulk Actions Form -->

    <!-- Modal for viewing submission details -->
    <div id="dtaf-submission-details-modal" class="dtaf-modal" style="display:none;">
        <div class="dtaf-modal-content">
            <button type="button" class="dtaf-modal-close button-link"><span class="screen-reader-text"><?php esc_html_e('Close modal', 'direct-to-admin-form'); ?></span><span class="dashicons dashicons-no-alt"></span></button>
            <div id="dtaf-modal-body">
                <!-- Content will be loaded via AJAX -->
            </div>
        </div>
    </div>
</div>