<?php
/**
 * Plugin Name: WooCommerce Invoice Manager
 * Plugin URI: https://kubik.gr
 * Description: A plugin to manage and send PDF invoices to WooCommerce customers. Allows manual PDF upload and automatic email delivery.
 * Version: 1.0.0
 * Author: KUBIK
 * Author URI: https://kubik.gr
 * Text Domain: wc-invoice-manager
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * WC requires at least: 5.0
 * WC tested up to: 8.5
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Check if WooCommerce is active
if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    add_action('admin_notices', function() {
        echo '<div class="notice notice-error"><p>' . __('WooCommerce Invoice Manager requires WooCommerce to be installed and active.', 'wc-invoice-manager') . '</p></div>';
    });
    return;
}

// Define plugin constants
define('WC_INVOICE_MANAGER_VERSION', '1.0.0');
define('WC_INVOICE_MANAGER_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WC_INVOICE_MANAGER_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('WC_INVOICE_MANAGER_UPLOAD_DIR', WP_CONTENT_DIR . '/uploads/invoices/');

/**
 * Main WooCommerce Invoice Manager Class
 */
class WC_Invoice_Manager {
    
    /**
     * Single instance of the class
     */
    protected static $_instance = null;
    
    /**
     * Main Instance
     */
    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    /**
     * Initialize the plugin
     */
    public function init() {
        $this->includes();
        $this->hooks();
    }
    
    /**
     * Include required files
     */
    private function includes() {
        require_once WC_INVOICE_MANAGER_PLUGIN_PATH . 'includes/class-admin.php';
        require_once WC_INVOICE_MANAGER_PLUGIN_PATH . 'includes/class-frontend.php';
        require_once WC_INVOICE_MANAGER_PLUGIN_PATH . 'includes/class-file-manager.php';
        require_once WC_INVOICE_MANAGER_PLUGIN_PATH . 'includes/class-email-manager.php';
    }
    
    /**
     * Hook into actions and filters
     */
    private function hooks() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
    }
    
    /**
     * Load plugin textdomain
     */
    public function load_textdomain() {
        load_plugin_textdomain('wc-invoice-manager', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Create upload directory structure
        $this->create_upload_directories();
        
        // Create database table for invoice tracking
        $this->create_database_table();
        
        // Set default options
        add_option('wc_invoice_manager_version', WC_INVOICE_MANAGER_VERSION);
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clean up if needed
    }
    
    /**
     * Create upload directories
     */
    private function create_upload_directories() {
        $upload_dir = WC_INVOICE_MANAGER_UPLOAD_DIR;
        
        if (!file_exists($upload_dir)) {
            wp_mkdir_p($upload_dir);
            
            // Create .htaccess file for security
            $htaccess_content = "Options -Indexes\n";
            $htaccess_content .= "deny from all\n";
            file_put_contents($upload_dir . '.htaccess', $htaccess_content);
        }
    }
    
    /**
     * Create database table for invoice tracking
     */
    private function create_database_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wc_invoice_manager';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            order_id bigint(20) NOT NULL,
            customer_email varchar(255) NOT NULL,
            file_name varchar(255) NOT NULL,
            file_path varchar(500) NOT NULL,
            upload_date datetime DEFAULT CURRENT_TIMESTAMP,
            sent_date datetime NULL,
            status varchar(20) DEFAULT 'pending',
            PRIMARY KEY (id),
            KEY order_id (order_id),
            KEY customer_email (customer_email)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'woocommerce',
            __('Παραστατικά', 'wc-invoice-manager'),
            __('Παραστατικά', 'wc-invoice-manager'),
            'manage_woocommerce',
            'wc-invoice-manager',
            array($this, 'admin_page')
        );
    }
    
    /**
     * Admin page callback
     */
    public function admin_page() {
        $admin = new WC_Invoice_Manager_Admin();
        $admin->display_page();
    }
    
    /**
     * Enqueue frontend scripts
     */
    public function enqueue_scripts() {
        wp_enqueue_style('wc-invoice-manager-style', WC_INVOICE_MANAGER_PLUGIN_URL . 'assets/css/style.css', array(), WC_INVOICE_MANAGER_VERSION);
    }
    
    /**
     * Enqueue admin scripts
     */
    public function admin_enqueue_scripts($hook) {
        if (strpos($hook, 'wc-invoice-manager') !== false) {
            wp_enqueue_script('wc-invoice-manager-admin', WC_INVOICE_MANAGER_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), WC_INVOICE_MANAGER_VERSION, true);
            wp_enqueue_style('wc-invoice-manager-admin-style', WC_INVOICE_MANAGER_PLUGIN_URL . 'assets/css/admin.css', array(), WC_INVOICE_MANAGER_VERSION);
        }
    }
}

// Initialize the plugin
function wc_invoice_manager() {
    return WC_Invoice_Manager::instance();
}

// Start the plugin
wc_invoice_manager();