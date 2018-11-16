<?php
/*
Plugin Name:  WWSC Project
Plugin URI:
Description:  Widget that shows Woo products filtered by certain SKU.
Version:      1.0.0
Author:       Vlăduț Ilie
Author URI:		https://vladilie.ro/
License:      GPL3
License URI:	https://www.gnu.org/licenses/gpl-3.0.html
Text Domain:	wwsc
Domain Path:	/lang
*/

defined('ABSPATH') || exit;

require_once( 'includes/class-wwsc-widget.php' );

/**
 * The main class of the plugin.
 *
 * Coordinates all the classes for doing a great work!
 *
 * @since 1.0.0
 */
class WWSC_Plugin
{

  /**
	 * Constructor function.
	 *
	 * Adds hooks.
	 *
	 * @since 1.0.0
	 *
	 * @see add_action function is relied on
	 * @link https://developer.wordpress.org/reference/functions/add_action/
	 */
  public function __construct() {
    add_action('plugins_loaded', array($this, 'i18n'));
    add_action('widgets_init', array($this, 'register_widget'));
    add_action('wp_ajax_AJAX_actions', array($this, 'AJAX_actions'));
    add_action('woocommerce_product_query', array($this, 'products_by_user_role'));
  }

  /**
	 * Filters all the products of the store.
	 *
	 * Shows and hides the products depending logged in user role:
	 * - if is logged in and is B2B Retail, the user will see all the products
	 * - if is logged in and is not a B2B Retail, the user will only see the products that have SKU starting with 102-
	 * - if is logged out, the user will see all the products without the ones that have SKU starting with 101-
	 *
	 * @since 1.0.0
	 *
	 * @see add_action function is relied on
	 * @link https://developer.wordpress.org/reference/functions/add_action/
	 */
  public function products_by_user_role( $query )
  {
    if (is_user_logged_in()) {
      $current_user = wp_get_current_user();
      if (! in_array('b2b_retail', $current_user->roles)) {
        // shows all the products that have sku with 102-
        $query->set('meta_query', array(
          array(
            'key' => '_sku',
            'value' => '^102-',
            'compare' => 'REGEXP',
          )
        ) );
      }
    } else {
      // hides all the products with sku 101-
      $query->set('meta_query', array(
        array(
          'key' => '_sku',
          'value' => '^101-',
          'compare' => 'NOT REGEXP',
        )
      ));
    }
  }

  /**
	 * Load translation.
	 *
	 * Load translation in Romanian language for the plugin.
	 *
	 * @since 1.0.0
	 *
	 * @see load_plugin_textdomain function is relied on
	 * @link https://developer.wordpress.org/reference/functions/load_plugin_textdomain/
	 *
	 * @see plugin_basename function is relied on
	 * @link https://developer.wordpress.org/reference/functions/plugin_basename/
	 */
	public function i18n() {
		load_plugin_textdomain('wwsc', FALSE, dirname(plugin_basename(__FILE__)) . '/lang');
	}

  /**
   * Register widget
   *
   * @since: 1.0.0
   *
   * @see register_widget function
   * @link https://developer.wordpress.org/reference/functions/wp_add_privacy_policy_content/
   */
   public function register_widget() {
     register_widget('WWSC_Widget');
   }

  /**
	 * AJAX functionalities.
	 *
	 * Fires when an `Add to cart` button from widget is clicked.
	 * The function adds the product to the cart.
	 *
	 * @since 1.0.0
	 *
	 * @see check_ajax_referer function relied on
	 * @link https://developer.wordpress.org/reference/functions/check_ajax_referer/
	 *
	 * @see absint function relied on
	 * @link https://developer.wordpress.org/reference/functions/absint/
	 * @see wp_send_json_success function relied on
	 * @link https://developer.wordpress.org/reference/functions/wp_send_json_success/
	 *
	 * @global Object $wpdb Used for database operations.
	 */
	public function AJAX_actions() {
		check_ajax_referer('WWSC-AJAX-nonce', 'nonce');
    $response = array();
    $data = isset($_POST) ? $_POST : array();
    $product_id = absint($data['product_id']);
    $quantity = absint($data['quantity']);
    $variation_id = absint($data['variation_id']);
    $result = WC()->cart->add_to_cart($product_id, $quantity, $variation_id);
    if ( $result ) {
      $response['return'] = true;
    } else {
      $response['return'] = false;
    }
    wp_send_json_success($response);
  }
}
$wwsc = new WWSC_Plugin();
