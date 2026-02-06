<?php
global $wpdb;

$log              = new Advanced_Form_Integration_Log();
$result           = $log->get_row( "SELECT * FROM {$log->table} WHERE id = {$id}" );
$integration_id   = isset( $result['integration_id'] ) && $result['integration_id'] ? $result['integration_id'] : '';
$request_data     = isset( $result['request_data'] ) && $result['request_data'] ? json_decode( $result['request_data'], true ) : '';
$response_code    = isset( $result['response_code'] ) && $result['response_code'] ? $result['response_code'] : '';
$response_data    = isset( $result['response_data'] ) && $result['response_data'] ? json_decode( $result['response_data'], true ) : '';
$response_message = isset( $result['response_message'] ) && $result['response_message'] ? $result['response_message'] : '';
$time             = isset( $result['time'] ) && $result['time'] ? $result['time'] : '';
$nonce            = wp_create_nonce( 'adfoin-resend-log' );
$full_log = array(
    'integration_id'   => $integration_id,
    'response_code'    => $response_code,
    'response_message' => $response_message,
    'request_data'     => $request_data,
    'response_data'    => $response_data,
    'time'             => $time
);
?>

<div class="afi-container">

    <div class="afi-log-header">
        <div class="afi-logo">
            <span class="afi-logo-text"><?php _e( 'Log Details', 'advanced-form-integration' ); ?></span>
        </div>
        <div class="afi-header-actions">
            <a href="<?php echo admin_url( 'admin.php?page=advanced-form-integration-log' ); ?>" class="afi-btn-secondary">
                <span class="dashicons dashicons-arrow-left-alt2"></span> <?php _e( 'Back', 'advanced-form-integration' ); ?>
            </a>

        </div>
    </div>

    <div class="afi-card">
        <div class="afi-card-header">
            <h2 class="afi-card-title">
                <span class="dashicons dashicons-info"></span>
                <?php _e( 'Log Information', 'advanced-form-integration' ); ?>
            </h2>
            <button class="afi-btn-secondary button-copy-full-log" style="margin-left: auto;">
                <span class="dashicons dashicons-clipboard" style="line-height: 1.5;"></span> <?php _e( 'Copy Full Log', 'advanced-form-integration' ); ?>
            </button>
        </div>

        <table class="afi-log-details-table">
            <tr>
                <th><?php _e( 'Time', 'advanced-form-integration' ); ?></th>
                <td>
                    <span class="dashicons dashicons-clock" style="color: #8c8f94; font-size: 16px; width: 16px; height: 16px; vertical-align: text-bottom;"></span>
                    <?php echo esc_attr( $time ); ?>
                </td>
            </tr>
            <tr>
                <th><?php _e( 'Integration ID', 'advanced-form-integration' ); ?></th>
                <td>
                    <code style="font-size: 13px; padding: 3px 6px; background: #f0f0f1; border-radius: 3px;"><?php echo esc_attr( $integration_id ); ?></code>
                </td>
            </tr>
            <tr>
                <th><?php _e( 'Request Data', 'advanced-form-integration' ); ?></th>
                <td>
                    <form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" id="resend-log-data">
                        <input type="hidden" name="action" value="adfoin_resend_log_data">
                        <input type="hidden" name="log_id" value="<?php echo $id; ?>">
                        <input type="hidden" name="integration_id" value="<?php echo $integration_id; ?>">
                        <input type="hidden" name="_wpnonce" value="<?php echo $nonce; ?>" />
                        
                        <div style="border: 1px solid #8c8f94; border-radius: 4px; overflow: hidden;">
                            <textarea id="adfoin-log-request-data" name="request-data"></textarea>
                        </div>
                        
                        <div style="margin-top: 15px;">
                            <input class="afi-btn-primary" type="submit" name="resend_log" value="<?php esc_attr_e( 'Resend Request', 'advanced-form-integration' ); ?>" />
                            <p class="afi-helper-text"><?php _e('You can edit the request data above before resending.', 'advanced-form-integration'); ?></p>
                        </div>
                    </form>
                </td>
            </tr>
            <tr>
                <th><?php _e( 'Response Code', 'advanced-form-integration' ); ?></th>
                <td>
                    <?php 
                        $code_class = '';
                        if ( $response_code >= 200 && $response_code < 300 ) {
                            $code_class = 'success';
                        } elseif ( $response_code >= 400 || ( ! empty( $response_code ) && ! is_numeric( $response_code ) ) ) {
                            $code_class = 'error';
                        }
                    ?>

                    <span class="afi-status-badge <?php echo $code_class; ?>">
                        <?php echo stripslashes( $response_code ); ?>
                    </span>
                </td>
            </tr>
            <tr>
                <th><?php _e( 'Response Data', 'advanced-form-integration' ); ?></th>
                <td>
                    <pre id="response-data" class="afi-code-block"></pre>
                </td>
            </tr>
            <tr>
                <th><?php _e( 'Response Message', 'advanced-form-integration' ); ?></th>
                <td><?php echo stripslashes( $response_message ); ?></td>
            </tr>
        </table>
    </div>

    <?php
    // Get Previous Log ID
    $prev_log_id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$log->table} WHERE id < %d ORDER BY id DESC LIMIT 1", $id ) );

    // Get Next Log ID
    $next_log_id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$log->table} WHERE id > %d ORDER BY id ASC LIMIT 1", $id ) );
    ?>

    <div class="afi-log-navigation">
        <?php if ( $prev_log_id ) : ?>
            <a href="<?php echo admin_url( 'admin.php?page=advanced-form-integration-log&action=view&id=' . $prev_log_id ); ?>" class="afi-btn-secondary">
                <span class="dashicons dashicons-arrow-left-alt2"></span> <?php _e( 'Previous Log', 'advanced-form-integration' ); ?>
            </a>
        <?php else : ?>
            <div></div> <!-- Spacer -->
        <?php endif; ?>

        <?php if ( $next_log_id ) : ?>
            <a href="<?php echo admin_url( 'admin.php?page=advanced-form-integration-log&action=view&id=' . $next_log_id ); ?>" class="afi-btn-secondary">
                <?php _e( 'Next Log', 'advanced-form-integration' ); ?> <span class="dashicons dashicons-arrow-right-alt2"></span>
            </a>
        <?php endif; ?>
    </div>

</div>

<script>
    var requestData  = <?php echo json_encode( $request_data, true ); ?>;
    var responseData = <?php echo json_encode( $response_data, true ); ?>;
    var fullLog = <?php echo json_encode( $full_log, true ); ?>;

    // document.getElementById("request-data").textContent = JSON.stringify(requestData, undefined, 2);
    document.getElementById("response-data").textContent = JSON.stringify(responseData, undefined, 2);

    document.getElementById("adfoin-log-request-data").textContent = JSON.stringify(requestData, undefined, 2);

    jQuery(document).ready(function($) {
        wp.codeEditor.initialize($('#adfoin-log-request-data'), adfoin);

        $('.button-copy-full-log').on( 'click', function(e) {
            e.preventDefault();
            var $this = $(this);
            $this.text( 'Copying...');
            navigator.clipboard.writeText(JSON.stringify(fullLog));

            setTimeout(function() {
                $this.text('Copied to Clipboard');
            }, 1000);
        });

        $('.adfoin-log-edit').on('click', function(){
            $('.log-edit-form').show();
        });
    });
</script>