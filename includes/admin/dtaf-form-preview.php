<?php
/**
 * Form preview template for admin
 *
 * @var string $form_slug Form slug
 * @var string $form_data['form_name'] Form name
 * @var array $form_settings Form settings
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="dtaf-form-container" id="dtaf-form-<?php echo esc_attr($form_data['form_slug']); ?>">
    <h3><?php echo esc_html($form_data['form_name']); ?></h3>
    
    <form method="post" enctype="multipart/form-data" class="dtaf-form">
        <div class="dtaf-form-field">
            <label for="dtaf_name_<?php echo esc_attr($form_data['form_slug']); ?>">
                <?php esc_html_e('ชื่อ-นามสกุล (ผู้ร้อง)', 'direct-to-admin-form'); ?>
                <?php if (in_array('name', $form_settings['required_fields'])): ?>
                    <span class="dtaf-required">*</span>
                <?php endif; ?>
            </label>
            <input type="text" 
                   id="dtaf_name_<?php echo esc_attr($form_data['form_slug']); ?>" 
                   name="name" 
                   placeholder="<?php esc_attr_e('กรอกชื่อ-นามสกุล', 'direct-to-admin-form'); ?>"
                   <?php echo in_array('name', $form_settings['required_fields']) ? 'required' : ''; ?>>
        </div>
        
        <div class="dtaf-form-field">
            <label for="dtaf_idcard_<?php echo esc_attr($form_data['form_slug']); ?>">
                <?php esc_html_e('บัตรประชาชน (ผู้ร้อง)', 'direct-to-admin-form'); ?>
                <?php if (in_array('idcard', $form_settings['required_fields'])): ?>
                    <span class="dtaf-required">*</span>
                <?php endif; ?>
            </label>
            <input type="text" 
                   id="dtaf_idcard_<?php echo esc_attr($form_data['form_slug']); ?>" 
                   name="idcard" 
                   placeholder="<?php esc_attr_e('กรอกเลข 13 หลัก', 'direct-to-admin-form'); ?>"
                   pattern="\d{13}" 
                   title="<?php esc_attr_e('กรุณากรอกเลขบัตรประชาชน 13 หลัก ไม่ต้องมีขีด', 'direct-to-admin-form'); ?>"
                   <?php echo in_array('idcard', $form_settings['required_fields']) ? 'required' : ''; ?>>
            <span class="dtaf-field-description"><?php esc_html_e('ต้องกรอกตัวเลข 13 หลักติดกัน', 'direct-to-admin-form'); ?></span>
        </div>
        
        <div class="dtaf-form-field">
            <label for="dtaf_phone_<?php echo esc_attr($form_data['form_slug']); ?>">
                <?php esc_html_e('โทรศัพท์', 'direct-to-admin-form'); ?>
                <?php if (in_array('phone', $form_settings['required_fields'])): ?>
                    <span class="dtaf-required">*</span>
                <?php endif; ?>
            </label>
            <input type="tel" 
                   id="dtaf_phone_<?php echo esc_attr($form_data['form_slug']); ?>" 
                   name="phone" 
                   placeholder="<?php esc_attr_e('กรอกเบอร์โทรศัพท์', 'direct-to-admin-form'); ?>"
                   pattern="[0-9\-+().\s]{7,20}" 
                   title="<?php esc_attr_e('กรุณากรอกเบอร์โทรศัพท์ที่ถูกต้อง', 'direct-to-admin-form'); ?>"
                   <?php echo in_array('phone', $form_settings['required_fields']) ? 'required' : ''; ?>>
        </div>
        
        <div class="dtaf-form-field">
            <label for="dtaf_email_<?php echo esc_attr($form_data['form_slug']); ?>">
                <?php esc_html_e('อีเมล', 'direct-to-admin-form'); ?>
                <?php if (in_array('email', $form_settings['required_fields'])): ?>
                    <span class="dtaf-required">*</span>
                <?php endif; ?>
            </label>
            <input type="email" 
                   id="dtaf_email_<?php echo esc_attr($form_data['form_slug']); ?>" 
                   name="email" 
                   placeholder="<?php esc_attr_e('กรอกอีเมล', 'direct-to-admin-form'); ?>"
                   <?php echo in_array('email', $form_settings['required_fields']) ? 'required' : ''; ?>>
        </div>
        
        <?php if (isset($form_settings['show_address_field']) && $form_settings['show_address_field']): ?>
            <div class="dtaf-form-field">
                <label for="dtaf_address_<?php echo esc_attr($form_data['form_slug']); ?>">
                    <?php esc_html_e('ที่อยู่', 'direct-to-admin-form'); ?>
                    <?php if (in_array('address', $form_settings['required_fields'])): ?>
                        <span class="dtaf-required">*</span>
                    <?php endif; ?>
                </label>
                <textarea id="dtaf_address_<?php echo esc_attr($form_data['form_slug']); ?>" 
                          name="address" 
                          placeholder="<?php esc_attr_e('กรอกที่อยู่ (ถ้ามี)', 'direct-to-admin-form'); ?>"
                          <?php echo in_array('address', $form_settings['required_fields']) ? 'required' : ''; ?>></textarea>
            </div>
        <?php endif; ?>
        
        <div class="dtaf-form-field">
            <label for="dtaf_type_<?php echo esc_attr($form_data['form_slug']); ?>">
                <?php esc_html_e('ประเภทเรื่องร้องเรียน', 'direct-to-admin-form'); ?>
                <?php if (in_array('type', $form_settings['required_fields'])): ?>
                    <span class="dtaf-required">*</span>
                <?php endif; ?>
            </label>
            <select id="dtaf_type_<?php echo esc_attr($form_data['form_slug']); ?>" 
                    name="type"
                    <?php echo in_array('type', $form_settings['required_fields']) ? 'required' : ''; ?>>
                <option value=""><?php esc_html_e('-- กรุณาเลือกประเภท --', 'direct-to-admin-form'); ?></option>
                <?php foreach ($form_settings['complaint_types'] as $type): ?>
                    <?php if (!empty(trim($type))): ?>
                        <option value="<?php echo esc_attr($type); ?>"><?php echo esc_html($type); ?></option>
                    <?php endif; ?>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="dtaf-form-field">
            <label for="dtaf_subject_<?php echo esc_attr($form_data['form_slug']); ?>">
                <?php esc_html_e('เรื่อง', 'direct-to-admin-form'); ?>
                <?php if (in_array('subject', $form_settings['required_fields'])): ?>
                    <span class="dtaf-required">*</span>
                <?php endif; ?>
            </label>
            <input type="text" 
                   id="dtaf_subject_<?php echo esc_attr($form_data['form_slug']); ?>" 
                   name="subject" 
                   placeholder="<?php esc_attr_e('กรอกหัวข้อเรื่อง', 'direct-to-admin-form'); ?>"
                   <?php echo in_array('subject', $form_settings['required_fields']) ? 'required' : ''; ?>>
        </div>
        
        <div class="dtaf-form-field">
            <label for="dtaf_detail_<?php echo esc_attr($form_data['form_slug']); ?>">
                <?php esc_html_e('รายละเอียด', 'direct-to-admin-form'); ?>
                <?php if (in_array('detail', $form_settings['required_fields'])): ?>
                    <span class="dtaf-required">*</span>
                <?php endif; ?>
            </label>
            <textarea id="dtaf_detail_<?php echo esc_attr($form_data['form_slug']); ?>" 
                      name="detail" 
                      placeholder="<?php esc_attr_e('กรอกรายละเอียดการร้องเรียน...', 'direct-to-admin-form'); ?>"
                      <?php echo in_array('detail', $form_settings['required_fields']) ? 'required' : ''; ?>></textarea>
        </div>
        
        <div class="dtaf-form-field">
            <label><?php esc_html_e('ไฟล์แนบ (ถ้ามี)', 'direct-to-admin-form'); ?></label>
            
            <div class="dtaf-file-upload">
                <input type="file" name="dtaf_file_1" id="dtaf_file_1_<?php echo esc_attr($form_data['form_slug']); ?>">
            </div>
            
            <div class="dtaf-file-upload">
                <input type="file" name="dtaf_file_2" id="dtaf_file_2_<?php echo esc_attr($form_data['form_slug']); ?>">
            </div>
            
            <div class="dtaf-file-upload">
                <input type="file" name="dtaf_file_3" id="dtaf_file_3_<?php echo esc_attr($form_data['form_slug']); ?>">
            </div>
            
            <div class="dtaf-file-upload-info">
                <?php esc_html_e('ไฟล์ที่อนุญาต: jpg, jpeg, png, pdf, doc, docx, ขนาดสูงสุด: 5 MB', 'direct-to-admin-form'); ?>
            </div>
        </div>
        
        <div class="dtaf-form-field">
            <button type="submit" class="dtaf-submit-button" style="background-color: <?php echo esc_attr($form_settings['button_color']); ?>">
                <?php esc_html_e('ส่งเรื่องร้องเรียน', 'direct-to-admin-form'); ?>
            </button>
        </div>
    </form>
    
    <div class="dtaf-preview-notice" style="margin-top: 15px; padding: 10px; background-color: #f8f9fa; border-radius: 4px; text-align: center;">
        <p><em><?php esc_html_e('นี่เป็นเพียงตัวอย่างแบบฟอร์ม ไม่สามารถกรอกข้อมูลได้จริง', 'direct-to-admin-form'); ?></em></p>
    </div>
</div>

<style type="text/css">
/* Form container */
.dtaf-form-container {
  max-width: 600px;
  margin: 20px auto;
  padding: 20px;
  background-color: #f9f9f9;
  border: 1px solid #ddd;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
  font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue",
    sans-serif;
}

/* Form title */
.dtaf-form-container h3 {
  text-align: center;
  margin-bottom: 20px;
  color: #333;
  font-size: 24px;
  font-weight: 600;
  padding-bottom: 10px;
  border-bottom: 1px solid #eee;
}

/* Form fields */
.dtaf-form-container .dtaf-form-field {
  margin-bottom: 20px;
}

.dtaf-form-container label {
  display: block;
  margin-bottom: 5px;
  font-weight: 600;
  color: #333;
}

.dtaf-form-container input[type="text"],
.dtaf-form-container input[type="email"],
.dtaf-form-container input[type="tel"],
.dtaf-form-container select,
.dtaf-form-container textarea {
  width: 100%;
  padding: 10px 12px;
  border: 1px solid #ddd;
  border-radius: 4px;
  box-sizing: border-box;
  font-size: 16px;
  line-height: 1.5;
  transition: border-color 0.3s, box-shadow 0.3s;
}

.dtaf-form-container textarea {
  min-height: 120px;
  resize: vertical;
}

/* Field description */
.dtaf-form-container .dtaf-field-description {
  display: block;
  font-size: 13px;
  color: #666;
  margin-top: 4px;
}

/* File uploads */
.dtaf-form-container .dtaf-file-upload {
  margin-bottom: 10px;
}

/* Submit button */
.dtaf-form-container .dtaf-submit-button {
  background-color: <?php echo esc_attr($form_settings['button_color']); ?>;
  color: white;
  padding: 12px 20px;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-size: 16px;
  font-weight: 600;
  width: 100%;
  transition: background-color 0.3s;
}

/* Required field indicator */
.dtaf-form-container .dtaf-required {
  color: #dc3545;
  margin-left: 2px;
}
</style>
