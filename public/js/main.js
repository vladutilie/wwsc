/**
 * main.js v1.0.0 (build:20181120)
 * jQuery for WWSC Project plugin
 * Copyright (c) 2018 Vlăduț Ilie
 * https://vladilie.ro
 */
jQuery( document ).ready( function( $ ) {
  $.ajax({
    url: wwsc_object.ajaxurl,
    data: {
      'action': 'AJAX_actions',
      'nonce': wwsc_object.nonce,
    },
    beforeSend: function() {
      $( '.wwsc-widget' ).html( '<div class="loader"></div>' );
    },
    success: function( response ) {
      $( '.wwsc-widget' ).html( '<div class="loader"></div>' );
      $( '.wwsc-widget' ).html( response );
    },
    complete: function() {
      $.getScript( wwsc_object.wc_url );
      $( '.loader' ).fadeOut( 800 );
      $( '.wwsc-widget>ul' ).fadeIn( 800 );
    }
  });
});
