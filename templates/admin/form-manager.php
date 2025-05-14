<?php
/**
 * Admin form manager template
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Check if we're adding/editing a form
$view_action = isset($_GET['view']) ? sanitize_text_field(wp_unslash($_GET['view'])) : ''; // Changed 'action' to 'view' to avoid conflict
$form_id_to_edit = isset($_GET['form_id']) ? intval($_GET['form_id']) : 0;

// If we're editing or adding, show the form editor template
if ($view_action === 'edit-form' || $view_action === 'add-new-form') {
    // Pass $form_id_to_edit to the editor. If it's 0, editor treats it as new.
    // The form-editor.php will handle fetching the form data if $form_id_to_edit > 0
    include DTAF_PLUGIN_DIR . 'templates/admin/form-editor.php';
    return; // Stop further processing of this page
}

// Get all forms for the list display
$forms = DTAF_Database::get_forms();
?>

<div class="wrap dtaf-admin-page dtaf-form-manager">
    <h1 class="wp-heading-inline"><?php esc_html_e('จัดการแบบฟอร์ม', 'direct-to-admin-form'); ?></h1>
    
    <a href="<?php echo esc_url(admin_url('admin.php?page=dtaf-form-manager&view=add-new-form')); ?>" class="page-title-action">
        <?php esc_html_e('เพิ่มแบบฟอร์มใหม่', 'direct-to-admin-form'); ?>
    </a>
    <hr class="wp-header-end">

    <!-- AJAX Message Container & Admin Notices -->
    <div id="dtaf-ajax-message-placeholder">
        <?php 
        // Display any persistent admin notices passed via URL (e.g., after form save from form-editor.php)
        if (isset($_GET['message'])) {
            $message_type = 'updated'; // 'updated' is WordPress class for success
            $message_text = '';
            if ($_GET['message'] === 'created') {
                $message_text = __('สร้างแบบฟอร์มเรียบร้อยแล้ว', 'direct-to-admin-form');
            } elseif ($_GET['message'] === 'updated') {
                $message_text = __('บันทึกแบบฟอร์มเรียบร้อยแล้ว', 'direct-to-admin-form');
            } elseif ($_GET['message'] === 'deleted') {
                $message_text = __('ลบแบบฟอร์มเรียบร้อยแล้ว', 'direct-to-admin-form');
            } elseif ($_GET['message'] === 'delete_failed_submissions') {
                $message_type = 'error';
                $message_text = __('ไม่สามารถลบแบบฟอร์มได้ เนื่องจากยังมีเรื่องร้องเรียนที่ใช้แบบฟอร์มนี้อยู่', 'direct-to-admin-form');
            } elseif ($_GET['message'] === 'delete_failed_default') {
                $message_type = 'error';
                $message_text = __('ไม่สามารถลบแบบฟอร์มค่าเริ่มต้นได้', 'direct-to-admin-form');
            }
             elseif ($_GET['message'] === 'delete_failed') {
                $message_type = 'error';
                $message_text = __('เกิดข้อผิดพลาดในการลบแบบฟอร์ม', 'direct-to-admin-form');
            }

            if ($message_text) {
                echo '<div id="setting-error-settings_updated" class="notice notice-' . esc_attr($message_type) . ' is-dismissible settings-error"><p><strong>' . esc_html($message_text) . '</strong></p><button type="button" class="notice-dismiss"><span class="screen-reader-text">' . esc_html__('Dismiss this notice.') . '</span></button></div>';
            }
        }
        ?>
    </div>
    <div id="dtaf-ajax-message" class="notice" style="display:none;"></div>


    <div class="dtaf-content-area">
        <table class="wp-list-table widefat fixed striped dtaf-form-manager-table">
            <thead>
                <tr>
                    <th scope="col" id="form_name" class="manage-column column-form_name column-primary"><?php esc_html_e('ชื่อแบบฟอร์ม', 'direct-to-admin-form'); ?></th>
                    <th scope="col" id="form_slug" class="manage-column column-form_slug"><?php esc_html_e('Slug / รหัส', 'direct-to-admin-form'); ?></th>
                    <th scope="col" id="shortcode" class="manage-column column-shortcode"><?php esc_html_e('Shortcode', 'direct-to-admin-form'); ?></th>
                    <th scope="col" id="submissions" class="manage-column column-submissions num"><?php esc_html_e('จำนวนการส่ง', 'direct-to-admin-form'); ?></th>
                </tr>
            </thead>
            <tbody id="the-list">
                <?php if (!empty($forms)): ?>
                    <?php foreach ($forms as $form_item): ?>
                        <?php
                        if (!is_object($form_item) || !isset($form_item->form_slug)) continue; // Skip if not a valid form object

                        global $wpdb;
                        $submissions_table_name = $wpdb->prefix . 'dtaf_submissions';
                        $submission_count = $wpdb->get_var($wpdb->prepare(
                            "SELECT COUNT(*) FROM $submissions_table_name WHERE form_id = %s",
                            $form_item->form_slug
                        ));
                        
                        $is_default_form = ($form_item->form_slug === 'default_form');
                        ?>
                        <tr id="form-row-<?php echo esc_attr($form_item->id); ?>">
                            <td class="form_name column-form_name column-primary has-row-actions" data-colname="<?php esc_attr_e('ชื่อแบบฟอร์ม', 'direct-to-admin-form'); ?>">
                                <strong>
                                    <a class="row-title" href="<?php echo esc_url(admin_url('admin.php?page=dtaf-form-manager&view=edit-form&form_id=' . $form_item->id)); ?>">
                                        <?php echo esc_html($form_item->form_name); ?>
                                    </a>
                                </strong>
                                <?php if ($is_default_form): ?>
                                    <span class="dtaf-default-badge"><?php esc_html_e('ค่าเริ่มต้น', 'direct-to-admin-form'); ?></span>
                                <?php endif; ?>
                                <div class="row-actions">
                                    <span class="edit">
                                        <a href="<?php echo esc_url(admin_url('admin.php?page=dtaf-form-manager&view=edit-form&form_id=' . $form_item->id)); ?>" aria-label="<?php printf(esc_attr__('Edit form %s', 'direct-to-admin-form'), $form_item->form_name); ?>">
                                            <?php esc_html_e('แก้ไข', 'direct-to-admin-form'); ?>
                                        </a>
                                    </span>
                                    <?php if (!$is_default_form): ?>
                                        | <span class="delete">
                                            <a href="#" class="dtaf-delete-form" data-id="<?php echo esc_attr($form_item->id); ?>" data-nonce="<?php echo esc_attr(wp_create_nonce('dtaf_delete_form_action_' . $form_item->id)); // Unique nonce per form ?>" aria-label="<?php printf(esc_attr__('Delete form %s', 'direct-to-admin-form'), $form_item->form_name); ?>" style="color:#b32d2e;">
                                                <?php esc_html_e('ลบ', 'direct-to-admin-form'); ?>
                                            </a>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <button type="button" class="toggle-row"><span class="screen-reader-text"><?php esc_html_e( 'Show more details' ); ?></span></button>
                            </td>
                            <td class="form_slug column-form_slug" data-colname="<?php esc_attr_e('Slug / รหัส', 'direct-to-admin-form'); ?>">
                                <?php echo esc_html($form_item->form_slug); ?>
                            </td>
                            <td class="shortcode column-shortcode" data-colname="<?php esc_attr_e('Shortcode', 'direct-to-admin-form'); ?>">
                                <code class="dtaf-shortcode-text">[direct_to_admin_form form_id="<?php echo esc_attr($form_item->form_slug); ?>"]</code>
                                <button type="button" class="button button-small dtaf-copy-shortcode" data-shortcode='[direct_to_admin_form form_id="<?php echo esc_attr($form_item->form_slug); ?>"]' title="<?php esc_attr_e('คัดลอก Shortcode', 'direct-to-admin-form'); ?>">
                                    <span class="dashicons dashicons-admin-page"></span>
                                    <span class="screen-reader-text"><?php esc_html_e('คัดลอก Shortcode', 'direct-to-admin-form'); ?></span>
                                </button>
                            </td>
                            <td class="submissions column-submissions num" data-colname="<?php esc_attr_e('จำนวนการส่ง', 'direct-to-admin-form'); ?>">
                                <?php if ($submission_count > 0): ?>
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=dtaf-submissions&form_id=' . urlencode($form_item->form_slug))); ?>">
                                        <?php echo number_format_i18n($submission_count); ?>
                                    </a>
                                <?php else: ?>
                                    <?php echo number_format_i18n(0); ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr class="no-items">
                        <td class="colspanchange" colspan="4"><?php esc_html_e('ยังไม่ได้สร้างแบบฟอร์มใดๆ', 'direct-to-admin-form'); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th scope="col" class="manage-column column-form_name column-primary"><?php esc_html_e('ชื่อแบบฟอร์ม', 'direct-to-admin-form'); ?></th>
                    <th scope="col" class="manage-column column-form_slug"><?php esc_html_e('Slug / รหัส', 'direct-to-admin-form'); ?></th>
                    <th scope="col" class="manage-column column-shortcode"><?php esc_html_e('Shortcode', 'direct-to-admin-form'); ?></th>
                    <th scope="col" class="manage-column column-submissions num"><?php esc_html_e('จำนวนการส่ง', 'direct-to-admin-form'); ?></th>
                </tr>
            </tfoot>
        </table>
    </div> <!-- .dtaf-content-area -->

    <div id="col-container" class="wp-clearfix" style="margin-top: 20px;">
        <div id="col-left">
            <div class="col-wrap">
                <div class="form-wrap">
                    <h2><?php esc_html_e('ข้อมูลเพิ่มเติม', 'direct-to-admin-form'); ?></h2>
                    <div class="inside">
                        <p><strong><?php esc_html_e('การใช้งาน Shortcode', 'direct-to-admin-form'); ?></strong></p>
                        <p><?php esc_html_e('คุณสามารถใช้ shortcode ต่อไปนี้เพื่อแสดงแบบฟอร์มบนหน้าเว็บไซต์ของคุณ:', 'direct-to-admin-form'); ?></p>
                        <ul class="ul-disc">
                            <li>
                                <code>[direct_to_admin_form]</code> - 
                                <?php esc_html_e('แสดงแบบฟอร์มค่าเริ่มต้น', 'direct-to-admin-form'); ?>
                            </li>
                            <li>
                                <code>[direct_to_admin_form form_id="your-custom-slug"]</code> - 
                                <?php esc_html_e('แสดงแบบฟอร์มที่กำหนดเอง โดยแทนที่ "your-custom-slug" ด้วย Slug ของแบบฟอร์มที่ต้องการ', 'direct-to-admin-form'); ?>
                            </li>
                        </ul>
                        <p><?php esc_html_e('คุณสามารถวาง shortcode ในหน้าเพจ, โพสต์, หรือวิดเจ็ตข้อความได้', 'direct-to-admin-form'); ?></p>
                    </div>
                </div>
            </div>
        </div>
        <div id="col-right">
            <div class="col-wrap">
                <div class="form-wrap">
                     <h2><?php esc_html_e('เกี่ยวกับปลั๊กอิน', 'direct-to-admin-form'); ?></h2>
                     <div class="inside">
                        <p><?php printf(esc_html__('Direct to Admin Form Plugin เวอร์ชั่น %s', 'direct-to-admin-form'), DTAF_VERSION); ?></p>
                        <p><?php esc_html_e('ปลั๊กอินนี้ช่วยให้คุณสร้างและจัดการแบบฟอร์มสำหรับให้ผู้ใช้ส่งเรื่องโดยตรงถึงผู้บริหารหรือทีมงานที่เกี่ยวข้องได้อย่างง่ายดาย', 'direct-to-admin-form'); ?></p>
                        <!-- Add links to documentation or support if available -->
                     </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php // JavaScript for this page (delete confirmation, copy shortcode) should be in dtaf-admin.js and enqueued ?>
<style type="text/css">
    .dtaf-form-manager .dtaf-default-badge {
        background-color: #f0f0f1;
        color: #1d2327;
        padding: 3px 7px;
        border-radius: 3px;
        font-size: 0.8em;
        margin-left: 8px;
        vertical-align: middle;
        border: 1px solid #ccd0d4;
    }
    .dtaf-form-manager .column-shortcode code {
        background-color: #f6f7f7;
        padding: 4px 6px;
        border-radius: 3px;
        cursor: pointer;
        transition: background-color 0.2s;
    }
    .dtaf-form-manager .column-shortcode code:hover,
    .dtaf-form-manager .column-shortcode code.copied {
        background-color: #e0e0e0;
    }
    .dtaf-form-manager .dtaf-copy-shortcode {
        margin-left: 5px;
        vertical-align: middle;
    }
    .dtaf-form-manager .ul-disc {
        list-style-type: disc;
        padding-left: 20px;
    }
    /* WordPress Admin Table Styling Adjustments (if needed) */
    .dtaf-form-manager-table .column-submissions { text-align: right; }
</style>