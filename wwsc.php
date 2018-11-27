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
    // Init actions
    add_action('init', array($this, 'remove_qty_add_to_cart'));
    add_action('widgets_init', array($this, 'register_widget'));
    add_action('plugins_loaded', array($this, 'i18n'));

    // Enqueue scripts
    add_action('wp_ajax_AJAX_actions', array($this, 'AJAX_actions'));
    add_action('wp_ajax_nopriv_AJAX_actions', array($this, 'AJAX_actions'));
    add_action('admin_enqueue_scripts', array($this, 'admin_enqueue'));
    add_action('wp_ajax_variations_actions', array($this, 'variations_actions'));

    // WooCommerce Hooks
    add_action('woocommerce_product_query', array($this, 'products_by_user_role'));
    remove_action('woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10);
    add_action('woocommerce_after_shop_loop_item', array($this, 'new_template_loop_add_to_cart'));
    add_filter('woocommerce_variation_is_visible', array($this, 'filter_variations'), 10, 4);
    add_filter('woocommerce_get_variation_price', array($this, 'filter_variations'), 10, 4);
    add_filter('woocommerce_get_variation_sale_price', array($this, 'filter_variations'), 10, 4);
    add_filter('woocommerce_get_variation_regular_price', array($this, 'filter_variations'), 10, 4);
  }

  /**
   * Removes the quantity input and add to cart button.
   *
   * If the user is not logged in, this method removes the qty input
   * and add to cart button from the WC template.
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
    if (! is_user_logged_in()) {
      remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30);
    }
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
	 *
   * @todo Translation template for /lang directory.
	 */
	public function i18n()
  {
    load_plugin_textdomain('wwsc', FALSE, dirname(plugin_basename(__FILE__)) . '/lang');
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
	 * @see get_option function relied on
	 * @link https://developer.wordpress.org/reference/functions/get_option/
	 *
	 * @see WP_Query class relied on
	 * @link https://developer.wordpress.org/reference/classes/wp_query/
	 *
	 * @see wp_reset_postdata function relied on
	 * @link https://developer.wordpress.org/reference/functions/wp_reset_postdata/
	 */
	public function AJAX_actions()
  {
		check_ajax_referer('WWSC-widget-nonce', 'nonce');
    echo '<ul class="products" style="display: none;">';
    $product_id = $_GET['product_id'];
    $args = array(
      'p' => $product_id,
      'post_type' => 'product',
    );
		$wc_query = new WP_Query($args);
		if ($wc_query->have_posts()) {
			while ($wc_query->have_posts()) {
        $wc_query->the_post();
        global $product;
        echo '<div class="product">';
          echo '<a href="'. get_permalink( $wc_query->post->ID ) .'" class="woocommerce-LoopProduct-link woocommerce-loop-product__link">';
          echo woocommerce_get_product_thumbnail();
            the_title('<h2 class="woocommerce-loop-product__title">', '</h2>');
            echo '<span class="price">'. $product->get_price_html() .'</span>';
          echo '</a><br />';

          echo '<div class="product_meta">
            <span class="sku_wrapper">'. esc_html_e( 'SKU:', 'woocommerce' ) .'
              <span class="sku">';
          echo ( $sku = $product->get_sku() ) ? $sku : esc_html__( 'N/A', 'woocommerce' );
          echo '</span></span></div>';
          $this->new_template_loop_add_to_cart();
        echo '</div>';
			}
		} else {
			_e('The product ID does not exist.', 'wwsc');
		}
		wp_reset_postdata();
    echo '</ul><!-- .products -->';
    die();
  }

  /**
   * Enqueue jQuery scripts in Dashboard.
   *
   * @since 1.0.0
   *
   * @see wp_register_script function is relied on
   * @link https://developer.wordpress.org/reference/functions/wp_register_script/
   *
   * @see plugins_url function is relied on
   * @link https://developer.wordpress.org/reference/functions/plugins_url/
   *
   * @see wp_create_nonce function is relied on
   * @link https://developer.wordpress.org/reference/functions/wp_create_nonce/
   *
   * @see admin_url function is relied on
   * @link https://developer.wordpress.org/reference/functions/admin_url/
   *
   * @see absint function is relied on
   * @link https://developer.wordpress.org/reference/functions/absint/
   *
   * @see apply_filters function is relied on
   * @link https://developer.wordpress.org/reference/functions/apply_filters/
   *
   * @see wp_localize_script function is relied on
   * @link https://developer.wordpress.org/reference/functions/wp_localize_script/
   *
   * @see wp_enqueue_script function is relied on
 	 * @link https://developer.wordpress.org/reference/functions/wp_enqueue_script/
 	 *
 	 * @global object $post Stores the data of the product.
   */
  public function admin_enqueue() {
    global $post;
    wp_register_script('WWSC-WC-variations', plugins_url('admin/js/variations.min.js', __FILE__), array('jquery'), '1.0.0', true);
    $args = array(
      'post_id' => isset($post->ID) ? $post->ID : '',
      'nonce' => wp_create_nonce('WWSC-variations-nonce'),
      'ajax_url' => admin_url('admin-ajax.php'),
      'variations_per_page' => absint(apply_filters('woocommerce_admin_meta_boxes_variations_per_page', 15)),
    );
    wp_localize_script('WWSC-WC-variations', 'wwsc_object', $args);
    wp_enqueue_script('WWSC-WC-variations');
  }

  /**
	 * Variations AJAX actions for dashboard products filter.
	 *
	 * Filter products from dashboard depending on user role.
	 *
	 * @since 1.0.0
	 *
	 * @see check_ajax_referer function relied on
	 * @link https://developer.wordpress.org/reference/functions/check_ajax_referer/
	 *
	 * @see current_user_can function relied on
	 * @link https://developer.wordpress.org/reference/functions/current_user_can/
	 *
	 * @see wp_die function relied on
	 * @link https://developer.wordpress.org/reference/functions/wp_die/
	 *
	 * @see absint function relied on
	 * @link https://developer.wordpress.org/reference/functions/absint/
	 *
	 * @see get_post function relied on
	 * @link https://developer.wordpress.org/reference/functions/get_post/
	 *
	 * @see wp_get_current_user function relied on
	 * @link https://developer.wordpress.org/reference/functions/wp_get_current_user/
	 *
	 * @see get_post_custom function relied on
	 * @link https://developer.wordpress.org/reference/functions/get_post_custom/
	 *
	 * @see wp_die function relied on
	 * @link https://developer.wordpress.org/reference/functions/wp_die/
	 *
	 * @global object $post Stores the data of the product.
	 */
  public function variations_actions() {
    ob_start();
    check_ajax_referer('WWSC-variations-nonce', 'security');

    if (! current_user_can('edit_products') || empty($_POST['product_id'])) {
      wp_die(-1);
    }

    // Set $post global so its available, like within the admin screens
    global $post;
    $loop           = 0;
    $product_id     = absint($_POST['product_id']);
    $post           = get_post($product_id);
    $product_object = wc_get_product($product_id);
    $per_page       = ! empty($_POST['per_page']) ? absint($_POST['per_page']):10;
    $page           = ! empty($_POST['page']) ? absint($_POST['page']):1;
    $args           = array(
      'status'  => array('private', 'publish'),
      'type'    => 'variation',
      'parent'  => $product_id,
      'limit'   => $per_page,
      'page'    => $page,
      'orderby' => array(
        'menu_order' => 'ASC',
        'ID'         => 'DESC',
      ),
      'return'  => 'objects',
    );
    $current_user = wp_get_current_user();
    if (! in_array('b2b_retail', $current_user->roles)) {
      // shows all the products that have sku with 102-
      $args['meta_key'] = '_sku';
      $args['meta_value'] = '^102-';
      $args['meta_compare'] = 'REGEXP';
    }
    $variations = wc_get_products($args);
    if ($variations) {
      foreach ($variations as $variation_object) {
        $variation_id   = $variation_object->get_id();
        $variation      = get_post($variation_id);
        $variation_data = array_merge(array_map('maybe_unserialize', get_post_custom($variation_id)), wc_get_product_variation_attributes($variation_id)); // kept for BW compatibility.
        include ABSPATH .'/wp-content/plugins/woocommerce/includes/admin/meta-boxes/views/html-variation-admin.php';
        $loop++;
      }
    }
    wp_die();
  }

  /**
	 * Filters all the products of the store: for store page, widget and dashboard.
	 *
	 * Shows and hides the products depending logged in user role:
	 *   (a) if is logged in and is B2B Retail, the user will see all the products;
	 *   (b) if is logged in and is not a B2B Retail, the user will only see the products that have SKU starting with 102-;
	 *   (c) if is logged out, the user will see all the products without the ones that have SKU starting with 101-.
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
    $post_type = $query->get('post_type');
    if ('product' != $post_type) {
      return;
    }
    if (is_user_logged_in()) {
      // [by default] (a) the meta query shows all products
      $current_user = wp_get_current_user();
      if (!in_array('b2b_retail', $current_user->roles)) {
        // (b) shows all the products that have sku with 102-
        $meta_query[] = array(
          'key' => '_sku',
          'value' => '^102-',
          'compare' => 'REGEXP',
        );
      }
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
    echo '<div class="product_meta">
    <span class="sku_wrapper">'. esc_html_e( 'SKU:', 'woocommerce' ) .'
    <span class="sku">';
    echo ( $sku = $product->get_sku() ) ? $sku : esc_html__( 'N/A', 'woocommerce' );
    echo '</span></span></div>';
    if (!$product->is_type('variable') && is_user_logged_in()) {
      woocommerce_template_loop_add_to_cart();
      return;
    }
    if (is_user_logged_in()) {
      woocommerce_template_single_add_to_cart();
    }
  }

  /**
   * Filter the variations from the store products.
   *
   * Filter the variations according to the conditions from products_by_user_role method above.
   *
   * @since: 1.0.0
   *
   * @see wp_get_current_user function is relied on
	 * @link https://developer.wordpress.org/reference/functions/wp_get_current_user/
   *
   * @see is_user_logged_in function is relied on
	 * @link https://developer.wordpress.org/reference/functions/is_user_logged_in/
   *
   * @global object $product Stores the data of the product for WC template.
   */
  public function filter_variations($bool, $variation_id, $product_id, $variation) {
    $current_user = wp_get_current_user();
    $starting = substr($variation->sku, 0, 4);
    if (is_user_logged_in() && !in_array('b2b_retail', $current_user->roles) && $starting === '102-') {
      return true;
    } else if (is_user_logged_in() && in_array('b2b_retail', $current_user->roles)) {
      return true;
    } else if (!is_user_logged_in() && $starting === '101-') {
      return false;
    } else {
      return false;
    }
  }
}
$wwsc = new WWSC_Plugin();
