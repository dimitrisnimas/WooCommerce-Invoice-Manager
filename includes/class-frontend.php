<?php
/**
 * Frontend functionality for WooCommerce Invoice Manager
 */

if (!defined('ABSPATH')) {
    exit;
}

class WC_Invoice_Manager_Frontend {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('woocommerce_account_menu_items', array($this, 'add_invoices_menu_item'));
        add_action('init', array($this, 'add_invoices_endpoint'));
        add_action('woocommerce_account_invoices_endpoint', array($this, 'invoices_content'));
        add_action('wp_ajax_download_invoice', array($this, 'handle_download'));
        add_action('wp_ajax_nopriv_download_invoice', array($this, 'handle_download'));
    }
    
    /**
     * Add invoices menu item to My Account
     */
    public function add_invoices_menu_item($items) {
        // Insert after orders
        $position = array_search('orders', array_keys($items));
        if ($position !== false) {
            $position++;
        } else {
            $position = count($items);
        }
        
        $new_items = array_slice($items, 0, $position, true) +
                    array('invoices' => __('Παραστατικά', 'wc-invoice-manager')) +
                    array_slice($items, $position, null, true);
        
        return $new_items;
    }
    
    /**
     * Add invoices endpoint
     */
    public function add_invoices_endpoint() {
        add_rewrite_endpoint('invoices', EP_ROOT | EP_PAGES);
    }
    
    /**
     * Display invoices content in My Account
     */
    public function invoices_content() {
        $customer_id = get_current_user_id();
        $customer = new WP_User($customer_id);
        $customer_email = $customer->user_email;
        
        // Get customer's invoices
        global $wpdb;
        $table_name = $wpdb->prefix . 'wc_invoice_manager';
        
        $invoices = $wpdb->get_results($wpdb->prepare(
            "SELECT i.*, o.post_date as order_date, o.post_status as order_status 
             FROM $table_name i 
             LEFT JOIN {$wpdb->posts} o ON i.order_id = o.ID 
             WHERE i.customer_email = %s 
             ORDER BY i.upload_date DESC",
            $customer_email
        ));
        
        ?>
        <div class="woocommerce-invoices">
            <h3><?php _e('Τα Παραστατικά Μου', 'wc-invoice-manager'); ?></h3>
            
            <?php if (empty($invoices)): ?>
                <p><?php _e('Δεν έχετε παραστατικά ακόμα.', 'wc-invoice-manager'); ?></p>
            <?php else: ?>
                <div class="invoices-table-wrapper">
                    <table class="shop_table shop_table_responsive my_account_invoices">
                        <thead>
                            <tr>
                                <th class="invoice-order">
                                    <span class="nobr"><?php _e('Παραγγελία', 'wc-invoice-manager'); ?></span>
                                </th>
                                <th class="invoice-date">
                                    <span class="nobr"><?php _e('Ημερομηνία Παραγγελίας', 'wc-invoice-manager'); ?></span>
                                </th>
                                <th class="invoice-upload-date">
                                    <span class="nobr"><?php _e('Ημερομηνία Παραστατικού', 'wc-invoice-manager'); ?></span>
                                </th>
                                <th class="invoice-status">
                                    <span class="nobr"><?php _e('Κατάσταση', 'wc-invoice-manager'); ?></span>
                                </th>
                                <th class="invoice-actions">
                                    <span class="nobr"><?php _e('Ενέργειες', 'wc-invoice-manager'); ?></span>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($invoices as $invoice): ?>
                                <?php
                                $order = wc_get_order($invoice->order_id);
                                $order_total = $order ? $order->get_formatted_order_total() : '';
                                ?>
                                <tr class="invoice">
                                    <td class="invoice-order" data-title="<?php _e('Παραγγελία', 'wc-invoice-manager'); ?>">
                                        <a href="<?php echo esc_url(wc_get_endpoint_url('view-order', $invoice->order_id)); ?>">
                                            #<?php echo $invoice->order_id; ?>
                                        </a>
                                        <?php if ($order_total): ?>
                                            <small class="order-total"><?php echo $order_total; ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="invoice-date" data-title="<?php _e('Ημερομηνία Παραγγελίας', 'wc-invoice-manager'); ?>">
                                        <?php echo date_i18n(get_option('date_format'), strtotime($invoice->order_date)); ?>
                                    </td>
                                    <td class="invoice-upload-date" data-title="<?php _e('Ημερομηνία Παραστατικού', 'wc-invoice-manager'); ?>">
                                        <?php echo date_i18n(get_option('date_format'), strtotime($invoice->upload_date)); ?>
                                    </td>
                                    <td class="invoice-status" data-title="<?php _e('Κατάσταση', 'wc-invoice-manager'); ?>">
                                        <?php if ($invoice->status === 'sent'): ?>
                                            <span class="status-sent">
                                                <?php _e('Απεστάλη', 'wc-invoice-manager'); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="status-pending">
                                                <?php _e('Εκκρεμές', 'wc-invoice-manager'); ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="invoice-actions" data-title="<?php _e('Ενέργειες', 'wc-invoice-manager'); ?>">
                                        <a href="<?php echo esc_url($this->get_download_url($invoice->id)); ?>" 
                                           class="button view" 
                                           target="_blank">
                                            <?php _e('Λήψη', 'wc-invoice-manager'); ?>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Get download URL for invoice
     */
    private function get_download_url($invoice_id) {
        return add_query_arg(array(
            'action' => 'download_invoice',
            'invoice_id' => $invoice_id,
            'nonce' => wp_create_nonce('download_invoice_' . $invoice_id)
        ), admin_url('admin-ajax.php'));
    }
    
    /**
     * Handle invoice download
     */
    public function handle_download() {
        $invoice_id = intval($_GET['invoice_id']);
        $nonce = sanitize_text_field($_GET['nonce']);
        
        if (!wp_verify_nonce($nonce, 'download_invoice_' . $invoice_id)) {
            wp_die(__('Μη έγκυρη αίτηση', 'wc-invoice-manager'));
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'wc_invoice_manager';
        
        $invoice = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $invoice_id
        ));
        
        if (!$invoice) {
            wp_die(__('Το παραστατικό δεν βρέθηκε', 'wc-invoice-manager'));
        }
        
        // Check if user is logged in and has access
        if (!is_user_logged_in()) {
            wp_die(__('Πρέπει να είστε συνδεδεμένοι για να κατεβάσετε το παραστατικό', 'wc-invoice-manager'));
        }
        
        $current_user = wp_get_current_user();
        if ($current_user->user_email !== $invoice->customer_email) {
            wp_die(__('Δεν έχετε άδεια να κατεβάσετε αυτό το παραστατικό', 'wc-invoice-manager'));
        }
        
        // Check if file exists
        if (!file_exists($invoice->file_path)) {
            wp_die(__('Το αρχείο δεν βρέθηκε στον διακομιστή', 'wc-invoice-manager'));
        }
        
        // Set headers for file download
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $invoice->file_name . '"');
        header('Content-Length: ' . filesize($invoice->file_path));
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        
        // Output file
        readfile($invoice->file_path);
        exit;
    }
}