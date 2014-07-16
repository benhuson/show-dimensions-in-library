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

    list($url, $width, $height) = wp_get_attachment_image_src($post_id, 'full');

    echo esc_html("{$width}&times;{$height}");
}


function jajadi_show_dimensions_load_textdomain() {
	load_plugin_textdomain( 'jajadi-show-dimensions', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}

// Hooks a function on to a specific action.
add_action( 'plugins_loaded', 'jajadi_show_dimensions_load_textdomain');
add_filter('manage_upload_columns', 'jajadi_show_dimensions_size_column_register');
add_action('manage_media_custom_column', 'jajadi_show_dimensions_size_column_display', 10, 2);

?>