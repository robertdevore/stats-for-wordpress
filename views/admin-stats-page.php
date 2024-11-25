<div class="wrap">
    <h1>
        <?php esc_html_e( 'Stats for WordPress®', 'sfwp' ); ?>
        <a id="stats-wp-download-data" href="<?php echo esc_url( add_query_arg( 'action', 'sfwp_download_stats', admin_url( 'admin-post.php' ) ) ); ?>" 
        class="button button-primary" 
        style="margin-left: 15px; float: right;">
            <span class="dashicons dashicons-download" style="margin-right: 5px;"></span>
            <?php esc_html_e( 'Download', 'sfwp' ); ?>
        </a>
    </h1>

    <hr style="margin: 24px 0;" />

    <canvas id="sfwp-chart" width="100%" height="40"></canvas>

    <div class="sfwp-tables">
        <!-- Most Visited Pages Table -->
        <div class="sfwp-table">
            <h2><?php esc_html_e( "Today's Most Visited Pages", 'sfwp' ); ?></h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Page', 'sfwp' ); ?></th>
                        <th><?php esc_html_e( 'Unique Visits', 'sfwp' ); ?></th>
                        <th><?php esc_html_e( 'All Visits', 'sfwp' ); ?></th>
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
                            <td colspan="3"><?php esc_html_e( 'No data available for today.', 'sfwp' ); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <?php if ( $not_found_data && ( $not_found_data->unique_visits || $not_found_data->all_visits ) ) : ?>
                <h2><?php esc_html_e( '404 Hits', 'sfwp' ); ?></h2>
                <p><?php printf( esc_html__( 'Unique 404 Hits: %d, Total 404 Hits: %d', 'sfwp' ), $not_found_data->unique_visits, $not_found_data->all_visits ); ?></p>
            <?php endif; ?>
        </div>

        <!-- Top Referrers Table -->
        <div class="sfwp-table">
            <h2><?php esc_html_e( 'Top Referrers', 'sfwp' ); ?></h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Referrer', 'sfwp' ); ?></th>
                        <th><?php esc_html_e( 'Visits', 'sfwp' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( $referrer_data ) : ?>
                        <?php foreach ( $referrer_data as $referrer ) : ?>
                            <tr>
                                <td>
                                    <?php echo esc_html( $referrer->referrer ? $referrer->referrer : __( 'Direct Traffic', 'sfwp' ) ); ?>
                                </td>
                                <td><?php echo esc_html( $referrer->visits ); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="2"><?php esc_html_e( 'No referrer data available for today.', 'sfwp' ); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener( "DOMContentLoaded", function() {
    const ctx = document.getElementById( "sfwp-chart" ).getContext( "2d" );
    new Chart( ctx, {
        type: "bar",
        data: {
            labels: <?php echo wp_json_encode( $dates ); ?>,
            datasets: [
                {
                    label: "<?php echo esc_js( __( 'Unique Visits', 'sfwp' ) ); ?>",
                    data: <?php echo wp_json_encode( $unique_visits ); ?>,
                    backgroundColor: "rgba(54, 162, 235, 0.6)",
                },
                {
                    label: "<?php echo esc_js( __( 'All Visits', 'sfwp' ) ); ?>",
                    data: <?php echo wp_json_encode( $all_visits ); ?>,
                    backgroundColor: "rgba(75, 192, 192, 0.6)",
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false, // Allows dynamic resizing
            scales: {
                x: { title: { display: true, text: "<?php echo esc_js( __( 'Date', 'sfwp' ) ); ?>" } },
                y: { title: { display: true, text: "<?php echo esc_js( __( 'Visits', 'sfwp' ) ); ?>" } }
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
#sfwp-chart {
    max-height: 500px; /* Default height for desktop */
}

@media ( max-width: 768px ) {
    #sfwp-chart {
        max-height: 700px; /* Taller height for mobile */
    }

    .sfwp-tables {
        flex-direction: column; /* Stack tables vertically */
    }
}

/* Flexbox styling for tables */
.sfwp-tables {
    display: flex;
    gap: 20px;
    margin-top: 20px;
}

.sfwp-table {
    flex: 1;
}
</style>
