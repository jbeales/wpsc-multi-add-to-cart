<?php


/**
 * @package Multi Add-to-Cart for WPEC
 * @author John Beales (http://johnbeales.com)
 * @version 0.1
 */




/*
Plugin Name:Multi Add-to-Cart WPEC
Plugin URI: http://johnbeales.com
Description: Lets customers add multiple products to their shopping cart without having to visit a separate page for each product.
Version: 0.1
Author: John Beales
Author URI: http://johnbeales.com
*/


function _wpsc_matc_default_params() {
	$default_parameters = array(
		'variation_values' =>null,
		'quantity' => 1,
		'provided_price' => null,
		'comment' => null,
		'time_requested' => null,
		'custom_message' => null,
		'file_data' => null,
		'is_customisable' => false,
		'meta' => null
	);
	return $default_parameters;
}




/*

$input_params (associative array):

'variation' => itself an associative array, where ints associate with ints
'is_customisable' => a true/false flag. Should be a string 'true' if the product is customizable.
'custom_text' => A string of text to be added to a customizable product
'custom_file' => A file to be saved that'll be somehow added to a customizable product. Probably pulled from $_FILES['custom_file']
'donation_price' => The donation price.
'quantity' => How many of the product to add

 Really, this function should be a method of the class wpsc_cart

 */


function wpsc_matc_add_to_cart( $product_id, $input_params = array() ) {

	global $wpsc_cart;

	$default_parameters = _wpsc_matc_default_params();
	$provided_parameters = array();
	
	$post_type_object = get_post_type_object( 'wpsc-product' );
	$permitted_post_statuses = current_user_can( $post_type_object->cap->edit_posts ) ? array( 'private', 'draft', 'pending', 'publish' ) : array( 'publish' );

	/// sanitise submitted values
	$product_id = apply_filters( 'wpsc_add_to_cart_product_id', (int) $product_id);

	$product = get_post( $product_id );

	if ( 
		! in_array( $product->post_status, $permitted_post_statuses ) 
		|| 'wpsc-product' != $product->post_type 
	) {
		return false;
	}


	// if variation data is set, update the product ID to be that of the variation
	if(isset($input_params['variation'])){
		foreach ( (array) $input_params['variation'] as $key => $variation ) {
			$provided_parameters['variation_values'][ (int) $key ] = (int) $variation;
		}

		if ( count( $provided_parameters['variation_values'] ) > 0 ) {
			$variation_product_id = wpsc_get_child_object_in_terms( $product_id, $provided_parameters['variation_values'], 'wpsc-variation' );
			if ( $variation_product_id > 0 ) {
				$product_id = $variation_product_id;
			}
		}
	}


	// note: I've removed stuff about updating the quantity from the original wpsc_add_to_cart(). I think that an "Add to Cart" function should add, and an "Update Cart Item" function should update.
	if ( $input_params['quantity'] > 0 ) {
		$provided_parameters['quantity'] = (int) $input_params['quantity'];
	}

	if ( isset( $input_params['is_customisable'] ) &&  'true' == $input_params['is_customisable'] ) {
		$provided_parameters['is_customisable'] = true;

		if ( isset( $input_params['custom_text'] ) ) {
			$provided_parameters['custom_message'] = stripslashes( $input_params['custom_text'] );
		}

		// the 'custom_file' parameter should come from the $_FILES array
		if ( isset( $input_params['custom_file'] ) ) {
			$provided_parameters['file_data'] = $input_params['custom_file'];
		}
	}

	if ( isset( $input_params['donation_price'] ) && ( (float) $input_params['donation_price'] > 0 ) ) {
		$provided_parameters['provided_price'] = (float) $input_params['donation_price'];
	}

	$parameters = array_merge( $default_parameters, (array) $provided_parameters );

	$cart_item = $wpsc_cart->set_item( $product_id, $parameters );

	if ( is_object( $cart_item ) ) {
		do_action( 'wpsc_add_to_cart', $product, $cart_item );
		return true;
		//$cart_messages[] = str_replace( "[product_name]", $cart_item->get_title(), __( 'You just added "[product_name]" to your cart.', 'wpsc' ) );
	} else {
		$error = new WP_Error();

		if ( $parameters['quantity'] <= 0 ) {
			$error->add( 'zero', __( 'Sorry, but you cannot add zero items to your cart', 'wpsc' ) );
		} else if ( $wpsc_cart->get_remaining_quantity( $product_id, $parameters['variation_values'], $parameters['quantity'] ) > 0 ) {
			$quantity = $wpsc_cart->get_remaining_quantity( $product_id, $parameters['variation_values'], $parameters['quantity'] );
			$error->add( 'insufficient_quantity', sprintf( _n( 'Sorry, but there is only %s of this item in stock.', 'Sorry, but there are only %s of this item in stock.', $quantity, 'wpsc' ), $quantity ) );
		} else {
			$error->add( 'out_of_stock', sprintf( __( 'Sorry, but the item "%s" is out of stock.', 'wpsc' ), $product->post_title ) );
		}
		return $error;
	}
}




// this will have to handle multiple post values.
// somehow, we'll have to determine which post field goes with which product ID.
// I'm thinking of dynamically building the post field names, and submitting a list
// of product IDs with the form.
function wpsc_matc_multi_add_to_cart() {

}

// I'll need a function to get the list of products that "go with" the main product,
// so we can show a list of products on the "main" product's single-product page.
function wpsc_matc_get_companions_for( $product ) {

}



// I'll need some functions to create field names
function wpsc_matc_get_form_field_name( $product, $field_name ) {

}



// Elsewhere: We'll need JS to handle Ajax submitting of the form.





?>