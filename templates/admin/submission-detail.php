<?php
/**
 * Admin submission detail template
 *
 * @var object $submission Submission object
 * @var array $history Status history
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
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
?>

<div class="wrap dtaf-admin-page dtaf-details-page">
    <h1 class="wp-heading-inline">
        <?php printf(
            esc_html__('รายละเอียดเรื่องร้องเรียน #%d', 'direct-to-admin-form'),
            $submission->id
        ); ?>
    </h1>
    
    <a href="<?php echo esc_url(admin_url('admin.php?page=dtaf-submissions')); ?>" class="page-title-action">
        <span class="dashicons dashicons-arrow-left-alt" style="vertical-align: text-bottom;"></span>
        <?php esc_html_e('กลับไปรายการ', 'direct-to-admin-form'); ?>
    </a>
    
    <!-- AJAX Message Container -->
    <div id="dtaf-ajax-message" class="notice" style="display:none;"></div>
    
    <div id="poststuff">
        <div id="post-body" class="metabox-holder columns-2">
            <!-- Main Content -->
            <div id="post-body-content">
                <div class="postbox">
                    <h2 class="hndle"><span><?php esc_html_e('ข้อมูลผู้ร้องเรียน', 'direct-to-admin-form'); ?></span></h2>
                    <div class="inside">
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
                        </table>
                    </div>
                </div>
                
                <div class="postbox">
                    <h2 class="hndle"><span><?php esc_html_e('รายละเอียดเรื่องร้องเรียน', 'direct-to-admin-form'); ?></span></h2>
                    <div class="inside">
                        <table class="form-table">
                            <tr>
                                <th><?php esc_html_e('ประเภท:', 'direct-to-admin-form'); ?></th>
                                <td><?php echo esc_html($submission->type); ?></td>
                            </tr>
                            <tr>
                                <th><?php esc_html_e('เรื่อง:', 'direct-to-admin-form'); ?></th>
                                <td><?php echo esc_html($submission->subject); ?></td>
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
                                <th><?php esc_html_e('IP Address:', 'direct-to-admin-form'); ?></th>
                                <td><?php echo esc_html($submission->ip_address); ?></td>
                            </tr>
                        </table>
                        
                        <?php if (!empty($file_paths)): ?>
                            <div class="dtaf-file-attachments">
                                <h3><?php esc_html_e('ไฟล์แนบ:', 'direct-to-admin-form'); ?></h3>
                                <?php foreach ($file_paths as $index => $file_path): ?>
                                    <?php
                                    $file_name = basename($file_path);
                                    $file_ext = pathinfo($file_name, PATHINFO_EXTENSION);
                                    $icon_class = 'dashicons-media-default';
                                    
                                    // Set icon based on file type
                                    if (in_array($file_ext, ['jpg', 'jpeg', 'png', 'gif'])) {
                                        $icon_class = 'dashicons-format-image';
                                    } elseif (in_array($file_ext, ['pdf'])) {
                                        $icon_class = 'dashicons-pdf';
                                    } elseif (in_array($file_ext, ['doc', 'docx'])) {
                                        $icon_class = 'dashicons-media-document';
                                    } elseif (in_array($file_ext, ['xls', 'xlsx'])) {
                                        $icon_class = 'dashicons-media-spreadsheet';
                                    }
                                    ?>
                                    <div class="dtaf-file-item">
                                        <span class="dtaf-file-icon dashicons <?php echo esc_attr($icon_class); ?>"></span>
                                        <span class="dtaf-file-name"><?php echo esc_html($file_name); ?></span>
                                        <div class="dtaf-file-actions">
                                            <a href="<?php echo esc_url($file_path); ?>" target="_blank" class="button button-small">
                                                <span class="dashicons dashicons-visibility" style="vertical-align: text-bottom;"></span>
                                                <?php esc_html_e('ดู', 'direct-to-admin-form'); ?>
                                            </a>
                                            <a href="<?php echo esc_url($file_path); ?>" download class="button button-small">
                                                <span class="dashicons dashicons-download" style="vertical-align: text-bottom;"></span>
                                                <?php esc_html_e('ดาวน์โหลด', 'direct-to-admin-form'); ?>
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="postbox">
                    <h2 class="hndle"><span><?php esc_html_e('ประวัติการเปลี่ยนแปลงสถานะ', 'direct-to-admin-form'); ?></span></h2>
                    <div class="inside">
                        <?php if (!empty($history)): ?>
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
                        <?php else: ?>
                            <p><?php esc_html_e('ยังไม่มีประวัติการเปลี่ยนแปลงสถานะ', 'direct-to-admin-form'); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Sidebar -->
            <div id="postbox-container-1" class="postbox-container">
                <div class="postbox">
                    <h2 class="hndle"><span><?php esc_html_e('อัปเดตสถานะ', 'direct-to-admin-form'); ?></span></h2>
                    <div class="inside">
                        <form id="dtaf-update-status-form" method="post">
                            <?php wp_nonce_field('dtaf_update_status', 'dtaf_status_nonce'); ?>
                            <input type="hidden" name="submission_id" value="<?php echo esc_attr($submission->id); ?>">
                            
                            <p>
                                <label for="dtaf_status"><strong><?php esc_html_e('สถานะปัจจุบัน:', 'direct-to-admin-form'); ?></strong></label>
                                <select name="status" id="dtaf_status" class="widefat">
                                    <option value="ใหม่" <?php selected($submission->status, 'ใหม่'); ?>><?php esc_html_e('ใหม่', 'direct-to-admin-form'); ?></option>
                                    <option value="กำลังดำเนินการ" <?php selected($submission->status, 'กำลังดำเนินการ'); ?>><?php esc_html_e('กำลังดำเนินการ', 'direct-to-admin-form'); ?></option>
                                    <option value="รอข้อมูลเพิ่มเติม" <?php selected($submission->status, 'รอข้อมูลเพิ่มเติม'); ?>><?php esc_html_e('รอข้อมูลเพิ่มเติม', 'direct-to-admin-form'); ?></option>
                                    <option value="แก้ไขแล้ว" <?php selected($submission->status, 'แก้ไขแล้ว'); ?>><?php esc_html_e('แก้ไขแล้ว', 'direct-to-admin-form'); ?></option>
                                    <option value="ปิดเรื่อง" <?php selected($submission->status, 'ปิดเรื่อง'); ?>><?php esc_html_e('ปิดเรื่อง', 'direct-to-admin-form'); ?></option>
                                </select>
                            </p>
                            
                            <p>
                                <label for="dtaf_admin_notes"><strong><?php esc_html_e('หมายเหตุ:', 'direct-to-admin-form'); ?></strong></label>
                                <textarea name="admin_notes" id="dtaf_admin_notes" class="widefat" rows="5"><?php echo esc_textarea($submission->admin_notes); ?></textarea>
                            </p>
                            
                            <p>
                                <label for="dtaf_notify_submitter">
                                    <input type="checkbox" name="notify_submitter" id="dtaf_notify_submitter" value="1">
                                    <?php esc_html_e('แจ้งเตือนผู้ร้องเรียนทางอีเมล', 'direct-to-admin-form'); ?>
                                </label>
                            </p>
                            
                            <p>
                                <button type="submit" class="button button-primary button-large">
                                    <span class="dashicons dashicons-update" style="vertical-align: middle;"></span>
                                    <?php esc_html_e('อัปเดตสถานะ', 'direct-to-admin-form'); ?>
                                </button>
                            </p>
                        </form>
                    </div>
                </div>
                
                <div class="postbox">
                    <h2 class="hndle"><span><?php esc_html_e('การดำเนินการ', 'direct-to-admin-form'); ?></span></h2>
                    <div class="inside">
                        <p>
                            <a href="mailto:<?php echo esc_attr($submission->email); ?>" class="button button-secondary button-large" style="width: 100%; text-align: center; margin-bottom: 10px;">
                                <span class="dashicons dashicons-email" style="vertical-align: middle;"></span>
                                <?php esc_html_e('ส่งอีเมลถึงผู้ร้อง', 'direct-to-admin-form'); ?>
                            </a>
                        </p>
                        
                        <p>
                            <button type="button" class="button button-secondary button-large dtaf-print-submission" style="width: 100%; text-align: center; margin-bottom: 10px;">
                                <span class="dashicons dashicons-printer" style="vertical-align: middle;"></span>
                                <?php esc_html_e('พิมพ์รายละเอียด', 'direct-to-admin-form'); ?>
                            </button>
                        </p>
                        
                        <p>
                            <a href="#" class="button button-secondary button-large dtaf-delete-submission" data-id="<?php echo esc_attr($submission->id); ?>" data-nonce="<?php echo esc_attr(wp_create_nonce('dtaf_delete_submission_' . $submission->id)); ?>" style="width: 100%; text-align: center; color: #a00;">
                                <span class="dashicons dashicons-trash" style="vertical-align: middle;"></span>
                                <?php esc_html_e('ลบเรื่องร้องเรียนนี้', 'direct-to-admin-form'); ?>
                            </a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    jQuery(document).ready(function($) {
        // Update status form submission
        $('#dtaf-update-status-form').on('submit', function(e) {
            e.preventDefault();
            
            var form = $(this);
            var submitButton = form.find('button[type="submit"]');
            var originalButtonText = submitButton.html();
            
            // Show loading state
            submitButton.prop('disabled', true).html('<span class="dashicons dashicons-update" style="vertical-align: middle;"></span> <?php echo esc_js(__('กำลังบันทึก...', 'direct-to-admin-form')); ?>');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'dtaf_update_submission_status',
                    submission_id: form.find('input[name="submission_id"]').val(),
                    status: form.find('#dtaf_status').val(),
                    admin_notes: form.find('#dtaf_admin_notes').val(),
                    notify_submitter: form.find('#dtaf_notify_submitter').is(':checked') ? 1 : 0,
                    nonce: form.find('#dtaf_status_nonce').val()
                },
                success: function(response) {
                    if (response.success) {
                        // Show success message
                        $('#dtaf-ajax-message')
                            .removeClass('notice-error')
                            .addClass('notice-success')
                            .html('<p>' + response.data.message + '</p>')
                            .show()
                            .delay(3000)
                            .fadeOut();
                            
                        // Update status history if provided
                        if (response.data.history_html) {
                            $('.postbox:contains("ประวัติการเปลี่ยนแปลงสถานะ") .inside').html(response.data.history_html);
                        }
                    } else {
                        // Show error message
                        $('#dtaf-ajax-message')
                            .removeClass('notice-success')
                            .addClass('notice-error')
                            .html('<p>' + response.data.message + '</p>')
                            .show();
                    }
                    
                    // Reset button state
                    submitButton.prop('disabled', false).html(originalButtonText);
                },
                error: function() {
                    // Show error message
                    $('#dtaf-ajax-message')
                        .removeClass('notice-success')
                        .addClass('notice-error')
                        .html('<p><?php echo esc_js(__('เกิดข้อผิดพลาดในการส่งข้อมูล', 'direct-to-admin-form')); ?></p>')
                        .show();
                        
                    // Reset button state
                    submitButton.prop('disabled', false).html(originalButtonText);
                }
            });
        });
        
        // Delete submission
        $('.dtaf-delete-submission').on('click', function(e) {
            e.preventDefault();
            
            if (confirm('<?php echo esc_js(__('คุณแน่ใจหรือไม่ว่าต้องการลบรายการนี้? การดำเนินการนี้ไม่สามารถย้อนกลับได้', 'direct-to-admin-form')); ?>')) {
                var submissionId = $(this).data('id');
                var nonce = $(this).data('nonce');
                
                // Show loading state
                $(this).prop('disabled', true).html('<span class="dashicons dashicons-trash" style="vertical-align: middle;"></span> <?php echo esc_js(__('กำลังลบ...', 'direct-to-admin-form')); ?>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'dtaf_delete_submission',
                        submission_id: submissionId,
                        nonce: nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            // Redirect to submissions list
                            window.location.href = '<?php echo esc_js(admin_url('admin.php?page=dtaf-submissions&deleted=1')); ?>';
                        } else {
                            // Show error message
                            $('#dtaf-ajax-message')
                                .removeClass('notice-success')
                                .addClass('notice-error')
                                .html('<p>' + response.data.message + '</p>')
                                .show();
                                
                            // Reset button state
                            $('.dtaf-delete-submission').prop('disabled', false).html('<span class="dashicons dashicons-trash" style="vertical-align: middle;"></span> <?php echo esc_js(__('ลบเรื่องร้องเรียนนี้', 'direct-to-admin-form')); ?>');
                        }
                    },
                    error: function() {
                        // Show error message
                        $('#dtaf-ajax-message')
                            .removeClass('notice-success')
                            .addClass('notice-error')
                            .html('<p><?php echo esc_js(__('เกิดข้อผิดพลาดในการลบข้อมูล', 'direct-to-admin-form')); ?></p>')
                            .show();
                            
                        // Reset button state
                        $('.dtaf-delete-submission').prop('disabled', false).html('<span class="dashicons dashicons-trash" style="vertical-align: middle;"></span> <?php echo esc_js(__('ลบเรื่องร้องเรียนนี้', 'direct-to-admin-form')); ?>');
                    }
                });
            }
        });
        
        // Print submission
        $('.dtaf-print-submission').on('click', function(e) {
            e.preventDefault();
            window.print();
        });
    });
</script>

<style type="text/css" media="print">
    #adminmenumain, #wpadminbar, .notice, #wpfooter, .postbox:last-child, .button, .dtaf-delete-submission {
        display: none !important;
    }
    
    #wpcontent, #wpbody-content {
        margin-left: 0 !important;
        padding-left: 0 !important;
    }
    
    .wrap {
        margin: 0 !important;
    }
    
    .postbox {
        box-shadow: none !important;
        border: 1px solid #ddd !important;
        margin-bottom: 20px !important;
    }
    
    .postbox .hndle {
        border-bottom: 1px solid #ddd !important;
    }
    
    .dtaf-details-page #post-body.columns-2 #postbox-container-1 {
        display: none !important;
    }
    
    .dtaf-details-page #post-body.columns-2 #post-body-content {
        margin-right: 0 !important;
    }
</style>
