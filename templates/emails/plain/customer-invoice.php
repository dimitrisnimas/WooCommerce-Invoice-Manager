<?php
/**
 * Customer Invoice Email Template (Plain Text)
 */

if (!defined('ABSPATH')) {
    exit;
}

echo "= " . $email_heading . " =\n\n";

printf(__('Αγαπητέ/ή %s,', 'wc-invoice-manager'), $order->get_formatted_billing_full_name());
echo "\n\n";

_e('Σας στέλνουμε το παραστατικό για την παραγγελία σας.', 'wc-invoice-manager');
echo "\n\n";

echo "= " . __('Στοιχεία Παραγγελίας', 'wc-invoice-manager') . " =\n";
printf(__('Αριθμός Παραγγελίας: #%s', 'wc-invoice-manager'), $order->get_order_number());
echo "\n";
printf(__('Ημερομηνία: %s', 'wc-invoice-manager'), $order->get_date_created()->date_i18n(get_option('date_format')));
echo "\n";
printf(__('Σύνολο: %s', 'wc-invoice-manager'), $order->get_formatted_order_total());
echo "\n\n";

_e('Το παραστατικό είναι επισυνάπτεται σε αυτό το email σε μορφή PDF.', 'wc-invoice-manager');
echo "\n\n";

_e('Εάν έχετε οποιεσδήποτε ερωτήσεις, μη διστάσετε να επικοινωνήσετε μαζί μας.', 'wc-invoice-manager');
echo "\n\n";

_e('Ευχαριστούμε που μας επιλέξατε!', 'wc-invoice-manager');
echo "\n\n";

_e('Με εκτίμηση,', 'wc-invoice-manager');
echo "\n";
echo get_bloginfo('name');
echo "\n";

if ($additional_content) {
    echo "\n" . $additional_content . "\n";
}

echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

echo apply_filters('woocommerce_email_footer_text', get_option('woocommerce_email_footer_text'));