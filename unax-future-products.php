<?php
/**
 * Plugin Name: Unax Addons - Future Products
 * Description: Plugin provides custom functionalities for future products
 * Version: 1.0.0
 * Author: Unax
 * Author URI: https://unax.org/
 * Text Domain: unax-addons-future-products
 * Domain Path: /languages
 * License: GNU General Public License v2 or later
 * License URI: http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 *
 * @package Unax_Addons\Future_Products
 * @author  Unax
 */

namespace Unax_Addons;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Load the textdomain.
load_plugin_textdomain( 'unax', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

// Hooks.
add_action( 'pre_get_posts', array( '\Unax_Addons\Future_Products', 'show_future_products' ) );
add_filter( 'woocommerce_product_is_visible', array( '\Unax_Addons\Future_Products', 'product_is_visible' ), 10, 2 );
add_filter( 'woocommerce_is_purchasable', array( '\Unax_Addons\Future_Products', 'is_purchasable' ), 10, 2 );

/*
 * Class Future_Products
 */
class Future_Products {

	/**
	 * Post types.
	 *
	 * @var array
	 */
	public static $post_types = array( 'product', 'product_variation' );


	/**
	 * Show future products.
	 *
	 * @param WP_Query $query object.
	 */
	public static function show_future_products( $query ) {
		// Check if the query is for products.
		if ( ! in_array( $query->get( 'post_type' ), self::$post_types, true )
			 && ! is_product_category()
			 && ! is_post_type_archive( self::$post_types ) ) {
			return;
	    }

		// Get query post statuses.
		$post_status   = $query->get( 'post_status' );
		$post_statuses = array();

		// Prepare post statuses array.
		if ( is_array( $post_status ) ) {
			$post_statuses = $post_status;
		} else {
			array_push( $post_statuses, $post_status );
		}

		// Add future to post statuses.
		array_push( $post_statuses, 'future' );

		// Set future post status
		$query->set( 'post_status', $post_statuses );

	    return $query;
	}


	/**
	 * Show future products.
	 *
	 * @param bool $visible Product is visible.
	 * @param int  $product_id WC Product id.
	 */
	public static function product_is_visible( $visible, $product_id ) {
	    global $product;

	    if ( empty( $product ) ) {
	        return $visible;
	    }

	    if ( 'future' === $product->get_status() ) {
	        $visible = true;
	    }

	    return $visible;
	}


	/**
	 * Show future products.
	 *
	 * @param bool $purchasable Product is purchasable.
	 * @param int  $product     WC Product.
	 */
	public static function is_purchasable( $purchasable, $product ) {
	    if ( $product->exists()
			 && ( 'future' === $product->get_status() || current_user_can( 'edit_post', $product->get_id() ) )
			 && '' !== $product->get_price() ) {
	        $purchasable = true;
	    }

	    return $purchasable;
	}
}
