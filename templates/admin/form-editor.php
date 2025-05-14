<?php
/**
 * Admin Form Editor Template
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Determine if we are editing an existing form or creating a new one
$form_id = isset($_GET['form_id']) ? intval($_GET['form_id']) : 0;
$form_data = null;
$form_settings = [];
$is_new_form = ($form_id === 0);
$page_title = $is_new_form ? __('สร้างแบบฟอร์มใหม่', 'direct-to-admin-form') : __('แก้ไขแบบฟอร์ม', 'direct-to-admin-form');

// Default settings for a new form (can be merged with global plugin settings)
$global_plugin_settings = get_option('dtaf_settings', []);
$default_complaint_types_string = isset($global_plugin_settings['complaint_types']) ? $global_plugin_settings['complaint_types'] : "ร้องเรียนทุจริต\nพฤติกรรมไม่เหมาะสม\nข้อเสนอแนะ/ร้องเรียนอื่นๆ";

$default_form_settings = [
    'recipient_email' => isset($global_plugin_settings['recipient_email']) ? $global_plugin_settings['recipient_email'] : get_option('admin_email'),
    'email_subject_prefix' => isset($global_plugin_settings['email_subject_prefix']) ? $global_plugin_settings['email_subject_prefix'] : __('[สายตรงผู้บริหาร]', 'direct-to-admin-form'),
    'success_message' => isset($global_plugin_settings['success_message']) ? $global_plugin_settings['success_message'] : __('ส่งข้อมูลเรียบร้อยแล้ว ขอบคุณสำหรับข้อมูลของท่าน', 'direct-to-admin-form'),
    'complaint_types' => array_map('trim', explode("\n", $default_complaint_types_string)),
    'form_color' => '#0073aa',
    'button_color' => '#0073aa',
    'show_address_field' => true,
    'required_fields' => ['name', 'idcard', 'phone', 'email', 'type', 'subject', 'detail'], // Default required fields
    // Add more settings as needed: custom_css, etc.
];

if (!$is_new_form) {
    $form_data = DTAF_Database::get_form($form_id);
    if ($form_data && isset($form_data->form_settings)) {
        $decoded_settings = json_decode($form_data->form_settings, true);
        if (is_array($decoded_settings)) {
            $form_settings = wp_parse_args($decoded_settings, $default_form_settings);
        } else {
            $form_settings = $default_form_settings; // Fallback if JSON is invalid
        }
    } else {
        // Form not found, treat as error or redirect
        wp_die(__('ไม่พบแบบฟอร์มที่ต้องการแก้ไข', 'direct-to-admin-form'));
    }
} else {
    // For new form, use default settings
    $form_settings = $default_form_settings;
    // Create a dummy form_data object for consistency in the form
    $form_data = new stdClass();
    $form_data->form_name = '';
    $form_data->form_slug = '';
}

// Handle form submission for saving/updating
if (isset($_POST['dtaf_save_form_nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['dtaf_save_form_nonce'])), 'dtaf_save_form_action')) {
    if (!current_user_can('manage_options')) {
        wp_die(__('คุณไม่มีสิทธิ์ดำเนินการนี้', 'direct-to-admin-form'));
    }

    $form_name = isset($_POST['form_name']) ? sanitize_text_field(wp_unslash($_POST['form_name'])) : '';
    $form_slug = isset($_POST['form_slug']) ? sanitize_title(wp_unslash($_POST['form_slug'])) : ''; // sanitize_title ensures a valid slug

    // Prevent changing slug of default_form if it's special
    if (!$is_new_form && $form_data->form_slug === 'default_form' && $form_slug !== 'default_form') {
        // Add an admin notice or error message here
        $form_slug = 'default_form'; // Revert
        add_settings_error('dtaf_form_messages', 'slug_change_denied', __('ไม่สามารถเปลี่ยน Slug ของแบบฟอร์มค่าเริ่มต้นได้', 'direct-to-admin-form'), 'error');
    }


    $new_settings = [
        'recipient_email' => isset($_POST['recipient_email']) ? sanitize_email(wp_unslash($_POST['recipient_email'])) : '',
        'email_subject_prefix' => isset($_POST['email_subject_prefix']) ? sanitize_text_field(wp_unslash($_POST['email_subject_prefix'])) : '',
        'success_message' => isset($_POST['success_message']) ? wp_kses_post(wp_unslash($_POST['success_message'])) : '',
        'complaint_types' => isset($_POST['complaint_types']) ? array_map('sanitize_text_field', array_map('trim', explode("\n", wp_unslash($_POST['complaint_types'])))) : [],
        'form_color' => isset($_POST['form_color']) ? sanitize_hex_color(wp_unslash($_POST['form_color'])) : '#0073aa',
        'button_color' => isset($_POST['button_color']) ? sanitize_hex_color(wp_unslash($_POST['button_color'])) : '#0073aa',
        'show_address_field' => isset($_POST['show_address_field']) ? true : false,
        'required_fields' => isset($_POST['required_fields']) && is_array($_POST['required_fields']) ? array_map('sanitize_key', $_POST['required_fields']) : [],
    ];

    $data_to_save = [
        'form_name' => $form_name,
        'form_slug' => $form_slug,
        'form_settings' => json_encode($new_settings), // Store settings as JSON
    ];

    $success = false;
    $redirect_url = admin_url('admin.php?page=dtaf-form-manager');

    if ($is_new_form) {
        if (empty($form_name) || empty($form_slug)) {
             add_settings_error('dtaf_form_messages', 'missing_fields', __('ชื่อแบบฟอร์มและ Slug จำเป็นต้องกรอก', 'direct-to-admin-form'), 'error');
        } else {
            // Check if slug already exists
            if (DTAF_Database::get_form($form_slug)) {
                add_settings_error('dtaf_form_messages', 'slug_exists', __('Slug นี้มีอยู่แล้ว กรุณาใช้ Slug อื่น', 'direct-to-admin-form'), 'error');
            } else {
                $new_form_id = DTAF_Database::insert_form($data_to_save);
                if ($new_form_id) {
                    $success = true;
                    $redirect_url = admin_url('admin.php?page=dtaf-form-manager&view=edit-form&form_id=' . $new_form_id . '&message=created');
                }
            }
        }
    } else {
        // Updating existing form
        if (empty($form_name)) { // Slug cannot be empty if form exists
             add_settings_error('dtaf_form_messages', 'missing_name', __('ชื่อแบบฟอร์มจำเป็นต้องกรอก', 'direct-to-admin-form'), 'error');
        } else {
            // If slug changed, check if new slug exists (excluding current form)
            if ($form_slug !== $form_data->form_slug && DTAF_Database::get_form($form_slug)) {
                 add_settings_error('dtaf_form_messages', 'slug_exists', __('Slug นี้มีอยู่แล้ว กรุณาใช้ Slug อื่น', 'direct-to-admin-form'), 'error');
            } else {
                if (DTAF_Database::update_form($form_id, $data_to_save)) {
                    $success = true;
                    $redirect_url = admin_url('admin.php?page=dtaf-form-manager&view=edit-form&form_id=' . $form_id . '&message=updated');
                }
            }
        }
    }

    if ($success) {
        wp_redirect($redirect_url);
        exit;
    } else {
        // If save failed, repopulate form_data and form_settings with submitted values for redisplay
        $form_data->form_name = $form_name;
        $form_data->form_slug = $form_slug; // Might be reverted if default_form
        $form_settings = $new_settings;
        if (!is_array($form_settings['complaint_types'])) { // Ensure it's an array for the textarea
            $form_settings['complaint_types'] = [];
        }
    }
}

// Messages from redirect
if (isset($_GET['message'])) {
    if ($_GET['message'] === 'created') {
        add_settings_error('dtaf_form_messages', 'form_created', __('สร้างแบบฟอร์มเรียบร้อยแล้ว', 'direct-to-admin-form'), 'updated');
    } elseif ($_GET['message'] === 'updated') {
        add_settings_error('dtaf_form_messages', 'form_updated', __('บันทึกแบบฟอร์มเรียบร้อยแล้ว', 'direct-to-admin-form'), 'updated');
    }
}

$all_possible_fields = [
    'name' => __('ชื่อ-นามสกุล', 'direct-to-admin-form'),
    'idcard' => __('เลขบัตรประชาชน', 'direct-to-admin-form'),
    'phone' => __('เบอร์โทรศัพท์', 'direct-to-admin-form'),
    'email' => __('อีเมล', 'direct-to-admin-form'),
    'address' => __('ที่อยู่', 'direct-to-admin-form'), // This one can be hidden/shown
    'type' => __('ประเภทเรื่องร้องเรียน', 'direct-to-admin-form'),
    'subject' => __('หัวข้อเรื่อง', 'direct-to-admin-form'),
    'detail' => __('รายละเอียด', 'direct-to-admin-form'),
    'file_upload' => __('ไฟล์แนบ', 'direct-to-admin-form'), // Assuming you have a file upload field
];

// Ensure complaint_types is a string for the textarea
$complaint_types_textarea = implode("\n", isset($form_settings['complaint_types']) && is_array($form_settings['complaint_types']) ? $form_settings['complaint_types'] : []);

?>
<div class="wrap dtaf-admin-page dtaf-form-editor">
    <h1><?php echo esc_html($page_title); ?>
        <?php if (!$is_new_form && isset($form_data->form_slug)): ?>
            <span class="dtaf-shortcode-display">
                <?php esc_html_e('Shortcode:', 'direct-to-admin-form'); ?>
                <code>[direct_to_admin_form form_id="<?php echo esc_attr($form_data->form_slug); ?>"]</code>
                <button type="button" class="button button-small dtaf-copy-shortcode" data-shortcode='[direct_to_admin_form form_id="<?php echo esc_attr($form_data->form_slug); ?>"]'><span class="dashicons dashicons-admin-page"></span> <?php esc_html_e('คัดลอก', 'direct-to-admin-form'); ?></button>
            </span>
        <?php endif; ?>
    </h1>

    <?php settings_errors('dtaf_form_messages'); ?>

    <form method="post" action="">
        <input type="hidden" name="form_id" value="<?php echo esc_attr($form_id); ?>">
        <?php wp_nonce_field('dtaf_save_form_action', 'dtaf_save_form_nonce'); ?>

        <div id="poststuff">
            <div id="post-body" class="metabox-holder columns-2">
                <!-- Main content -->
                <div id="post-body-content">
                    <div class="postbox">
                        <h2 class="hndle"><span><?php esc_html_e('ข้อมูลพื้นฐานของแบบฟอร์ม', 'direct-to-admin-form'); ?></span></h2>
                        <div class="inside">
                            <table class="form-table">
                                <tr>
                                    <th scope="row"><label for="form_name"><?php esc_html_e('ชื่อแบบฟอร์ม', 'direct-to-admin-form'); ?></label></th>
                                    <td><input name="form_name" type="text" id="form_name" value="<?php echo esc_attr($form_data->form_name); ?>" class="regular-text" required />
                                    <p class="description"><?php esc_html_e('ชื่อที่แสดงในหน้าจัดการแบบฟอร์ม', 'direct-to-admin-form'); ?></p></td>
                                </tr>
                                <tr>
                                    <th scope="row"><label for="form_slug"><?php esc_html_e('Slug ของแบบฟอร์ม', 'direct-to-admin-form'); ?></label></th>
                                    <td>
                                        <?php if (!$is_new_form && $form_data->form_slug === 'default_form'): ?>
                                            <input name="form_slug" type="text" id="form_slug" value="default_form" class="regular-text" readonly />
                                            <p class="description"><?php esc_html_e('Slug ของแบบฟอร์มค่าเริ่มต้น ไม่สามารถแก้ไขได้', 'direct-to-admin-form'); ?></p>
                                        <?php else: ?>
                                            <input name="form_slug" type="text" id="form_slug" value="<?php echo esc_attr($form_data->form_slug); ?>" class="regular-text" <?php echo $is_new_form ? 'required' : ''; ?> />
                                            <p class="description"><?php esc_html_e('ใช้ใน shortcode (เช่น my-custom-form) ควรเป็นภาษาอังกฤษ ตัวพิมพ์เล็ก ไม่มีเว้นวรรค และเครื่องหมายพิเศษ', 'direct-to-admin-form'); ?></p>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <div class="postbox">
                        <h2 class="hndle"><span><?php esc_html_e('การตั้งค่าอีเมลและการแจ้งเตือน', 'direct-to-admin-form'); ?></span></h2>
                        <div class="inside">
                            <table class="form-table">
                                <tr>
                                    <th scope="row"><label for="recipient_email"><?php esc_html_e('อีเมลผู้รับการแจ้งเตือน', 'direct-to-admin-form'); ?></label></th>
                                    <td><input name="recipient_email" type="email" id="recipient_email" value="<?php echo esc_attr($form_settings['recipient_email']); ?>" class="regular-text" />
                                    <p class="description"><?php esc_html_e('อีเมลแอดมินที่จะรับข้อมูลเมื่อมีการส่งฟอร์มนี้ (คั่นด้วยจุลภาคหากมีหลายอีเมล)', 'direct-to-admin-form'); ?></p></td>
                                </tr>
                                <tr>
                                    <th scope="row"><label for="email_subject_prefix"><?php esc_html_e('คำนำหน้าหัวข้ออีเมล', 'direct-to-admin-form'); ?></label></th>
                                    <td><input name="email_subject_prefix" type="text" id="email_subject_prefix" value="<?php echo esc_attr($form_settings['email_subject_prefix']); ?>" class="regular-text" />
                                    <p class="description"><?php esc_html_e('เช่น [เรื่องร้องเรียนใหม่]', 'direct-to-admin-form'); ?></p></td>
                                </tr>
                                 <tr>
                                    <th scope="row"><label for="success_message"><?php esc_html_e('ข้อความเมื่อส่งสำเร็จ', 'direct-to-admin-form'); ?></label></th>
                                    <td><textarea name="success_message" id="success_message" rows="3" class="large-text"><?php echo esc_textarea($form_settings['success_message']); ?></textarea>
                                    <p class="description"><?php esc_html_e('ข้อความที่ผู้ใช้จะเห็นหลังจากส่งแบบฟอร์มสำเร็จ', 'direct-to-admin-form'); ?></p></td>
                                </tr>
                            </table>
                        </div>
                    </div>

                     <div class="postbox">
                        <h2 class="hndle"><span><?php esc_html_e('การตั้งค่าฟิลด์ในแบบฟอร์ม', 'direct-to-admin-form'); ?></span></h2>
                        <div class="inside">
                            <table class="form-table">
                                <tr>
                                    <th scope="row"><label for="complaint_types"><?php esc_html_e('ประเภทเรื่องร้องเรียน', 'direct-to-admin-form'); ?></label></th>
                                    <td><textarea name="complaint_types" id="complaint_types" rows="5" class="large-text"><?php echo esc_textarea($complaint_types_textarea); ?></textarea>
                                    <p class="description"><?php esc_html_e('ใส่ประเภทละหนึ่งบรรทัด ประเภทเหล่านี้จะแสดงเป็นตัวเลือกในแบบฟอร์ม', 'direct-to-admin-form'); ?></p></td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php esc_html_e('ฟิลด์ที่อยู่', 'direct-to-admin-form'); ?></th>
                                    <td>
                                        <label><input name="show_address_field" type="checkbox" value="1" <?php checked($form_settings['show_address_field'], true); ?> />
                                        <?php esc_html_e('แสดงฟิลด์ที่อยู่ในแบบฟอร์ม', 'direct-to-admin-form'); ?></label>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php esc_html_e('ฟิลด์ที่บังคับกรอก (Required Fields)', 'direct-to-admin-form'); ?></th>
                                    <td>
                                        <?php foreach ($all_possible_fields as $field_key => $field_label): ?>
                                            <?php
                                            // Address field's required status depends on show_address_field
                                            $disabled = ($field_key === 'address' && !$form_settings['show_address_field']);
                                            // Some fields are always required and cannot be unchecked (e.g., type, subject, detail)
                                            $always_required = in_array($field_key, ['type', 'subject', 'detail', 'name', 'idcard', 'phone', 'email']);
                                            ?>
                                            <label style="margin-right: 15px; display: inline-block;">
                                                <input name="required_fields[]" type="checkbox" value="<?php echo esc_attr($field_key); ?>"
                                                       <?php checked(in_array($field_key, $form_settings['required_fields']), true); ?>
                                                       <?php disabled($disabled || $always_required); ?> />
                                                <?php echo esc_html($field_label); ?>
                                                <?php if ($always_required && !$disabled): ?>
                                                    <span class="description">(<?php esc_html_e('บังคับเสมอ', 'direct-to-admin-form'); ?>)</span>
                                                <?php endif; ?>
                                            </label><br>
                                        <?php endforeach; ?>
                                        <p class="description"><?php esc_html_e('เลือกฟิลด์ที่ต้องการให้ผู้ใช้จำเป็นต้องกรอกข้อมูล', 'direct-to-admin-form'); ?></p>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>

                </div> <!-- /post-body-content -->

                <!-- Sidebar -->
                <div id="postbox-container-1" class="postbox-container">
                    <div class="postbox">
                        <h2 class="hndle"><span><?php esc_html_e('เผยแพร่', 'direct-to-admin-form'); ?></span></h2>
                        <div class="inside">
                            <div class="submitbox" id="submitpost">
                                <div id="major-publishing-actions">
                                    <div id="publishing-action">
                                        <span class="spinner"></span>
                                        <input name="save" type="submit" class="button button-primary button-large" id="publish" value="<?php echo $is_new_form ? esc_attr__('สร้างแบบฟอร์ม', 'direct-to-admin-form') : esc_attr__('บันทึกการเปลี่ยนแปลง', 'direct-to-admin-form'); ?>">
                                    </div>
                                    <div class="clear"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="postbox">
                        <h2 class="hndle"><span><?php esc_html_e('ลักษณะที่ปรากฏของแบบฟอร์ม', 'direct-to-admin-form'); ?></span></h2>
                        <div class="inside">
                            <p>
                                <label for="form_color"><?php esc_html_e('สีหลักของแบบฟอร์ม (เช่น เส้นขอบ, หัวข้อ)', 'direct-to-admin-form'); ?></label><br>
                                <input name="form_color" type="text" id="form_color" value="<?php echo esc_attr($form_settings['form_color']); ?>" class="dtaf-color-picker" data-default-color="#0073aa">
                            </p>
                            <p>
                                <label for="button_color"><?php esc_html_e('สีปุ่มส่งข้อมูล', 'direct-to-admin-form'); ?></label><br>
                                <input name="button_color" type="text" id="button_color" value="<?php echo esc_attr($form_settings['button_color']); ?>" class="dtaf-color-picker" data-default-color="#0073aa">
                            </p>
                        </div>
                    </div>
                </div> <!-- /postbox-container-1 -->

            </div> <!-- /post-body -->
            <br class="clear">
        </div> <!-- /poststuff -->
    </form>
</div>
<script type="text/javascript">
    jQuery(document).ready(function($) {
        // Initialize WordPress color pickers
        $('.dtaf-color-picker').wpColorPicker();

        // Logic for disabling/enabling 'address' in required fields based on 'show_address_field'
        var $showAddressCheckbox = $('input[name="show_address_field"]');
        var $requiredAddressCheckbox = $('input[name="required_fields[]"][value="address"]');

        function toggleRequiredAddress() {
            if ($showAddressCheckbox.is(':checked')) {
                // If address is shown, remove disabled and check its original required state (if any)
                // Note: 'always_required' logic in PHP already handles some fields.
                // This JS is mainly for the UI interaction of the address field itself.
                var wasRequired = <?php echo json_encode(in_array('address', $default_form_settings['required_fields'])); ?>; // Default required state
                <?php if (!$is_new_form && isset($form_settings['required_fields'])): ?>
                    wasRequired = <?php echo json_encode(in_array('address', $form_settings['required_fields'])); ?>; // Current form's required state
                <?php endif; ?>
                $requiredAddressCheckbox.prop('disabled', false).prop('checked', wasRequired);

            } else {
                // If address is hidden, disable its "required" checkbox and uncheck it
                $requiredAddressCheckbox.prop('disabled', true).prop('checked', false);
            }
        }
        // Initial state
        toggleRequiredAddress();
        // On change
        $showAddressCheckbox.on('change', toggleRequiredAddress);

        // Slug auto-generation for new forms (simple version)
        <?php if ($is_new_form): ?>
        $('#form_name').on('keyup blur', function() {
            var title = $(this).val();
            var slug = title.toLowerCase()
                .replace(/\s+/g, '-')           // Replace spaces with -
                .replace(/[^\w-]+/g, '')       // Remove all non-word chars
                .replace(/--+/g, '-')         // Replace multiple - with single -
                .replace(/^-+/, '')             // Trim - from start of text
                .replace(/-+$/, '');            // Trim - from end of text
            $('#form_slug').val(slug);
        });
        <?php endif; ?>
    });
</script>