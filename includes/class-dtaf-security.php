<?php
/**
 * Security class
 * 
 * Handles security-related functionality
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class DTAF_Security {
    /**
     * Honeypot field name
     */
    const HONEYPOT_FIELD = 'dtaf_contact_email_hp';

    /**
     * Verify nonce
     *
     * @param string $nonce Nonce value
     * @param string $action Nonce action
     * @return bool True if nonce is valid, false otherwise
     */
    public static function verify_nonce($nonce, $action) {
        return wp_verify_nonce($nonce, $action) !== false;
    }

    /**
     * Verify honeypot
     *
     * @return bool True if honeypot is empty (valid), false if filled (spam)
     */
    public static function verify_honeypot() {
        return !isset($_POST[self::HONEYPOT_FIELD]) || empty($_POST[self::HONEYPOT_FIELD]);
    }

    /**
     * Render honeypot field
     *
     * @return string Honeypot field HTML
     */
    public static function render_honeypot_field() {
        ob_start();
        ?>
        <div class="dtaf-honeypot-field" aria-hidden="true">
            <label for="<?php echo esc_attr(self::HONEYPOT_FIELD); ?>">
                <?php esc_html_e('If you are human, leave this field blank.', 'direct-to-admin-form'); ?>
            </label>
            <input type="text" name="<?php echo esc_attr(self::HONEYPOT_FIELD); ?>" 
                   id="<?php echo esc_attr(self::HONEYPOT_FIELD); ?>" 
                   value="" tabindex="-1" autocomplete="off">
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Sanitize and validate form data
     *
     * @param array $data Form data
     * @param array $required_fields Required fields
     * @return array|WP_Error Sanitized data or error
     */
    public static function sanitize_form_data($data, $required_fields = []) {
        $sanitized = [];
        $errors = [];

        // Define field sanitization rules
        $fields = [
            'name' => [
                'sanitize' => 'sanitize_text_field',
                'validate' => function($value) {
                    return !empty($value);
                },
                'error' => __('กรุณากรอกชื่อ-นามสกุล', 'direct-to-admin-form'),
            ],
            'idcard' => [
                'sanitize' => 'sanitize_text_field',
                'validate' => function($value) {
                    return preg_match('/^\d{13}$/', $value);
                },
                'error' => __('กรุณากรอกเลขบัตรประชาชน 13 หลัก', 'direct-to-admin-form'),
            ],
            'phone' => [
                'sanitize' => 'sanitize_text_field',
                'validate' => function($value) {
                    return preg_match('/^[0-9\-+().\s]{7,20}$/', $value);
                },
                'error' => __('กรุณากรอกเบอร์โทรศัพท์ให้ถูกต้อง', 'direct-to-admin-form'),
            ],
            'email' => [
                'sanitize' => 'sanitize_email',
                'validate' => function($value) {
                    return is_email($value);
                },
                'error' => __('กรุณากรอกอีเมลให้ถูกต้อง', 'direct-to-admin-form'),
            ],
            'address' => [
                'sanitize' => 'sanitize_textarea_field',
                'validate' => function($value) {
                    return true; // Address is optional
                },
                'error' => '',
            ],
            'type' => [
                'sanitize' => 'sanitize_text_field',
                'validate' => function($value) {
                    return !empty($value);
                },
                'error' => __('กรุณาเลือกประเภทเรื่องร้องเรียน', 'direct-to-admin-form'),
            ],
            'subject' => [
                'sanitize' => 'sanitize_text_field',
                'validate' => function($value) {
                    return !empty($value);
                },
                'error' => __('กรุณากรอกหัวข้อเรื่อง', 'direct-to-admin-form'),
            ],
            'detail' => [
                'sanitize' => 'sanitize_textarea_field',
                'validate' => function($value) {
                    return !empty($value);
                },
                'error' => __('กรุณากรอกรายละเอียด', 'direct-to-admin-form'),
            ],
        ];

        // Process each field
        foreach ($fields as $field => $rules) {
            if (isset($data[$field])) {
                // Sanitize
                $sanitized[$field] = call_user_func($rules['sanitize'], $data[$field]);
                
                // Validate if required
                if (in_array($field, $required_fields) && !$rules['validate']($sanitized[$field])) {
                    $errors[$field] = $rules['error'];
                }
            } elseif (in_array($field, $required_fields)) {
                $errors[$field] = $rules['error'];
            }
        }

        if (!empty($errors)) {
            return new WP_Error('validation_failed', __('กรุณากรอกข้อมูลให้ถูกต้องครบถ้วน', 'direct-to-admin-form'), $errors);
        }

        return $sanitized;
    }

    /**
     * Validate file upload
     *
     * @param array $file File data from $_FILES
     * @param array $allowed_types Allowed file extensions
     * @param int $max_size Maximum file size in MB
     * @return array|WP_Error File data or error
     */
    public static function validate_file_upload($file, $allowed_types, $max_size) {
        // Check if file was uploaded properly
        if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
            return new WP_Error('upload_error', __('ไม่มีไฟล์ที่อัปโหลด', 'direct-to-admin-form'));
        }

        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $error_message = self::get_upload_error_message($file['error']);
            return new WP_Error('upload_error', $error_message);
        }

        // Check file size
        $max_size_bytes = $max_size * 1024 * 1024;
        if ($file['size'] > $max_size_bytes) {
            return new WP_Error(
                'file_too_large',
                sprintf(__('ไฟล์มีขนาดใหญ่เกินไป (สูงสุด %d MB)', 'direct-to-admin-form'), $max_size)
            );
        }

        // Check file type
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed_types_array = array_map('trim', explode(',', $allowed_types));
        
        if (!in_array($file_ext, $allowed_types_array)) {
            return new WP_Error(
                'invalid_file_type',
                sprintf(__('ประเภทไฟล์ไม่ได้รับอนุญาต (อนุญาตเฉพาะ %s)', 'direct-to-admin-form'), $allowed_types)
            );
        }

        return $file;
    }

    /**
     * Get upload error message
     *
     * @param int $error_code PHP upload error code
     * @return string Error message
     */
    private static function get_upload_error_message($error_code) {
        switch ($error_code) {
            case UPLOAD_ERR_INI_SIZE:
                return __('ไฟล์มีขนาดใหญ่เกินกว่าที่กำหนดในการตั้งค่า PHP', 'direct-to-admin-form');
            case UPLOAD_ERR_FORM_SIZE:
                return __('ไฟล์มีขนาดใหญ่เกินกว่าที่กำหนดในฟอร์ม', 'direct-to-admin-form');
            case UPLOAD_ERR_PARTIAL:
                return __('ไฟล์ถูกอัปโหลดเพียงบางส่วน', 'direct-to-admin-form');
            case UPLOAD_ERR_NO_FILE:
                return __('ไม่มีไฟล์ที่อัปโหลด', 'direct-to-admin-form');
            case UPLOAD_ERR_NO_TMP_DIR:
                return __('ไม่พบโฟลเดอร์ชั่วคราวสำหรับอัปโหลด', 'direct-to-admin-form');
            case UPLOAD_ERR_CANT_WRITE:
                return __('ไม่สามารถเขียนไฟล์ลงดิสก์ได้', 'direct-to-admin-form');
            case UPLOAD_ERR_EXTENSION:
                return __('การอัปโหลดถูกหยุดโดยส่วนขยาย PHP', 'direct-to-admin-form');
            default:
                return __('เกิดข้อผิดพลาดในการอัปโหลดไฟล์', 'direct-to-admin-form');
        }
    }
}
