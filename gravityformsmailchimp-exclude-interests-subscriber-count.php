<?php
/**
 * Plugin Name:     Gravity Forms Mailchimp Add-On: Exclude Interests Subscriber Count
 * Plugin URI:      PLUGIN SITE HERE
 * Description:     Exclude interests subscriber count from merge tags. This is useful if your Audience size is large and you want to speed up Mailchimp API requests.
 * Author:          Sean Clendening
 * Text Domain:     gravityformsmailchimp-exclude-interests-subscriber-count
 * Domain Path:     /languages
 * Version:         0.1.0
 */

// Don't load directly
if ( ! defined( 'ABSPATH' ) ) {
	die();
}

// If Gravity Forms is loaded, bootstrap the plugin
add_action( 'gform_loaded', array( 'GF_Mailchimp_Exclude_Interests_Subscriber_Count', 'load' ), 5 );

/**
 * Class GF_Mailchimp_Exclude_Interests_Subscriber_Count
 *
 * Main plugin class
 */
class GF_Mailchimp_Exclude_Interests_Subscriber_Count {

    /**
     * Load the plugin filters
     */
    public static function load() {
        add_filter( 'pre_http_request', array( $this, 'exclude_interests_subscriber_count' ), 10, 3 );
    }

    /**
     * Filter the Mailchimp API request to exclude interests subscriber count
     */
    public function exclude_interests_subscriber_count( $pre, $args, $url ) {
        // Check if the request is to the Mailchimp API and contains the interest-categories parameter
        if ( strpos( $url, 'api.mailchimp.com/3.0/lists/' ) !== false && strpos( $url, 'interest-categories' ) !== false ) {
            // Decode the URL to manipulate query parameters
            $url_parts = parse_url( $url );
            parse_str( $url_parts['query'], $query_params );

            // Exclude the subscriber count by setting the appropriate parameter
            $query_params['exclude_fields'] = 'interests.subscriber_count';

            // Rebuild the URL with the modified query parameters
            $url_parts['query'] = http_build_query( $query_params );
            $new_url = $url_parts['scheme'] . '://' . $url_parts['host'] . $url_parts['path'] . '?' . $url_parts['query'];

            // Update the URL in the args
            $args['url'] = $new_url;

            // Temporarily remove this filter to avoid infinite loop
            remove_filter( 'pre_http_request', array( $this, 'exclude_interests_subscriber_count' ), 10 );

            // Make the modified request
            $response = wp_remote_request( $new_url, $args );

            // Re-add the filter
            add_filter( 'pre_http_request', array( $this, 'exclude_interests_subscriber_count' ), 10, 3 );

            return $response;
        }

        return $pre;
    }
}