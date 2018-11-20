<?php
defined('ABSPATH') || exit;
/**
 * WWSC Widget class.
 *
 * WWSC class for setting the widget and show it on the site.
 *
 * @since: 1.0.0
 *
 * @see	WP_Widget class
 * @link https://developer.wordpress.org/reference/classes/wp_widget/
 */
class WWSC_Widget extends WP_Widget
{

	/**
	 * Register widget with WordPress. Class constructor with initialization.
	 *
	 * @since: 1.0.0
	 */
	public function __construct()
  {
		parent::__construct(
			'WWSC_Widget', // Widget Base ID
			__('WWSC Widget', 'wwsc'), // Widget name
			/* translators: Description of the Widget in Dashboard */
			array('description' => __('Put me in the site :)', 'wwsc'))
		);
	}

	/**
	 * Front-end display of WWSC Widget.
	 *
	 * @since: 1.0.0
	 *
	 * @see WP_Widget::widget()
	 * @link https://developer.wordpress.org/reference/classes/wp_widget/widget/
	 *
	 * @param array $args      Widget arguments.
	 * @param array $instance  Saved values from database.
	 */
	public function widget($args, $instance)
  {
		$this->enqueue();
		$title = __('WWSC Products', 'wwsc');
		echo $args['before_widget'];
		if (! empty($title)) {
			echo $args['before_title'] . $title . $args['after_title'];
		}
    echo '<div class="wwsc-widget"></div>';
		echo $args['after_widget'];
	}

	/**
	* Enqueue jQuery scripts.
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
	* @see plugin_dir_url function is relied on
	* @link https://developer.wordpress.org/reference/functions/plugin_dir_url/
	*
	* @see wp_localize_script function is relied on
	* @link https://developer.wordpress.org/reference/functions/wp_localize_script/
	*
	* @see wp_enqueue_script function is relied on
	* @link https://developer.wordpress.org/reference/functions/wp_enqueue_script/
	*/
 private function enqueue() {
	 wp_register_script('WWSC-AJAX', plugins_url('../public/js/main.min.js', __FILE__), array('jquery'), '1.0.0', true);
	 $args = array(
		 'nonce' => wp_create_nonce('WWSC-AJAX-nonce'),
		 'ajaxurl' => admin_url('admin-ajax.php'),
		 'wc_url' => plugin_dir_url(__FILE__) .'../../woocommerce/assets/js/frontend/add-to-cart-variation.min.js',
		 'msgs' => array(
			 'success' => __('The product has been added successfully to your cart.', 'wwsc'),
			 'failure' => __('An error has been occurred during adding your product to the cart. Please try again.', 'wwsc'),
		 )
	);
	 wp_localize_script('WWSC-AJAX', 'wwsc_object', $args);
	 wp_enqueue_script('WWSC-AJAX');
	 wp_enqueue_script('wc-add-to-cart-variation');
 }
} // End WWSC_Widget class
