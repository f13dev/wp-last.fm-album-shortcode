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

// Temporary api key variable
$key = '';

// Register the shortcode
add_shortcode( 'album', 'f13_lastfm_album_shortcode');
// Register the CSS
add_action( 'wp_enqueue_scripts', 'f13_lastfm_album_shortcode_stylesheet');

function f13_lastfm_album_shortcode( $atts, $content = null )
{
    // Get the attributes
    extract( shortcode_atts ( array (
        'artist' => '', // Get the artist attribute
        'album' => '', // Get the album attribute
    ), $atts ));

    // Check if both an artist and album have been set
    if ($artist == '' || $album == '')
    {
        // Warn the user that both the artist and album attribute
        // must be set.
        $response = 'Both the artist and album attributes must be set.<br />
        e.g. [album artist="Metallica" album="The Black Album"]'
    }
    else
    {
        // If both the artist and album attributes are set, an API call is
        // now required, from here the shortcode will be cached to reduce
        // API calls.

        // Get the associated transietn cache entry if it exists.
        $cache = get_transient('f13lfmas' . md5(serialize($atts)));

        if ($cache)
        {
            // If the cache exists, set the response to the cache, this way
            // the already cached data will be returned and the API will not
            // be called.
            $response = $cache;
        }
        else
        {
            // If there isn't a valid cache that matches the attributes then
            // the API will need to be called to create the response and store
            // it into the cache.
            $albumData = f13_get_lastfm_data($anArtist, $anAlbum)

            // Check if the response includes an error. In the case of
            // an error, the artist/album combination are not found.
            if (in_array('error', $albumData))
            {
                // Warn the user that the artist album combination did not return
                // a valid result.
                $response = 'We could not find the album: ' . $album . ' for the artist: ' . $artist;
            }
            else
            {
                // Everything appears to be ok, so we can now build the widget.
                // Return the response of the album data formatter, sending over the
                // album data obtained from last.fm
                $response = f13_album_data_formatter($albumData);
            }
        }
    }
    // Return the response
    return $response;
}

function f13_lastfm_album_shortcode_stylesheet()
{
    wp_register_style( 'f13album-style', plugins_url('wp-last.fm-album-shortcode.css', __FILE__));
    wp_enqueue_style( 'f13album-style' );
}

function f13_get_lastfm_data($anArtist, $anAlbum)
{
    // start curl
    $curl = curl_init();

    // Replace spaces in anArtist and anAlbum with +
    $anArtist = str_replace(' ', '+', $anArtist);
    $anAlbum = str_replace(' ', '+', $anAlbum);

    // set the curl URL
    $url = 'http://ws.audioscrobbler.com/2.0/?method=album.getinfo&api_key=' . $key . '&artist=' . $anArtist . '&album=' . $anAlbum . '&format=json';

    // Set curl options
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HTTPGET, true);

    // Set the user agent
    curl_setopt($curl, CURLOPT_USERAGENT, 'F13 WP Last.fm Album Shortcode/1.0');
    // Set curl to return the response, rather than print it
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

    // Get the results and store the XML to results
    $results = json_decode(curl_exec($curl), true);

    // Close the curl session
    curl_close($curl);

    return $results;
}

function f13_album_data_formatter($albumData)
{
    // Format the album data into a nice looking widget
}
