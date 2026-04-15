/**
 * Side Cart Upsells — JavaScript
 *
 * Responsibilities:
 *  - Show a loading state while WooCommerce processes the AJAX add-to-cart.
 *  - Show a brief "Added!" confirmation on success.
 *  - Reset button text if the fragment refresh replaces the section (handled
 *    automatically because the DOM node is swapped out by WC).
 *
 * WooCommerce's own `wc-add-to-cart.js` handles the actual AJAX request for
 * buttons with the `ajax_add_to_cart` class.  This file only manages the
 * visual feedback layer.
 *
 * All code is wrapped in an IIFE — no globals are created.
 * All selectors use the `scu-` prefix — no generic selectors are targeted.
 */

/* global scuData, jQuery */

( function ( $ ) {
	'use strict';

	// -------------------------------------------------------------------------
	// Helpers
	// -------------------------------------------------------------------------

	/**
	 * Put a button into the loading state.
	 *
	 * @param {jQuery} $btn
	 */
	function setLoading( $btn ) {
		$btn
			.addClass( 'scu-upsells__add-btn--loading' )
			.prop( 'disabled', true )
			.data( 'scu-original-text', $btn.text() );
	}

	/**
	 * Put a button into the "added" success state.
	 *
	 * @param {jQuery} $btn
	 */
	function setAdded( $btn ) {
		var addedText = ( typeof scuData !== 'undefined' && scuData.addedText )
			? scuData.addedText
			: 'Added!';

		$btn
			.removeClass( 'scu-upsells__add-btn--loading' )
			.addClass( 'scu-upsells__add-btn--added' )
			.text( addedText );
	}

	// -------------------------------------------------------------------------
	// Event: user clicks an upsell add-to-cart button
	// -------------------------------------------------------------------------

	/**
	 * WooCommerce fires `adding_to_cart` before its own AJAX call.
	 * We use that to trigger the loading state on our button specifically,
	 * identified by the product ID passed in the event data.
	 *
	 * Fallback: also listen directly on click so the loading state appears
	 * immediately even if WC's event fires slightly later.
	 */
	$( document ).on( 'click', '.scu-upsells__add-btn', function () {
		setLoading( $( this ) );
	} );

	// -------------------------------------------------------------------------
	// Event: WooCommerce confirms a product was added
	// -------------------------------------------------------------------------

	/**
	 * `added_to_cart` is triggered by wc-add-to-cart.js on success.
	 * Signature: ( event, fragments, cartHash, $button )
	 *
	 * $button is the element that triggered the add, so we only act when it
	 * is one of our own buttons.
	 *
	 * After this event WooCommerce will:
	 *  1. Replace cart fragment keys in the DOM (including `.scu-upsells`).
	 *  2. Fire `wc_fragments_refreshed`.
	 *
	 * Because the DOM node is replaced, we only need to mark it as added
	 * for the brief window before the fragment swap.
	 */
	$( document ).on( 'added_to_cart', function ( event, fragments, cartHash, $button ) {
		if ( $button && $button.hasClass( 'scu-upsells__add-btn' ) ) {
			setAdded( $button );
		}
	} );

	// -------------------------------------------------------------------------
	// Event: WooCommerce AJAX error (generic guard)
	// -------------------------------------------------------------------------

	/**
	 * If WooCommerce's AJAX call fails, reset any stuck loading buttons
	 * inside the upsells section so the user can retry.
	 */
	$( document ).on( 'ajax_error added_to_cart', function () {
		$( '.scu-upsells__add-btn--loading' ).each( function () {
			var $btn = $( this );
			var originalText = $btn.data( 'scu-original-text' );

			// Only reset if still in loading state (not already handled above).
			if ( originalText ) {
				$btn
					.removeClass( 'scu-upsells__add-btn--loading' )
					.prop( 'disabled', false )
					.removeData( 'scu-original-text' );
				// Don't restore text here — WC will refresh the fragment.
			}
		} );
	} );

} )( jQuery );
