<?php
/*
Plugin Name: F13 Last.fm album Shortcode
Plugin URI: http://f13dev.com/wordpress-plugin-lastfm-album-shortcode/
Description: Embed information about a music album into a page or blog post using shortcode.
Version: 1.0
Author: Jim Valentine - f13dev
Author URI: http://f13dev.com
Text Domain: f13-lastfm-album-shortcode
License: GPLv3
*/

/*
Copyright 2016 James Valentine - f13dev (jv@f13dev.com)
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of the License, or
any later version.
This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
*/

// Register the shortcode
add_shortcode( 'album', 'f13_lastfm_album_shortcode');
// Register the CSS
add_action( 'wp_enqueue_scripts', 'f13_lastfm_album_shortcode_stylesheet');

function f13_lastfm_album_shortcode( $atts, $content = null )
{
  // Handle shortcode
}

function f13_lastfm_album_shortcode_stylesheet()
{
    wp_register_style( 'f13album-style', plugins_url('wp-last.fm-album-shortcode.css', __FILE__));
    wp_enqueue_style( 'f13album-style' );
}
