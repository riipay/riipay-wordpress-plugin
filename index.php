<?php
/**
 * Plugin Name: Riipay for Woocommerce
 * Description: Start accepting payments with zero-interest instalments using credit or debit cards now!
 * Version: 1.0.17
 * Author: Riipay
 * Author URI: https://riipay.my
 * WC requires at least: 3.2
 * WC tested up to: 5.4
 */

defined( 'ABSPATH' ) || exit;

class Woocommerce_Riipay {

    const RIIPAY_MIN_WOOCOMMERCE_VER = '3.0';
    const RIIPAY_MIN_PHP_VER = '7.0';

    private static $instance;
    private $notices = array();

    public function __construct()
    {
        register_activation_hook( __FILE__, array( $this, 'activation_check' ) );

        add_action('admin_init', array( $this, 'check_environment' ) );
        add_action( 'admin_notices', array( $this, 'admin_notices' ), 15);

        add_action( 'plugins_loaded', array( $this, 'riipay_init') );
        add_action( 'plugins_loaded', array( $this, 'get_current_plugin_version') );
        add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'riipay_links'));
        add_filter( 'woocommerce_payment_gateways', array( $this, 'add_riipay') );
    }

    public function check_environment()
    {
        if ( !$this->is_woocommerce_compatible() && is_plugin_active( plugin_basename( __FILE__ ) ) ) {
            $this->add_admin_notice('prompt_woocommerce_version_update', 'error', sprintf(__('<strong>Activation Error:</strong> You must have the <a href="%s" target="_blank">WooCommerce</a> core version %s+ for the Riipay for WooCommerce plugin to activate.', 'riipay'), 'https://woocommerce.com', self::RIIPAY_MIN_WOOCOMMERCE_VER));
            deactivate_plugins(plugin_basename( __FILE__ ));
            if (isset($_GET['activate'])) {
                unset($_GET['activate']);
            }
            return false;
        }
    }

    private function is_environment_compatible() {

        return version_compare( PHP_VERSION, self::RIIPAY_MIN_PHP_VER, '>=' );
    }

    public function is_woocommerce_compatible()
    {
        if ( !self::RIIPAY_MIN_WOOCOMMERCE_VER ) {
            return true;
        }

        return defined('WC_VERSION') && version_compare(WC_VERSION, self::RIIPAY_MIN_WOOCOMMERCE_VER, '>=');
    }

    public function activation_check()
    {
        $environment_warning = $this->get_environment_warning(true);

        if ($environment_warning) {
            deactivate_plugins(plugin_basename( __FILE__ ));
            wp_die($environment_warning);
        }
    }

    public function get_environment_warning($during_activation = false)
    {
        if ( !$this->is_environment_compatible() ) {
            if ($during_activation) {
                $message = __('The plugin could not be activated. The minimum PHP version required for this plugin is %1$s. You are running %2$s. Please contact your web host to upgrade your server\'s PHP version.', 'riipay');
            } else {
                $message = __('The plugin has been deactivated. The minimum PHP version required for this plugin is %1$s. You are running %2$s.', 'riipay');
            }
            return sprintf($message, self::RIIPAY_MIN_PHP_VER, phpversion());
        }

        if (!class_exists('WC_Payment_Gateway')) {
            if ($during_activation) {
                return __('The plugin could not be activated. Riipay for WooCommerce depends on the latest version of <a href="http://wordpress.org/extend/plugins/woocommerce/">WooCommerce</a> to work.', 'riipay');
            }
            return __('The plugin has been deactivated. Riipay for WooCommerce depends on the latest version of <a href="http://wordpress.org/extend/plugins/woocommerce/">WooCommerce</a> to work.', 'riipay');
        }

        return false;
    }

    public function riipay_init()
    {
        if ( !class_exists( 'WC_Payment_Gateway' )) {
            return;
        }

        include_once('src/riipay.php');
        include_once('src/riipay-theme.php');
        include_once('src/riipay-surcharge.php');
    }

    public function add_riipay( $methods )
    {
        $methods[] = 'riipay';
        return $methods;
    }


    public function riipay_links( $links )
    {
        $plugin_links = array(
            '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=riipay' ) . '">' . __( 'Settings', 'riipay' ) . '</a>',
        );

        return array_merge( $plugin_links, $links );
    }

    public function add_admin_notice($slug, $class, $message)
    {
        $this->notices[$slug] = array(
            'class' => $class,
            'message' => $message,
        );
    }

    public function get_current_plugin_version()
    {
        $plugin_data = get_file_data(__FILE__, array('Version' => 'Version'), false);
        $plugin_version = $plugin_data['Version'];
        define('RIIPAY_CURRENT_VERSION', $plugin_version);
    }

    public function admin_notices()
    {
        foreach ( (array) $this->notices as $notice_key => $notice ) {

            ?>
            <div class="<?php echo esc_attr( $notice['class'] ); ?>">
                <p><?php echo wp_kses( $notice['message'], array( 'a' => array( 'href' => array() ) ) ); ?></p>
            </div>
            <?php
        }
    }

    public static function instance()
    {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

}

Woocommerce_Riipay::instance();



