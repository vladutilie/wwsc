/**
 * variations.js v1.0.0 (build:20181125)
 * jQuery for WWSC Project plugin
 * Copyright (c) 2018 Vlăduț Ilie
 * https://vladilie.ro
 */
jQuery( document ).ready( function( $ ) {

  /**
   * Variations actions.
   */
  var wwsc_admin_filter_products_variations = {

    /**
     * Initialization.
     */
    init: function() {
      $( '#woocommerce-product-data' ).on( 'woocommerce_variations_loaded', this.update_variations );
    },
    update_variations: function() {
      wwsc_admin_filter_products_variations.remove_variations();

      var page = parseInt( $( '.variations-pagenav .page-selector' ).val(), 10 );
      wwsc_admin_filter_products_variations.add_filtered_variations( page );
    },
    remove_variations: function() {
      $( '.woocommerce_variation' ).remove();
    },
    add_filtered_variations: function( page, per_page ) {
      page = page || 1;
			per_page = per_page || wwsc_object.variations_per_page;
      var wrapper = $( '#variable_product_options' ).find( '.woocommerce_variations' );
      wwsc_admin_filter_products_variations.block();
      $.ajax({
				url: wwsc_object.ajax_url,
				data: {
          action:     'variations_actions',
          security:   wwsc_object.nonce,
          product_id: wwsc_object.post_id,
          attributes: wrapper.data( 'attributes' ),
          page:       page,
          per_page:   per_page
        },
				type: 'POST',
				success: function( response ) {
					wrapper.empty().append( response ).attr( 'data-page', page );
					wwsc_admin_filter_products_variations.unblock();
				}
			});
    },
    /**
     * Block edit screen
     */
    block: function() {
			$( '#woocommerce-product-data' ).block({
				message: null,
				overlayCSS: {
					background: '#fff',
					opacity: 0.6
				}
			});
		},

		/**
		 * Unblock edit screen
		 */
		unblock: function() {
			$( '#woocommerce-product-data' ).unblock();
		}
  };

  wwsc_admin_filter_products_variations.init();
});
