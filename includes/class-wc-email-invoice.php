<?php
/**
 * WooCommerce Email Class for Invoices
 */

if (!defined('ABSPATH')) {
    exit;
}

class WC_Email_Invoice extends WC_Email {
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->id = 'invoice';
        $this->title = __('Παραστατικό', 'wc-invoice-manager');
        $this->description = __('Email που στέλνεται στους πελάτες με το παραστατικό της παραγγελίας.', 'wc-invoice-manager');
        
        $this->heading = __('Παραστατικό Παραγγελίας', 'wc-invoice-manager');
        $this->subject = __('Παραστατικό για την παραγγελία #{order_number}', 'wc-invoice-manager');
        
        $this->template_html = 'emails/customer-invoice.php';
        $this->template_plain = 'emails/plain/customer-invoice.php';
        
        // Triggers for this email
        add_action('wc_invoice_send', array($this, 'trigger'), 10, 3);
        
        // Call parent constructor
        parent::__construct();
    }
    
    /**
     * Trigger the sending of this email
     */
    public function trigger($order_id, $invoice_data, $customer_email) {
        if ($order_id) {
            $this->object = wc_get_order($order_id);
            $this->recipient = $customer_email;
            $this->invoice_data = $invoice_data;
            
            if (!$this->is_enabled() || !$this->get_recipient()) {
                return;
            }
            
            $this->send(
                $this->get_recipient(),
                $this->get_subject(),
                $this->get_content(),
                $this->get_headers(),
                $this->get_attachments()
            );
        }
    }
    
    /**
     * Get email subject
     */
    public function get_subject() {
        return str_replace('{order_number}', $this->object->get_order_number(), $this->subject);
    }
    
    /**
     * Get email heading
     */
    public function get_heading() {
        return str_replace('{order_number}', $this->object->get_order_number(), $this->heading);
    }
    
    /**
     * Get content html
     */
    public function get_content_html() {
        return wc_get_template_html(
            $this->template_html,
            array(
                'order' => $this->object,
                'invoice_data' => $this->invoice_data,
                'email_heading' => $this->get_heading(),
                'additional_content' => $this->get_additional_content(),
                'sent_to_admin' => false,
                'plain_text' => false,
                'email' => $this
            ),
            '',
            WC_INVOICE_MANAGER_PLUGIN_PATH . 'templates/'
        );
    }
    
    /**
     * Get content plain
     */
    public function get_content_plain() {
        return wc_get_template_html(
            $this->template_plain,
            array(
                'order' => $this->object,
                'invoice_data' => $this->invoice_data,
                'email_heading' => $this->get_heading(),
                'additional_content' => $this->get_additional_content(),
                'sent_to_admin' => false,
                'plain_text' => true,
                'email' => $this
            ),
            '',
            WC_INVOICE_MANAGER_PLUGIN_PATH . 'templates/'
        );
    }
    
    /**
     * Get attachments
     */
    public function get_attachments() {
        if (!empty($this->invoice_data['file_path']) && file_exists($this->invoice_data['file_path'])) {
            return array($this->invoice_data['file_path']);
        }
        return array();
    }
    
    /**
     * Initialise settings form fields
     */
    public function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title' => __('Ενεργοποίηση/Απενεργοποίηση', 'wc-invoice-manager'),
                'type' => 'checkbox',
                'label' => __('Ενεργοποίηση αυτού του τύπου email', 'wc-invoice-manager'),
                'default' => 'yes'
            ),
            'subject' => array(
                'title' => __('Θέμα', 'wc-invoice-manager'),
                'type' => 'text',
                'description' => sprintf(__('Αυτό το θέμα ελέγχεται από το <code>%s</code> hook.', 'wc-invoice-manager'), 'woocommerce_email_subject_' . $this->id),
                'placeholder' => $this->subject,
                'default' => ''
            ),
            'heading' => array(
                'title' => __('Επικεφαλίδα Email', 'wc-invoice-manager'),
                'type' => 'text',
                'description' => sprintf(__('Αυτή η επικεφαλίδα ελέγχεται από το <code>%s</code> hook.', 'wc-invoice-manager'), 'woocommerce_email_heading_' . $this->id),
                'placeholder' => $this->heading,
                'default' => ''
            ),
            'additional_content' => array(
                'title' => __('Επιπλέον Περιεχόμενο', 'wc-invoice-manager'),
                'type' => 'textarea',
                'description' => __('Επιπλέον περιεχόμενο που θα εμφανίζεται στο κάτω μέρος του email.', 'wc-invoice-manager'),
                'placeholder' => __('Ευχαριστούμε που μας επιλέξατε!', 'wc-invoice-manager'),
                'default' => ''
            ),
            'email_type' => array(
                'title' => __('Τύπος Email', 'wc-invoice-manager'),
                'type' => 'select',
                'description' => __('Επιλέξτε ποια μορφή email θέλετε να στέλνετε.', 'wc-invoice-manager'),
                'default' => 'html',
                'class' => 'email_type wc-enhanced-select',
                'options' => array(
                    'plain' => __('Απλό Κείμενο', 'wc-invoice-manager'),
                    'html' => __('HTML', 'wc-invoice-manager'),
                    'multipart' => __('Multipart', 'wc-invoice-manager')
                )
            )
        );
    }
}