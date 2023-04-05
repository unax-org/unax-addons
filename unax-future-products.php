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
add_filter( 'woocommerce_product_object_query_args', array( '\Unax_Addons\Future_Products', 'product_object_query_args' ) );
add_action( 'pre_get_posts', array( '\Unax_Addons\Future_Products', 'show_future_products' ) );
add_filter( 'the_permalink', array( '\Unax_Addons\Future_Products', 'fix_permalink' ), 10, 2 );
add_filter( 'woocommerce_is_purchasable', array( '\Unax_Addons\Future_Products', 'is_purchasable' ), 10, 2 );
add_filter( 'woocommerce_product_is_visible', array( '\Unax_Addons\Future_Products', 'product_is_visible' ), 10, 2 );
add_filter( 'woocommerce_variation_is_purchasable', array( '\Unax_Addons\Future_Products', 'is_purchasable' ), 10, 2 );
add_filter( 'woocommerce_variation_is_visible', array( '\Unax_Addons\Future_Products', 'variation_is_visible' ), 10, 4 );

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
	 * @param array $query object.
	 */
	public static function product_object_query_args( $query_vars ) {
		array_push( $query_vars['status'], 'future' );

		return $query_vars;
	}


	/**
	 * Show future products.
	 *
	 * @param WP_Query $query object.
	 */
	public static function show_future_products( $query ) {
		if ( empty( $query->get( 'post_status' ) ) ) {
			return;
		}

		// Check if the query is for products.
		if ( ! in_array( $query->get( 'post_type' ), self::$post_types, true )
			&& ! is_product_category()
			&& ! is_post_type_archive( self::$post_types ) ) {
			return;
		}

		// Set post status.
		if ( 'future' === $query->get( 'post_status' ) ) {
			$query->set( 'post_status', 'publish' );
		}
	}


	/**
	 * Fix future product permalink.
     *
     * @param string $permalink Permalink.
     * @param int    $post      WP_Post.
     *
     * @return string
	 */
    public static function fix_permalink( $permalink, $post ) {
        /* For filter recursion (infinite loop) */
        static $recursing = false;

        if ( empty( $post->ID ) ) {
            return $permalink;
        }

        if ( ! $recursing ) {
            if ( isset( $post->post_status ) && ( 'future' === $post->post_status ) ) {
                // Set the post status to publish to get the 'publish' permalink.
                $post->post_status = 'publish';
                $recursing = true;
                return get_permalink( $post ) ;
            }
        }

        $recursing = false;

        return $permalink;
    }


	/**
	 * Show future products.
	 *
	 * @param bool $purchasable Product is purchasable.
	 * @param int  $product     WC Product.
	 */
	public static function is_purchasable( $purchasable, $product ) {
	    if ( $product->exists() && ( 'future' === $product->get_status() || current_user_can( 'edit_post', $product->get_id() ) ) && '' !== $product->get_price() ) {
	        $purchasable = true;
	    }

	    return $purchasable;
	}


	/**
	 * Show future products.
	 *
	 * @param bool $visible    Product is visible.
	 * @param int  $product_id WC Product id.
	 */
	public static function product_is_visible( $visible, $product_id ) {
	    $product = wc_get_product( $product_id );
	    if ( empty( $product ) ) {
	        return $visible;
	    }

		if ( 'visible' !== $product->get_catalog_visibility() ) {
			return $visible;
		}

		if ( 'future' === $product->get_status() ) {
	        $visible = true;
	    }

		if ( $product->get_parent_id() ) {
			$parent_product = wc_get_product( $product->get_parent_id() );

			if ( $parent_product && 'future' === $parent_product->get_status() ) {
				$visible = true;
			}
		}

		if ( 'yes' === get_option( 'woocommerce_hide_out_of_stock_items' ) && ! $product->is_in_stock() ) {
			$visible = false;
		}

	    return $visible;
	}


	/**
	 * Show future products.
	 *
	 * @param bool $purchasable Product is purchasable.
	 * @param int  $product     WC Product.
	 */
	public static function variation_is_purchasable( $purchasable, $variation ) {
	    if ( $variation->variation_is_visible() && ( 'future' === $variation->parent_data['status'] || current_user_can( 'edit_post', $variation->get_parent_id() ) ) ) {
	        $purchasable = true;
	    }

	    return $purchasable;
	}


	/**
	 * Show future products.
	 *
	 * @param bool 					$visible      Variation is visible.
	 * @param int  					$variation_id Product variation id.
	 * @param int  					$parent_id    Product id.
	 * @param WC_Product_Variation  $variation    Product variation.
	 */
	public static function variation_is_visible( $visible, $variation_id, $parent_id, $variation ) {
	    if ( 'future' === get_post_status( $variation_id ) && '' !== $variation->get_price() ) {
	        $visible = true;
	    }

	    return $visible;
	}
}
