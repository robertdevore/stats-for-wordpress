<?php

defined( 'ABSPATH' ) || exit;

/**
 * Registers a settings page for viewing stats.
 * 
 * @since  1.0.0
 * @return void
 */
function sfwp_register_settings_page() {
    add_menu_page(
        esc_html__( 'Stats for WordPress®', 'sfwp' ),
        esc_html__( 'Stats', 'sfwp' ),
        'manage_options',
        'sfwp-stats',
        'sfwp_render_stats_page',
        'dashicons-chart-bar',
        26
    );
}
add_action( 'admin_menu', 'sfwp_register_settings_page' );

/**
 * Renders the stats page in the admin area.
 * 
 * @since  1.0.0
 * @return void
 */
function sfwp_render_stats_page() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'sfwp_stats';

    // Fetch daily data for the past week, excluding 404 pages.
    $results = $wpdb->get_results( "
        SELECT date, SUM(unique_visits) AS unique_visits, SUM(all_visits) AS all_visits
        FROM $table_name
        WHERE page != '/404'
        GROUP BY date
        ORDER BY date DESC
        LIMIT 7
    " );

    $dates         = array_reverse( array_column( $results, 'date' ) );
    $unique_visits = array_reverse( array_column( $results, 'unique_visits' ) );
    $all_visits    = array_reverse( array_column( $results, 'all_visits' ) );

    // Fetch today's most visited pages, excluding 404 pages.
    $today      = current_time( 'Y-m-d' );
    $page_data  = $wpdb->get_results( $wpdb->prepare( "
        SELECT page, SUM(unique_visits) AS unique_visits, SUM(all_visits) AS all_visits
        FROM $table_name
        WHERE date = %s AND page != '/404'
        GROUP BY page
        ORDER BY all_visits DESC
    ", $today ) );

    // Fetch top referrers for today.
    $referrer_data = $wpdb->get_results( $wpdb->prepare( "
        SELECT referrer, SUM(all_visits) AS visits
        FROM $table_name
        WHERE date = %s AND page != '/404'
        GROUP BY referrer
        ORDER BY visits DESC
        LIMIT 20
    ", $today ) );

    // Fetch 404 hits for today.
    $not_found_data = $wpdb->get_row( $wpdb->prepare( "
        SELECT SUM(unique_visits) AS unique_visits, SUM(all_visits) AS all_visits
        FROM $table_name
        WHERE date = %s AND page = '/404'
    ", $today ) );

    include plugin_dir_path( dirname( __FILE__ ) ) . 'views/admin-stats-page.php';
}
