<?php

defined( 'ABSPATH' ) || exit;

/**
 * Creates or updates the stats database table during plugin activation.
 * 
 * @since  1.0.0
 * @return void
 */
function sfwp_create_stats_table() {
    global $wpdb;

    $table_name      = $wpdb->prefix . 'sfwp_stats';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        date DATE NOT NULL,
        page VARCHAR(255) NOT NULL,
        referrer VARCHAR(255) DEFAULT NULL,
        unique_visits INT(11) NOT NULL DEFAULT 0,
        all_visits INT(11) NOT NULL DEFAULT 0,
        PRIMARY KEY (id),
        UNIQUE KEY page_date_referrer (page, date, referrer)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );
}
register_activation_hook( __FILE__, 'sfwp_create_stats_table' );
