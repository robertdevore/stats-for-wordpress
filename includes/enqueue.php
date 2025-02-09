<?php

defined( 'ABSPATH' ) || exit;

/**
 * Enqueues scripts and styles
 *
 * @param string $hook Current admin page hook.
 * 
 * @since  1.0.0
 * @return void
 */
function sfwp_enqueue_scripts( $hook ) {
    if ( $hook !== 'toplevel_page_sfwp-stats' ) {
        return;
    }

    $plugin_url = plugin_dir_url( dirname( __FILE__ ) );

    wp_enqueue_style( 'stats-wp', $plugin_url . 'assets/css/styles.css', [], STATS_WP_VERSION, 'all' );

    wp_enqueue_script( 'chartjs', $plugin_url . 'assets/js/charts.js', [], STATS_WP_VERSION, true );
}
add_action( 'admin_enqueue_scripts', 'sfwp_enqueue_scripts' );
