<?php
/**
 * Google Maps API Integration
 */

if (!defined('ABSPATH')) {
    exit;
}

class Glint_WC_Distance_Google_Maps_API
{

    /**
     * Calculate distance between origin and destination using Distance Matrix API
     *
     * @param string $origin
     * @param string $destination
     * @return string|WP_Error Distance text (e.g., '15.5 km') or error
     */
    public static function calculate_distance($origin, $destination)
    {
        $api_key = Glint_WC_Distance_Database::get_setting('glint_wc_distance_google_api_key');

        if (empty($api_key)) {
            return new WP_Error('no_api_key', 'Google Maps API Key is not set.');
        }

        if (empty($origin) || empty($destination)) {
            return new WP_Error('missing_addresses', 'Origin or destination address is missing.');
        }

        $url = add_query_arg(array(
            'origins' => urlencode($origin),
            'destinations' => urlencode($destination),
            'key' => $api_key,
            'units' => 'metric' // Adjust if needed
        ), 'https://maps.googleapis.com/maps/api/distancematrix/json');

        $response = wp_remote_get($url);

        if (is_wp_error($response)) {
            return $response;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (isset($data['status']) && $data['status'] !== 'OK') {
            $error_message = isset($data['error_message']) ? $data['error_message'] : $data['status'];
            return new WP_Error('api_error', 'API Error: ' . $error_message);
        }

        if (isset($data['rows'][0]['elements'][0]['status']) && $data['rows'][0]['elements'][0]['status'] === 'OK') {
            $distance_text = $data['rows'][0]['elements'][0]['distance']['text'];
            return $distance_text;
        }
        else {
            $element_status = isset($data['rows'][0]['elements'][0]['status']) ? $data['rows'][0]['elements'][0]['status'] : 'Unknown';
            return new WP_Error('not_found', 'Distance not found for the given addresses. Element status: ' . $element_status);
        }
    }
}
