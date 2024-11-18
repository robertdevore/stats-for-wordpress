<div class="wrap">
    <h1>
        <?php esc_html_e( 'Stats for WordPress®', 'sfpw' ); ?>
        <a id="stats-wp-download-data" href="<?php echo esc_url( add_query_arg( 'action', 'sfpw_download_stats', admin_url( 'admin-post.php' ) ) ); ?>" 
        class="button button-primary" 
        style="margin-left: 15px; float: right;">
            <span class="dashicons dashicons-download" style="margin-right: 5px;"></span>
            <?php esc_html_e( 'Download', 'sfpw' ); ?>
        </a>
    </h1>

    <hr style="margin: 24px 0;" />

    <canvas id="sfpw-chart" width="100%" height="40"></canvas>

    <div class="sfpw-tables">
        <!-- Most Visited Pages Table -->
        <div class="sfpw-table">
            <h2><?php esc_html_e( "Today's Most Visited Pages", 'sfpw' ); ?></h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Page', 'sfpw' ); ?></th>
                        <th><?php esc_html_e( 'Unique Visits', 'sfpw' ); ?></th>
                        <th><?php esc_html_e( 'All Visits', 'sfpw' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( $page_data ) : ?>
                        <?php foreach ( $page_data as $page ) : ?>
                            <tr>
                                <td>
                                    <a href="<?php echo esc_url( home_url( $page->page ) ); ?>" target="_blank">
                                        <?php echo esc_html( $page->page ); ?>
                                    </a>
                                </td>
                                <td><?php echo esc_html( $page->unique_visits ); ?></td>
                                <td><?php echo esc_html( $page->all_visits ); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="3"><?php esc_html_e( 'No data available for today.', 'sfpw' ); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Top Referrers Table -->
        <div class="sfpw-table">
            <h2><?php esc_html_e( 'Top Referrers', 'sfpw' ); ?></h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Referrer', 'sfpw' ); ?></th>
                        <th><?php esc_html_e( 'Visits', 'sfpw' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( $referrer_data ) : ?>
                        <?php foreach ( $referrer_data as $referrer ) : ?>
                            <tr>
                                <td>
                                    <?php echo esc_html( $referrer->referrer ? $referrer->referrer : __( 'Direct Traffic', 'sfpw' ) ); ?>
                                </td>
                                <td><?php echo esc_html( $referrer->visits ); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="2"><?php esc_html_e( 'No referrer data available for today.', 'sfpw' ); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener( "DOMContentLoaded", function() {
    const ctx = document.getElementById( "sfpw-chart" ).getContext( "2d" );
    new Chart( ctx, {
        type: "bar",
        data: {
            labels: <?php echo wp_json_encode( $dates ); ?>,
            datasets: [
                {
                    label: "<?php echo esc_js( __( 'Unique Visits', 'sfpw' ) ); ?>",
                    data: <?php echo wp_json_encode( $unique_visits ); ?>,
                    backgroundColor: "rgba(54, 162, 235, 0.6)",
                },
                {
                    label: "<?php echo esc_js( __( 'All Visits', 'sfpw' ) ); ?>",
                    data: <?php echo wp_json_encode( $all_visits ); ?>,
                    backgroundColor: "rgba(75, 192, 192, 0.6)",
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false, // Allows dynamic resizing
            scales: {
                x: { title: { display: true, text: "<?php echo esc_js( __( 'Date', 'sfpw' ) ); ?>" } },
                y: { title: { display: true, text: "<?php echo esc_js( __( 'Visits', 'sfpw' ) ); ?>" } }
            }
        }
    } );
} );
</script>

<style>
#stats-wp-download-data {
    margin-left: 15px;
    float: right;
    display: flex;
    justify-content: space-between;
    align-content: center;
    align-items: center;
}

/* Adjust chart height for mobile */
#sfpw-chart {
    max-height: 500px; /* Default height for desktop */
}

@media ( max-width: 768px ) {
    #sfpw-chart {
        max-height: 700px; /* Taller height for mobile */
    }

    .sfpw-tables {
        flex-direction: column; /* Stack tables vertically */
    }
}

/* Flexbox styling for tables */
.sfpw-tables {
    display: flex;
    gap: 20px;
    margin-top: 20px;
}

.sfpw-table {
    flex: 1;
}
</style>
