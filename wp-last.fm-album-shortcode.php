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
// Register the admin page
add_action('admin_menu', 'f13_lfmas_create_menu');

function f13_lastfm_album_shortcode( $atts, $content = null )
{
    // Get the attributes
    extract( shortcode_atts ( array (
        'artist' => '', // Get the artist attribute
        'album' => '', // Get the album attribute
    ), $atts ));

    // Check if an API key is present, if not return a message
    // to the user stating that an api key is required
    if (esc_attr( get_option('lfmastoken')) == '')
    {
        $response = 'A Last.fm API token is required for this shortcode to work.<br />
            please visit \'WPAdmin => Settings => F13 Last.fm Album Shortcode\' for more information';
    }
    else
    {
        // If a token has been entered, continue to produce the
        // output of the shortcode

        // Check if both an artist and album have been set
        if ($artist == '' || $album == '')
        {
            // Warn the user that both the artist and album attribute
            // must be set.
            $response = 'Both the artist and album attributes must be set.<br />
            e.g. [album artist="Metallica" album="The Black Album"]';
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
                $albumData = f13_get_lastfm_data($artist, $album);

                // Check if the response includes an error. In the case of
                // an error, the artist/album combination are not found.
                if (array_key_exists('error', $albumData))
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

                // Get the cache timeout and store it in seconds (from an input in minutes)
                $cache_time = esc_attr( get_option('lfmascache_timeout')) * 60;

                // If the cache time is zero, convert it to 1 second for a near
                // instant timeout as a cache time of zero will provide a never
                // ending cache that will never update.
                if ($cache_time == 0 || !is_numeric($cache_time))
                {
                    $cache_time = 1;
                }

                // Store the response in the cache
                set_transient('f13lfmas' . md5(serialize($atts)), $string, $cache_time);
            }
        }
    }
    // Return the response
    return $response;
}

function f13_lfmas_create_menu()
{
    // Create the top-level menu
    add_options_page('F13Devs Last.fm Album Shortcode Settings', 'F13 Last.fm Album Shortcode', 'administrator', 'f13-lastfm-album-shortcode', 'f13_lfmas_settings_page');
    // Retister the Settings
    add_action( 'admin_init', 'f13_lfmas_settings');
}

function f13_lfmas_settings()
{
    // Register settings for token and timeout
    register_setting( 'f13-lfmas-settings-group', 'lfmastoken');
    register_setting( 'f13-lfmas-settings-group', 'lfmascache_timeout');
}

function f13_lfmas_settings_page()
{
?>
    <div class="wrap">
        <h2>F13 Album Shortcode Settings</h2>
        <p>
            This plugin requires an API Key from last.fm in order to function.
        </p>
        <p>
            To obtain a Last.fm API Key:
            <ol>
                <li>
                    Login or register with <a href="http://last.fm">Last.fm</a>.
                </li>
                <li>
                    Visit <a href="http://www.last.fm/api/account/create">http://www.last.fm/api/account/create</a> to create an API key
                </li>
                <li>
                    Your email address should already be present, if not add it.
                </li>
                <li>
                    Enter an application name, such as 'Album information on my blog'.
                </li>
                <li>
                    Enter an application description, such as:<br />
                    Use of F13 Last.fm Album Shortcode WordPress plugin on
                    my blog to insert album information.
                </li>
                <li>
                    The callback URL and application homepage can be left blank.
                </li>
                <li>
                    Read the API terms and conditions, if you agree to them, click 'Submit' to obtain your API details.
                </li>
                <li>
                    Copy and past the provided API key to the field below.
                </li>
            </ol>
        </p>

        <form method="post" action="options.php">
            <?php settings_fields( 'f13-lfmas-settings-group' ); ?>
            <?php do_settings_sections( 'f13-lfmas-settings-group' ); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">
                        Last.fm API Key
                    </th>
                    <td>
                        <input type="password" name="lfmastoken" value="<?php echo esc_attr( get_option( 'lfmastoken' ) ); ?>" style="width: 50%;"/>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">
                        Cache timeout (minutes)
                    </th>
                    <td>
                        <input type="number" name="lfmascache_timeout" value="<?php echo esc_attr( get_option( 'lfmascache_timeout' ) ); ?>" style="width: 75px;"/>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
<?php
}


function f13_lastfm_album_shortcode_stylesheet()
{
    wp_register_style( 'f13album-style', plugins_url('wp-last.fm-album-shortcode.css', __FILE__));
    wp_enqueue_style( 'f13album-style' );
}

function f13_get_lastfm_data($anArtist, $anAlbum)
{
    // Get the API Key from the admin settings
    $key = esc_attr( get_option('lfmastoken'));

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

    // Create a response variable
    $response = '';

    // Open a container div
    $response .= '<div class="f13-album-container">';

        // Open a header div to hold the artist and album information
        $response .= '<div class="f13-album-head">';

            $response .= $albumData['album']['artist'] . ' - ' . $albumData['album']['name'];

        // Close the head div
        $response .= '</div>';

        // Create an albumArt variable
        $albumArt = null;
        // Get the mega image filename
        foreach ($albumData['album']['image'] as &$eachImage)
        {
            // If image size is mega
            if ($eachImage['size'] == 'mega')
            {
                // Store the URL to the mega image
                $albumArt = $eachImage['#text'];
            }
        }

        // Add the image if it is set
        if ($albumArt != null)
        {
            // Get the filename from the URL
            $fileName = explode('/', $albumArt);
            $fileName = end($fileName);

            // Get the image ID of the file if it exists
            $imageID = f13_get_album_attachment_id($fileName);

            // If the image doesn't exist locally
            if ($imageID == null)
            {
                // If the image file does not already exist try and
                // add it to the media library.

                // Require files used to sideload
                require_once(ABSPATH . 'wp-admin/includes/media.php');
                require_once(ABSPATH . 'wp-admin/includes/file.php');
                require_once(ABSPATH . 'wp-admin/includes/image.php');

                // Attempt to sideload image
                media_sideload_image($albumArt, get_the_ID(), $albumData['album']['artist'] . ' - ' . $albumData['album']['name']);
                // Get the newly sideloaded image
                $imageID = f13_get_album_attachment_id($fileName);
                // Get the image url
                $image_url = wp_get_attachment_url($imageID);
            }
            else
            {
              // If the image already exists, use the
              // image id already obtained.
              $image_url = wp_get_attachment_url($imageID);

            }

            // Check if the image id is a number, if so add
            // the image.
            if (is_numeric($imageID) && $imageID != null)
            {
                // Open a div to house the image
                $response .= '<div class="f13-album-art">';
                    // Add the image using the pre-found image url
                    $response .= '<img src="' . $image_url . '" />';
                // Close the image div
                $response .= '</div>';
            }
        }

        // Make sure the track listing array is not empty
        if (!empty($albumData['album']['tracks']['track']))
        {
            // If the track listing is not empty, open a track listing
            // div and enter the track listing into it.
            $response .= '<div class="f13-album-tracks">';

                // Store the track number
                $currentTrack = 1;
                // Add track listing
                foreach ($albumData['album']['tracks']['track'] as &$eachTrack)
                {
                    // Output the track number and name followed by the track
                    // time in minutes:seconds
                    $response .= '<span>' . $currentTrack . ') <a href="' . $eachTrack['url'] . '">' . $eachTrack['name'] . '</a> (' . gmdate("i:s", $eachTrack['duration']) . ')</span>';

                    // Increment the track number
                    $currentTrack++;
                }
            // Close the tracks div
            $response .= '</div>';
        }

        // Make sure the tags array is not empty
        if (!empty($albumData['album']['tags']['tag']))
        {
            // Open a tags div and add each entry to it.
            $response .= '<div class="f13-album-tags">';

                // Add a label for tags
                $response .= '<span class="f13-album-tags-label">Tags:</span>';

                // Add each of the tags
                foreach ($albumData['album']['tags']['tag'] as &$eachTag)
                {
                    // Check if the tag is numeric, if not add the tag
                    // numeric tags are the year of the album.
                    if (!is_numeric($eachTag['name']))
                    {
                        $response .= '<span class="f13-album-tags-tag"><a href="' . $eachTag['url'] . '">' . $eachTag['name'] . '</a></span>';
                    }
                }
            // Close the tags div
            $response .= '</div>';
        }

        // Add the published date if it's present
        if (array_key_exists('published', $albumData['album']['wiki']))
        {
            // Remove the time from the end of the publish date
            // to just return the date.
            $publishDate = explode(',', $albumData['album']['wiki']['published']);
            $publishDate = $publishDate[0];
            // Add the date to the response.
            $response .= '<div class="f13-album-published"><span>Published</span>: ' . $publishDate . '</div>';
        }

        // Add the summary if it exists
        if (array_key_exists('summary', $albumData['album']['wiki']))
        {
            $response .= '<div class="f13-album-summary"><span>Album summary:</span> ' . $albumData['album']['wiki']['summary'] . '</div>';
        }

    // Close the container div
    $response .= '</div>';

    // Return the response.
    return $response;
}

// retrieves the attachment ID from the filename
function f13_get_album_attachment_id($file_name) {
    global $wpdb;
    // Search the database for an attachment ending with the filename
    $attachment = $wpdb->get_col($wpdb->prepare("SELECT post_id FROM {$wpdb->base_prefix}postmeta WHERE meta_key='_wp_attached_file' AND meta_value LIKE %s;", '%' . $file_name ));
    // Returns the post ID or null
    if ($attachment[0] == null || $attachment[0] == '')
    {
        // If the post ID is not valid return null
        return null;
    }
    else
    {
        // Otherwise return the valid post ID
        return $attachment[0];
    }
}
