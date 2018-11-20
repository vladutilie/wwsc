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

require_once('includes/class-wwsc-widget.php');

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
	 * Adds hooks to extend the functionality of the WooCommerce plugin.
	 *
	 * @since 1.0.0
	 *
	 * @see add_action function is relied on
	 * @link https://developer.wordpress.org/reference/functions/add_action/
	 *
	 * @see remove_action function is relied on
	 * @link https://developer.wordpress.org/reference/functions/remove_action/
	 */
  public function __construct() {
    add_action('plugins_loaded', array($this, 'i18n'));
    add_action('widgets_init', array($this, 'register_widget'));
    add_action('woocommerce_product_query', array($this, 'products_by_user_role'));
    add_action('init', array($this, 'remove_qty_add_to_cart'));
    add_action('wp_ajax_AJAX_actions', array($this, 'AJAX_actions'));
    add_action('wp_ajax_nopriv_AJAX_actions', array($this, 'AJAX_actions'));

    remove_action('woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10);
    add_action('woocommerce_after_shop_loop_item', array($this, 'new_template_loop_add_to_cart'));
  }

  /**
	 * Internationalization method.
	 *
	 * Loads translations in the plugin.
	 *
	 * @since 1.0.0
	 *
	 * @see load_plugin_textdomain function is relied on
	 * @link https://developer.wordpress.org/reference/functions/load_plugin_textdomain/
	 *
	 * @see plugin_basename function is relied on
	 * @link https://developer.wordpress.org/reference/functions/plugin_basename/
	 */
	public function i18n()
  {
    load_plugin_textdomain('wwsc', FALSE, dirname(plugin_basename(__FILE__)) . '/lang');
	}

  /**
   * Register widget.
   *
   * Register the plugins's Widget with WordPress Widgets API.
   *
   * @since: 1.0.0
   *
   * @see register_widget function
   * @link https://developer.wordpress.org/reference/functions/wp_add_privacy_policy_content/
   */
   public function register_widget()
   {
     register_widget('WWSC_Widget');
   }

  /**
	 * AJAX functionalities.
	 *
	 * Fires when the document is ready. Populates the widget content with WC products.
	 *
	 * @since 1.0.0
	 *
	 * @see check_ajax_referer function relied on
	 * @link https://developer.wordpress.org/reference/functions/check_ajax_referer/
	 *
	 * @see is_user_logged_in function relied on
	 * @link https://developer.wordpress.org/reference/functions/is_user_logged_in/
	 *
	 * @see wp_get_current_user function relied on
	 * @link https://developer.wordpress.org/reference/functions/wp_get_current_user/
	 *
	 * @see WP_Query class relied on
	 * @link https://developer.wordpress.org/reference/classes/wp_query/
	 *
	 * @see wp_reset_postdata function relied on
	 * @link https://developer.wordpress.org/reference/functions/wp_reset_postdata/
	 */
	public function AJAX_actions()
  {
		check_ajax_referer('WWSC-AJAX-nonce', 'nonce');
    echo '<ul class="products" style="display: none;">';
    $args = array(
      'post_type' => 'product',
      'posts_per_page' => 20
    );
    if (is_user_logged_in()) {
      $current_user = wp_get_current_user();
      if (!in_array('b2b_retail', $current_user->roles)) {
        // shows all the products that have sku with 102-
        $args = array(
          'post_type' => 'product',
          'meta_key' => '_sku',
          'meta_value' => '^102-',
          'meta_compare' => 'REGEXP',
        );
      }
    } else {
      // hides all the products with sku 101-
      $args = array(
        'post_type' => 'product',
        'meta_key' => '_sku',
        'meta_value' => '^101-',
        'meta_compare' => 'NOT REGEXP',
      );
    }
		$wc_query = new WP_Query($args);
		if ($wc_query->have_posts()) {
			while ($wc_query->have_posts()) {
        $wc_query->the_post();
				wc_get_template_part('content', 'product');
			}
		} else {
			_e('No products found');
		}
		wp_reset_postdata();
    echo '</ul><!-- .products -->';
    die();
  }

  /**
	 * Filters all the products of the store.
	 *
	 * Shows and hides the products depending logged in user role:
	 * (a) if is logged in and is B2B Retail, the user will see all the products
	 * (b) if is logged in and is not a B2B Retail, the user will only see the products that have SKU starting with 102-
	 * (c) if is logged out, the user will see all the products without the ones that have SKU starting with 101-
	 *
	 * @since 1.0.0
	 *
	 * @see is_user_logged_in function is relied on
	 * @link https://developer.wordpress.org/reference/functions/is_user_logged_in/
	 *
	 * @see wp_get_current_user function is relied on
	 * @link https://developer.wordpress.org/reference/functions/wp_get_current_user/
	 *
	 * @param object $query The query of the WC loop.
	 */
  public function products_by_user_role($query)
  {
    $meta_query = (array)$query->get('meta_query');
    if (is_user_logged_in()) {
      $current_user = wp_get_current_user();
      if (!in_array('b2b_retail', $current_user->roles)) {
        // (b) shows all the products that have sku with 102-
        $meta_query[] = array(
          'key' => '_sku',
          'value' => '^102-',
          'compare' => 'REGEXP',
        );
      } // (a) else the meta query shows all products (by default)
    } else {
      // (c) hides all the products with sku 101-
      $meta_query[] = array(
        'key' => '_sku',
        'value' => '^101-',
        'compare' => 'NOT REGEXP',
      );
    }
    $query->set('meta_query', $meta_query);
  }

  /**
   * Removes the quantity input and add to cart button.
   *
   *  If the user is not logged in, the method removes the qty input
   *  and add to cart button from the WC template.
   *
   * @since: 1.0.0
   *
   * @see is_user_logged_in function is relied on
	 * @link https://developer.wordpress.org/reference/functions/is_user_logged_in/
   *
   * @see remove_action function is relied on
	 * @link https://developer.wordpress.org/reference/functions/remove_action/
   */
  public function remove_qty_add_to_cart()
  {
    if (!is_user_logged_in()) {
      remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30);
    }
  }

  /**
   * Replace the WC product template.
   *
   * Checks if the product is not variable and if the user is logged in
   * and shows the variations and the add to cart button.
   *
   * @since: 1.0.0
   *
   * @see is_user_logged_in function is relied on
	 * @link https://developer.wordpress.org/reference/functions/is_user_logged_in/
   *
   * @global object $product Stores the data of the product for WC template.
   */
  public function new_template_loop_add_to_cart()
  {
    global $product;
    if (!$product->is_type('variable') && is_user_logged_in()) {
      woocommerce_template_loop_add_to_cart();
      return;
    }
    if (is_user_logged_in()) {
      woocommerce_template_single_add_to_cart();
    }
  }
}
$wwsc = new WWSC_Plugin();
