<?php
/**
 * Email functionality for WooCommerce Invoice Manager
 */

if (!defined('ABSPATH')) {
    exit;
}

class WC_Invoice_Manager_Email_Manager {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_filter('woocommerce_email_classes', array($this, 'add_email_class'));
    }
    
    /**
     * Send invoice via email
     */
    public function send_invoice($invoice_id) {
        try {
            global $wpdb;
            $table_name = $wpdb->prefix . 'wc_invoice_manager';
            
            // Get invoice data
            $invoice = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table_name WHERE id = %d",
                $invoice_id
            ));
            
            if (!$invoice) {
                return array(
                    'success' => false,
                    'message' => __('Το παραστατικό δεν βρέθηκε', 'wc-invoice-manager')
                );
            }
            
            // Check if already sent
            if ($invoice->status === 'sent') {
                return array(
                    'success' => false,
                    'message' => __('Το παραστατικό έχει ήδη αποσταλεί', 'wc-invoice-manager')
                );
            }
            
            // Get order data
            $order = wc_get_order($invoice->order_id);
            if (!$order) {
                return array(
                    'success' => false,
                    'message' => __('Η παραγγελία δεν βρέθηκε', 'wc-invoice-manager')
                );
            }
            
            // Check if file exists
            if (!file_exists($invoice->file_path)) {
                return array(
                    'success' => false,
                    'message' => __('Το αρχείο παραστατικού δεν βρέθηκε', 'wc-invoice-manager')
                );
            }
            
            // Send email
            $email_sent = $this->send_invoice_email($order, $invoice);
            
            if ($email_sent) {
                // Update status in database
                $wpdb->update(
                    $table_name,
                    array(
                        'status' => 'sent',
                        'sent_date' => current_time('mysql')
                    ),
                    array('id' => $invoice_id),
                    array('%s', '%s'),
                    array('%d')
                );
                
                return array(
                    'success' => true,
                    'message' => __('Το παραστατικό στάλθηκε επιτυχώς', 'wc-invoice-manager')
                );
            } else {
                return array(
                    'success' => false,
                    'message' => __('Δεν ήταν δυνατή η αποστολή του email', 'wc-invoice-manager')
                );
            }
            
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => __('Σφάλμα κατά την αποστολή: ', 'wc-invoice-manager') . $e->getMessage()
            );
        }
    }
    
    /**
     * Send invoice email
     */
    private function send_invoice_email($order, $invoice) {
        $customer_email = $order->get_billing_email();
        $customer_name = $order->get_formatted_billing_full_name();
        
        // Email subject
        $subject = sprintf(
            __('Παραστατικό για την παραγγελία #%s - %s', 'wc-invoice-manager'),
            $order->get_order_number(),
            get_bloginfo('name')
        );
        
        // Email content
        $message = $this->get_email_template($order, $invoice);
        
        // Email headers
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>'
        );
        
        // Attach PDF file
        $attachments = array($invoice->file_path);
        
        // Send email
        $result = wp_mail($customer_email, $subject, $message, $headers, $attachments);
        
        return $result;
    }
    
    /**
     * Get email template
     */
    private function get_email_template($order, $invoice) {
        $customer_name = $order->get_formatted_billing_full_name();
        $order_number = $order->get_order_number();
        $order_date = $order->get_date_created()->date_i18n(get_option('date_format'));
        $order_total = $order->get_formatted_order_total();
        $site_name = get_bloginfo('name');
        $site_url = get_home_url();
        
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?php _e('Παραστατικό Παραγγελίας', 'wc-invoice-manager'); ?></title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    line-height: 1.6;
                    color: #333;
                    max-width: 600px;
                    margin: 0 auto;
                    padding: 20px;
                }
                .header {
                    background-color: #f8f9fa;
                    padding: 20px;
                    text-align: center;
                    border-radius: 5px;
                    margin-bottom: 30px;
                }
                .content {
                    background-color: #fff;
                    padding: 20px;
                    border: 1px solid #ddd;
                    border-radius: 5px;
                }
                .order-details {
                    background-color: #f8f9fa;
                    padding: 15px;
                    border-radius: 5px;
                    margin: 20px 0;
                }
                .footer {
                    text-align: center;
                    margin-top: 30px;
                    color: #666;
                    font-size: 12px;
                }
                .button {
                    display: inline-block;
                    padding: 10px 20px;
                    background-color: #007cba;
                    color: white;
                    text-decoration: none;
                    border-radius: 3px;
                    margin: 10px 0;
                }
            </style>
        </head>
        <body>
            <div class="header">
                <h1><?php echo esc_html($site_name); ?></h1>
                <p><?php _e('Παραστατικό Παραγγελίας', 'wc-invoice-manager'); ?></p>
            </div>
            
            <div class="content">
                <p><?php printf(__('Αγαπητέ/ή %s,', 'wc-invoice-manager'), esc_html($customer_name)); ?></p>
                
                <p><?php _e('Σας στέλνουμε το παραστατικό για την παραγγελία σας.', 'wc-invoice-manager'); ?></p>
                
                <div class="order-details">
                    <h3><?php _e('Στοιχεία Παραγγελίας', 'wc-invoice-manager'); ?></h3>
                    <p><strong><?php _e('Αριθμός Παραγγελίας:', 'wc-invoice-manager'); ?></strong> #<?php echo esc_html($order_number); ?></p>
                    <p><strong><?php _e('Ημερομηνία:', 'wc-invoice-manager'); ?></strong> <?php echo esc_html($order_date); ?></p>
                    <p><strong><?php _e('Σύνολο:', 'wc-invoice-manager'); ?></strong> <?php echo $order_total; ?></p>
                </div>
                
                <p><?php _e('Το παραστατικό είναι επισυνάπτεται σε αυτό το email σε μορφή PDF.', 'wc-invoice-manager'); ?></p>
                
                <p><?php _e('Εάν έχετε οποιεσδήποτε ερωτήσεις, μη διστάσετε να επικοινωνήσετε μαζί μας.', 'wc-invoice-manager'); ?></p>
                
                <p><?php _e('Ευχαριστούμε που μας επιλέξατε!', 'wc-invoice-manager'); ?></p>
                
                <p><?php _e('Με εκτίμηση,', 'wc-invoice-manager'); ?><br>
                <?php echo esc_html($site_name); ?></p>
            </div>
            
            <div class="footer">
                <p>&copy; <?php echo date('Y'); ?> <?php echo esc_html($site_name); ?>. <?php _e('Όλα τα δικαιώματα διατηρούνται.', 'wc-invoice-manager'); ?></p>
                <p><a href="<?php echo esc_url($site_url); ?>"><?php echo esc_html($site_url); ?></a></p>
            </div>
        </body>
        </html>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * Add custom email class to WooCommerce
     */
    public function add_email_class($email_classes) {
        require_once WC_INVOICE_MANAGER_PLUGIN_PATH . 'includes/class-wc-email-invoice.php';
        $email_classes['WC_Email_Invoice'] = new WC_Email_Invoice();
        return $email_classes;
    }
    
    /**
     * Send test email
     */
    public function send_test_email($email_address) {
        $subject = __('Δοκιμαστικό Email - Παραστατικό', 'wc-invoice-manager');
        
        $message = sprintf(
            __('Αυτό είναι ένα δοκιμαστικό email από το plugin WooCommerce Invoice Manager.<br><br>Εάν λαμβάνετε αυτό το μήνυμα, η λειτουργία email λειτουργεί σωστά.<br><br>Ημερομηνία: %s', 'wc-invoice-manager'),
            current_time(get_option('date_format') . ' ' . get_option('time_format'))
        );
        
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>'
        );
        
        return wp_mail($email_address, $subject, $message, $headers);
    }
}