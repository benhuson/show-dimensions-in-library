<?php
/*
	Plugin Name: Show Dimensions in Library
	Plugin URI: http://wordpress.org/extend/plugins/
	Description: Show Dimensions in Media Library
	Version: 1.2
	Author: Janjaap van Dijk
	Author URI: http://janjaapvandijk.nl/
	Last Updated: 2014-04-06
 	License: GPLv2 or later
 	Text Domain: jajadi-show-dimensions
	Domain Path: /languages/

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.
	
	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.
	
	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/


function jajadi_show_dimensions_size_column_register($columns) {

    $columns['dimensions'] = __('Dimensions', 'jajadi-show-dimensions');

    return $columns;
}


function jajadi_show_dimensions_size_column_display($column_name, $post_id) {

    if( 'dimensions' != $column_name || !wp_attachment_is_image($post_id))
        return;

	$metadata = wp_get_attachment_metadata( $post_id );

	if ( $metadata ) {
		if ( isset( $metadata['width'] ) && isset( $metadata['height'] ) ) {
			$width = absint( $metadata['width'] );
			$height = absint( $metadata['height'] );
			update_post_meta( $post_id, 'jajadi_show_dimensions', $width * $height );
		} else {
			update_post_meta( $post_id, 'jajadi_show_dimensions', 0 );
		}
	} else {
		update_post_meta( $post_id, 'jajadi_show_dimensions', 0 );
	}

    echo esc_html("{$width}&times;{$height}");
}

function jajadi_show_dimensions_size_column_register_sortable( $columns ) {
	$columns['dimensions'] = 'jajadi_show_dimensions';
	return $columns;
}

function jajadi_show_dimensions_size_column_orderby( $vars ) {

	if ( isset( $vars['orderby'] ) && 'jajadi_show_dimensions' == $vars['orderby'] ) {
		$vars = array_merge( $vars, array(
			'meta_key' => 'jajadi_show_dimensions',
			'orderby'  => 'meta_value_num'
		) );
	}

	return $vars;
}

function jajadi_show_dimensions_update_size( $post_id ) {
	$size = 0;
	$metadata = wp_get_attachment_metadata( $post_id );

	if ( $metadata ) {
		if ( isset( $metadata['width'] ) && isset( $metadata['height'] ) ) {
			$size = absint( $metadata['width'] ) * absint( $metadata['height'] );
			update_post_meta( $post_id, 'jajadi_show_dimensions', $size );
		} else {
			update_post_meta( $post_id, 'jajadi_show_dimensions', $size );
		}
	} else {
		update_post_meta( $post_id, 'jajadi_show_dimensions', $size );
	}

	return $size;
}

function jajadi_show_dimensions_run_metadata() {
	$args = array(
		'numberposts' => -1,
		'post_parent' => null,
		'post_status' => null,
		'post_type'   => 'attachment'
	);
	$attachments = get_posts( $args );

	if ( $attachments ) {
		foreach ( $attachments as $post ) {
			jajadi_show_dimensions_update_size( $post->ID );
		}
		wp_reset_postdata();
	}
}

function jajadi_show_dimensions_clear_metadata() {
	$args = array(
		'numberposts' => -1,
		'post_parent' => null,
		'post_status' => null,
		'post_type'   => 'attachment'
	);
	$attachments = get_posts( $args );

	if ( $attachments ) {
		foreach ( $attachments as $post ) {
			delete_post_meta( $post->ID, 'jajadi_show_dimensions' );
		}
	}
}

function jajadi_show_dimensions_load_textdomain() {
	load_plugin_textdomain( 'jajadi-show-dimensions', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}

function jajadi_show_dimensions_check_upgrade() {
	if ( function_exists( 'get_current_screen' ) ) {
		$screen = get_current_screen();
		if ( isset( $screen->base ) && 'upload' == $screen->base ) {
			$version = get_option( 'jajadi_show_dimensions_version', '0' );
			if ( version_compare( $version, '1.3', '<' ) ) {
				jajadi_show_dimensions_clear_metadata();
				jajadi_show_dimensions_run_metadata();
				update_option( 'jajadi_show_dimensions_version', '1.3' );
			}	
		}
	}
}

function jajadi_show_dimensions_activate() {
	jajadi_show_dimensions_run_metadata();
}

function jajadi_show_dimensions_deactivate() {
	jajadi_show_dimensions_clear_metadata();
	delete_option( 'jajadi_show_dimensions_version' );
}

// Actvation / Deactivation Hooks
register_activation_hook( __FILE__, 'jajadi_show_dimensions_activate' );
register_deactivation_hook( __FILE__, 'jajadi_show_dimensions_deactivate' );

// Hooks a function on to a specific action.
add_action( 'wp', 'jajadi_show_dimensions_check_upgrade');
add_action( 'plugins_loaded', 'jajadi_show_dimensions_load_textdomain');
add_filter('manage_upload_columns', 'jajadi_show_dimensions_size_column_register');
add_filter( 'manage_upload_sortable_columns', 'jajadi_show_dimensions_size_column_register_sortable' );
add_filter( 'request', 'jajadi_show_dimensions_size_column_orderby' );
add_action('manage_media_custom_column', 'jajadi_show_dimensions_size_column_display', 10, 2);

?>