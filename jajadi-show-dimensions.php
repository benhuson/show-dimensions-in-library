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

class JajaDi_Show_Dimensions {

	/**
	 * Setup plugin actions
	 */
	public function __construct() {
		if ( is_admin() ) {
			add_action( 'wp', array( $this, 'check_upgrade' ) );
			add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
			add_filter( 'manage_upload_columns', array( $this, 'column_register' ) );
			add_filter( 'manage_upload_sortable_columns', array( $this, 'column_register_sortable' ) );
			add_filter( 'request', array( $this, 'column_orderby' ) );
			add_action( 'manage_media_custom_column', array( $this, 'column_display' ), 10, 2 );
		}
	}

	/**
	 * Admin Column Display
	 *
	 * @param  string  $column_name  Admi column name.
	 * @param  int     $post_id      Post ID.
	 */
	public function column_display( $column_name, $post_id ) {

		if ( 'dimensions' != $column_name || ! wp_attachment_is_image( $post_id ) ) {
			return;
		}

		$attachment = new JajaDi_Show_Dimensions_Attachment( $post_id );

		// Update size meta data
		// @todo  This could be done on image upload/edit instead?
		$attachment->update_meta_data();

		echo $attachment->get_dimensions_display();
	}

	/**
	 * Register Admin Column
	 *
	 * @param   array  $columns  Admin columns.
	 * @return  array            Updated columns.
	 */
	public function column_register( $columns ) {
		$columns['dimensions'] = __( 'Dimensions', 'jajadi-show-dimensions' );
		return $columns;
	}

	/**
	 * Register Sortable Admin Column
	 *
	 * @param   array  $columns  Sortable admin columns.
	 * @return  array            Updated columns.
	 */
	public function column_register_sortable( $columns ) {
		$columns['dimensions'] = 'jajadi_show_dimensions';
		return $columns;
	}

	/**
	 * Order Column By Dimensions
	 *
	 * @param   array  $vars  Request vars.
	 * @return  array         Updated vars.
	 */
	public function column_orderby( $vars ) {

		if ( isset( $vars['orderby'] ) && 'jajadi_show_dimensions' == $vars['orderby'] ) {
			$vars = array_merge( $vars, array(
				'meta_key' => 'jajadi_show_dimensions',
				'orderby'  => 'meta_value_num'
			) );
		}

		return $vars;
	}

	/**
	 * Update Metadata
	 */
	public function update_metadata() {
		$args = array(
			'numberposts' => -1,
			'post_parent' => null,
			'post_status' => null,
			'post_type'   => 'attachment'
		);
		$attachments = get_posts( $args );

		if ( $attachments ) {
			foreach ( $attachments as $post ) {
				$attachment = new JajaDi_Show_Dimensions_Attachment( $post->ID );
				$attachment->update_meta_data();
			}
			wp_reset_postdata();
		}
	}

	/**
	 * Delete Metadata
	 */
	public function delete_metadata() {
		$args = array(
			'numberposts' => -1,
			'post_parent' => null,
			'post_status' => null,
			'post_type'   => 'attachment'
		);
		$attachments = get_posts( $args );

		if ( $attachments ) {
			foreach ( $attachments as $post ) {
				$attachment = new JajaDi_Show_Dimensions_Attachment( $post->ID );
				$attachment->delete_meta_data();
			}
		}
	}

	/**
	 * Check upgrade
	 */
	public function check_upgrade() {
		if ( function_exists( 'get_current_screen' ) ) {
			$screen = get_current_screen();
			if ( isset( $screen->base ) && 'upload' == $screen->base ) {
				$version = get_option( 'jajadi_show_dimensions_version', '0' );
				if ( version_compare( $version, '1.3', '<' ) ) {
					$this->delete_metadata();
					$this->update_metadata();
					update_option( 'jajadi_show_dimensions_version', '1.3' );
				}	
			}
		}
	}

	/**
	 * Activate
	 */
	public function activate() {
		$this->update_metadata();
	}

	/**
	 * Deactivate
	 */
	public function deactivate() {
		$this->delete_metadata();
		delete_option( 'jajadi_show_dimensions_version' );
	}

	/**
	 * Load Text Domain
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'jajadi-show-dimensions', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

}

class JajaDi_Show_Dimensions_Attachment {

	protected $id = 0;

	/**
	 * Create attachment
	 *
	 * @param  int  $attachment_id  Post ID.
	 */
	public function __construct( $attachment_id ) {
		$this->id = absint( $attachment_id );
	}

	/**
	 * Get Dimensions
	 *
	 * @return  array  Dimension values.
	 */
	public function get_dimensions() {
		$metadata = wp_get_attachment_metadata( $this->id );
		$dimensions = array(
			'width'  => '',
			'height' => ''
		);

		if ( $metadata ) {
			$dimensions = shortcode_atts( $dimensions, $metadata );
			return array_map( 'absint', $dimensions );
		}

		return $dimensions;
	}

	/**
	 * Get Dimensions Display
	 *
	 * @return  string  Dimensions HTML.
	 */
	public function get_dimensions_display() {
		$metadata = $this->get_dimensions();

		if ( '' != $metadata['width'] && '' != $metadata['height'] ) {
			return esc_html( $metadata['width'] . '&times;' . $metadata['height'] );
		}

		return '';
	}

	/**
	 * Update dimensions meta data
	 *
	 * @return  int  Image area.
	 */
	public function update_meta_data() {
		$size = 0;
		$metadata = wp_get_attachment_metadata( $this->id );

		if ( $metadata ) {
			if ( isset( $metadata['width'] ) && isset( $metadata['height'] ) ) {
				$size = absint( $metadata['width'] ) * absint( $metadata['height'] );
				update_post_meta( $this->id, 'jajadi_show_dimensions', $size );
			} else {
				update_post_meta( $this->id, 'jajadi_show_dimensions', $size );
			}
		} else {
			update_post_meta( $this->id, 'jajadi_show_dimensions', $size );
		}

		return $size;
	}

	/**
	 * Delete dimensions meta data
	 */
	public function delete_meta_data() {
		delete_post_meta( $this->id, 'jajadi_show_dimensions' );
	}

}

// Init
global $jajadi_show_dimensions;
$jajadi_show_dimensions = new JajaDi_Show_Dimensions();

// Activation / Deactivation Hooks
register_activation_hook( __FILE__, array( $jajadi_show_dimensions, 'activate' ) );
register_deactivation_hook( __FILE__, array( $jajadi_show_dimensions, 'deactivate' ) );
