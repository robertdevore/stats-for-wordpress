# Stats for WordPress®

Stats for WordPress® is a lightweight analytics plugin designed to provide insights into your website's performance directly from the WordPress® admin dashboard. 

Track page views, unique visits, and referrers without relying on third-party services.

Your data, period.

---

## Features

- **Simple Analytics Tracking**: Logs page views, unique visits, and referrers.
- **Exclusion Rules**: Automatically excludes admin pages, crawlers, bots, and non-content paths (e.g., static assets, API requests).
- **Admin Dashboard Visualization**: Provides clear data visualization for page views and referrers.
- **Historical Data Export**: Download stats as a CSV file for further analysis.
- **404 Tracking**: Logs visits to non-existent pages for debugging purposes.

---

## Installation

1. Download the plugin from [GitHub](https://github.com/robertdevore/stats-for-wordpress/).
2. Upload the plugin files to the `/wp-content/plugins/stats-for-wordpress/` directory.
3. Activate the plugin through the 'Plugins' menu in WordPress®.
4. Visit the **Stats** section in your WordPress® admin dashboard to view analytics.

---

## Usage

### Viewing Stats
- Navigate to the **Stats** menu in your WordPress® admin dashboard to:
  - View daily unique and all visits for the past 7 days.
  - See the most visited pages on your site.
  - Analyze the top referrers sending traffic to your site.

### Downloading Stats
- Click the **Download CSV** button on the stats page to export historical data for a custom date range.

### Resetting Stats
- Append `?delete_stats=1` to your admin URL (e.g., `https://example.com/wp-admin/admin.php?page=sfwp-stats&delete_stats=1`) to clear all recorded stats.

---

## Excluded Pages and Bots

### Pages Excluded by Default
- WordPress® core pages and assets:
  - `/favicon.ico`, `/robots.txt`, `/sitemap.xml`, `/wp-json/`, etc.
- Static files (e.g., `.css`, `.js`, `.jpg`).
- Admin pages and AJAX endpoints.
- Feeds and query patterns (e.g., `?utm_`, `?ver=`).

### Bots Excluded by Default
- Common search engine bots (e.g., Googlebot, Bingbot).
- SEO tools (e.g., AhrefsBot, SemrushBot).
- Scrapers and crawlers (e.g., `curl`, `wget`).
- Headless browsers and security tools.

---

## Database Schema

The plugin creates a custom database table to store analytics data:

| Field          | Type          | Description                             |
|----------------|---------------|-----------------------------------------|
| `id`           | BIGINT(20)    | Auto-incremented unique ID.             |
| `date`         | DATE          | Date of the visit.                      |
| `page`         | VARCHAR(255)  | The URL path of the visited page.       |
| `referrer`     | VARCHAR(255)  | Referring URL, if available.            |
| `unique_visits`| INT(11)       | Count of unique visitors.               |
| `all_visits`   | INT(11)       | Total visit count (includes repeats).   |

---

## Hooks & Filters

### Hooks
- **`wp`**: Logs page visits, excluding non-content requests.
- **`register_activation_hook`**: Creates or updates the database table during activation.
- **`admin_post_sfwp_download_stats`**: Handles CSV downloads.
- **`admin_init`**: Deletes all stats if triggered via a query parameter.

### Filters
None currently available.

---

## Customization

### Adding Excluded Paths
You can add more excluded paths by modifying the `$excluded_paths` array in the `sfwp_log_visit()` function.

### Adding Bots to Exclude
To exclude additional bots, add their user-agent strings to the `$crawlers` array in the `sfwp_is_crawler()` function.

---

## Troubleshooting

### Stats Not Updating
1. Ensure the plugin is activated.
2. Check if the database table `wp_sfwp_stats` exists. If not, deactivate and reactivate the plugin.

### Missing Data for Specific Pages
- Ensure the page URLs are not part of the excluded paths or patterns.

### Referrers Include My Site
- Ensure the plugin is properly filtering internal referrers. If you use multiple domains, add them to the internal referrer check in `sfwp_log_visit()`.

---

## Contributing

Contributions are welcome!  
1. Fork the repository on GitHub: [Stats for WordPress](https://github.com/robertdevore/stats-for-wordpress/).
2. Submit a pull request with your proposed changes.

---

## License

This plugin is licensed under the [GPL-2.0+](http://www.gnu.org/licenses/gpl-2.0.txt).  
You are free to use, modify, and distribute it under the terms of the GNU General Public License.

---

## Support

For support, visit [Robert DeVore's website](https://robertdevore.com) or open an issue on the [GitHub repository](https://github.com/robertdevore/stats-for-wordpress/issues).
