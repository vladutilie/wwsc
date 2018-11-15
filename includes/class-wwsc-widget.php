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
    if (is_user_logged_in()) {
      $user = wp_get_current_user();
      if (in_array('b2b_retail', (array) $user->roles)) {
          // The user is logged in and is a B2B Retail.
          $params = array(
            'posts_per_page' => 10,
            'post_type' => 'product',
         );
      } else {
        // The user is logged in, but is not a B2B Retail.
        $params = array(
          'posts_per_page' => 10,
          'post_type' => 'product',
          'meta_key' => '_sku',
          'meta_value' => '^102-',
          'meta_compare' => 'REGEXP',
       );
      }
    } else {
      // The user is not logged in.
      $params = array(
        'posts_per_page' => 10,
        'post_type' => 'product',
        'meta_key' => '_sku',
        'meta_value' => '^101-',
        'meta_compare' => 'NOT REGEXP',
     );
    }
    $query = new WP_Query($params);
    if ($query->have_posts()) {
      while ($query->have_posts()) {
        $query->the_post();
				$product = wc_get_product(get_the_ID());
				if($product->is_type('variable')) {
					wp_enqueue_script('wc-add-to-cart-variation');
					$attribute_keys = array_keys($product->get_variation_attributes());
					echo '<h2 class="woocommerce-loop-product__title">'. $product->post->post_title .'</h2>
					<form class="variations_form cart" method="post" enctype="multipart/form-data" data-product_id="'. absint($product->id) .'" data-product_variations="'. htmlspecialchars(json_encode($product->get_available_variations())) .'">
						<span class="price">'. $product->get_price_html() .'</span><br/>
						<span class="sku_wrapper">SKU: <span class="sku" data-o_content="'. $product->get_sku() .'">'. $product->get_sku() .'</span></span>';
						do_action('woocommerce_before_variations_form');
						if (empty($product->get_available_variations()) && false !== $product->get_available_variations()) {
							echo '<p class="stock out-of-stock">'. __('This product is currently out of stock and unavailable.', 'woocommerce') .'</p>';
						} else {
							echo '<table class="variations" cellspacing="0">
								<tbody>';
									foreach ($product->get_variation_attributes() as $attribute_name => $options) {
										echo '<tr>
											<td class="label"><label for="'. sanitize_title($attribute_name) .'">'. wc_attribute_label($attribute_name) .'</label></td>
											<td class="value">';
													$selected = isset($_REQUEST[ 'attribute_' . sanitize_title($attribute_name) ]) ? wc_clean(urldecode($_REQUEST[ 'attribute_' . sanitize_title($attribute_name) ])) : $product->get_variation_default_attribute($attribute_name);
													wc_dropdown_variation_attribute_options(array('options' => $options, 'attribute' => $attribute_name, 'product' => $product, 'selected' => $selected));
													echo end($attribute_keys) === $attribute_name ? apply_filters('woocommerce_reset_variations_link', '<a class="reset_variations" href="#">' . __('Clear', 'woocommerce') . '</a>') : '';
											echo '</td>
										</tr>';
									}
								echo '</tbody>
							</table>';
								if (is_user_logged_in()) {
									do_action('woocommerce_before_add_to_cart_button');
									echo '<div class="single_variation_wrap">';
										do_action('woocommerce_before_single_variation');
										/**
										 * woocommerce_single_variation hook. Used to output the cart button and placeholder for variation data.
										 * @since 2.4.0
										 * @hooked woocommerce_single_variation - 10 Empty div for variation data.
										 * @hooked woocommerce_single_variation_add_to_cart_button - 20 Qty and cart button.
										 */
										do_action('woocommerce_single_variation');
										/**
										 * woocommerce_after_single_variation Hook.
										 */
										do_action('woocommerce_after_single_variation');
									echo '</div>';
									do_action('woocommerce_after_add_to_cart_button');
								}
							}
						do_action('woocommerce_after_variations_form');
						echo '</form>';
					} else {
						echo '<h2 class="woocommerce-loop-product__title">'. $product->post->post_title .'</h2>
						<form class="cart" action="'. esc_url(get_permalink()) .'" method="post" enctype="multipart/form-data">
						<span class="price">'. $product->get_price_html() .'</span><br/>'.
						'<span class="sku_wrapper">SKU: <span class="sku" data-o_content="101-">'. $product->get_sku() .'</span></span>';
						if (is_user_logged_in()) {
							woocommerce_quantity_input(array(
								'min_value'   => apply_filters('woocommerce_quantity_input_min', $product->get_min_purchase_quantity(), $product),
								'max_value'   => apply_filters('woocommerce_quantity_input_max', $product->get_max_purchase_quantity(), $product),
								'input_value' => isset($_POST['quantity']) ? wc_stock_amount($_POST['quantity']) : $product->get_min_purchase_quantity(),
							));
							echo '<button type="submit" name="add-to-cart" value="'. esc_attr($product->get_id()) .'" class="button product_type_simple add_to_cart_button ajax_add_to_cart">Add to cart</button>';
						}
					echo '</form>';
				}
				echo '<hr/>';
      }
      wp_reset_postdata();
    } else {
      _e('No Products', 'wwsc');
    }
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
	* @see wp_enqueue_script function is relied on
	* @link https://developer.wordpress.org/reference/functions/wp_enqueue_script/
	*
	* @see wp_localize_script function is relied on
	* @link https://developer.wordpress.org/reference/functions/wp_localize_script/
	*/
 private function enqueue() {
	 wp_register_script('WWSC-AJAX', plugins_url('../public/js/main.min.js', __FILE__), array('jquery'), '1.0.0', true);
	 $args = array(
		 'nonce' => wp_create_nonce('WWSC-AJAX-nonce'),
		 'ajaxurl' => admin_url('admin-ajax.php'),
		 'msgs' => array(
			 'success' => __('The product has been added successfully to your cart.', 'wwsc'),
			 'failure' => __('An error has been occurred during adding your product to the cart. Please try again.', 'wwsc'),
		 )
	);
	 wp_localize_script('WWSC-AJAX', 'wwsc_object', $args);
	 wp_enqueue_script('WWSC-AJAX');
 }
} // End WWSC_Widget class
