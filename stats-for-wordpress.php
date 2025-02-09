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

define( 'SEO_WP_VERSION', '1.0.0' );
define( 'SEO_WP_PLUGIN_DIR', plugin_dir_url( __FILE__ ) );

require 'includes/db-table.php';
require 'includes/enqueue.php';
require 'includes/settings.php';

require 'vendor/plugin-update-checker/plugin-update-checker.php';
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$myUpdateChecker = PucFactory::buildUpdateChecker(
	'https://github.com/robertdevore/stats-for-wordpress/',
	__FILE__,
	'stats-for-wordpress'
);

// Set the branch that contains the stable release.
$myUpdateChecker->setBranch( 'main' );

// Check if Composer's autoloader is already registered globally.
if ( ! class_exists( 'RobertDevore\WPComCheck\WPComPluginHandler' ) ) {
    require_once __DIR__ . '/vendor/autoload.php';
}

use RobertDevore\WPComCheck\WPComPluginHandler;

new WPComPluginHandler( plugin_basename( __FILE__ ), 'https://robertdevore.com/why-this-plugin-doesnt-support-wordpress-com-hosting/' );

/**
 * Load plugin text domain for localization.
 *
 * @since  1.1.0
 * @return void
 */
function stats_wp_load_textdomain() {
    load_plugin_textdomain( 'stats-wp', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'plugins_loaded', 'stats_wp_load_textdomain' );

/**
 * Logs visits on every page load, excluding crawlers and non-page resources.
 * 
 * @since  1.0.0
 * @return void
 */
function sfwp_log_visit() {
    if ( is_admin() || current_user_can( 'manage_options' ) ) {
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
        '/wp-json/wp/',
        '/wp-json/oembed/',
        '/wp-json/contact-form-7/',
        '/wp-json/wc/',
        '/wp-json/jetpack/',
        '/wp-login.php',
        '/wp-register.php',
        '/wp-cron.php',
        '/xmlrpc.php',
        '/wp-trackback.php',
        '/wp-comments-post.php',
        '/wp-admin/admin-ajax.php',
        '/?wc-ajax=get_refreshed_fragments',
        '/?wc-ajax=update_order_review',
        '/?wc-ajax=apply_coupon',
        '/?wc-ajax=remove_coupon',
        '/?wc-ajax=add_to_cart',
        '/?wc-ajax=remove_from_cart',
        '/?wc-ajax=checkout',
        '/?wc-ajax=get_variation',
        '/?wc-ajax=update_shipping_method',
        '/?wc-ajax=get_cart_contents',
        '/?wc-ajax=update_cart',
        '/?wc-ajax=checkout_order_review',
        '/?wc-ajax=update_customer',
        '/?wc-ajax=get_customer_details',

        // WordPress® admin and asset paths.
        '/wp-admin/',
        '/wp-admin/load-scripts.php',
        '/wp-admin/load-styles.php',
        '/wp-admin/async-upload.php',
        '/wp-admin/customize.php',
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
        '/trackback/', '/wp-json/wp/v2/comments/', '/wp-json/wp/v2/posts/',

        // Common WordPress® plugin paths.
        '/wp-content/plugins/woocommerce/',
        '/wp-content/plugins/elementor/',
        '/wp-content/plugins/jetpack/',
        '/wp-content/plugins/contact-form-7/',
        '/wp-content/plugins/wp-rocket/',
        '/wp-content/plugins/wpforms/',
        '/wp-content/plugins/litespeed-cache/',
        '/wp-content/plugins/wp-super-cache/',
        '/wp-content/plugins/all-in-one-seo-pack/',
        '/wp-content/plugins/yoast/',
        '/wp-content/plugins/google-site-kit/',
        'breeze_check_cache_available',

        // Query string patterns often used in WordPress®.
        '?ver=', '?preview=', '?attachment_id=', '?utm_', '?amp=',
        '?fbclid=', '?gclid=', '?ref=', '?_ga=', '?_gl=', '?_hsenc=', '?_openstat=',

        // Additional patterns.
        '/.well-known/', '/.well-known/security.txt', '/.well-known/assetlinks.json',
        '/.well-known/apple-app-site-association', '/.well-known/openid-configuration',
        '/.well-known/change-password',
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
        'Googlebot', 'Bingbot', 'Slurp', 'DuckDuckBot', 'Baiduspider',
        'YandexBot', 'Sogou', 'Exabot', 'facebot', 'ia_archiver',
        'SeznamBot', 'APIs-Google', 'Google-Read-Aloud', 'Google Favicon',
        'YetiBot', 'Bytespider', 'Amazonbot', 'CoccocBot',

        // SEO & Marketing Bots.
        'AhrefsBot', 'SemrushBot', 'DotBot', 'rogerbot', 'MJ12bot',
        'Screaming Frog', 'Seokicks-Robot', 'LinkpadBot', 'MegaIndex',
        'Mediapartners-Google', 'OnPageBot', 'Uptime.com Bot', 'SerpstatBot',
        'DataForSeoBot', 'Moz', 'SEMrush', 'SEOkicks',

        // AI Bots and Chatbots.
        'ChatGPT', 'OpenAI', 'Bard', 'Claude', 'Anthropic', 'Jasper',
        'Wit.ai', 'Dialogflow', 'ChatGPTBot', 'Google-ChatBot', 'DeepMind',
        'HuggingFace', 'GPT', 'AI', 'Transformer', 'BingPreview',

        // Website Monitoring/Testing Tools.
        'UptimeRobot', 'Pingdom', 'Site24x7', 'Zabbix', 'Monitis',
        'AppDynamics', 'StatusCake', 'BetterUptime', 'NewRelicPinger',
        'Catchpoint', 'GTmetrix', 'Cloudinary',

        // Social Media Bots.
        'Twitterbot', 'LinkedInBot', 'Slackbot', 'Pinterestbot',
        'WhatsApp', 'DiscordBot', 'TelegramBot', 'WeChatBot',
        'Tumblr', 'SkypeUriPreview', 'SnapchatBot', 'FacebookBot',

        // Scrapers & Crawlers.
        'python-requests', 'PostmanRuntime', 'curl', 'wget', 'HTTrack',
        'Scrapy', 'Java/1.', 'HttpClient', 'libwww-perl', 'PHP/',
        'Go-http-client', 'Google-HTTP-Java-Client', 'HttpURLConnection',
        'urllib', 'aiohttp', 'http-kit', 'scraper', 'fetch',

        // Other Common Bots.
        'Applebot', 'FacebookExternalHit', 'CensysInspect',
        'Archive.org_bot', 'ZoominfoBot', 'heritrix', 'LinkChecker',
        'Googlebot-Image', 'Googlebot-Video', 'PetalBot', 'BLEXBot',
        'Siteimprove', 'DuckDuckBot', 'CCBot', 'AlexaWebCrawler',

        // Developer Tools & Libraries.
        'OkHttp', 'python-urllib', 'PycURL', 'aiohttp', 'Ruby',
        'Node.js', 'HttpClient', 'Go-http-client', 'HttpRequest',
        'Java/', 'Apache-HttpClient', 'CURL', 'Wget',

        // Headless Browsers & Automation Tools.
        'HeadlessChrome', 'Puppeteer', 'PhantomJS', 'Selenium',
        'Playwright', 'Trident', 'Electron', 'Node.js',
        'Cloudflare-Workers', 'Googlebot-News',

        // Vulnerability Scanners & Security Tools.
        'WPScan', 'Nessus', 'Nikto', 'Acunetix', 'sqlmap',
        'BurpSuite', 'ZAP', 'AppSpider', 'F-Secure',
        'Nmap', 'Metasploit', 'OpenVAS', 'Qualys',
        'Shodan', 'Censys', 'NetcraftSurveyAgent',

        // API Clients.
        'Postman', 'Insomnia', 'Swagger-Codegen', 'SoapUI',
        'RestSharp', 'http-kit', 'HttpClient', 'Guzzle',
        'Google-API-Java-Client', 'Axios', 'Fetch', 'GraphQL',

        // Miscellaneous Bots.
        'DataForSeoBot', 'SerpstatBot', 'netEstate NE Crawler',
        'feed', 'bot', 'checker', 'fetch', 'scan',
        'probe', 'monitor', 'index', 'explorer',
        'Spider', 'Crawler', 'Robot', 'Headless',

        // Generic Bot Patterns.
        'robot', 'spider', 'crawler', 'headless', 'scraper',
        'scan', 'fetch', 'indexer', 'probe', 'checker',
        'monitor', 'bot', 'search', 'preview'
    ];

    foreach ( $crawlers as $crawler ) {
        if ( stripos( $user_agent, $crawler ) !== false ) {
            return true;
        }
    }

    return false;
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
