<?php
/**
 * File management functionality for WooCommerce Invoice Manager
 */

if (!defined('ABSPATH')) {
    exit;
}

class WC_Invoice_Manager_File_Manager {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Constructor
    }
    
    /**
     * Upload invoice file
     */
    public function upload_invoice($order, $file) {
        try {
            // Validate file
            $validation = $this->validate_file($file);
            if (!$validation['valid']) {
                return array(
                    'success' => false,
                    'message' => $validation['message']
                );
            }
            
            // Get customer email
            $customer_email = $order->get_billing_email();
            if (!$customer_email) {
                return array(
                    'success' => false,
                    'message' => __('Δεν βρέθηκε email πελάτη για την παραγγελία', 'wc-invoice-manager')
                );
            }
            
            // Create customer directory
            $customer_dir = $this->create_customer_directory($customer_email);
            if (!$customer_dir) {
                return array(
                    'success' => false,
                    'message' => __('Δεν ήταν δυνατή η δημιουργία φακέλου πελάτη', 'wc-invoice-manager')
                );
            }
            
            // Generate unique filename
            $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $unique_filename = $this->generate_unique_filename($customer_dir, $file['name']);
            
            // Fix path construction - remove double slashes
            $file_path = rtrim($customer_dir, '/') . '/' . ltrim($unique_filename, '/');
            
            // Check if upload directory is writable
            if (!is_writable($customer_dir)) {
                return array(
                    'success' => false,
                    'message' => __('Ο φάκελος δεν είναι εγγράψιμος', 'wc-invoice-manager')
                );
            }
            
            // Move uploaded file
            if (!move_uploaded_file($file['tmp_name'], $file_path)) {
                $upload_error = $this->get_upload_error_message($file['error']);
                return array(
                    'success' => false,
                    'message' => sprintf(__('Δεν ήταν δυνατή η αποθήκευση του αρχείου: %s', 'wc-invoice-manager'), $upload_error)
                );
            }
            
            // Set proper file permissions
            chmod($file_path, 0644);
            
            // Verify file was uploaded correctly
            if (!file_exists($file_path) || filesize($file_path) === 0) {
                if (file_exists($file_path)) {
                    unlink($file_path);
                }
                return array(
                    'success' => false,
                    'message' => __('Το αρχείο δεν ανέβηκε σωστά', 'wc-invoice-manager')
                );
            }
            
            // Save to database
            $invoice_id = $this->save_invoice_to_database($order->get_id(), $customer_email, $unique_filename, $file_path);
            
            if (!$invoice_id) {
                // Clean up file if database save failed
                if (file_exists($file_path)) {
                    unlink($file_path);
                }
                return array(
                    'success' => false,
                    'message' => __('Δεν ήταν δυνατή η αποθήκευση των στοιχείων στη βάση δεδομένων', 'wc-invoice-manager')
                );
            }
            
            return array(
                'success' => true,
                'message' => __('Το παραστατικό ανέβηκε επιτυχώς', 'wc-invoice-manager'),
                'invoice_id' => $invoice_id
            );
            
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => __('Σφάλμα κατά το ανέβασμα: ', 'wc-invoice-manager') . $e->getMessage()
            );
        }
    }
    
    /**
     * Validate uploaded file
     */
    private function validate_file($file) {
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $error_message = $this->get_upload_error_message($file['error']);
            return array(
                'valid' => false,
                'message' => sprintf(__('Σφάλμα κατά το ανέβασμα του αρχείου: %s', 'wc-invoice-manager'), $error_message)
            );
        }
        
        // Check if file was uploaded
        if (!is_uploaded_file($file['tmp_name'])) {
            return array(
                'valid' => false,
                'message' => __('Το αρχείο δεν ανέβηκε σωστά', 'wc-invoice-manager')
            );
        }
        
        // Check file size (max 10MB)
        $max_size = 10 * 1024 * 1024; // 10MB
        if ($file['size'] > $max_size) {
            return array(
                'valid' => false,
                'message' => __('Το αρχείο είναι πολύ μεγάλο. Μέγιστο μέγεθος: 10MB', 'wc-invoice-manager')
            );
        }
        
        // Check if file is empty
        if ($file['size'] === 0) {
            return array(
                'valid' => false,
                'message' => __('Το αρχείο είναι κενό', 'wc-invoice-manager')
            );
        }
        
        // Check file type using multiple methods
        $allowed_types = array('application/pdf');
        $file_type = '';
        
        // Method 1: Using mime_content_type
        if (function_exists('mime_content_type')) {
            $file_type = mime_content_type($file['tmp_name']);
        }
        
        // Method 2: Using finfo if available
        if (empty($file_type) && function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $file_type = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
        }
        
        // Method 3: Using $_FILES mime type as fallback
        if (empty($file_type) && !empty($file['type'])) {
            $file_type = $file['type'];
        }
        
        // If we can't determine MIME type, just check extension
        if (empty($file_type)) {
            $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if ($file_extension !== 'pdf') {
                return array(
                    'valid' => false,
                    'message' => __('Το αρχείο πρέπει να έχει επέκταση .pdf', 'wc-invoice-manager')
                );
            }
        } else {
            if (!in_array($file_type, $allowed_types)) {
                return array(
                    'valid' => false,
                    'message' => sprintf(__('Μόνο αρχεία PDF επιτρέπονται. Δεχόμαστε: %s, Ελήφθη: %s', 'wc-invoice-manager'), implode(', ', $allowed_types), $file_type)
                );
            }
        }
        
        // Check file extension
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($file_extension !== 'pdf') {
            return array(
                'valid' => false,
                'message' => __('Το αρχείο πρέπει να έχει επέκταση .pdf', 'wc-invoice-manager')
            );
        }
        
        return array('valid' => true);
    }
    
    /**
     * Get upload error message
     */
    private function get_upload_error_message($error_code) {
        switch ($error_code) {
            case UPLOAD_ERR_INI_SIZE:
                return __('Το αρχείο υπερβαίνει το upload_max_filesize directive του php.ini', 'wc-invoice-manager');
            case UPLOAD_ERR_FORM_SIZE:
                return __('Το αρχείο υπερβαίνει το MAX_FILE_SIZE directive που καθορίστηκε στο HTML form', 'wc-invoice-manager');
            case UPLOAD_ERR_PARTIAL:
                return __('Το αρχείο ανέβηκε μόνο εν μέρει', 'wc-invoice-manager');
            case UPLOAD_ERR_NO_FILE:
                return __('Δεν ανέβηκε κανένα αρχείο', 'wc-invoice-manager');
            case UPLOAD_ERR_NO_TMP_DIR:
                return __('Λείπει ο προσωρινός φάκελος', 'wc-invoice-manager');
            case UPLOAD_ERR_CANT_WRITE:
                return __('Αποτυχία εγγραφής αρχείου στο δίσκο', 'wc-invoice-manager');
            case UPLOAD_ERR_EXTENSION:
                return __('Η ανέβασμα σταμάτησε από extension', 'wc-invoice-manager');
            default:
                return sprintf(__('Άγνωστο σφάλμα ανεβάσματος (κωδικός: %s)', 'wc-invoice-manager'), $error_code);
        }
    }
    
    /**
     * Create customer directory
     */
    private function create_customer_directory($customer_email) {
        // Sanitize email for directory name
        $safe_email = sanitize_file_name($customer_email);
        
        // Fix path construction - ensure no double slashes
        $base_dir = rtrim(WC_INVOICE_MANAGER_UPLOAD_DIR, '/');
        $customer_dir = $base_dir . '/' . $safe_email;
        
        // Create main upload directory if it doesn't exist
        if (!file_exists($base_dir)) {
            if (!wp_mkdir_p($base_dir)) {
                return false;
            }
            
            // Create .htaccess file for security
            $htaccess_content = "Options -Indexes\n";
            $htaccess_content .= "deny from all\n";
            file_put_contents($base_dir . '/.htaccess', $htaccess_content);
        }
        
        // Create customer directory if it doesn't exist
        if (!file_exists($customer_dir)) {
            if (!wp_mkdir_p($customer_dir)) {
                return false;
            }
            
            // Create .htaccess file for security
            $htaccess_content = "Options -Indexes\n";
            $htaccess_content .= "deny from all\n";
            file_put_contents($customer_dir . '/.htaccess', $htaccess_content);
        }
        
        return $customer_dir;
    }
    
    /**
     * Generate unique filename
     */
    private function generate_unique_filename($directory, $original_filename) {
        $file_extension = pathinfo($original_filename, PATHINFO_EXTENSION);
        $base_name = pathinfo($original_filename, PATHINFO_FILENAME);
        $base_name = sanitize_file_name($base_name);
        
        // Add timestamp to make it more unique
        $timestamp = date('Y-m-d_H-i-s');
        $filename = $base_name . '_' . $timestamp . '.' . $file_extension;
        
        $counter = 1;
        while (file_exists($directory . '/' . $filename)) {
            $filename = $base_name . '_' . $timestamp . '_' . $counter . '.' . $file_extension;
            $counter++;
        }
        
        return $filename;
    }
    
    /**
     * Save invoice to database
     */
    private function save_invoice_to_database($order_id, $customer_email, $filename, $file_path) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wc_invoice_manager';
        
        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
        if (!$table_exists) {
            return false;
        }
        
        $result = $wpdb->insert(
            $table_name,
            array(
                'order_id' => $order_id,
                'customer_email' => $customer_email,
                'file_name' => $filename,
                'file_path' => $file_path,
                'status' => 'pending'
            ),
            array(
                '%d',
                '%s',
                '%s',
                '%s',
                '%s'
            )
        );
        
        if ($result === false) {
            return false;
        }
        
        return $wpdb->insert_id;
    }
    
    /**
     * Delete invoice file
     */
    public function delete_invoice($invoice_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wc_invoice_manager';
        
        // Get invoice data
        $invoice = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $invoice_id
        ));
        
        if (!$invoice) {
            return false;
        }
        
        // Delete file
        if (file_exists($invoice->file_path)) {
            unlink($invoice->file_path);
        }
        
        // Delete database record
        $result = $wpdb->delete(
            $table_name,
            array('id' => $invoice_id),
            array('%d')
        );
        
        return $result !== false;
    }
    
    /**
     * Get invoice file info
     */
    public function get_invoice_info($invoice_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wc_invoice_manager';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $invoice_id
        ));
    }
    
    /**
     * Clean up old files (for maintenance)
     */
    public function cleanup_old_files($days_old = 365) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wc_invoice_manager';
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$days_old} days"));
        
        // Get old invoices
        $old_invoices = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE upload_date < %s",
            $cutoff_date
        ));
        
        $deleted_count = 0;
        
        foreach ($old_invoices as $invoice) {
            if ($this->delete_invoice($invoice->id)) {
                $deleted_count++;
            }
        }
        
        return $deleted_count;
    }
}