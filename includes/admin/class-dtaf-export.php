<?php
/**
 * Export class
 * 
 * Handles data export functionality
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class DTAF_Export {
    /**
     * Initialize the class
     */
    public function __construct() {
        // Register export handlers
        add_action('admin_post_dtaf_export_submissions', [$this, 'export_submissions']);
        add_action('admin_post_dtaf_export_report', [$this, 'export_report']);
    }
    
    /**
     * Export submissions
     */
    public function export_submissions() {
        // Check nonce
        if (!isset($_POST['dtaf_export_nonce']) || !wp_verify_nonce($_POST['dtaf_export_nonce'], 'dtaf_export_submissions')) {
            wp_die(__('การตรวจสอบความปลอดภัยล้มเหลว', 'direct-to-admin-form'));
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('คุณไม่มีสิทธิ์เข้าถึงหน้านี้', 'direct-to-admin-form'));
        }
        
        // Get filter parameters
        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';
        $form_id = isset($_POST['form_id']) ? sanitize_text_field($_POST['form_id']) : '';
        $type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : '';
        $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
        $date_from = isset($_POST['date_from']) ? sanitize_text_field($_POST['date_from']) : '';
        $date_to = isset($_POST['date_to']) ? sanitize_text_field($_POST['date_to']) : '';
        
        // Get export format
        $format = isset($_POST['export_format']) ? sanitize_text_field($_POST['export_format']) : 'csv';
        
        // Get submissions
        $submissions_data = DTAF_Database::get_submissions([
            'per_page' => -1, // Get all
            'status' => $status,
            'form_id' => $form_id,
            'type' => $type,
            'search' => $search,
            'date_from' => $date_from,
            'date_to' => $date_to,
        ]);
        
        $submissions = $submissions_data['items'];
        
        // Check if we have submissions
        if (empty($submissions)) {
            wp_die(__('ไม่พบข้อมูลเรื่องร้องเรียนที่ตรงกับเงื่อนไข', 'direct-to-admin-form'));
        }
        
        // Get form names
        $forms = DTAF_Database::get_forms();
        $form_names = [];
        foreach ($forms as $form) {
            $form_names[$form->form_slug] = $form->form_name;
        }
        
        // Prepare data for export
        $export_data = [];
        
        // Add header row
        $export_data[] = [
            __('ID', 'direct-to-admin-form'),
            __('ชื่อผู้ร้อง', 'direct-to-admin-form'),
            __('บัตรประชาชน', 'direct-to-admin-form'),
            __('โทรศัพท์', 'direct-to-admin-form'),
            __('อีเมล', 'direct-to-admin-form'),
            __('ที่อยู่', 'direct-to-admin-form'),
            __('ประเภท', 'direct-to-admin-form'),
            __('เรื่อง', 'direct-to-admin-form'),
            __('รายละเอียด', 'direct-to-admin-form'),
            __('แบบฟอร์ม', 'direct-to-admin-form'),
            __('วันที่', 'direct-to-admin-form'),
            __('สถานะ', 'direct-to-admin-form'),
            __('หมายเหตุ', 'direct-to-admin-form'),
            __('IP Address', 'direct-to-admin-form'),
        ];
        
        // Add data rows
        foreach ($submissions as $submission) {
            $form_name = isset($form_names[$submission->form_id]) ? $form_names[$submission->form_id] : $submission->form_id;
            $date = date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($submission->created_at));
            
            $export_data[] = [
                $submission->id,
                $submission->name,
                $submission->idcard,
                $submission->phone,
                $submission->email,
                $submission->address,
                $submission->type,
                $submission->subject,
                $submission->detail,
                $form_name,
                $date,
                $submission->status,
                $submission->admin_notes,
                $submission->ip_address,
            ];
        }
        
        // Export based on format
        switch ($format) {
            case 'csv':
                $this->export_as_csv($export_data, 'dtaf-submissions-export');
                break;
                
            case 'excel':
                $this->export_as_excel($export_data, 'dtaf-submissions-export');
                break;
                
            case 'pdf':
                $this->export_as_pdf($export_data, 'dtaf-submissions-export');
                break;
                
            default:
                wp_die(__('รูปแบบการส่งออกไม่ถูกต้อง', 'direct-to-admin-form'));
        }
    }
    
    /**
     * Export report
     */
    public function export_report() {
        // Check nonce
        if (!isset($_POST['dtaf_export_nonce']) || !wp_verify_nonce($_POST['dtaf_export_nonce'], 'dtaf_export_report')) {
            wp_die(__('การตรวจสอบความปลอดภัยล้มเหลว', 'direct-to-admin-form'));
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('คุณไม่มีสิทธิ์เข้าถึงหน้านี้', 'direct-to-admin-form'));
        }
        
        // Get date range
        $start_date = isset($_POST['start_date']) ? sanitize_text_field($_POST['start_date']) : date('Y-m-d', strtotime('-30 days'));
        $end_date = isset($_POST['end_date']) ? sanitize_text_field($_POST['end_date']) : date('Y-m-d');
        
        // Get export format
        $format = isset($_POST['export_format']) ? sanitize_text_field($_POST['export_format']) : 'csv';
        
        // Get statistics
        $stats = DTAF_Database::get_filtered_statistics($start_date, $end_date);
        
        // Prepare data for export
        $export_data = [];
        
        // Add header and summary
        $export_data[] = [__('รายงานสรุปเรื่องร้องเรียน', 'direct-to-admin-form')];
        $export_data[] = [__('ช่วงวันที่', 'direct-to-admin-form') . ': ' . $start_date . ' - ' . $end_date];
        $export_data[] = [__('จำนวนเรื่องร้องเรียนทั้งหมด', 'direct-to-admin-form') . ': ' . $stats['total']];
        $export_data[] = [''];
        
        // Add status statistics
        $export_data[] = [__('เรื่องร้องเรียนตามสถานะ', 'direct-to-admin-form')];
        $export_data[] = [__('สถานะ', 'direct-to-admin-form'), __('จำนวน', 'direct-to-admin-form'), __('เปอร์เซ็นต์', 'direct-to-admin-form')];
        
        foreach ($stats['by_status'] as $status) {
            $export_data[] = [
                $status->status,
                $status->count,
                round(($status->count / $stats['total']) * 100, 1) . '%',
            ];
        }
        
        $export_data[] = [''];
        
        // Add type statistics
        $export_data[] = [__('เรื่องร้องเรียนตามประเภท', 'direct-to-admin-form')];
        $export_data[] = [__('ประเภท', 'direct-to-admin-form'), __('จำนวน', 'direct-to-admin-form'), __('เปอร์เซ็นต์', 'direct-to-admin-form')];
        
        foreach ($stats['by_type'] as $type) {
            $export_data[] = [
                $type->type,
                $type->count,
                round(($type->count / $stats['total']) * 100, 1) . '%',
            ];
        }
        
        $export_data[] = [''];
        
        // Add form statistics
        $export_data[] = [__('เรื่องร้องเรียนตามแบบฟอร์ม', 'direct-to-admin-form')];
        $export_data[] = [__('แบบฟอร์ม', 'direct-to-admin-form'), __('จำนวน', 'direct-to-admin-form'), __('เปอร์เซ็นต์', 'direct-to-admin-form')];
        
        // Get form names
        $forms = DTAF_Database::get_forms();
        $form_names = [];
        foreach ($forms as $form) {
            $form_names[$form->form_slug] = $form->form_name;
        }
        
        foreach ($stats['by_form'] as $form) {
            $form_name = isset($form_names[$form->form_id]) ? $form_names[$form->form_id] : $form->form_id;
            
            $export_data[] = [
                $form_name,
                $form->count,
                round(($form->count / $stats['total']) * 100, 1) . '%',
            ];
        }
        
        $export_data[] = [''];
        
        // Add monthly statistics
        $export_data[] = [__('เรื่องร้องเรียนตามเดือน', 'direct-to-admin-form')];
        $export_data[] = [__('เดือน', 'direct-to-admin-form'), __('จำนวน', 'direct-to-admin-form')];
        
        foreach ($stats['by_month'] as $month) {
            $month_date = new DateTime($month->month . '-01');
            
            $export_data[] = [
                $month_date->format('F Y'),
                $month->count,
            ];
        }
        
        // Export based on format
        switch ($format) {
            case 'csv':
                $this->export_as_csv($export_data, 'dtaf-report');
                break;
                
            case 'excel':
                $this->export_as_excel($export_data, 'dtaf-report');
                break;
                
            case 'pdf':
                $this->export_as_pdf($export_data, 'dtaf-report');
                break;
                
            default:
                wp_die(__('รูปแบบการส่งออกไม่ถูกต้อง', 'direct-to-admin-form'));
        }
    }
    
    /**
     * Export data as CSV
     *
     * @param array $data Data to export
     * @param string $filename Filename without extension
     */
    private function export_as_csv($data, $filename) {
        // Set headers
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename . '.csv');
        
        // Create a file pointer connected to the output stream
        $output = fopen('php://output', 'w');
        
        // Add UTF-8 BOM to fix Excel encoding issues
        fputs($output, "\xEF\xBB\xBF");
        
        // Output each row of the data
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
        
        // Close the file pointer
        fclose($output);
        exit;
    }
    
    /**
     * Export data as Excel
     *
     * @param array $data Data to export
     * @param string $filename Filename without extension
     */
    private function export_as_excel($data, $filename) {
        // Check if PHPExcel is available
        if (!class_exists('PHPExcel')) {
            // Fallback to CSV if PHPExcel is not available
            $this->export_as_csv($data, $filename);
            return;
        }
        
        // Create new PHPExcel object
        $excel = new PHPExcel();
        
        // Set document properties
        $excel->getProperties()->setCreator('Direct to Admin Form')
            ->setLastModifiedBy('Direct to Admin Form')
            ->setTitle($filename)
            ->setSubject('Direct to Admin Form Export')
            ->setDescription('Export from Direct to Admin Form plugin');
        
        // Add data
        $excel->setActiveSheetIndex(0);
        $sheet = $excel->getActiveSheet();
        
        // Add rows
        $row = 1;
        foreach ($data as $rowData) {
            $col = 0;
            foreach ($rowData as $cellData) {
                $sheet->setCellValueByColumnAndRow($col, $row, $cellData);
                $col++;
            }
            $row++;
        }
        
        // Set auto column widths
        $sheet->calculateColumnWidths();
        
        // Set headers
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '.xlsx"');
        header('Cache-Control: max-age=0');
        
        // Save to output
        $writer = PHPExcel_IOFactory::createWriter($excel, 'Excel2007');
        $writer->save('php://output');
        exit;
    }
    
    /**
     * Export data as PDF
     *
     * @param array $data Data to export
     * @param string $filename Filename without extension
     */
    private function export_as_pdf($data, $filename) {
        // Check if TCPDF is available
        if (!class_exists('TCPDF')) {
            // Fallback to CSV if TCPDF is not available
            $this->export_as_csv($data, $filename);
            return;
        }
        
        // Create new PDF document
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        
        // Set document information
        $pdf->SetCreator('Direct to Admin Form');
        $pdf->SetAuthor('Direct to Admin Form');
        $pdf->SetTitle($filename);
        $pdf->SetSubject('Direct to Admin Form Export');
        
        // Set default header data
        $pdf->SetHeaderData('', 0, $filename, '');
        
        // Set header and footer fonts
        $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
        $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
        
        // Set default monospaced font
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        
        // Set margins
        $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
        
        // Set auto page breaks
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
        
        // Set image scale factor
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
        
        // Add a page
        $pdf->AddPage();
        
        // Build HTML content
        $html = '<table border="1" cellpadding="5">';
        
        foreach ($data as $row) {
            $html .= '<tr>';
            foreach ($row as $cell) {
                $html .= '<td>' . $cell . '</td>';
            }
            $html .= '</tr>';
        }
        
        $html .= '</table>';
        
        // Output the HTML content
        $pdf->writeHTML($html, true, false, true, false, '');
        
        // Close and output PDF document
        $pdf->Output($filename . '.pdf', 'D');
        exit;
    }
}
