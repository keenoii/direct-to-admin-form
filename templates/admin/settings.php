<?php
/**
 * Admin settings template
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get current settings
$options = get_option('dtaf_settings', []);

// Default values for settings not yet saved
$defaults = [
    'recipient_email' => get_option('admin_email'),
    'email_subject_prefix' => __('[สายตรงผู้บริหาร]', 'direct-to-admin-form'),
    'success_message' => __('ส่งข้อมูลร้องเรียนเรียบร้อยแล้ว ขอบคุณสำหรับข้อมูลของท่าน', 'direct-to-admin-form'),
    'error_message' => __('เกิดข้อผิดพลาดในการส่งข้อมูล โปรดลองอีกครั้ง', 'direct-to-admin-form'),
    'allowed_file_types' => 'jpg,jpeg,png,pdf,doc,docx',
    'max_file_size' => 5, // MB
    'enable_honeypot' => true,
    'complaint_types' => "ร้องเรียนทุจริต\nพฤติกรรมไม่เหมาะสม\nข้อเสนอแนะ/ร้องเรียนอื่นๆ",
    // Line Notify defaults removed
    // 'line_notify_token' => '',
    // 'enable_line_notify' => false,
    // 'line_notify_message' => "มีเรื่องร้องเรียนใหม่\nเรื่อง: {subject}\nจาก: {name}\nประเภท: {type}\nวันที่: {date}",
    'delete_data' => false,
];

// Merge current options with defaults to ensure all keys exist
$options = wp_parse_args($options, $defaults);
?>

<div class="wrap dtaf-admin-page">
    <h1 class="wp-heading-inline"><?php esc_html_e('ตั้งค่าปลั๊กอิน', 'direct-to-admin-form'); ?></h1>
    <hr class="wp-header-end">

    <?php
    // Display saved messages
    settings_errors('dtaf_settings_group'); // This will display errors registered by add_settings_error or success from settings_fields
    
    // Check for settings-updated for manual success message if not using settings_errors for it
    if (isset($_GET['settings-updated']) && $_GET['settings-updated']) {
        // Check if there are no errors already displayed by settings_errors for this group
        $error_messages = get_settings_errors('dtaf_settings_group');
        $updated_message_shown = false;
        if(!empty($error_messages)){
            foreach($error_messages as $message_item){
                if($message_item['type'] === 'updated' || $message_item['type'] === 'success' ){
                     $updated_message_shown = true;
                     break;
                }
            }
        }
        if(!$updated_message_shown){
            echo '<div id="setting-error-settings_updated" class="notice notice-success is-dismissible settings-error"><p><strong>' . esc_html__('บันทึกการตั้งค่าเรียบร้อยแล้ว', 'direct-to-admin-form') . '</strong></p></div>';
        }
    }
    ?>
    
    <form method="post" action="options.php">
        <?php 
        settings_fields('dtaf_settings_group'); // Output nonce, action, and option_page fields for a settings page.
        // do_settings_sections('dtaf_settings_page'); // If you were using add_settings_section and add_settings_field
        ?>
        
        <div id="poststuff">
            <div class="metabox-holder">
                <!-- General Settings -->
                <div id="dtaf-general-settings" class="postbox">
                    <button type="button" class="handlediv button-link" aria-expanded="true">
                        <span class="screen-reader-text"><?php printf( esc_html__( 'Toggle panel: %s' ), esc_html__('การตั้งค่าทั่วไป', 'direct-to-admin-form') ); ?></span>
                        <span class="toggle-indicator" aria-hidden="true"></span>
                    </button>
                    <h2 class="hndle"><span><?php esc_html_e('การตั้งค่าทั่วไป', 'direct-to-admin-form'); ?></span></h2>
                    <div class="inside">
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="dtaf_recipient_email"><?php esc_html_e('อีเมลผู้รับการแจ้งเตือน', 'direct-to-admin-form'); ?></label>
                                </th>
                                <td>
                                    <input type="text" name="dtaf_settings[recipient_email]" id="dtaf_recipient_email" value="<?php echo esc_attr($options['recipient_email']); ?>" class="regular-text" placeholder="email1@example.com, email2@example.com">
                                    <p class="description"><?php esc_html_e('อีเมลที่จะได้รับการแจ้งเตือนเมื่อมีการส่งแบบฟอร์ม (สามารถระบุได้หลายอีเมลโดยคั่นด้วยเครื่องหมายจุลภาค)', 'direct-to-admin-form'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="dtaf_email_subject_prefix"><?php esc_html_e('คำนำหน้าหัวข้ออีเมล', 'direct-to-admin-form'); ?></label>
                                </th>
                                <td>
                                    <input type="text" name="dtaf_settings[email_subject_prefix]" id="dtaf_email_subject_prefix" value="<?php echo esc_attr($options['email_subject_prefix']); ?>" class="regular-text">
                                    <p class="description"><?php esc_html_e('คำนำหน้าที่จะแสดงในหัวข้ออีเมลแจ้งเตือน เช่น [เรื่องร้องเรียนใหม่]', 'direct-to-admin-form'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="dtaf_success_message"><?php esc_html_e('ข้อความเมื่อส่งสำเร็จ', 'direct-to-admin-form'); ?></label>
                                </th>
                                <td>
                                    <textarea name="dtaf_settings[success_message]" id="dtaf_success_message" class="large-text" rows="3"><?php echo esc_textarea($options['success_message']); ?></textarea>
                                    <p class="description"><?php esc_html_e('ข้อความที่จะแสดงเมื่อผู้ใช้ส่งแบบฟอร์มสำเร็จ (รองรับ HTML พื้นฐาน)', 'direct-to-admin-form'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="dtaf_error_message"><?php esc_html_e('ข้อความเมื่อเกิดข้อผิดพลาด', 'direct-to-admin-form'); ?></label>
                                </th>
                                <td>
                                    <textarea name="dtaf_settings[error_message]" id="dtaf_error_message" class="large-text" rows="3"><?php echo esc_textarea($options['error_message']); ?></textarea>
                                    <p class="description"><?php esc_html_e('ข้อความที่จะแสดงเมื่อเกิดข้อผิดพลาดในการส่งแบบฟอร์ม (รองรับ HTML พื้นฐาน)', 'direct-to-admin-form'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="dtaf_complaint_types"><?php esc_html_e('ประเภทเรื่องร้องเรียนค่าเริ่มต้น', 'direct-to-admin-form'); ?></label>
                                </th>
                                <td>
                                    <textarea name="dtaf_settings[complaint_types]" id="dtaf_complaint_types" class="large-text" rows="5" placeholder="<?php esc_attr_e("ประเภทที่ 1\nประเภทที่ 2\nประเภทที่ 3", 'direct-to-admin-form'); ?>"><?php echo esc_textarea($options['complaint_types']); ?></textarea>
                                    <p class="description"><?php esc_html_e('ระบุประเภทเรื่องร้องเรียนค่าเริ่มต้นที่จะใช้เมื่อสร้าง "แบบฟอร์มค่าเริ่มต้น" หรือหากไม่ได้กำหนดในแบบฟอร์มเฉพาะ (แต่ละประเภทให้ขึ้นบรรทัดใหม่)', 'direct-to-admin-form'); ?></p>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <!-- File Upload Settings -->
                <div id="dtaf-upload-settings" class="postbox">
                     <button type="button" class="handlediv button-link" aria-expanded="true">
                        <span class="screen-reader-text"><?php printf( esc_html__( 'Toggle panel: %s' ), esc_html__('การตั้งค่าการอัปโหลดไฟล์', 'direct-to-admin-form') ); ?></span>
                        <span class="toggle-indicator" aria-hidden="true"></span>
                    </button>
                    <h2 class="hndle"><span><?php esc_html_e('การตั้งค่าการอัปโหลดไฟล์', 'direct-to-admin-form'); ?></span></h2>
                    <div class="inside">
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="dtaf_allowed_file_types"><?php esc_html_e('ประเภทไฟล์ที่อนุญาต', 'direct-to-admin-form'); ?></label>
                                </th>
                                <td>
                                    <input type="text" name="dtaf_settings[allowed_file_types]" id="dtaf_allowed_file_types" value="<?php echo esc_attr($options['allowed_file_types']); ?>" class="regular-text" placeholder="jpg,png,pdf">
                                    <p class="description"><?php esc_html_e('ระบุนามสกุลไฟล์ที่อนุญาตให้อัปโหลด คั่นด้วยเครื่องหมายจุลภาค (เช่น jpg,jpeg,png,pdf,doc,docx)', 'direct-to-admin-form'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="dtaf_max_file_size"><?php esc_html_e('ขนาดไฟล์สูงสุด (MB)', 'direct-to-admin-form'); ?></label>
                                </th>
                                <td>
                                    <?php 
                                    // Helper function to convert php.ini size string to MB
                                    if (!function_exists('dtaf_convert_to_megabytes')) {
                                        function dtaf_convert_to_megabytes($size_str) {
                                            $size_str = trim($size_str);
                                            $unit = strtolower(substr($size_str, -1));
                                            $value = (int) substr($size_str, 0, -1);
                                            switch ($unit) {
                                                case 'g': $value *= 1024; break;
                                                case 'm': break;
                                                case 'k': $value /= 1024; break;
                                            }
                                            return (int) round($value);
                                        }
                                    }
                                    $upload_max_mb = dtaf_convert_to_megabytes(ini_get('upload_max_filesize'));
                                    $post_max_mb = dtaf_convert_to_megabytes(ini_get('post_max_size'));
                                    $server_limit_mb = min($upload_max_mb, $post_max_mb);
                                    ?>
                                    <input type="number" name="dtaf_settings[max_file_size]" id="dtaf_max_file_size" value="<?php echo esc_attr($options['max_file_size']); ?>" class="small-text" min="1" max="<?php echo esc_attr($server_limit_mb); ?>">
                                    <p class="description">
                                        <?php 
                                        printf(
                                            esc_html__('ขนาดไฟล์สูงสุดที่อนุญาตให้อัปโหลด (เซิร์ฟเวอร์ของคุณอนุญาตสูงสุด %d MB)', 'direct-to-admin-form'),
                                            $server_limit_mb
                                        ); 
                                        ?>
                                    </p>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <!-- Security Settings -->
                <div id="dtaf-security-settings" class="postbox">
                     <button type="button" class="handlediv button-link" aria-expanded="true">
                        <span class="screen-reader-text"><?php printf( esc_html__( 'Toggle panel: %s' ), esc_html__('การตั้งค่าความปลอดภัย', 'direct-to-admin-form') ); ?></span>
                        <span class="toggle-indicator" aria-hidden="true"></span>
                    </button>
                    <h2 class="hndle"><span><?php esc_html_e('การตั้งค่าความปลอดภัย', 'direct-to-admin-form'); ?></span></h2>
                    <div class="inside">
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <?php esc_html_e('การป้องกันสแปม', 'direct-to-admin-form'); ?>
                                </th>
                                <td>
                                    <fieldset>
                                        <legend class="screen-reader-text"><?php esc_html_e('การป้องกันสแปม', 'direct-to-admin-form'); ?></legend>
                                        <label for="dtaf_enable_honeypot">
                                            <input type="checkbox" name="dtaf_settings[enable_honeypot]" id="dtaf_enable_honeypot" value="1" <?php checked($options['enable_honeypot'], 1); ?>>
                                            <?php esc_html_e('เปิดใช้งานการป้องกันสแปมด้วย Honeypot', 'direct-to-admin-form'); ?>
                                        </label>
                                        <p class="description"><?php esc_html_e('Honeypot เป็นเทคนิคการป้องกันสแปมโดยการเพิ่มฟิลด์ที่ซ่อนไว้ หากฟิลด์นี้ถูกกรอกข้อมูล (โดยบอท) การส่งฟอร์มจะถูกปฏิเสธ', 'direct-to-admin-form'); ?></p>
                                    </fieldset>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <!-- Line Notify Settings - REMOVED -->
                <!--
                <div class="postbox">
                    <h2 class="hndle"><span><?php esc_html_e('การตั้งค่า Line Notify', 'direct-to-admin-form'); ?></span></h2>
                    <div class="inside">
                        ... (Line Notify fields were here) ...
                    </div>
                </div>
                -->
                
                <!-- Advanced Settings -->
                <div id="dtaf-advanced-settings" class="postbox">
                     <button type="button" class="handlediv button-link" aria-expanded="true">
                        <span class="screen-reader-text"><?php printf( esc_html__( 'Toggle panel: %s' ), esc_html__('การตั้งค่าขั้นสูง', 'direct-to-admin-form') ); ?></span>
                        <span class="toggle-indicator" aria-hidden="true"></span>
                    </button>
                    <h2 class="hndle"><span><?php esc_html_e('การตั้งค่าขั้นสูง', 'direct-to-admin-form'); ?></span></h2>
                    <div class="inside">
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <?php esc_html_e('ล้างข้อมูลเมื่อถอนการติดตั้ง', 'direct-to-admin-form'); ?>
                                </th>
                                <td>
                                    <fieldset>
                                        <legend class="screen-reader-text"><?php esc_html_e('ล้างข้อมูลเมื่อถอนการติดตั้ง', 'direct-to-admin-form'); ?></legend>
                                        <label for="dtaf_delete_data">
                                            <input type="checkbox" name="dtaf_settings[delete_data]" id="dtaf_delete_data" value="1" <?php checked($options['delete_data'], 1); ?>>
                                            <?php esc_html_e('ลบข้อมูลทั้งหมด (เรื่องร้องเรียน, แบบฟอร์ม, การตั้งค่า) ของปลั๊กอินนี้เมื่อถอนการติดตั้ง', 'direct-to-admin-form'); ?>
                                        </label>
                                        <p class="description"><strong style="color:red;"><?php esc_html_e('คำเตือน:', 'direct-to-admin-form'); ?></strong> <?php esc_html_e('หากเลือกตัวเลือกนี้ ข้อมูลทั้งหมดจะถูกลบอย่างถาวรและไม่สามารถกู้คืนได้เมื่อคุณถอนการติดตั้งปลั๊กอิน', 'direct-to-admin-form'); ?></p>
                                    </fieldset>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div> <!-- .metabox-holder -->
        </div> <!-- #poststuff -->
        
        <?php submit_button(__('บันทึกการตั้งค่า', 'direct-to-admin-form')); ?>
    </form>
</div>
<script type="text/javascript">
    jQuery(document).ready(function($) {
        // Make postboxes toggleable (WordPress standard behavior)
        if (typeof postboxes !== 'undefined' && typeof postboxes.add_postbox_toggles === 'function') {
            postboxes.add_postbox_toggles(pagenow); // pagenow should be defined by WordPress for admin pages
        } else if (typeof add_postbox_toggles === 'function') { // Fallback for older WP or different context
             add_postbox_toggles(pagenow);
        }
    });
</script>