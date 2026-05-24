<?php
global $wpdb;

$log    = new Advanced_Form_Integration_Log();
$result = $log->get_row( $log->db->prepare( "SELECT * FROM {$log->table} WHERE id = %d", $id ) );

// Guard: log not found
if ( empty( $result ) ) {
    echo '<div class="wrap"><div class="notice notice-error"><p>';
    /* translators: %d: log ID */
    printf( esc_html__( 'Log #%d was not found.', 'advanced-form-integration' ), absint( $id ) );
    echo '</p></div></div>';
    return;
}

$integration_id   = isset( $result['integration_id'] ) && $result['integration_id'] ? $result['integration_id'] : '';
$request_data     = isset( $result['request_data'] ) && $result['request_data'] ? json_decode( $result['request_data'], true ) : '';
$response_code    = isset( $result['response_code'] ) && $result['response_code'] ? $result['response_code'] : '';
$response_data    = isset( $result['response_data'] ) && $result['response_data'] ? json_decode( $result['response_data'], true ) : '';
$response_message = isset( $result['response_message'] ) && $result['response_message'] ? $result['response_message'] : '';
$time             = isset( $result['time'] ) && $result['time'] ? $result['time'] : '';

$resend_nonce = wp_create_nonce( 'adfoin-resend-log' );
$delete_url   = wp_nonce_url(
    admin_url( 'admin.php?page=advanced-form-integration-log&action=delete&id=' . absint( $id ) ),
    'bulk-logs'
);

// Check whether the integration still exists
$integration_title = '';
if ( $integration_id ) {
    $post = get_post( absint( $integration_id ) );
    if ( $post && ! is_wp_error( $post ) ) {
        $integration_title = get_the_title( $post );
    }
}

$full_log = array(
    'log_id'           => absint( $id ),
    'integration_id'   => $integration_id,
    'response_code'    => $response_code,
    'response_message' => $response_message,
    'request_data'     => $request_data,
    'response_data'    => $response_data,
    'time'             => $time,
);

// Response code CSS class
$code_class = '';
if ( $response_code >= 200 && $response_code < 300 ) {
    $code_class = 'success';
} elseif ( $response_code >= 400 || ( ! empty( $response_code ) && ! is_numeric( $response_code ) ) ) {
    $code_class = 'error';
}
?>

<div class="wrap">
    <?php adfoin_display_admin_header(); ?>

    <div class="afi-container">

        <div class="afi-card">
            <div class="afi-card-header">
                <div class="afi-log-title-group">
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=advanced-form-integration-log' ) ); ?>" class="afi-log-back-btn" title="<?php esc_attr_e( 'Back to logs', 'advanced-form-integration' ); ?>">
                        <span class="dashicons dashicons-arrow-left-alt2"></span>
                    </a>
                    <h2 class="afi-card-title">
                        <?php
                        /* translators: %d: log ID */
                        printf( esc_html__( 'Log #%d', 'advanced-form-integration' ), absint( $id ) );
                        ?>
                    </h2>
                </div>
                <div class="afi-log-view-header-actions">
                    <button class="afi-btn-secondary afi-btn-copy-full-log"
                            data-full-log="<?php echo esc_attr( wp_json_encode( $full_log ) ); ?>"
                            title="<?php esc_attr_e( 'Copy Full Log', 'advanced-form-integration' ); ?>">
                        <svg class="afi-svg-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="14" height="14" x="8" y="8" rx="2" ry="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>
                        <span class="afi-btn-copy-label"><?php esc_html_e( 'Copy Full Log', 'advanced-form-integration' ); ?></span>
                    </button>
                    <a href="<?php echo esc_url( $delete_url ); ?>"
                       class="afi-btn-danger afi-btn-delete-log"
                       onclick="return confirm('<?php esc_attr_e( 'Delete this log entry? This cannot be undone.', 'advanced-form-integration' ); ?>');">
                        <svg class="afi-svg-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/></svg>
                        <?php esc_html_e( 'Delete Log', 'advanced-form-integration' ); ?>
                    </a>
                </div>
            </div>

            <table class="afi-log-details-table">
                <tr>
                    <th><?php esc_html_e( 'Time', 'advanced-form-integration' ); ?></th>
                    <td>
                        <span class="dashicons dashicons-clock afi-log-detail-clock"></span>
                        <?php echo esc_html( $time ); ?>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Integration ID', 'advanced-form-integration' ); ?></th>
                    <td>
                        <?php if ( $integration_id ) : ?>
                            <?php if ( $integration_title ) : ?>
                                <a href="<?php echo esc_url( admin_url( 'admin.php?page=advanced-form-integration&action=edit&id=' . absint( $integration_id ) ) ); ?>"
                                   title="<?php echo esc_attr( $integration_title ); ?>">
                                    <code class="afi-log-id-code">#<?php echo esc_html( $integration_id ); ?></code>
                                </a>
                            <?php else : ?>
                                <code class="afi-log-id-code">#<?php echo esc_html( $integration_id ); ?></code>
                                <span class="afi-log-deleted-badge" title="<?php esc_attr_e( 'This integration no longer exists', 'advanced-form-integration' ); ?>">
                                    <?php esc_html_e( 'deleted', 'advanced-form-integration' ); ?>
                                </span>
                            <?php endif; ?>
                        <?php else : ?>
                            &mdash;
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Response Code', 'advanced-form-integration' ); ?></th>
                    <td>
                        <span class="afi-status-badge <?php echo esc_attr( $code_class ); ?>">
                            <?php echo esc_html( $response_code ); ?>
                        </span>
                    </td>
                </tr>
                <?php if ( ! empty( $response_message ) ) : ?>
                <tr>
                    <th><?php esc_html_e( 'Response Message', 'advanced-form-integration' ); ?></th>
                    <td><?php echo esc_html( $response_message ); ?></td>
                </tr>
                <?php endif; ?>
                <tr>
                    <th><?php esc_html_e( 'Request Data', 'advanced-form-integration' ); ?></th>
                    <td>
                        <form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" id="resend-log-data">
                            <input type="hidden" name="action" value="adfoin_resend_log_data">
                            <input type="hidden" name="log_id" value="<?php echo absint( $id ); ?>">
                            <input type="hidden" name="integration_id" value="<?php echo absint( $integration_id ); ?>">
                            <input type="hidden" name="_wpnonce" value="<?php echo esc_attr( $resend_nonce ); ?>" />

                            <div class="afi-log-textarea-wrap">
                                <textarea id="adfoin-log-request-data" name="request-data"></textarea>
                            </div>

                            <div class="afi-log-resend-actions">
                                <input class="afi-btn-primary" type="submit" name="resend_log" value="<?php esc_attr_e( 'Resend Request', 'advanced-form-integration' ); ?>" />
                                <p class="afi-helper-text"><?php esc_html_e( 'You can edit the request data above before resending.', 'advanced-form-integration' ); ?></p>
                            </div>
                        </form>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Response Data', 'advanced-form-integration' ); ?></th>
                    <td>
                        <pre id="response-data" class="afi-code-block"></pre>
                    </td>
                </tr>
            </table>
        </div>

        <?php
        // Previous / Next navigation
        $prev_log_id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$log->table} WHERE id < %d ORDER BY id DESC LIMIT 1", $id ) );
        $next_log_id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$log->table} WHERE id > %d ORDER BY id ASC LIMIT 1", $id ) );
        ?>

        <div class="afi-log-navigation">
            <?php if ( $prev_log_id ) : ?>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=advanced-form-integration-log&action=view&id=' . absint( $prev_log_id ) ) ); ?>" class="afi-btn-secondary">
                    <span class="dashicons dashicons-arrow-left-alt2"></span>
                    <?php esc_html_e( 'Previous Log', 'advanced-form-integration' ); ?>
                </a>
            <?php else : ?>
                <div></div>
            <?php endif; ?>

            <?php if ( $next_log_id ) : ?>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=advanced-form-integration-log&action=view&id=' . absint( $next_log_id ) ) ); ?>" class="afi-btn-secondary">
                    <?php esc_html_e( 'Next Log', 'advanced-form-integration' ); ?>
                    <span class="dashicons dashicons-arrow-right-alt2"></span>
                </a>
            <?php endif; ?>
        </div>

    </div><!-- .afi-container -->
</div><!-- .wrap -->

<script>
(function () {
    var requestData  = <?php echo wp_json_encode( $request_data ); ?>;
    var responseData = <?php echo wp_json_encode( $response_data ); ?>;

    document.getElementById( 'response-data' ).textContent = JSON.stringify( responseData, null, 2 );
    document.getElementById( 'adfoin-log-request-data' ).textContent = JSON.stringify( requestData, null, 2 );

    jQuery( document ).ready( function ( $ ) {
        // CodeMirror on #adfoin-log-request-data is initialised by
        // add_log_code_editor() (advanced-form-integration.php) with the
        // proper application/json editor settings from wp_enqueue_code_editor().
        // The previous init here passed the plugin's localized `adfoin` object
        // as settings, which was incorrect — removed to avoid a double init.

        // Copy Full Log — spinner → tick → revert
        var COPY_SVG = {
            copy: '<svg class="afi-svg-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="14" height="14" x="8" y="8" rx="2" ry="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>',
            spin: '<svg class="afi-svg-icon afi-copy-spinner" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>',
            tick: '<svg class="afi-svg-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>'
        };

        $( '.afi-btn-copy-full-log' ).on( 'click', function ( e ) {
            e.preventDefault();
            var $btn = $( this );
            if ( $btn.hasClass( 'afi-copy-busy' ) ) { return; }

            var originalTitle = $btn.attr( 'title' ) || '';
            var fullLog       = $btn.data( 'full-log' );

            $btn.addClass( 'afi-copy-busy' )
                .find( '.afi-btn-copy-label' ).text( '' ).end()
                .find( '.afi-svg-icon' ).replaceWith( COPY_SVG.spin );

            navigator.clipboard.writeText( JSON.stringify( fullLog ) )
                .then( function () {
                    $btn.addClass( 'afi-copy-success' )
                        .find( '.afi-copy-spinner' ).replaceWith( COPY_SVG.tick );
                    $btn.find( '.afi-btn-copy-label' ).text( 'Copied!' );

                    setTimeout( function () {
                        $btn.removeClass( 'afi-copy-busy afi-copy-success' )
                            .attr( 'title', originalTitle )
                            .find( '.afi-svg-icon' ).replaceWith( COPY_SVG.copy );
                        $btn.find( '.afi-btn-copy-label' ).text( 'Copy Full Log' );
                    }, 2000 );
                } )
                .catch( function () {
                    $btn.removeClass( 'afi-copy-busy' )
                        .attr( 'title', originalTitle )
                        .find( '.afi-copy-spinner' ).replaceWith( COPY_SVG.copy );
                    $btn.find( '.afi-btn-copy-label' ).text( 'Copy Full Log' );
                } );
        } );
    } );
}());
</script>
