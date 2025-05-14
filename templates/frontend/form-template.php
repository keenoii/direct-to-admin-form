<?php
/**
 * Frontend form template
 *
 * @var string $form_id Form ID
 * @var object $form Form object
 * @var array $settings Form settings
 * @var string $message Flash message
 * @var string $message_type Flash message type
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="dtaf-form-container" id="dtaf-form-<?php echo esc_attr($form_id); ?>">
    <h3><?php echo esc_html($form->form_name); ?></h3>
    
    <?php if (!empty($message)): ?>
        <div class="dtaf-message <?php echo ($message_type === 'success') ? 'dtaf-success-message' : 'dtaf-error-message'; ?>">
            <?php echo wp_kses_post($message); ?>
        </div>
    <?php endif; ?>
    
    <form method="post" enctype="multipart/form-data" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="dtaf-form">
        <input type="hidden" name="action" value="dtaf_submit_form">
        <input type="hidden" name="form_id" value="<?php echo esc_attr($form_id); ?>">
        <?php wp_nonce_field('dtaf_submit_form', 'dtaf_nonce'); ?>
        
        <?php if (isset($settings['enable_honeypot']) && $settings['enable_honeypot']): ?>
            <?php echo DTAF_Security::render_honeypot_field(); ?>
        <?php endif; ?>
        
        <div class="dtaf-form-field">
            <label for="dtaf_name_<?php echo esc_attr($form_id); ?>">
                <?php esc_html_e('ชื่อ-นามสกุล (ผู้ร้อง)', 'direct-to-admin-form'); ?>
                <?php if (in_array('name', $settings['required_fields'])): ?>
                    <span class="dtaf-required">*</span>
                <?php endif; ?>
            </label>
            <input type="text" 
                   id="dtaf_name_<?php echo esc_attr($form_id); ?>" 
                   name="name" 
                   placeholder="<?php esc_attr_e('กรอกชื่อ-นามสกุล', 'direct-to-admin-form'); ?>"
                   <?php echo in_array('name', $settings['required_fields']) ? 'required' : ''; ?>>
        </div>
        
        <div class="dtaf-form-field">
            <label for="dtaf_idcard_<?php echo esc_attr($form_id); ?>">
                <?php esc_html_e('บัตรประชาชน (ผู้ร้อง)', 'direct-to-admin-form'); ?>
                <?php if (in_array('idcard', $settings['required_fields'])): ?>
                    <span class="dtaf-required">*</span>
                <?php endif; ?>
            </label>
            <input type="text" 
                   id="dtaf_idcard_<?php echo esc_attr($form_id); ?>" 
                   name="idcard" 
                   placeholder="<?php esc_attr_e('กรอกเลข 13 หลัก', 'direct-to-admin-form'); ?>"
                   pattern="\d{13}" 
                   title="<?php esc_attr_e('กรุณากรอกเลขบัตรประชาชน 13 หลัก ไม่ต้องมีขีด', 'direct-to-admin-form'); ?>"
                   <?php echo in_array('idcard', $settings['required_fields']) ? 'required' : ''; ?>>
            <span class="dtaf-field-description"><?php esc_html_e('ต้องกรอกตัวเลข 13 หลักติดกัน', 'direct-to-admin-form'); ?></span>
        </div>
        
        <div class="dtaf-form-field">
            <label for="dtaf_phone_<?php echo esc_attr($form_id); ?>">
                <?php esc_html_e('โทรศัพท์', 'direct-to-admin-form'); ?>
                <?php if (in_array('phone', $settings['required_fields'])): ?>
                    <span class="dtaf-required">*</span>
                <?php endif; ?>
            </label>
            <input type="tel" 
                   id="dtaf_phone_<?php echo esc_attr($form_id); ?>" 
                   name="phone" 
                   placeholder="<?php esc_attr_e('กรอกเบอร์โทรศัพท์', 'direct-to-admin-form'); ?>"
                   pattern="[0-9\-+().\s]{7,20}" 
                   title="<?php esc_attr_e('กรุณากรอกเบอร์โทรศัพท์ที่ถูกต้อง', 'direct-to-admin-form'); ?>"
                   <?php echo in_array('phone', $settings['required_fields']) ? 'required' : ''; ?>>
        </div>
        
        <div class="dtaf-form-field">
            <label for="dtaf_email_<?php echo esc_attr($form_id); ?>">
                <?php esc_html_e('อีเมล', 'direct-to-admin-form'); ?>
                <?php if (in_array('email', $settings['required_fields'])): ?>
                    <span class="dtaf-required">*</span>
                <?php endif; ?>
            </label>
            <input type="email" 
                   id="dtaf_email_<?php echo esc_attr($form_id); ?>" 
                   name="email" 
                   placeholder="<?php esc_attr_e('กรอกอีเมล', 'direct-to-admin-form'); ?>"
                   <?php echo in_array('email', $settings['required_fields']) ? 'required' : ''; ?>>
        </div>
        
        <?php if (isset($settings['show_address_field']) && $settings['show_address_field']): ?>
            <div class="dtaf-form-field">
                <label for="dtaf_address_<?php echo esc_attr($form_id); ?>">
                    <?php esc_html_e('ที่อยู่', 'direct-to-admin-form'); ?>
                    <?php if (in_array('address', $settings['required_fields'])): ?>
                        <span class="dtaf-required">*</span>
                    <?php endif; ?>
                </label>
                <textarea id="dtaf_address_<?php echo esc_attr($form_id); ?>" 
                          name="address" 
                          placeholder="<?php esc_attr_e('กรอกที่อยู่ (ถ้ามี)', 'direct-to-admin-form'); ?>"
                          <?php echo in_array('address', $settings['required_fields']) ? 'required' : ''; ?>></textarea>
            </div>
        <?php endif; ?>
        
        <div class="dtaf-form-field">
            <label for="dtaf_type_<?php echo esc_attr($form_id); ?>">
                <?php esc_html_e('ประเภทเรื่องร้องเรียน', 'direct-to-admin-form'); ?>
                <?php if (in_array('type', $settings['required_fields'])): ?>
                    <span class="dtaf-required">*</span>
                <?php endif; ?>
            </label>
            <select id="dtaf_type_<?php echo esc_attr($form_id); ?>" 
                    name="type"
                    <?php echo in_array('type', $settings['required_fields']) ? 'required' : ''; ?>>
                <option value=""><?php esc_html_e('-- กรุณาเลือกประเภท --', 'direct-to-admin-form'); ?></option>
                <?php foreach ($settings['complaint_types'] as $type): ?>
                    <?php if (!empty(trim($type))): ?>
                        <option value="<?php echo esc_attr($type); ?>"><?php echo esc_html($type); ?></option>
                    <?php endif; ?>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="dtaf-form-field">
            <label for="dtaf_subject_<?php echo esc_attr($form_id); ?>">
                <?php esc_html_e('เรื่อง', 'direct-to-admin-form'); ?>
                <?php if (in_array('subject', $settings['required_fields'])): ?>
                    <span class="dtaf-required">*</span>
                <?php endif; ?>
            </label>
            <input type="text" 
                   id="dtaf_subject_<?php echo esc_attr($form_id); ?>" 
                   name="subject" 
                   placeholder="<?php esc_attr_e('กรอกหัวข้อเรื่อง', 'direct-to-admin-form'); ?>"
                   <?php echo in_array('subject', $settings['required_fields']) ? 'required' : ''; ?>>
        </div>
        
        <div class="dtaf-form-field">
            <label for="dtaf_detail_<?php echo esc_attr($form_id); ?>">
                <?php esc_html_e('รายละเอียด', 'direct-to-admin-form'); ?>
                <?php if (in_array('detail', $settings['required_fields'])): ?>
                    <span class="dtaf-required">*</span>
                <?php endif; ?>
            </label>
            <textarea id="dtaf_detail_<?php echo esc_attr($form_id); ?>" 
                      name="detail" 
                      placeholder="<?php esc_attr_e('กรอกรายละเอียดการร้องเรียน...', 'direct-to-admin-form'); ?>"
                      <?php echo in_array('detail', $settings['required_fields']) ? 'required' : ''; ?>></textarea>
        </div>
        
        <?php
        // Get file upload settings
        $allowed_types = isset($settings['allowed_file_types']) ? $settings['allowed_file_types'] : 'jpg,jpeg,png,pdf,doc,docx';
        $max_file_size = isset($settings['max_file_size']) ? (int)$settings['max_file_size'] : 5;
        ?>
        
        <div class="dtaf-form-field">
            <label><?php esc_html_e('ไฟล์แนบ (ถ้ามี)', 'direct-to-admin-form'); ?></label>
            
            <div class="dtaf-file-upload">
                <input type="file" name="dtaf_file_1" id="dtaf_file_1_<?php echo esc_attr($form_id); ?>">
            </div>
            
            <div class="dtaf-file-upload">
                <input type="file" name="dtaf_file_2" id="dtaf_file_2_<?php echo esc_attr($form_id); ?>">
            </div>
            
            <div class="dtaf-file-upload">
                <input type="file" name="dtaf_file_3" id="dtaf_file_3_<?php echo esc_attr($form_id); ?>">
            </div>
            
            <div class="dtaf-file-upload-info">
                <?php 
                printf(
                    esc_html__('ไฟล์ที่อนุญาต: %1$s, ขนาดสูงสุด: %2$s MB', 'direct-to-admin-form'),
                    esc_html($allowed_types),
                    esc_html($max_file_size)
                ); 
                ?>
            </div>
        </div>
        
        <div class="dtaf-form-field">
            <button type="submit" class="dtaf-submit-button" style="background-color: <?php echo esc_attr($settings['button_color']); ?>">
                <?php esc_html_e('ส่งเรื่องร้องเรียน', 'direct-to-admin-form'); ?>
            </button>
        </div>
        
        <div class="dtaf-loading">
            <span class="dtaf-loading-spinner"></span>
            <?php esc_html_e('กำลังส่งข้อมูล...', 'direct-to-admin-form'); ?>
        </div>
    </form>
</div>

<script type="text/javascript">
    (function($) {
        $(document).ready(function() {
            // Client-side form validation
            $('#dtaf-form-<?php echo esc_js($form_id); ?> form').on('submit', function(e) {
                var $form = $(this);
                var $submitBtn = $form.find('.dtaf-submit-button');
                var $loading = $form.find('.dtaf-loading');
                
                // Check if using AJAX submission
                if (typeof dtaf_frontend !== 'undefined') {
                    e.preventDefault();
                    
                    // Show loading indicator
                    $submitBtn.prop('disabled', true);
                    $loading.show();
                    
                    // Collect form data
                    var formData = new FormData(this);
                    
                    // Send AJAX request
                    $.ajax({
                        url: dtaf_frontend.ajax_url,
                        type: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            if (response.success) {
                                // Show success message
                                $form.before('<div class="dtaf-message dtaf-success-message">' + response.data.message + '</div>');
                                $form[0].reset();
                            } else {
                                // Show error message
                                $form.before('<div class="dtaf-message dtaf-error-message">' + response.data.message + '</div>');
                            }
                            
                            // Scroll to message
                            $('html, body').animate({
                                scrollTop: $('#dtaf-form-<?php echo esc_js($form_id); ?>').offset().top - 50
                            }, 500);
                            
                            // Hide loading indicator
                            $submitBtn.prop('disabled', false);
                            $loading.hide();
                        },
                        error: function() {
                            // Show error message
                            $form.before('<div class="dtaf-message dtaf-error-message"><?php echo esc_js(__('เกิดข้อผิดพลาดในการส่งข้อมูล โปรดลองอีกครั้ง', 'direct-to-admin-form')); ?></div>');
                            
                            // Scroll to message
                            $('html, body').animate({
                                scrollTop: $('#dtaf-form-<?php echo esc_js($form_id); ?>').offset().top - 50
                            }, 500);
                            
                            // Hide loading indicator
                            $submitBtn.prop('disabled', false);
                            $loading.hide();
                        }
                    });
                } else {
                    // Regular form submission
                    $submitBtn.prop('disabled', true);
                    $loading.show();
                }
            });
        });
    })(jQuery);
</script>
