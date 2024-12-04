<?php

/**
  * The plugin bootstrap file
  *
  * @link              https://robertdevore.com
  * @since             1.0.0
  * @package           Stats_For_WordPress
  *
  * @wordpress-plugin
  *
  * Plugin Name: Stats for WordPress®
  * Description: A simple analytics tracker for WordPress®.
  * Plugin URI:  https://github.com/robertdevore/stats-for-wordpress/
  * Version:     1.0.0
  * Author:      Robert DeVore
  * Author URI:  https://robertdevore.com/
  * License:     GPL-2.0+
  * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
  * Text Domain: stats-wp
  * Domain Path: /languages
  * Update URI:  https://github.com/robertdevore/stats-for-wordpress/
  */

defined( 'ABSPATH' ) || exit;

require 'includes/plugin-update-checker/plugin-update-checker.php';
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$myUpdateChecker = PucFactory::buildUpdateChecker(
	'https://github.com/robertdevore/stats-for-wordpress/',
	__FILE__,
	'stats-for-wordpress'
);

// Set the branch that contains the stable release.
$myUpdateChecker->setBranch( 'main' );

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

/**
 * Logs visits on every page load, excluding crawlers and non-page resources.
 * 
 * @since  1.0.0
 * @return void
 */
function sfwp_log_visit() {
    if ( is_admin() ) {
        return;
    }

    // Exclude known crawlers/spiders.
    if ( sfwp_is_crawler() ) {
        return;
    }

    // Exclude favicon, sitemap, WordPress®-specific paths, and other non-page resources.
    $excluded_paths = [
        // WordPress® core files.
        '/favicon.ico',
        '/robots.txt',
        '/sitemap.xml',
        '/sitemap_index.xml',
        '/wp-json/',
        '/wp-login.php',
        '/wp-cron.php',
        '/xmlrpc.php',
        '/wp-trackback.php',
        '/wp-admin/admin-ajax.php',
        '/?wc-ajax=get_refreshed_fragments',

        // WordPress® admin and asset paths.
        '/wp-admin/',
        '/wp-content/uploads/',
        '/wp-content/plugins/',
        '/wp-content/themes/',

        // Static file types (styles, scripts, images, etc).
        '.css', '.js', '.jpg', '.jpeg', '.png', '.gif', '.svg', '.webp', '.ico',
        '.woff', '.woff2', '.ttf', '.eot', '.otf', '.ttc', '.font',
        '.mp4', '.mp3', '.avi', '.mov', '.mkv', '.flv', '.wmv',
        '.pdf', '.doc', '.docx', '.xls', '.xlsx', '.ppt', '.pptx',
        '.zip', '.rar', '.tar', '.gz', '.7z', '.bz2',

        // Feeds and API endpoints.
        '/feed/', '/rss/', '/rss2/', '/atom/', '/comments/feed/',
        '/trackback/',

        // Common WordPress® plugin paths.
        '/wp-content/plugins/woocommerce/',
        '/wp-content/plugins/elementor/',
        '/wp-content/plugins/jetpack/',
        '/wp-content/plugins/contact-form-7/',
        '/wp-content/plugins/wp-rocket/',
        'breeze_check_cache_available',

        // Query string patterns often used in WordPress®.
        '?ver=',
        '?preview=',
        '?attachment_id=',
        '?utm_',
        '?amp=',

        // Additional patterns.
        '/.well-known/',
    ];

    // Extract the request path.
    $request_uri = $_SERVER['REQUEST_URI'] ?? '';
    $parsed_url  = parse_url( $request_uri );
    $path        = $parsed_url['path'] ?? '';

    // Normalize the path by adding a trailing slash.
    $path = user_trailingslashit( $path );

    // Canonicalize `/` entries to a single normalized home page path.
    if ( $path === '/' || $path === '/index.php' ) {
        $path = '/';
    }

    // Reconstruct the page URL without query strings.
    $page = esc_url_raw( $path );

    // Loop through excluded paths.
    foreach ( $excluded_paths as $excluded ) {
        if ( stripos( $request_uri, $excluded ) !== false || stripos( $path, $excluded ) !== false ) {
            return;
        }
    }

    // Handle 404 pages separately.
    if ( is_404() ) {
        $page = '/404';
    }

    global $wpdb;

    $table_name = $wpdb->prefix . 'sfwp_stats';

    // Detect referrer and filter out internal referrers.
    $referrer  = isset( $_SERVER['HTTP_REFERER'] ) ? esc_url_raw( $_SERVER['HTTP_REFERER'] ) : null;
    $site_url  = home_url();
    $site_host = parse_url( $site_url, PHP_URL_HOST );

    // Set to null if the referrer is internal (match both http:// and https:// versions).
    if ( $referrer ) {
        $referrer_host = parse_url( $referrer, PHP_URL_HOST );
        if ( $referrer_host === $site_host ) {
            $referrer = null;
        }
    }

    $date = current_time( 'Y-m-d' );

    // Check if it's a unique visit using a cookie.
    $is_unique = ! isset( $_COOKIE['sfwp_unique_visit'] );

    if ( $is_unique ) {
        setcookie( 'sfwp_unique_visit', '1', time() + DAY_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true );
    }

    // Update or insert visit count.
    $wpdb->query(
        $wpdb->prepare(
            "INSERT INTO $table_name (date, page, referrer, unique_visits, all_visits)
            VALUES ( %s, %s, %s, %d, %d )
            ON DUPLICATE KEY UPDATE 
                unique_visits = unique_visits + %d, 
                all_visits = all_visits + 1",
            $date,
            $page,
            $referrer,
            $is_unique ? 1 : 0,
            1,
            $is_unique ? 1 : 0
        )
    );

}
add_action( 'wp', 'sfwp_log_visit' );

/**
 * Determines if the request is from a crawler/spider.
 *
 * @since  1.0.0
 * @return bool True if a crawler is detected, false otherwise.
 */
function sfwp_is_crawler() {
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

    // Comprehensive list of known crawler/spider user agents.
    $crawlers = [
        // Search Engine Bots.
        'Googlebot', 'Bingbot', 'Slurp', 'DuckDuckBot',
        'Baiduspider', 'YandexBot', 'Sogou', 'Exabot',
        'facebot', 'ia_archiver', 'SeznamBot', 'APIs-Google',
        'Google-Read-Aloud', 'Google Favicon',

        // SEO Tools.
        'AhrefsBot', 'SemrushBot', 'DotBot', 'rogerbot',
        'MJ12bot', 'Screaming Frog', 'Seokicks-Robot',
        'LinkpadBot', 'MegaIndex', 'Mediapartners-Google',
        'OnPageBot', 'Uptime.com Bot',

        // AI Bots and Chatbots.
        'ChatGPT', 'OpenAI', 'Bard', 'Claude',
        'Anthropic', 'Jasper', 'Wit.ai', 'Dialogflow',
        'ChatGPTBot', 'Google-ChatBot', 'DeepMind',
        'HuggingFace', 'GPT', 'AI', 'Transformer',

        // Website Monitoring/Testing Tools.
        'UptimeRobot', 'Pingdom', 'Site24x7', 'Zabbix',
        'Monitis', 'AppDynamics', 'StatusCake', 'BetterUptime',

        // Social Media Bots.
        'Twitterbot', 'LinkedInBot', 'Slackbot', 'Pinterestbot',
        'WhatsApp', 'DiscordBot', 'TelegramBot', 'WeChatBot',

        // Scrapers.
        'python-requests', 'PostmanRuntime', 'curl', 'wget',
        'HTTrack', 'Scrapy', 'Java/1.', 'HttpClient',
        'libwww-perl', 'PHP/', 'Go-http-client', 'Google-HTTP-Java-Client',
        'HttpURLConnection', 'urllib', 'aiohttp', 'http-kit',

        // Other Common Bots.
        'Applebot', 'FacebookExternalHit', 'CensysInspect',
        'Archive.org_bot', 'ZoominfoBot', 'heritrix', 'LinkChecker',
        'Googlebot-Image', 'Googlebot-Video', 'PetalBot',
        'BLEXBot', 'Siteimprove', 'DuckDuckBot', 'CCBot',

        // Developer Tools and Libraries.
        'OkHttp', 'python-urllib', 'PycURL', 'aiohttp',
        'Ruby', 'Node.js', 'HttpClient', 'Go-http-client',
        'HttpRequest', 'Java/', 'Apache-HttpClient',

        // Miscellaneous Bots.
        'DataForSeoBot', 'SerpstatBot', 'netEstate NE Crawler',
        'feed', 'bot', 'checker', 'fetch', 'scan',
        'probe', 'monitor', 'index', 'explorer',
        'Spider', 'Crawler', 'Robot', 'Headless',

        // Headless Browsers.
        'HeadlessChrome', 'Puppeteer', 'PhantomJS', 'Selenium',
        'Playwright', 'Trident', 'Electron', 'Node.js',
        
        // Vulnerability Scanners and Security Tools.
        'WPScan', 'Nessus', 'Nikto', 'Acunetix', 'sqlmap',
        'BurpSuite', 'ZAP', 'AppSpider', 'F-Secure',
        'Nmap', 'Metasploit', 'OpenVAS', 'Qualys',
        'Shodan', 'Censys',

        // API Clients.
        'Postman', 'Insomnia', 'Swagger-Codegen', 'SoapUI',
        'RestSharp', 'http-kit', 'HttpClient', 'Guzzle',
        'Google-API-Java-Client', 'Axios', 'Fetch',

        // Extensions to Catch Generic Bot Patterns.
        'robot', 'spider', 'crawler', 'headless',
        'scraper', 'scan', 'fetch', 'indexer', 'probe',
        'checker', 'monitor', 'bot'
    ];

    foreach ( $crawlers as $crawler ) {
        if ( stripos( $user_agent, $crawler ) !== false ) {
            return true;
        }
    }

    return false;
}

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
 * Enqueues Chart.js for stats visualization.
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

    wp_enqueue_script( 'chartjs', plugins_url( 'assets/js/charts.js', __FILE__ ), [], null, true );
}
add_action( 'admin_enqueue_scripts', 'sfwp_enqueue_scripts' );

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
        SELECT page, unique_visits, all_visits
        FROM $table_name
        WHERE date = %s AND page != '/404'
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

    include plugin_dir_path( __FILE__ ) . 'views/admin-stats-page.php';
}

/**
 * Handles the download of historical traffic data.
 * 
 * @since 1.0.0
 * @return void
 */
function sfwp_download_stats() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'Unauthorized access.', 'sfwp' ) );
    }

    global $wpdb;

    // Define the date range (can be customized for filterable dates).
    $start_date = isset( $_GET['start_date'] ) ? sanitize_text_field( $_GET['start_date'] ) : date( 'Y-m-d', strtotime( '-7 days' ) );
    $end_date   = isset( $_GET['end_date'] ) ? sanitize_text_field( $_GET['end_date'] ) : date( 'Y-m-d' );

    // Fetch stats data within the date range.
    $results = $wpdb->get_results( $wpdb->prepare( "
        SELECT date, page, referrer, unique_visits, all_visits
        FROM {$wpdb->prefix}sfwp_stats
        WHERE date BETWEEN %s AND %s
        ORDER BY date ASC
    ", $start_date, $end_date ) );

    // Generate CSV content.
    $csv_output = fopen( 'php://output', 'w' );
    header( 'Content-Type: text/csv' );
    header( 'Content-Disposition: attachment; filename="stats.csv"' );

    // Add headers.
    fputcsv( $csv_output, [ 'Date', 'Page', 'Referrer', 'Unique Visits', 'All Visits' ] );

    // Add rows.
    foreach ( $results as $row ) {
        fputcsv( $csv_output, [
            $row->date,
            $row->page,
            $row->referrer ? $row->referrer : 'Direct Traffic',
            $row->unique_visits,
            $row->all_visits
        ] );
    }

    fclose( $csv_output );
    exit;
}
add_action( 'admin_post_sfwp_download_stats', 'sfwp_download_stats' );

function sfwp_update_database() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'sfwp_stats';

    $wpdb->query( "ALTER TABLE $table_name ADD COLUMN referrer VARCHAR(255) DEFAULT NULL AFTER page;" );
}
register_activation_hook( __FILE__, 'sfwp_update_database' );

/**
 * Deletes all data from the stats table.
 *
 * @since 1.0.1
 * @return void
 */
function sfwp_delete_all_stats_data() {
    if ( isset( $_GET['delete_stats'] ) && current_user_can( 'manage_options' ) ) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'sfwp_stats';

        // Delete all data from the stats table
        $wpdb->query( "TRUNCATE TABLE $table_name" );

        // Add admin notice
        add_action( 'admin_notices', function() {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'All stats data has been deleted.', 'sfwp' ) . '</p></div>';
        } );
    }
}
add_action( 'admin_init', 'sfwp_delete_all_stats_data' );
