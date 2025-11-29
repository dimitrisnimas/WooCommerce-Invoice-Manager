<?php
/**
 * Admin functionality for WooCommerce Invoice Manager
 */

if (!defined('ABSPATH')) {
    exit;
}

class WC_Invoice_Manager_Admin {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('wp_ajax_upload_invoice', array($this, 'handle_upload'));
        add_action('wp_ajax_send_invoice', array($this, 'handle_send_invoice'));
        add_action('wp_ajax_get_order_details', array($this, 'get_order_details'));
        
        // Add debugging
        add_action('wp_ajax_debug_upload', array($this, 'debug_upload'));
    }
    
    /**
     * Debug upload function
     */
    public function debug_upload() {
        // Log all request data
        error_log('WC Invoice Manager Debug - POST: ' . print_r($_POST, true));
        error_log('WC Invoice Manager Debug - FILES: ' . print_r($_FILES, true));
        error_log('WC Invoice Manager Debug - SERVER: ' . print_r($_SERVER, true));
        
        wp_send_json_success('Debug data logged');
    }
    
    /**
     * Display admin page
     */
    public function display_page() {
        // Get recent orders for dropdown
        $recent_orders = $this->get_recent_orders();
        
        // Check if upload directory exists and is writable
        $upload_issues = $this->check_upload_directory();
        ?>
        <div class="wrap">
            <h1><?php _e('Παραστατικά', 'wc-invoice-manager'); ?></h1>
            
            <?php if (!empty($upload_issues)): ?>
                <div class="notice notice-warning">
                    <p><strong><?php _e('Προειδοποίηση:', 'wc-invoice-manager'); ?></strong></p>
                    <ul>
                        <?php foreach ($upload_issues as $issue): ?>
                            <li><?php echo $issue; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <div class="wc-invoice-manager-admin">
                <div class="invoice-upload-section">
                    <h2><?php _e('Ανέβασμα Παραστατικού', 'wc-invoice-manager'); ?></h2>
                    
                    <!-- Debug Button -->
                    <p><button type="button" id="debug-upload" class="button"><?php _e('Debug Upload', 'wc-invoice-manager'); ?></button></p>
                    
                    <form id="invoice-upload-form" enctype="multipart/form-data">
                        <?php wp_nonce_field('upload_invoice', 'invoice_nonce'); ?>
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="order_id"><?php _e('Επιλογή Παραγγελίας', 'wc-invoice-manager'); ?></label>
                                </th>
                                <td>
                                    <select id="order_id" name="order_id" class="regular-text" required>
                                        <option value=""><?php _e('-- Επιλέξτε Παραγγελία --', 'wc-invoice-manager'); ?></option>
                                        <?php foreach ($recent_orders as $order): ?>
                                            <option value="<?php echo $order->get_id(); ?>" 
                                                    data-customer="<?php echo esc_attr($order->get_formatted_billing_full_name()); ?>"
                                                    data-email="<?php echo esc_attr($order->get_billing_email()); ?>"
                                                    data-total="<?php echo esc_attr($order->get_formatted_order_total()); ?>"
                                                    data-status="<?php echo esc_attr($order->get_status()); ?>"
                                                    data-date="<?php echo esc_attr($order->get_date_created()->date_i18n(get_option('date_format'))); ?>">
                                                #<?php echo $order->get_order_number(); ?> - 
                                                <?php echo esc_html($order->get_formatted_billing_full_name()); ?> - 
                                                <?php echo $order->get_formatted_order_total(); ?> - 
                                                <?php echo $order->get_date_created()->date_i18n(get_option('date_format')); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                            </tr>
                            <tr id="order-details-row" style="display: none;">
                                <th scope="row"><?php _e('Στοιχεία Παραγγελίας', 'wc-invoice-manager'); ?></th>
                                <td id="order-details"></td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="invoice_file"><?php _e('Αρχείο PDF', 'wc-invoice-manager'); ?></label>
                                </th>
                                <td>
                                    <input type="file" id="invoice_file" name="invoice_file" accept=".pdf" required />
                                    <p class="description"><?php _e('Επιλέξτε αρχείο PDF για το παραστατικό (μέγιστο μέγεθος: 10MB)', 'wc-invoice-manager'); ?></p>
                                </td>
                            </tr>
                        </table>
                        
                        <p class="submit">
                            <input type="submit" class="button-primary" value="<?php _e('Ανέβασμα Παραστατικού', 'wc-invoice-manager'); ?>" />
                        </p>
                    </form>
                </div>
                
                <div class="invoices-list-section">
                    <h2><?php _e('Λίστα Παραστατικών', 'wc-invoice-manager'); ?></h2>
                    <?php $this->display_invoices_list(); ?>
                </div>
                
                <!-- Debug Information -->
                <div class="debug-section" style="margin-top: 30px; padding: 15px; background: #f9f9f9; border: 1px solid #ddd;">
                    <h3><?php _e('Πληροφορίες Συστήματος', 'wc-invoice-manager'); ?></h3>
                    <p><strong><?php _e('Upload Directory:', 'wc-invoice-manager'); ?></strong> <?php echo WC_INVOICE_MANAGER_UPLOAD_DIR; ?></p>
                    <p><strong><?php _e('Directory Exists:', 'wc-invoice-manager'); ?></strong> <?php echo file_exists(WC_INVOICE_MANAGER_UPLOAD_DIR) ? __('Ναι', 'wc-invoice-manager') : __('Όχι', 'wc-invoice-manager'); ?></p>
                    <p><strong><?php _e('Directory Writable:', 'wc-invoice-manager'); ?></strong> <?php echo is_writable(WC_INVOICE_MANAGER_UPLOAD_DIR) ? __('Ναι', 'wc-invoice-manager') : __('Όχι', 'wc-invoice-manager'); ?></p>
                    <p><strong><?php _e('PHP Upload Max Filesize:', 'wc-invoice-manager'); ?></strong> <?php echo ini_get('upload_max_filesize'); ?></p>
                    <p><strong><?php _e('PHP Post Max Size:', 'wc-invoice-manager'); ?></strong> <?php echo ini_get('post_max_size'); ?></p>
                    <p><strong><?php _e('AJAX URL:', 'wc-invoice-manager'); ?></strong> <?php echo admin_url('admin-ajax.php'); ?></p>
                </div>
            </div>
        </div>
        
        <div id="loading-overlay" style="display: none;">
            <div class="loading-spinner"></div>
        </div>
        <?php
    }
    
    /**
     * Check upload directory
     */
    private function check_upload_directory() {
        $issues = array();
        
        $upload_dir = WC_INVOICE_MANAGER_UPLOAD_DIR;
        
        if (!file_exists($upload_dir)) {
            $issues[] = __('Ο φάκελος ανεβάσματος δεν υπάρχει: ', 'wc-invoice-manager') . $upload_dir;
        } elseif (!is_writable($upload_dir)) {
            $issues[] = __('Ο φάκελος ανεβάσματος δεν είναι εγγράψιμος: ', 'wc-invoice-manager') . $upload_dir;
        }
        
        // Check PHP upload settings
        $upload_max = ini_get('upload_max_filesize');
        $post_max = ini_get('post_max_size');
        
        if ($upload_max && $post_max) {
            $upload_bytes = $this->convert_to_bytes($upload_max);
            $post_bytes = $this->convert_to_bytes($post_max);
            
            if ($upload_bytes < 10485760) { // 10MB
                $issues[] = sprintf(__('Το upload_max_filesize (%s) είναι μικρότερο από 10MB', 'wc-invoice-manager'), $upload_max);
            }
            
            if ($post_bytes < 10485760) { // 10MB
                $issues[] = sprintf(__('Το post_max_size (%s) είναι μικρότερο από 10MB', 'wc-invoice-manager'), $post_max);
            }
        }
        
        return $issues;
    }
    
    /**
     * Convert PHP size format to bytes
     */
    private function convert_to_bytes($size) {
        $size = trim($size);
        $last = strtolower($size[strlen($size)-1]);
        $size = (int) $size;
        
        switch($last) {
            case 'g': $size *= 1024;
            case 'm': $size *= 1024;
            case 'k': $size *= 1024;
        }
        
        return $size;
    }
    
    /**
     * Get recent orders for dropdown
     */
    private function get_recent_orders($limit = 100) {
        $args = array(
            'limit' => $limit,
            'status' => array('wc-processing', 'wc-completed', 'wc-on-hold'),
            'orderby' => 'date',
            'order' => 'DESC',
            'return' => 'objects'
        );
        
        return wc_get_orders($args);
    }
    
    /**
     * Handle file upload
     */
    public function handle_upload() {
        // Log the request
        error_log('WC Invoice Manager - Upload request received');
        error_log('WC Invoice Manager - POST data: ' . print_r($_POST, true));
        error_log('WC Invoice Manager - FILES data: ' . print_r($_FILES, true));
        
        // Verify nonce
        if (!wp_verify_nonce($_POST['invoice_nonce'], 'upload_invoice')) {
            error_log('WC Invoice Manager - Nonce verification failed');
            wp_send_json_error(__('Μη έγκυρη αίτηση', 'wc-invoice-manager'));
        }
        
        if (!current_user_can('manage_woocommerce')) {
            error_log('WC Invoice Manager - User capability check failed');
            wp_send_json_error(__('Δεν έχετε άδεια για αυτή την ενέργεια', 'wc-invoice-manager'));
        }
        
        $order_id = intval($_POST['order_id']);
        if (!$order_id) {
            error_log('WC Invoice Manager - No order ID provided');
            wp_send_json_error(__('Παρακαλώ επιλέξτε μια παραγγελία', 'wc-invoice-manager'));
        }
        
        $order = wc_get_order($order_id);
        if (!$order) {
            error_log('WC Invoice Manager - Order not found: ' . $order_id);
            wp_send_json_error(__('Η παραγγελία δεν βρέθηκε', 'wc-invoice-manager'));
        }
        
        // Check if file was uploaded
        if (empty($_FILES['invoice_file']) || $_FILES['invoice_file']['error'] === UPLOAD_ERR_NO_FILE) {
            error_log('WC Invoice Manager - No file uploaded');
            wp_send_json_error(__('Δεν επιλέχθηκε αρχείο', 'wc-invoice-manager'));
        }
        
        // Handle file upload
        try {
            error_log('WC Invoice Manager - Starting file upload process');
            $file_manager = new WC_Invoice_Manager_File_Manager();
            $result = $file_manager->upload_invoice($order, $_FILES['invoice_file']);
            
            if ($result['success']) {
                error_log('WC Invoice Manager - Upload successful');
                wp_send_json_success($result['message']);
            } else {
                error_log('WC Invoice Manager - Upload failed: ' . $result['message']);
                wp_send_json_error($result['message']);
            }
        } catch (Exception $e) {
            error_log('WC Invoice Manager - Upload exception: ' . $e->getMessage());
            wp_send_json_error(__('Σφάλμα κατά το ανέβασμα: ', 'wc-invoice-manager') . $e->getMessage());
        }
    }
    
    /**
     * Handle sending invoice via email
     */
    public function handle_send_invoice() {
        if (!wp_verify_nonce($_POST['send_nonce'], 'send_invoice')) {
            wp_send_json_error(__('Μη έγκυρη αίτηση', 'wc-invoice-manager'));
        }
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(__('Δεν έχετε άδεια για αυτή την ενέργεια', 'wc-invoice-manager'));
        }
        
        $invoice_id = intval($_POST['invoice_id']);
        if (!$invoice_id) {
            wp_send_json_error(__('Μη έγκυρο ID παραστατικού', 'wc-invoice-manager'));
        }
        
        try {
            $email_manager = new WC_Invoice_Manager_Email_Manager();
            $result = $email_manager->send_invoice($invoice_id);
            
            if ($result['success']) {
                wp_send_json_success($result['message']);
            } else {
                wp_send_json_error($result['message']);
            }
        } catch (Exception $e) {
            wp_send_json_error(__('Σφάλμα κατά την αποστολή: ', 'wc-invoice-manager') . $e->getMessage());
        }
    }
    
    /**
     * Get order details via AJAX (now using dropdown data)
     */
    public function get_order_details() {
        if (!wp_verify_nonce($_POST['invoice_nonce'], 'upload_invoice')) {
            wp_send_json_error(__('Μη έγκυρη αίτηση', 'wc-invoice-manager'));
        }
        
        $order_id = intval($_POST['order_id']);
        $order = wc_get_order($order_id);
        
        if (!$order) {
            wp_send_json_error(__('Η παραγγελία δεν βρέθηκε', 'wc-invoice-manager'));
        }
        
        $customer_email = $order->get_billing_email();
        $customer_name = $order->get_formatted_billing_full_name();
        $total = $order->get_formatted_order_total();
        $status = $order->get_status();
        $date = $order->get_date_created()->date_i18n(get_option('date_format'));
        
        $details = sprintf(
            '<strong>%s:</strong> %s<br><strong>%s:</strong> %s<br><strong>%s:</strong> %s<br><strong>%s:</strong> %s<br><strong>%s:</strong> %s',
            __('Πελάτης', 'wc-invoice-manager'),
            $customer_name,
            __('Email', 'wc-invoice-manager'),
            $customer_email,
            __('Σύνολο', 'wc-invoice-manager'),
            $total,
            __('Κατάσταση', 'wc-invoice-manager'),
            ucfirst($status),
            __('Ημερομηνία', 'wc-invoice-manager'),
            $date
        );
        
        wp_send_json_success($details);
    }
    
    /**
     * Display invoices list
     */
    private function display_invoices_list() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wc_invoice_manager';
        
        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
        if (!$table_exists) {
            echo '<div class="notice notice-error"><p>' . __('Ο πίνακας βάσης δεδομένων δεν υπάρχει. Παρακαλώ απενεργοποιήστε και ξανα-ενεργοποιήστε το plugin.', 'wc-invoice-manager') . '</p></div>';
            return;
        }
        
        $invoices = $wpdb->get_results(
            "SELECT * FROM $table_name ORDER BY upload_date DESC LIMIT 50"
        );
        
        if (empty($invoices)) {
            echo '<p>' . __('Δεν υπάρχουν παραστατικά', 'wc-invoice-manager') . '</p>';
            return;
        }
        
        ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('ID', 'wc-invoice-manager'); ?></th>
                    <th><?php _e('Παραγγελία', 'wc-invoice-manager'); ?></th>
                    <th><?php _e('Πελάτης', 'wc-invoice-manager'); ?></th>
                    <th><?php _e('Αρχείο', 'wc-invoice-manager'); ?></th>
                    <th><?php _e('Ημερομηνία', 'wc-invoice-manager'); ?></th>
                    <th><?php _e('Κατάσταση', 'wc-invoice-manager'); ?></th>
                    <th><?php _e('Ενέργειες', 'wc-invoice-manager'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($invoices as $invoice): ?>
                    <tr>
                        <td><?php echo $invoice->id; ?></td>
                        <td>
                            <a href="<?php echo admin_url('post.php?post=' . $invoice->order_id . '&action=edit'); ?>" target="_blank">
                                #<?php echo $invoice->order_id; ?>
                            </a>
                        </td>
                        <td><?php echo esc_html($invoice->customer_email); ?></td>
                        <td><?php echo esc_html($invoice->file_name); ?></td>
                        <td><?php echo date('d/m/Y H:i', strtotime($invoice->upload_date)); ?></td>
                        <td>
                            <span class="status-<?php echo $invoice->status; ?>">
                                <?php echo $this->get_status_label($invoice->status); ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($invoice->status === 'pending'): ?>
                                <button class="button button-small send-invoice-btn" data-invoice-id="<?php echo $invoice->id; ?>">
                                    <?php _e('Αποστολή', 'wc-invoice-manager'); ?>
                                </button>
                            <?php else: ?>
                                <span class="sent-date"><?php echo date('d/m/Y H:i', strtotime($invoice->sent_date)); ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }
    
    /**
     * Get status label in Greek
     */
    private function get_status_label($status) {
        switch ($status) {
            case 'pending':
                return __('Εκκρεμές', 'wc-invoice-manager');
            case 'sent':
                return __('Απεστάλη', 'wc-invoice-manager');
            default:
                return $status;
        }
    }
}