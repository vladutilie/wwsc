jQuery(document).ready(function($) {
  var variation_data = $('form.variations_form').attr('data-product_variations');
  var variation_data = JSON.parse(variation_data);
  $('.single_add_to_cart_button').prop('disabled', true);
  $('.variations_form').on('change', 'select', function() {
    var sku_changed = false;
    $('.single_add_to_cart_button').prop('disabled', true);
    for (var i = 0; i < variation_data.length; i++) {
  		var variation = variation_data[i];
      if ($('#color').val() === variation.attributes.attribute_color &&
           $('#size').val() === variation.attributes.attribute_size) {
  			$('.sku_wrapper > .sku').text(variation.sku);
        sku_changed = true;
        $('.single_add_to_cart_button').prop('disabled', false);
      }
      if (! sku_changed) {
        $('.sku_wrapper > .sku').text($('.sku_wrapper > .sku').attr('data-o_content'));
        $('.single_add_to_cart_button').prop('disabled', true);
      }
    }
  });

  $(document.body).on('click', '.single_add_to_cart_button, .ajax_add_to_cart', function(e) {
    e.preventDefault();
    var $this = $(this);
    if ($this.is(':disabled')) {
      return;
    }
    var form = new FormData(),
    id = $(this).data('product-id'),
    variation_form = $(this).closest('.variations_form'),
    product_id = variation_form.find('input[name=product_id]').val(),
    quantity = variation_form.find('input[name=quantity]').val(),
    var_id = variation_form.find('input[name=variation_id]').val();

    form.append('action', 'AJAX_actions');
    form.append('nonce', wwsc_object.nonce);
    form.append('product_id', product_id);
    form.append('quantity', quantity);
    form.append('variation_id', var_id);

    $.ajax({
      url: wwsc_object.ajaxurl,
      type: 'POST',
      data: form,
      dataType: 'json',
      processData: false,
      contentType: false,
      success: function(response, textStatus, jqXHR) {
        if (response.success) {
          if (true === response.data.return) {
            alert(wwsc_object.msgs.success);
          } else {
            alert(wwsc_object.msgs.failure);
          }
        } else {
          alert('Error: ' + response.data.error);
        }
      },
      error: function(jqXHR, textStatus, errorThrown) {
        // console.log(errorThrown);
      }
    });
  });
});