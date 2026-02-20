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
                                   data-fields='<?php echo esc_attr( json_encode( $fields ) ); ?>'>
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

        <script type="text/javascript">
        jQuery(document).ready(function($) {
            var platform = '<?php echo esc_js( $platform ); ?>';
            var $modal = $('#adfoin-modal-overlay');
            var $modalContent = $('#adfoin-' + platform + '-modal');
            var $form = $('#adfoin-' + platform + '-form');
            var $table = $('#adfoin-' + platform + '-table');
            var $submitBtn = $('#adfoin-' + platform + '-submit-btn');
            var $spinner = $submitBtn.siblings('.spinner');
            var fields = <?php echo json_encode( $fields ); ?>;
            
            // Define text strings to avoid HTML encoding issues
            var saveText = <?php echo json_encode( $config['submit_text'] ); ?>;
            var updateText = <?php echo json_encode( __( 'Update & Authorize', 'advanced-form-integration' ) ); ?>;
            var confirmDeleteText = <?php echo json_encode( __( 'Are you sure you want to delete this account?', 'advanced-form-integration' ) ); ?>;
            var deleteFailedText = <?php echo json_encode( __( 'Failed to delete account.', 'advanced-form-integration' ) ); ?>;
            var saveFailedText = <?php echo json_encode( __( 'Failed to save account.', 'advanced-form-integration' ) ); ?>;
            var errorText = <?php echo json_encode( __( 'An error occurred. Please try again.', 'advanced-form-integration' ) ); ?>;
            var loadingText = <?php echo json_encode( __( 'Loading...', 'advanced-form-integration' ) ); ?>;
            var untitledText = <?php echo json_encode( __( 'Untitled', 'advanced-form-integration' ) ); ?>;
            var connectedText = <?php echo json_encode( __( 'Connected', 'advanced-form-integration' ) ); ?>;
            var notConnectedText = <?php echo json_encode( __( 'Not Connected', 'advanced-form-integration' ) ); ?>;
            var noAccountsText = <?php echo json_encode( __( 'No accounts found. Click "Add Account" to get started.', 'advanced-form-integration' ) ); ?>;
            var authFailedText = <?php echo json_encode( __( 'Authorization failed:', 'advanced-form-integration' ) ); ?>;

            // Open Modal
            function openModal(reset) {
                if (reset !== false) {
                    $form[0].reset();
                    $('#adfoin_' + platform + '_id').val('');
                    $submitBtn.text(saveText);
                }
                $modal.fadeIn(300);
            }

            // Close Modal
            function closeModal() {
                $modal.fadeOut(300);
            }

            // Add Account Button
            $('#adfoin-add-' + platform + '-account').on('click', function(e) {
                e.preventDefault();
                openModal(true);
            });

            // Close Modal Button
            $('.adfoin-modal-close').on('click', function(e) {
                e.preventDefault();
                closeModal();
            });

            // Close on outside click
            $modal.on('click', function(e) {
                if ($(e.target).hasClass('afi-modal')) {
                    closeModal();
                }
            });

            // Edit Account
            $(document).on('click', '.adfoin-edit-account-btn', function(e) {
                e.preventDefault();
                var $btn = $(this);
                
                if ($btn.closest($table).length) {
                    var data = $btn.data();
                    $('#adfoin_' + platform + '_id').val(data.id);
                    $('#adfoin_' + platform + '_title').val(data.title);
                    
                    // Populate all fields
                    $.each(fields, function(i, field) {
                        var fieldName = field.name;
                        var $field = $('#adfoin_' + platform + '_' + fieldName);
                        if ($field.length && typeof data[fieldName] !== 'undefined') {
                            $field.val(data[fieldName]);
                        }
                    });
                    
                    $submitBtn.text(updateText);
                    openModal(false);
                }
            });

            // Delete Account
            $(document).on('click', '.adfoin-delete-account-btn', function(e) {
                e.preventDefault();
                var $btn = $(this);
                
                if ($btn.closest($table).length) {
                    if (!confirm(confirmDeleteText)) {
                        return;
                    }
                    
                    var index = $btn.data('index');
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'adfoin_save_' + platform + '_credentials',
                            _nonce: '<?php echo esc_js( $nonce ); ?>',
                            delete_index: index
                        },
                        success: function(response) {
                            if (response.success) {
                                refreshTable();
                            } else {
                                alert(response.data.message || deleteFailedText);
                            }
                        }
                    });
                }
            });

            // Form Submission
            $form.on('submit', function(e) {
                e.preventDefault();
                
                var originalText = $submitBtn.text();
                $submitBtn.prop('disabled', true);
                $spinner.addClass('is-active');
                
                // Collect form data
                var formData = {
                    action: 'adfoin_save_' + platform + '_credentials',
                    _nonce: '<?php echo esc_js( $nonce ); ?>',
                    id: $('#adfoin_' + platform + '_id').val(),
                    title: $('#adfoin_' + platform + '_title').val()
                };
                
                // Add all field values
                $.each(fields, function(i, field) {
                    var fieldName = field.name;
                    formData[fieldName] = $('#adfoin_' + platform + '_' + fieldName).val();
                });
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: formData,
                    success: function(response) {
                        if (response.success) {
                            if (response.data && response.data.auth_url) {
                                // Open OAuth popup
                                var authUrl = response.data.auth_url;
                                var width = 600;
                                var height = 700;
                                var left = (screen.width / 2) - (width / 2);
                                var top = (screen.height / 2) - (height / 2);
                                
                                window.adfoin_oauth_popup = window.open(
                                    authUrl, 
                                    'adfoin_oauth_popup', 
                                    'width=' + width + ',height=' + height + ',top=' + top + ',left=' + left
                                );
                                
                                closeModal();
                            } else {
                                // Simple save without OAuth
                                closeModal();
                                refreshTable();
                            }
                        } else {
                            alert(response.data.message || saveFailedText);
                        }
                        
                        $submitBtn.prop('disabled', false);
                        $spinner.removeClass('is-active');
                    },
                    error: function() {
                        alert(errorText);
                        $submitBtn.prop('disabled', false);
                        $spinner.removeClass('is-active');
                    }
                });
            });

            // Refresh Table
            function refreshTable() {
                var $tbody = $table.find('tbody');
                var colCount = $table.find('thead th').length;
                
                $tbody.html('<tr><td colspan="' + colCount + '">' + loadingText + '</td></tr>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'adfoin_get_' + platform + '_credentials',
                        _nonce: '<?php echo esc_js( $nonce ); ?>'
                    },
                    success: function(response) {
                        if (response.success && response.data && response.data.length > 0) {
                            var html = '';
                            
                            $.each(response.data, function(index, row) {
                                html += '<tr>';
                                html += '<td>' + (row.title || untitledText) + '</td>';
                                
                                // Field columns
                                $.each(fields, function(i, field) {
                                    if (field.show_in_table !== false) {
                                        var value = row[field.name] || '';
                                        
                                        // Mask sensitive fields - show first 6 chars + 4 asterisks
                                        if (field.mask && value) {
                                            if (value.length > 6) {
                                                value = value.substring(0, 6) + '****';
                                            } else if (value.length > 2) {
                                                value = value.substring(0, 2) + '****';
                                            } else {
                                                value = '****';
                                            }
                                        }
                                        
                                        html += '<td>' + value + '</td>';
                                    }
                                });
                                
                                // Status column
                                <?php if ( $config['show_status'] ) : ?>
                                var isConnected = row.access_token || row.accessToken;
                                if (isConnected) {
                                    html += '<td><span style="color: #46b450; font-weight: 600;"><span class="dashicons dashicons-yes-alt" style="font-size: 16px; vertical-align: middle;"></span> ' + connectedText + '</span></td>';
                                } else {
                                    html += '<td><span style="color: #dc3232; font-weight: 600;"><span class="dashicons dashicons-dismiss" style="font-size: 16px; vertical-align: middle;"></span> ' + notConnectedText + '</span></td>';
                                }
                                <?php endif; ?>
                                
                                // Actions column
                                html += '<td>';
                                html += '<button class="button-link adfoin-edit-account-btn" ';
                                html += 'data-index="' + index + '" ';
                                html += 'data-id="' + row.id + '" ';
                                html += 'data-title="' + (row.title || '') + '" ';
                                
                                // Add all field data attributes
                                $.each(fields, function(i, field) {
                                    html += 'data-' + field.name + '="' + (row[field.name] || '') + '" ';
                                });
                                
                                html += 'title="<?php esc_attr_e( 'Edit', 'advanced-form-integration' ); ?>">';
                                html += '<span class="dashicons dashicons-edit"></span>';
                                html += '</button> ';
                                
                                html += '<button class="button-link adfoin-delete-account-btn" ';
                                html += 'data-index="' + index + '" ';
                                html += 'data-id="' + row.id + '" ';
                                html += 'title="<?php esc_attr_e( 'Delete', 'advanced-form-integration' ); ?>" ';
                                html += 'style="color: #dc3232;">';
                                html += '<span class="dashicons dashicons-trash"></span>';
                                html += '</button>';
                                
                                html += '</td>';
                                html += '</tr>';
                            });
                            
                            $tbody.html(html);
                        } else {
                            $tbody.html('<tr><td colspan="' + colCount + '" style="text-align: center; padding: 40px 20px; color: #666;"><span class="dashicons dashicons-info" style="font-size: 24px; opacity: 0.5;"></span><p style="margin: 10px 0 0 0;">' + noAccountsText + '</p></td></tr>');
                        }
                    }
                });
            }

            // Listen for OAuth popup response via localStorage
            window.addEventListener('storage', function(event) {
                if (event.key === 'adfoin_oauth_response') {
                    var data = JSON.parse(event.newValue);
                    
                    if (data && data.type === 'adfoin_oauth_response') {
                        if (data.status === 'success') {
                            refreshTable();
                        } else {
                            alert(authFailedText + ' ' + data.message);
                        }
                        
                        // Clear storage
                        localStorage.removeItem('adfoin_oauth_response');
                        
                        // Close popup if still open
                        if (window.adfoin_oauth_popup && !window.adfoin_oauth_popup.closed) {
                            window.adfoin_oauth_popup.close();
                        }
                    }
                }
            });

            // Initial table load
            refreshTable();
        });
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
            $delete_index = intval( $_POST['delete_index'] );
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
        $status = $success ? 'success' : 'error';
        $safe_message = esc_js( $message );
        
        if ( ! headers_sent() ) {
            header( 'Content-Type: text/html; charset=utf-8' );
        }

        echo '<!DOCTYPE html>
        <html>
        <head>
            <title>Authorization ' . ucfirst($status) . '</title>
            <style>
                body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif; text-align: center; padding: 50px; }
                .success { color: green; }
                .error { color: red; }
            </style>
        </head>
        <body>
            <h3 class="' . $status . '">' . ($success ? 'Authorization Successful!' : 'Authorization Failed') . '</h3>
            <p>' . esc_html( $message ) . '</p>
            <p>This window will close automatically...</p>
            <script type="text/javascript">
                localStorage.setItem("adfoin_oauth_response", JSON.stringify({
                    type: "adfoin_oauth_response",
                    status: "' . $status . '",
                    message: "' . $safe_message . '",
                    timestamp: new Date().getTime()
                }));
                setTimeout(function() {
                    window.close();
                }, 1000);
            </script>
        </body>
        </html>';
        exit;
    }
}
