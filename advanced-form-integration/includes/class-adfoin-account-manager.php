<?php
/**
 * Centralized Account Management System
 * 
 * Handles account management UI and AJAX operations for all platforms
 * Uses PHP/jQuery approach instead of Vue.js for simplicity and consistency
 * 
 * @package Advanced_Form_Integration
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ADFOIN_Account_Manager {

    /**
     * Render the settings view with accounts table and modal.
     *
     * @param string $platform     Platform slug (e.g., 'klaviyo', 'brevo').
     * @param string $title        Platform title (e.g., 'Klaviyo', 'Brevo').
     * @param array  $fields       Array of field definitions.
     *                             [
     *                               'name'        => 'api_key',      // Field name (will be saved as-is)
     *                               'label'       => 'API Key',      // Display label
     *                               'type'        => 'text',         // Input type (text, password, select, textarea)
     *                               'required'    => true,           // Whether field is required
     *                               'placeholder' => '',             // Optional placeholder
     *                               'description' => '',             // Optional help text
     *                               'mask'        => true,           // Whether to mask in table (for sensitive data)
     *                               'show_in_table' => true,         // Whether to show in accounts table
     *                               'options'     => []              // For select fields: ['value' => 'Label']
     *                             ]
     * @param string $instructions HTML content for instructions sidebar.
     * @param array  $config       Optional configuration:
     *                             [
     *                               'ajax_action_prefix' => 'adfoin', // AJAX action prefix
     *                               'show_status'        => false,    // Show connection status column
     *                               'custom_save_handler' => null,    // Custom save callback
     *                               'custom_delete_handler' => null,  // Custom delete callback
     *                             ]
     */
    public static function render_settings_view( $platform, $title, $fields, $instructions, $config = array() ) {
        
        // Default config
        $config = wp_parse_args( $config, array(
            'ajax_action_prefix'    => 'adfoin',
            'show_status'           => false,
            'custom_save_handler'   => null,
            'custom_delete_handler' => null,
        ) );

        // Auto-sanitize existing credentials for security
        self::sanitize_existing_credentials( $platform );
        
        // Read existing credentials
        $credentials = adfoin_read_credentials( $platform );
        if ( ! is_array( $credentials ) ) {
            $credentials = array();
        }

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
                                    class="button button-primary adfoin-add-account-btn">
                                <?php esc_html_e( 'Add Account', 'advanced-form-integration' ); ?>
                            </button>
                        </div>
                        <div class="afi-accounts-body">
                            <table class="wp-list-table widefat striped adfoin-accounts-table" 
                                   id="adfoin-<?php echo esc_attr( $platform ); ?>-table">
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
                                    <?php if ( ! empty( $credentials ) ) : ?>
                                        <?php foreach ( $credentials as $index => $cred ) : ?>
                                            <tr data-index="<?php echo esc_attr( $index ); ?>" 
                                                data-id="<?php echo esc_attr( $cred['id'] ); ?>">
                                                <td><?php echo esc_html( ! empty( $cred['title'] ) ? $cred['title'] : __( 'Untitled', 'advanced-form-integration' ) ); ?></td>
                                                
                                                <?php foreach ( $fields as $field ) : ?>
                                                    <?php if ( ! isset( $field['show_in_table'] ) || $field['show_in_table'] ) : ?>
                                                        <td>
                                                            <?php 
                                                            $value = isset( $cred[ $field['name'] ] ) ? $cred[ $field['name'] ] : '';
                                                            
                                                            // Mask sensitive fields
                                                            if ( ! empty( $field['mask'] ) && $value ) {
                                                                echo esc_html( self::mask_value( $value ) );
                                                            } else {
                                                                echo esc_html( $value );
                                                            }
                                                            ?>
                                                        </td>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                                
                                                <?php if ( $config['show_status'] ) : ?>
                                                    <td>
                                                        <?php 
                                                        $is_connected = ! empty( $cred['access_token'] ) || ! empty( $cred['accessToken'] );
                                                        if ( $is_connected ) : ?>
                                                            <span style="color: #46b450; font-weight: 600;">
                                                                <span class="dashicons dashicons-yes-alt" style="font-size: 16px; vertical-align: middle;"></span>
                                                                <?php esc_html_e( 'Connected', 'advanced-form-integration' ); ?>
                                                            </span>
                                                        <?php else : ?>
                                                            <span style="color: #dc3232; font-weight: 600;">
                                                                <span class="dashicons dashicons-dismiss" style="font-size: 16px; vertical-align: middle;"></span>
                                                                <?php esc_html_e( 'Not Connected', 'advanced-form-integration' ); ?>
                                                            </span>
                                                        <?php endif; ?>
                                                    </td>
                                                <?php endif; ?>
                                                
                                                <td>
                                                    <button class="button-link adfoin-edit-account-btn" 
                                                            data-index="<?php echo esc_attr( $index ); ?>"
                                                            data-id="<?php echo esc_attr( $cred['id'] ); ?>"
                                                            data-title="<?php echo esc_attr( $cred['title'] ); ?>"
                                                            <?php 
                                                            // Safely encode credential data for JavaScript
                                                            $safe_cred_data = array();
                                                            foreach ( $fields as $field ) {
                                                                $field_name = $field['name'];
                                                                $safe_cred_data[$field_name] = isset( $cred[$field_name] ) ? sanitize_text_field( $cred[$field_name] ) : '';
                                                            }
                                                            ?>
                                                            data-cred="<?php echo esc_attr( wp_json_encode( $safe_cred_data ) ); ?>"
                                                            title="<?php esc_attr_e( 'Edit', 'advanced-form-integration' ); ?>">
                                                        <span class="dashicons dashicons-edit"></span>
                                                    </button>
                                                    <button class="button-link adfoin-delete-account-btn" 
                                                            data-index="<?php echo esc_attr( $index ); ?>"
                                                            data-id="<?php echo esc_attr( $cred['id'] ); ?>"
                                                            title="<?php esc_attr_e( 'Delete', 'advanced-form-integration' ); ?>"
                                                            style="color: #dc3232;">
                                                        <span class="dashicons dashicons-trash"></span>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else : ?>
                                        <tr class="adfoin-no-accounts">
                                            <td colspan="<?php echo count( $fields ) + 2 + ( $config['show_status'] ? 1 : 0 ); ?>" 
                                                style="text-align: center; padding: 40px 20px; color: #666;">
                                                <span class="dashicons dashicons-info" style="font-size: 24px; opacity: 0.5;"></span>
                                                <p style="margin: 10px 0 0 0;"><?php esc_html_e( 'No accounts found. Click "Add Account" to get started.', 'advanced-form-integration' ); ?></p>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
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
        <div id="adfoin-<?php echo esc_attr( $platform ); ?>-modal" 
             class="afi-modal" 
             style="display:none;">
            <div class="afi-modal-content">
                <span class="afi-close" id="adfoin-<?php echo esc_attr( $platform ); ?>-modal-close">&times;</span>
                
                <h3 id="adfoin-<?php echo esc_attr( $platform ); ?>-modal-title">
                    <?php echo esc_html( sprintf( __( 'Add %s Account', 'advanced-form-integration' ), $title ) ); ?>
                </h3>
                
                <form id="adfoin-<?php echo esc_attr( $platform ); ?>-form" class="adfoin-account-form">
                    <input type="hidden" name="action" value="<?php echo esc_attr( $config['ajax_action_prefix'] . '_save_' . $platform . '_credentials' ); ?>">
                    <input type="hidden" name="_nonce" value="<?php echo esc_attr( $nonce ); ?>">
                    <input type="hidden" name="id" id="adfoin_<?php echo esc_attr( $platform ); ?>_id" value="">
                    <input type="hidden" name="platform" value="<?php echo esc_attr( $platform ); ?>">
                    
                    <table class="form-table">
                        <tr>
                            <th>
                                <label for="adfoin_<?php echo esc_attr( $platform ); ?>_title">
                                    <?php esc_html_e( 'Title', 'advanced-form-integration' ); ?>
                                    <span class="required" style="color: #dc3232;">*</span>
                                </label>
                            </th>
                            <td>
                                <input type="text" 
                                       name="title" 
                                       id="adfoin_<?php echo esc_attr( $platform ); ?>_title" 
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
                                        <?php if ( ! empty( $field['required'] ) ) : ?>
                                            <span class="required" style="color: #dc3232;">*</span>
                                        <?php endif; ?>
                                    </label>
                                </th>
                                <td>
                                    <?php if ( $field['type'] === 'select' && ! empty( $field['options'] ) ) : ?>
                                        <select name="<?php echo esc_attr( $field['name'] ); ?>" 
                                                id="adfoin_<?php echo esc_attr( $platform ); ?>_<?php echo esc_attr( $field['name'] ); ?>" 
                                                class="regular-text"
                                                <?php echo ! empty( $field['required'] ) ? 'required' : ''; ?>>
                                            <?php foreach ( $field['options'] as $value => $label ) : ?>
                                                <option value="<?php echo esc_attr( $value ); ?>">
                                                    <?php echo esc_html( $label ); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    <?php elseif ( $field['type'] === 'textarea' ) : ?>
                                        <textarea name="<?php echo esc_attr( $field['name'] ); ?>" 
                                                  id="adfoin_<?php echo esc_attr( $platform ); ?>_<?php echo esc_attr( $field['name'] ); ?>" 
                                                  class="large-text" 
                                                  rows="4"
                                                  placeholder="<?php echo esc_attr( ! empty( $field['placeholder'] ) ? $field['placeholder'] : '' ); ?>"
                                                  <?php echo ! empty( $field['required'] ) ? 'required' : ''; ?>></textarea>
                                    <?php else : ?>
                                        <input type="<?php echo esc_attr( $field['type'] ); ?>" 
                                               name="<?php echo esc_attr( $field['name'] ); ?>" 
                                               id="adfoin_<?php echo esc_attr( $platform ); ?>_<?php echo esc_attr( $field['name'] ); ?>" 
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
                    
                    <div class="adfoin-modal-footer" style="margin-top: 20px; text-align: right; border-top: 1px solid #ddd; padding-top: 15px;">
                        <button type="submit" 
                                class="button button-primary" 
                                id="adfoin-<?php echo esc_attr( $platform ); ?>-submit-btn">
                            <span class="dashicons dashicons-saved" style="margin-top: 3px;"></span>
                            <?php esc_html_e( 'Save Account', 'advanced-form-integration' ); ?>
                        </button>
                        <span class="spinner" style="float: none; margin: 0 0 0 10px;"></span>
                    </div>
                </form>
            </div>
        </div>

        <script type="text/javascript">
        jQuery(document).ready(function($) {
            var platform = '<?php echo esc_js( $platform ); ?>';
            var modalId = '#adfoin-' + platform + '-modal';
            var formId = '#adfoin-' + platform + '-form';
            var tableId = '#adfoin-' + platform + '-table';
            
            // Open Modal for Add
            $('#adfoin-add-' + platform + '-account').on('click', function(e) {
                e.preventDefault();
                $('#adfoin_' + platform + '_id').val('');
                $('#adfoin_' + platform + '_title').val('');
                $(formId + ' input[type="text"], ' + formId + ' input[type="password"], ' + formId + ' textarea').not('#adfoin_' + platform + '_id, #adfoin_' + platform + '_title').val('');
                $('#adfoin-' + platform + '-modal-title').text('Add <?php echo esc_js( $title ); ?> Account');
                $(modalId).fadeIn();
            });
            
            // Open Modal for Edit
            $(document).on('click', '.adfoin-edit-account-btn', function(e) {
                e.preventDefault();
                var $btn = $(this);
                
                // Only handle if it's for this platform's table
                if ($btn.closest(tableId).length) {
                    var data = $btn.data();
                    $('#adfoin_' + platform + '_id').val(data.id);
                    $('#adfoin_' + platform + '_title').val(data.title);
                    
                    // Parse credential data from JSON
                    var credData = {};
                    if (data.cred) {
                        try {
                            credData = typeof data.cred === 'string' ? JSON.parse(data.cred) : data.cred;
                        } catch (e) {
                            console.error('Failed to parse credential data:', e);
                        }
                    }
                    
                    // Populate all field values
                    $(formId + ' input[name], ' + formId + ' select[name], ' + formId + ' textarea[name]').each(function() {
                        var $field = $(this);
                        var fieldName = $field.attr('name');
                        
                        if (fieldName && fieldName !== 'action' && fieldName !== '_nonce' && 
                            fieldName !== 'id' && fieldName !== 'platform' && fieldName !== 'title') {
                            // Get value from parsed credential data
                            var value = credData[fieldName];
                            if (typeof value !== 'undefined' && value !== null) {
                                $field.val(value);
                            }
                        }
                    });
                    
                    $('#adfoin-' + platform + '-modal-title').text('Edit <?php echo esc_js( $title ); ?> Account');
                    $(modalId).fadeIn();
                }
            });
            
            // Close Modal
            $('#adfoin-' + platform + '-modal-close').on('click', function() {
                $(modalId).fadeOut();
            });
            
            // Close Modal on outside click
            $(window).on('click', function(e) {
                if ($(e.target).is(modalId)) {
                    $(modalId).fadeOut();
                }
            });
            
            // Form submission
            $(formId).on('submit', function(e) {
                e.preventDefault();
                var $submitBtn = $('#adfoin-' + platform + '-submit-btn');
                var $spinner = $(formId + ' .spinner');
                
                $submitBtn.prop('disabled', true);
                $spinner.addClass('is-active');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: $(formId).serialize(),
                    success: function(response) {
                        $submitBtn.prop('disabled', false);
                        $spinner.removeClass('is-active');
                        
                        if (response.success) {
                            $(modalId).fadeOut();
                            location.reload();
                        } else {
                            alert(response.data.message || 'Failed to save account.');
                        }
                    },
                    error: function(xhr, status, error) {
                        $submitBtn.prop('disabled', false);
                        $spinner.removeClass('is-active');
                        alert('An error occurred: ' + error);
                    }
                });
            });
            
            // Delete account
            $(document).on('click', '.adfoin-delete-account-btn', function(e) {
                e.preventDefault();
                var $btn = $(this);
                
                // Only handle if it's for this platform's table
                if ($btn.closest(tableId).length) {
                    if (!confirm('Are you sure you want to delete this account? This action cannot be undone.')) {
                        return;
                    }
                    
                    var index = $btn.data('index');
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'adfoin_save_' + platform + '_credentials',
                            _nonce: $(formId + ' input[name="_nonce"]').val(),
                            delete_index: index
                        },
                        success: function(response) {
                            if (response.success) {
                                location.reload();
                            } else {
                                alert(response.data.message || 'Failed to delete account.');
                            }
                        },
                        error: function(xhr, status, error) {
                            alert('An error occurred: ' + error);
                        }
                    });
                }
            });
        });
        </script>

        <?php
    }

    /**
     * Mask sensitive values for display in table.
     * Shows first 6 characters followed by 4 asterisks.
     * Example: "pk_live_abc123xyz" becomes "pk_liv****"
     *
     * @param string $value The value to mask.
     * @return string Masked value.
     */
    public static function mask_value( $value ) {
        if ( empty( $value ) ) {
            return '****';
        }
        
        $length = strlen( $value );
        
        if ( $length > 6 ) {
            return substr( $value, 0, 6 ) . '****';
        }
        
        // For short values, show first 2 chars + asterisks
        if ( $length > 2 ) {
            return substr( $value, 0, 2 ) . str_repeat( '*', $length - 2 );
        }
        
        return str_repeat( '*', $length );
    }

    /**
     * Build a legacy-account record from old wp_options keys.
     *
     * Intended for use inside an `adfoin_get_credentials` filter callback to
     * surface a single legacy account when the new credentials store is empty.
     * Returns $credentials unchanged if not for this platform or already populated.
     *
     * @param array  $credentials      Current filter value.
     * @param string $platform         Filter's $platform argument.
     * @param string $target_platform  Slug this importer handles.
     * @param array  $field_map        [ new_field_name => legacy_option_key ]
     *                                 or [ new_field_name => [ key1, key2, ... ] ]
     *                                 (first non-empty key wins — supports
     *                                 platforms that renamed an option over time).
     * @param array  $args             Optional: [ 'id' => 'legacy', 'title' => 'Legacy Account' ].
     * @return array
     */
    public static function import_legacy_option( $credentials, $platform, $target_platform, $field_map, $args = array() ) {
        if ( $platform !== $target_platform || ! empty( $credentials ) ) {
            return $credentials;
        }

        $args = wp_parse_args( $args, array(
            'id'    => 'legacy',
            'title' => __( 'Legacy Account', 'advanced-form-integration' ),
        ) );

        $record = array( 'id' => $args['id'], 'title' => $args['title'] );
        $any    = false;

        foreach ( $field_map as $new_field => $option_keys ) {
            $keys = is_array( $option_keys ) ? $option_keys : array( $option_keys );
            $val  = '';
            foreach ( $keys as $option_key ) {
                $candidate = get_option( $option_key, '' );
                if ( $candidate !== '' && $candidate !== false ) {
                    $val = $candidate;
                    break;
                }
            }

            if ( $val !== '' && $val !== false ) {
                $any = true;
            }
            $record[ $new_field ] = is_string( $val ) ? $val : '';
        }

        if ( ! $any ) {
            return $credentials;
        }

        $credentials[] = $record;
        return $credentials;
    }

    /**
     * One-liner registration: hooks `adfoin_get_credentials` for $platform with
     * the supplied legacy option mapping. Replaces ~15 lines of per-platform
     * boilerplate.
     *
     * Example:
     *     ADFOIN_Account_Manager::register_legacy_option_importer( 'activecampaign', array(
     *         'apiKey' => 'adfoin_activecampaign_api_key',
     *         'url'    => 'adfoin_activecampaign_url',
     *     ) );
     *
     * @param string $platform   Platform slug.
     * @param array  $field_map  [ new_field_name => legacy_option_key ].
     * @param array  $args       Optional: [ 'id' => ..., 'title' => ... ].
     */
    public static function register_legacy_option_importer( $platform, $field_map, $args = array() ) {
        add_filter( 'adfoin_get_credentials', function( $credentials, $current_platform ) use ( $platform, $field_map, $args ) {
            return self::import_legacy_option( $credentials, $current_platform, $platform, $field_map, $args );
        }, 10, 2 );
    }

    /**
     * Handle AJAX request to save credentials.
     * This is a generic handler that can be used by all platforms.
     *
     * @param string $platform    Platform slug.
     * @param array  $field_names Array of field names to save.
     */
    public static function ajax_save_credentials( $platform, $field_names ) {
        // Authorization check
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'advanced-form-integration' ) ), 403 );
        }

        // Security check
        if ( ! wp_verify_nonce( wp_unslash( $_POST['_nonce'] ), 'advanced-form-integration' ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed', 'advanced-form-integration' ) ) );
        }

        $credentials = adfoin_read_credentials( $platform );
        if ( ! is_array( $credentials ) ) {
            $credentials = array();
        }

        // Handle deletion
        if ( isset( $_POST['delete_index'] ) ) {
            $index = intval( wp_unslash( $_POST['delete_index'] ) );
            if ( isset( $credentials[ $index ] ) ) {
                array_splice( $credentials, $index, 1 );
                adfoin_save_credentials( $platform, $credentials );
                wp_send_json_success( array( 'message' => __( 'Account deleted successfully', 'advanced-form-integration' ) ) );
            }
            wp_send_json_error( array( 'message' => __( 'Account not found', 'advanced-form-integration' ) ) );
        }

        // Handle save/update
        $id = isset( $_POST['id'] ) ? sanitize_text_field( wp_unslash( $_POST['id'] ) ) : '';
        $title = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';

        if ( empty( $id ) ) {
            $id = wp_generate_uuid4();
        }

        $new_data = array(
            'id'    => $id,
            'title' => $title,
        );

        // Save all field values with type-aware sanitization.
        // $field_names accepts either:
        //   [ 'apiKey', 'url' ]                       (back-compat — defaults to 'text')
        //   [ 'apiKey' => 'password', 'url' => 'url' ] (typed)
        // XSS prevention is handled at output via esc_html()/esc_attr() in the render path.
        foreach ( $field_names as $key => $val ) {
            $name = is_int( $key ) ? $val : $key;
            $type = is_int( $key ) ? 'text' : $val;

            $raw = isset( $_POST[ $name ] ) ? wp_unslash( $_POST[ $name ] ) : '';
            $new_data[ $name ] = self::sanitize_field_value( $raw, $type );
        }

        // Update existing or add new
        $found = false;
        foreach ( $credentials as &$cred ) {
            if ( $cred['id'] === $id ) {
                foreach ( $new_data as $k => $v ) {
                    $cred[ $k ] = $v;
                }
                $found = true;
                break;
            }
        }

        if ( ! $found ) {
            $credentials[] = $new_data;
        }

        adfoin_save_credentials( $platform, $credentials );

        wp_send_json_success( array( 
            'message' => __( 'Account saved successfully', 'advanced-form-integration' ),
            'credentials' => $credentials
        ) );
    }

    /**
     * Sanitize existing credentials to remove any potential XSS
     * 
     * @param string $platform Platform slug
     */
    public static function sanitize_existing_credentials( $platform ) {
        $credentials = adfoin_read_credentials( $platform );
        if ( ! is_array( $credentials ) ) {
            return;
        }

        $sanitized = false;
        foreach ( $credentials as &$cred ) {
            foreach ( $cred as $key => &$value ) {
                if ( is_string( $value ) ) {
                    $original = $value;
                    // sanitize_text_field strips tags and control chars but
                    // preserves `&`, `"`, `'` — output escaping handles XSS.
                    $value = sanitize_text_field( $value );

                    if ( $original !== $value ) {
                        $sanitized = true;
                    }
                }
            }
        }

        if ( $sanitized ) {
            adfoin_save_credentials( $platform, $credentials );
        }
    }

    /**
     * Handle AJAX request to get credentials list (for Vue components).
     * Returns masked sensitive data.
     *
     * @param string $platform Platform slug.
     * @param array  $fields   Field definitions (to know which to mask).
     */
    public static function ajax_get_credentials_list( $platform, $fields = array() ) {
        // Authorization check
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'advanced-form-integration' ) ), 403 );
        }

        if ( ! wp_verify_nonce( wp_unslash( $_POST['_nonce'] ), 'advanced-form-integration' ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed', 'advanced-form-integration' ) ) );
        }

        $credentials = adfoin_read_credentials( $platform );
        $list = array();

        if ( is_array( $credentials ) ) {
            foreach ( $credentials as $cred ) {
                $masked_cred = array(
                    'id'    => $cred['id'],
                    'title' => isset( $cred['title'] ) ? $cred['title'] : __( 'Untitled', 'advanced-form-integration' ),
                );

                // Mask sensitive fields
                foreach ( $fields as $field ) {
                    $field_name = $field['name'];
                    if ( isset( $cred[ $field_name ] ) ) {
                        if ( ! empty( $field['mask'] ) ) {
                            $masked_cred[ $field_name ] = self::mask_value( $cred[ $field_name ] );
                        } else {
                            $masked_cred[ $field_name ] = $cred[ $field_name ];
                        }
                    }
                }

                $list[] = $masked_cred;
            }
        }

        wp_send_json_success( $list );
    }

    /**
     * Handle AJAX request to get full credentials (for editing).
     *
     * @param string $platform Platform slug.
     */
    public static function ajax_get_credentials( $platform ) {
        // Authorization check
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'advanced-form-integration' ) ), 403 );
        }

        if ( ! wp_verify_nonce( wp_unslash( $_POST['_nonce'] ), 'advanced-form-integration' ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed', 'advanced-form-integration' ) ) );
        }

        $credentials = adfoin_read_credentials( $platform );
        wp_send_json_success( $credentials );
    }

    /**
     * Sanitize a single field value by type.
     *
     * Input sanitization just normalizes the stored shape; XSS prevention is
     * handled at output via esc_html()/esc_attr() in the render path.
     *
     * @param mixed  $value Raw value from $_POST (already wp_unslash'd).
     * @param string $type  Field type: 'text', 'password', 'token', 'url',
     *                      'email', 'textarea', 'select'. Unknown values fall
     *                      through to 'text'.
     * @return string
     */
    protected static function sanitize_field_value( $value, $type = 'text' ) {
        if ( ! is_string( $value ) ) {
            return '';
        }

        switch ( $type ) {
            case 'url':
                return esc_url_raw( trim( $value ) );

            case 'email':
                return sanitize_email( $value );

            case 'textarea':
                return sanitize_textarea_field( $value );

            case 'password':
            case 'token':
                // API keys / tokens / secrets: preserve content (incl. quotes,
                // ampersands, angle brackets); strip only control chars.
                return preg_replace( '/[\x00-\x1F\x7F]/u', '', trim( $value ) );

            case 'text':
            case 'select':
            default:
                return sanitize_text_field( $value );
        }
    }

    /**
     * Translate the legacy `adfoin_platform_settings_template()` field shape
     * into the schema expected by render_settings_view().
     *
     * Legacy shape:   [ 'key' => 'apiKey', 'label' => 'API Key', 'hidden' => true ]
     * Modern shape:   [ 'name' => 'apiKey', 'label' => '...', 'type' => 'password',
     *                   'mask' => true, 'required' => true, 'show_in_table' => true ]
     *
     * @param array $legacy_fields
     * @return array
     */
    public static function translate_legacy_fields( $legacy_fields ) {
        $fields = array();
        if ( ! is_array( $legacy_fields ) ) {
            return $fields;
        }

        foreach ( $legacy_fields as $f ) {
            if ( empty( $f['key'] ) ) {
                continue;
            }

            $is_secret = ! empty( $f['hidden'] );
            $label     = isset( $f['label'] ) ? (string) $f['label'] : $f['key'];

            // Labels carrying "(optional)" should not force a required input.
            $is_required = ( false === stripos( $label, '(optional)' ) );

            $fields[] = array(
                'name'          => $f['key'],
                'label'         => $label,
                'type'          => $is_secret ? 'password' : 'text',
                'required'      => $is_required,
                'mask'          => $is_secret,
                'show_in_table' => true,
            );
        }

        return $fields;
    }

    /**
     * Intercept legacy `adfoin_save_<platform>_credentials` AJAX requests
     * issued by the new manager UI, before the platform's own bulk handler
     * runs.
     *
     * The legacy per-platform handler expects `$_POST['data']` (the full Vue
     * table) and silently discards a per-row submit. We detect the modern
     * shape (`title` or `delete_index` is present, `data` is not) and route
     * through ajax_save_credentials(), inferring field types from name.
     *
     * Fires on `admin_init` because that hook runs before the action-specific
     * `wp_ajax_<action>` dispatch in admin-ajax.php. Calls wp_send_json_*,
     * which terminates execution, so the legacy handler never runs for
     * modern submissions. Old-style submissions (which the legacy UI is no
     * longer rendered to produce) pass through untouched.
     */
    public static function legacy_save_bridge() {
        if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) {
            return;
        }
        if ( empty( $_POST['action'] ) || ! is_string( $_POST['action'] ) ) {
            return;
        }
        if ( ! preg_match( '/^adfoin_save_([a-z0-9_]+)_credentials$/', $_POST['action'], $m ) ) {
            return;
        }

        $is_modern_save   = isset( $_POST['title'] ) && ! isset( $_POST['data'] );
        $is_modern_delete = isset( $_POST['delete_index'] ) && ! isset( $_POST['data'] );
        if ( ! $is_modern_save && ! $is_modern_delete ) {
            return;
        }

        $platform = $m[1];

        // CRITICAL: skip OAuth platforms. Each OAuth platform (mstodo,
        // googlesheets, zohocrm, salesforce, bigin, etc.) has its own
        // wp_ajax_adfoin_save_<platform>_credentials handler that generates
        // an `auth_url` and returns it to the OAuth Manager UI so the popup
        // can navigate to the provider. If this bridge intercepts the
        // request first (admin_init priority 5 fires before wp_ajax_*),
        // it returns a plain "Account saved successfully" response WITHOUT
        // auth_url and the OAuth Manager JS closes the popup thinking it
        // was a non-OAuth save. The OAuth flow never starts. This was the
        // cause of the "popup closes immediately, no provider screen" bug.
        if ( self::is_oauth_platform( $platform ) ) {
            return;
        }

        // Reserved keys that aren't credential fields.
        $reserved = array( 'action', '_nonce', 'id', 'platform', 'title', 'delete_index' );

        // Heuristic field-type inference from the field name.
        // Names containing token/secret/key/password get the 'token' sanitizer
        // (preserves arbitrary characters); everything else is plain text.
        $field_names = array();
        foreach ( array_keys( $_POST ) as $key ) {
            if ( in_array( $key, $reserved, true ) ) {
                continue;
            }
            $field_names[ $key ] = preg_match( '/token|secret|password|pass|apikey|api_key|key$/i', $key )
                ? 'token'
                : 'text';
        }

        self::ajax_save_credentials( $platform, $field_names );
        // ajax_save_credentials() calls wp_send_json_* which dies.
    }

    /**
     * Detect whether a platform slug is served by an OAuth Manager class
     * (i.e., a subclass of Advanced_Form_Integration_OAuth2). Used by
     * legacy_save_bridge() to avoid stomping on OAuth save_credentials
     * handlers that return auth_url.
     *
     * Walks declared classes once and caches the slug→is-oauth map for the
     * remainder of the request. Each OAuth platform sets
     * `protected $platform_slug = '<slug>';` at the class level, so the
     * default-properties reflection picks it up without instantiation.
     */
    protected static function is_oauth_platform( $platform ) {
        static $cache = null;

        if ( null === $cache ) {
            $cache = array();
            if ( class_exists( 'Advanced_Form_Integration_OAuth2' ) ) {
                foreach ( get_declared_classes() as $class ) {
                    if ( $class === 'Advanced_Form_Integration_OAuth2' ) {
                        continue;
                    }
                    if ( ! is_subclass_of( $class, 'Advanced_Form_Integration_OAuth2' ) ) {
                        continue;
                    }
                    try {
                        $reflection = new ReflectionClass( $class );
                        $defaults   = $reflection->getDefaultProperties();
                        if ( ! empty( $defaults['platform_slug'] ) ) {
                            $cache[ (string) $defaults['platform_slug'] ] = true;
                        }
                    } catch ( ReflectionException $e ) {
                        // ignore — unreachable class metadata
                    }
                }
            }
        }

        return isset( $cache[ $platform ] );
    }
}

// Register the legacy bridge once, regardless of who loads the class.
if ( ! has_action( 'admin_init', array( 'ADFOIN_Account_Manager', 'legacy_save_bridge' ) ) ) {
    add_action( 'admin_init', array( 'ADFOIN_Account_Manager', 'legacy_save_bridge' ), 5 );
}