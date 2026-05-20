<?php
/**
 * OAuth Account Management System
 * 
 * Handles OAuth account management UI and popup communication
 * 
 * @package Advanced_Form_Integration
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ADFOIN_OAuth_Manager {

    /**
     * Render OAuth settings view with accounts table, modal, and inline JavaScript.
     *
     * @param string $platform     Platform slug (e.g., 'zohocrm').
     * @param string $title        Platform title (e.g., 'Zoho CRM').
     * @param array  $fields       Array of field definitions.
     * @param string $instructions HTML content for instructions sidebar.
     * @param array  $config       Optional configuration.
     */
    public static function render_oauth_settings_view( $platform, $title, $fields, $instructions, $config = array() ) {
        
        // Default config
        $config = wp_parse_args( $config, array(
            'ajax_action_prefix' => 'adfoin',
            'show_status'        => true,
            'enable_test'        => false,
            'modal_title'        => sprintf( __( 'Connect %s', 'advanced-form-integration' ), $title ),
            'submit_text'        => __( 'Save & Authorize', 'advanced-form-integration' ),
        ) );

        $nonce = wp_create_nonce( 'advanced-form-integration' );
        ?>

        <div class="afi-container" id="poststuff">
            <div id="post-body" class="metabox-holder columns-2">
                <!-- Main Content -->
                <div id="post-body-content">
                    <div class="afi-accounts-card">
                        <div class="afi-accounts-header">
                            <h2 class="afi-accounts-title">
                                <span class="dashicons dashicons-admin-users"></span>
                                <?php echo esc_html( sprintf( __( '%s Accounts', 'advanced-form-integration' ), $title ) ); ?>
                            </h2>
                            <button id="adfoin-add-<?php echo esc_attr( $platform ); ?>-account" 
                                    class="button button-primary">
                                <?php esc_html_e( 'Add Account', 'advanced-form-integration' ); ?>
                            </button>
                        </div>
                        <div class="afi-accounts-body">
                            <table class="wp-list-table widefat striped" 
                                   id="adfoin-<?php echo esc_attr( $platform ); ?>-table"
                                   data-fields='<?php echo esc_attr( wp_json_encode( $fields ) ); ?>'>
                                <thead>
                                    <tr>
                                        <th><?php esc_html_e( 'Title', 'advanced-form-integration' ); ?></th>
                                        <?php foreach ( $fields as $field ) : ?>
                                            <?php if ( ! isset( $field['show_in_table'] ) || $field['show_in_table'] ) : ?>
                                                <th><?php echo esc_html( $field['label'] ); ?></th>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                        <?php if ( $config['show_status'] ) : ?>
                                            <th><?php esc_html_e( 'Status', 'advanced-form-integration' ); ?></th>
                                        <?php endif; ?>
                                        <th><?php esc_html_e( 'Actions', 'advanced-form-integration' ); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Populated by JavaScript -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div id="postbox-container-1" class="postbox-container">
                    <div class="afi-instructions-card">
                        <div class="afi-instructions-header">
                            <span class="dashicons dashicons-book"></span>
                            <?php esc_html_e( 'Instructions', 'advanced-form-integration' ); ?>
                        </div>
                        <div class="afi-instructions-body">
                            <?php echo wp_kses_post( $instructions ); ?>
                        </div>
                    </div>
                </div>
            </div>
            <br class="clear">
        </div>

        <!-- Modal -->
        <div id="adfoin-modal-overlay" class="afi-modal" style="display:none;">
            <div id="adfoin-<?php echo esc_attr( $platform ); ?>-modal" class="afi-modal-content" style="display:block; width:500px;">
                <span class="afi-close adfoin-modal-close">&times;</span>
                
                <h2 style="margin-top:0;"><?php echo esc_html( $config['modal_title'] ); ?></h2>
                
                <form id="adfoin-<?php echo esc_attr( $platform ); ?>-form">
                    <input type="hidden" id="adfoin_<?php echo esc_attr( $platform ); ?>_id" name="id">
                    
                    <table class="form-table">
                        <tr>
                            <th>
                                <label for="adfoin_<?php echo esc_attr( $platform ); ?>_title">
                                    <?php esc_html_e( 'Title', 'advanced-form-integration' ); ?>
                                </label>
                            </th>
                            <td>
                                <input type="text" 
                                       id="adfoin_<?php echo esc_attr( $platform ); ?>_title" 
                                       name="title" 
                                       class="regular-text" 
                                       placeholder="<?php esc_attr_e( 'e.g., My Account', 'advanced-form-integration' ); ?>" 
                                       required>
                            </td>
                        </tr>
                        
                        <?php foreach ( $fields as $field ) : ?>
                            <tr>
                                <th>
                                    <label for="adfoin_<?php echo esc_attr( $platform ); ?>_<?php echo esc_attr( $field['name'] ); ?>">
                                        <?php echo esc_html( $field['label'] ); ?>
                                    </label>
                                </th>
                                <td>
                                    <?php if ( $field['type'] === 'select' && ! empty( $field['options'] ) ) : ?>
                                        <select id="adfoin_<?php echo esc_attr( $platform ); ?>_<?php echo esc_attr( $field['name'] ); ?>" 
                                                name="<?php echo esc_attr( $field['name'] ); ?>" 
                                                class="regular-text"
                                                <?php echo ! empty( $field['required'] ) ? 'required' : ''; ?>>
                                            <?php foreach ( $field['options'] as $value => $label ) : ?>
                                                <option value="<?php echo esc_attr( $value ); ?>">
                                                    <?php echo esc_html( $label ); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    <?php else : ?>
                                        <input type="<?php echo esc_attr( $field['type'] ); ?>" 
                                               id="adfoin_<?php echo esc_attr( $platform ); ?>_<?php echo esc_attr( $field['name'] ); ?>" 
                                               name="<?php echo esc_attr( $field['name'] ); ?>" 
                                               class="regular-text" 
                                               placeholder="<?php echo esc_attr( ! empty( $field['placeholder'] ) ? $field['placeholder'] : '' ); ?>"
                                               <?php echo ! empty( $field['required'] ) ? 'required' : ''; ?>>
                                    <?php endif; ?>
                                    
                                    <?php if ( ! empty( $field['description'] ) ) : ?>
                                        <p class="description"><?php echo wp_kses_post( $field['description'] ); ?></p>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                    
                    <p class="submit" style="text-align: right; margin-top: 15px;">
                        <button type="submit" 
                                id="adfoin-<?php echo esc_attr( $platform ); ?>-submit-btn" 
                                class="button button-primary">
                            <?php echo $config['submit_text']; ?>
                        </button>
                        <span class="spinner" style="float: none; margin-left: 10px;"></span>
                    </p>
                </form>
            </div>
        </div>

        <style>
            .afi-modal {
                position: fixed;
                z-index: 10000;
                left: 0;
                top: 0;
                width: 100%;
                height: 100%;
                overflow: auto;
                background-color: rgba(0,0,0,0.4);
                animation: fadeIn 0.3s;
            }
            .afi-modal-content {
                background-color: #fefefe;
                margin: 5% auto;
                padding: 20px;
                border: 1px solid #888;
                width: 80%;
                max-width: 500px;
                border-radius: 4px;
                box-shadow: 0 4px 6px rgba(0,0,0,0.1);
                animation: slideIn 0.3s;
            }
            .afi-close {
                color: #aaa;
                float: right;
                font-size: 28px;
                font-weight: bold;
                line-height: 20px;
                cursor: pointer;
            }
            .afi-close:hover,
            .afi-close:focus {
                color: #000;
            }
            @keyframes fadeIn {
                from { opacity: 0; }
                to { opacity: 1; }
            }
            @keyframes slideIn {
                from { transform: translateY(-50px); opacity: 0; }
                to { transform: translateY(0); opacity: 1; }
            }
        </style>

        <?php
        // Enqueue the controller JS (does nothing if already enqueued elsewhere
        // on the page). Per-platform config is passed via the inline init call
        // below.
        if ( defined( 'ADVANCED_FORM_INTEGRATION_ASSETS' ) ) {
            // Use filemtime() as the cache-busting version so any change to
            // oauth-manager.js is picked up by browsers without a manual hard
            // reload OR a plugin-version bump. Falls back to the plugin
            // version (or null) if the file isn't readable.
            $asset_path = defined( 'ADVANCED_FORM_INTEGRATION_PATH' )
                ? rtrim( ADVANCED_FORM_INTEGRATION_PATH, '/' ) . '/assets/js/oauth-manager.js'
                : '';
            $plugin_version = defined( 'ADVANCED_FORM_INTEGRATION_VERSION' )
                ? ADVANCED_FORM_INTEGRATION_VERSION
                : false;
            $mtime = ( $asset_path && is_readable( $asset_path ) ) ? (string) filemtime( $asset_path ) : '';
            $version = $mtime
                ? ( $plugin_version ? $plugin_version . '.' . $mtime : $mtime )
                : $plugin_version;
            wp_enqueue_script(
                'adfoin-oauth-manager',
                ADVANCED_FORM_INTEGRATION_ASSETS . '/js/oauth-manager.js',
                array( 'jquery' ),
                $version,
                true
            );
        }

        $oauth_init = array(
            'platform'   => $platform,
            'fields'     => $fields,
            'nonce'      => $nonce,
            'showStatus' => (bool) $config['show_status'],
            'enableTest' => (bool) $config['enable_test'],
            'i18n'       => array(
                'save'             => $config['submit_text'],
                'update'           => __( 'Update & Authorize', 'advanced-form-integration' ),
                'confirmDelete'    => __( 'Are you sure you want to delete this account?', 'advanced-form-integration' ),
                'deleteFailed'     => __( 'Failed to delete account.', 'advanced-form-integration' ),
                'saveFailed'       => __( 'Failed to save account.', 'advanced-form-integration' ),
                'error'            => __( 'An error occurred. Please try again.', 'advanced-form-integration' ),
                'loading'          => __( 'Loading...', 'advanced-form-integration' ),
                'untitled'         => __( 'Untitled', 'advanced-form-integration' ),
                'connected'        => __( 'Connected', 'advanced-form-integration' ),
                'notConnected'     => __( 'Not Connected', 'advanced-form-integration' ),
                'connectionBroken' => __( 'Connection broken — Reconnect', 'advanced-form-integration' ),
                'noAccounts'       => __( 'No accounts found. Click "Add Account" to get started.', 'advanced-form-integration' ),
                'authFailed'       => __( 'Authorization failed:', 'advanced-form-integration' ),
                'edit'             => __( 'Edit', 'advanced-form-integration' ),
                'delete'           => __( 'Delete', 'advanced-form-integration' ),
                'test'             => __( 'Test connection', 'advanced-form-integration' ),
                'testing'          => __( 'Testing…', 'advanced-form-integration' ),
                'testOk'           => __( 'Connection OK', 'advanced-form-integration' ),
                'testFailed'       => __( 'Connection test failed:', 'advanced-form-integration' ),
            ),
        );
        ?>
        <script type="text/javascript">
        (function () {
            function boot() {
                if (window.ADFOIN_OAuthManager && typeof ADFOIN_OAuthManager.init === 'function') {
                    ADFOIN_OAuthManager.init(<?php echo wp_json_encode( $oauth_init ); ?>);
                } else {
                    // Asset still loading — try again shortly.
                    setTimeout(boot, 50);
                }
            }
            boot();
        }());
        </script>

        <?php
    }

    /**
     * Get all credentials for a platform.
     *
     * @param string $platform Platform slug.
     * @return array Array of credentials.
     */
    public static function get_credentials( $platform ) {
        $option_name = 'adfoin_' . $platform . '_credentials';
        $credentials = get_option( $option_name, array() );
        
        if ( ! is_array( $credentials ) ) {
            $credentials = array();
        }
        
        return $credentials;
    }

    /**
     * Get a specific credential by ID.
     *
     * @param string $platform Platform slug.
     * @param string $id Credential ID.
     * @return array|false Credential data or false if not found.
     */
    public static function get_credentials_by_id( $platform, $id ) {
        $credentials = self::get_credentials( $platform );
        
        foreach ( $credentials as $credential ) {
            if ( isset( $credential['id'] ) && $credential['id'] === $id ) {
                return $credential;
            }
        }
        
        return false;
    }

    /**
     * Save credentials for a platform.
     *
     * @param string $platform Platform slug.
     * @param array $new_credentials New credential data.
     * @return bool True on success, false on failure.
     */
    public static function save_credentials( $platform, $new_credentials ) {
        $option_name = 'adfoin_' . $platform . '_credentials';
        $credentials = self::get_credentials( $platform );
        
        // Check if this is a delete operation
        if ( isset( $_POST['delete_index'] ) ) {
            $delete_index = intval( wp_unslash( $_POST['delete_index'] ) );
            if ( isset( $credentials[ $delete_index ] ) ) {
                unset( $credentials[ $delete_index ] );
                $credentials = array_values( $credentials ); // Re-index array
                return update_option( $option_name, $credentials );
            }
            return false;
        }
        
        // Generate ID if not present
        if ( ! isset( $new_credentials['id'] ) ) {
            $new_credentials['id'] = uniqid( 'cred_' );
        }
        
        // Check if updating existing credential
        $updated = false;
        foreach ( $credentials as $index => $credential ) {
            if ( isset( $credential['id'] ) && $credential['id'] === $new_credentials['id'] ) {
                $credentials[ $index ] = array_merge( $credential, $new_credentials );
                $updated = true;
                break;
            }
        }
        
        // Add new credential if not updating
        if ( ! $updated ) {
            $credentials[] = $new_credentials;
        }
        
        return update_option( $option_name, $credentials );
    }

    /**
     * Update specific credentials by ID.
     *
     * @param string $platform Platform slug.
     * @param string $id Credential ID.
     * @param array $updated_data Updated credential data.
     * @return bool True on success, false on failure.
     */
    public static function update_credentials( $platform, $id, $updated_data ) {
        $option_name = 'adfoin_' . $platform . '_credentials';
        $credentials = self::get_credentials( $platform );
        
        foreach ( $credentials as $index => $credential ) {
            if ( isset( $credential['id'] ) && $credential['id'] === $id ) {
                $credentials[ $index ] = array_merge( $credential, $updated_data );
                return update_option( $option_name, $credentials );
            }
        }
        
        return false;
    }

    /**
     * Outputs the HTML/JS to close the OAuth popup and notify the parent window.
     *
     * @param bool $success Whether the authorization was successful.
     * @param string $message Optional message to send back.
     */
    public static function handle_callback_close_popup( $success = true, $message = '' ) {
        $status         = $success ? 'success' : 'error';
        $message_json   = wp_json_encode( (string) $message );
        $status_json    = wp_json_encode( $status );

        if ( ! headers_sent() ) {
            header( 'Content-Type: text/html; charset=utf-8' );
        }

        // We use localStorage (not postMessage) for popup→parent
        // communication. Reason: `window.opener.postMessage` requires
        // `window.opener` to be alive, but it gets severed in many real-world
        // setups — Cross-Origin-Opener-Policy: same-origin, popup blockers,
        // some security plugins, and browser tracking-protection all strip
        // the opener relationship when the popup navigates cross-origin to
        // the OAuth provider. Result: the parent never received the
        // success/error notification, the popup auto-closed silently, and
        // the user saw "OAuth doesn't work" with no feedback.
        //
        // localStorage events fire across every same-origin tab regardless
        // of opener state, so the parent's `storage` listener always picks
        // up the result.
        echo '<!DOCTYPE html>
        <html>
        <head>
            <title>Authorization ' . esc_html( ucfirst( $status ) ) . '</title>
            <style>
                body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif; text-align: center; padding: 50px; }
                .success { color: green; }
                .error { color: red; }
            </style>
        </head>
        <body>
            <h3 class="' . esc_attr( $status ) . '">' . ( $success ? 'Authorization Successful!' : 'Authorization Failed' ) . '</h3>
            <p>' . esc_html( $message ) . '</p>
            <p>This window will close automatically...</p>
            <script type="text/javascript">
                (function () {
                    var payload = {
                        type: "adfoin_oauth_response",
                        status: ' . $status_json . ',
                        message: ' . $message_json . ',
                        timestamp: new Date().getTime()
                    };
                    try {
                        // localStorage cross-tab broadcast — the parent
                        // listens via the `storage` event, which works even
                        // when `window.opener` has been severed by COOP.
                        localStorage.setItem("adfoin_oauth_response", JSON.stringify(payload));
                    } catch (e) { /* storage may be disabled — ignore */ }
                    setTimeout(function () { window.close(); }, 1000);
                })();
            </script>
        </body>
        </html>';
        exit;
    }
}
