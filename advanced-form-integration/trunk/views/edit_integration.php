<?php
global $wpdb;

$table        = $wpdb->prefix . 'adfoin_integration';
$result       = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ), ARRAY_A );
$result['title'] = esc_html( $result['title'] );
$data         = json_decode( $result["data"], true );
$trigger_data = $data['trigger_data'];
$action_data  = $data['action_data'];
$field_data   = $data['field_data'];
$nonce        = wp_create_nonce( 'adfoin-integration' );

$integration_title  = $result["title"];
$form_providers     = adfoin_get_form_providers();
$action_providers   = adfoin_get_action_porviders();
ksort( $action_providers );
$form_providers_html = adfoin_get_form_providers_html();

$log_table = $wpdb->prefix . 'adfoin_log';
$last_log = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$log_table} WHERE integration_id = %d ORDER BY id DESC LIMIT 1", $id ), ARRAY_A );
if ( $last_log && $last_log["response_code"] && substr( $last_log["response_code"], 0, 1 ) != 2 ) {
    $error = true;
} else {
    $error = false;
}

$log_url = admin_url( 'admin.php?page=advanced-form-integration-log&id=' . $id );
?>

<script type="text/javascript">
    var triggerData = <?php echo json_encode( $trigger_data, true ); ?>;
    var actionData  = <?php echo json_encode( $action_data, true ); ?>;
    var fieldData   = <?php echo json_encode( $field_data, true ); ?>;
    window.adfoinIntegrationId = <?php echo (int) $id; ?>;
</script>

<?php adfoin_display_admin_header( $id, $integration_title ); ?>

<div class="wrap">
    <?php if ( $error ) { ?>
        <div class="notice notice-error is-dismissible">
            <p><?php _e( 'Something went wrong with this integration recently. Check the log for more information.', 'advanced-form-integration' ); ?></p>
            <p><a href="<?php echo $log_url; ?>"><?php _e( 'View Log', 'advanced-form-integration' ); ?></a></p>
        </div>
    <?php } ?>

    <div id="adfoin-new-integration" v-cloak class="afi-container">

        <form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" id="edit-integration">

            <input type="hidden" name="type" value="update_integration" />
            <input type="hidden" name="action" value="adfoin_save_integration">
            <input type="hidden" name="_wpnonce" value="<?php echo esc_attr( $nonce ); ?>" />
            <input type="hidden" name="edit_id" value="<?php echo $id; ?>" />
            <input type="hidden" name="form_name" :value="trigger.formName" />
            <input type="hidden" name="triggerData" :value="JSON.stringify( trigger )" />
            <input type="hidden" name="actionData" :value="JSON.stringify( action )" />

            <!-- General Settings -->
            <div class="afi-card">
                <div class="afi-card-header">
                    <h3 class="afi-card-title">
                        <span class="afi-step-badge">Step 1</span>
                        <?php esc_html_e( 'Integration Settings', 'advanced-form-integration' ); ?>
                    </h3>
                </div>
                <div class="afi-form-group">
                    <label class="afi-label"><?php esc_html_e( 'Integration Title', 'advanced-form-integration' ); ?></label>
                    <input type="text" class="afi-input" v-model="trigger.integrationTitle" name="integration_title" placeholder="<?php _e( 'Enter a descriptive title', 'advanced-form-integration'); ?>" required="required">
                </div>
            </div>

            <!-- Trigger Section -->
            <div class="afi-card">
                <div class="afi-card-header">
                    <h3 class="afi-card-title">
                        <span class="afi-step-badge">Step 2</span>
                        <?php esc_html_e( 'Trigger', 'advanced-form-integration' ); ?>
                    </h3>
                </div>
                
                <div class="afi-row">
                    <div class="afi-col afi-col-6">
                        <div class="afi-form-group">
                            <label class="afi-label"><?php esc_html_e( 'Form Provider', 'advanced-form-integration' ); ?></label>
                            <div style="display: flex; align-items: center;">
                                <select class="afi-select" name="form_provider_id" v-model="trigger.formProviderId" @change="changeFormProvider" required="required">
                                    <option value=""> <?php _e( 'Select Provider...', 'advanced-form-integration' ); ?> </option>
                                    <?php echo $form_providers_html; ?>
                                </select>
                                <div class="afi-spinner" v-bind:class="{'is-active': formLoading}"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="afi-col afi-col-6">
                        <div class="afi-form-group">
                            <label class="afi-label"><?php esc_html_e( 'Form Name', 'advanced-form-integration' ); ?></label>
                            <div style="display: flex; align-items: center;">
                                <select class="afi-select" name="form_id" v-model="trigger.formId" :disabled="formValidated == 1" @change="changedForm" required="required">
                                    <option value=""> <?php _e( 'Select Form...', 'advanced-form-integration' ); ?> </option>
                                    <option v-for="(item, index) in trigger.forms" :value="index" > {{ item }}  </option>
                                </select>
                                <span @click="refreshForms" v-if="!refreshing" class="afi-refresh-button dashicons dashicons-update" title="Refresh Forms"></span>
                                <div class="afi-spinner" v-bind:class="{'is-active': fieldLoading}"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <?php do_action( "adfoin_trigger_extra_fields", $field_data ); ?>
            </div>

            <!-- Action Section -->
            <div class="afi-card">
                <div class="afi-card-header">
                    <h3 class="afi-card-title">
                        <span class="afi-step-badge">Step 3</span>
                        <?php esc_html_e( 'Action', 'advanced-form-integration' ); ?>
                    </h3>
                </div>

                <div class="afi-row">
                    <div class="afi-col afi-col-6">
                        <div class="afi-form-group">
                            <label class="afi-label"><?php esc_html_e( 'Platform', 'advanced-form-integration' ); ?></label>
                            <div style="display: flex; align-items: center;">
                                <select class="afi-select" name="action_provider" v-model="action.actionProviderId" @change="changeActionProvider" required="required">
                                    <option value=""> <?php _e( 'Select Platform...', 'advanced-form-integration' ); ?> </option>
                                    <?php
                                    foreach ( $action_providers as $key => $value ) {
                                        echo "<option value='" . esc_attr( $key ) . "'> " . esc_html( $value ) . " </option>";
                                    } ?>
                                </select>
                                <div class="afi-spinner" v-bind:class="{'is-active': actionLoading}"></div>
                            </div>
                        </div>
                    </div>

                    <div class="afi-col afi-col-6">
                        <div class="afi-form-group">
                            <label class="afi-label"><?php esc_html_e( 'Task', 'advanced-form-integration' ); ?></label>
                            <select class="afi-select" name="task" v-model="action.task">
                                <option value=""> <?php _e( 'Select Task...', 'advanced-form-integration' ); ?> </option>
                                <option v-for="(task, index) in action.tasks" :value="index" > {{ task }}  </option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Loading Action Components -->
            <div class="afi-card" v-if="actionsComponentsLoading">
                <div class="afi-card-header">
                    <h3 class="afi-card-title">
                        <span class="afi-step-badge">Step 4</span>
                        <?php esc_html_e( 'Map Fields', 'advanced-form-integration' ); ?>
                    </h3>
                </div>
                <div style="padding: 20px; text-align: center;">
                    <div class="afi-spinner is-active" style="float: none; display: inline-block;"></div>
                    <p style="margin-top: 10px;"><?php esc_html_e( 'Loading platform components...', 'advanced-form-integration' ); ?></p>
                </div>
            </div>

            <!-- Dynamic Mapping Section -->
            <div class="afi-card" v-if="action.actionProviderId && action.task && !actionsComponentsLoading">
                <div class="afi-card-header">
                    <h3 class="afi-card-title">
                        <span class="afi-step-badge">Step 4</span>
                        <?php esc_html_e( 'Map Fields', 'advanced-form-integration' ); ?>
                    </h3>
                </div>
                
                <table class="afi-mapping-table">
                    <tbody>
                        <component v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fieldData" v-bind:is="action.actionProviderId" :key="action.actionProviderId + '-' + componentKey"></component>
                    </tbody>
                </table>
                
                <div style="margin-top: 20px;">
                    <cl-main v-if="action.task" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fieldData"></cl-main>
                </div>
            </div>

            <!-- Save Actions -->
            <div class="afi-card" style="border: none; box-shadow: none; background: transparent; padding: 0;">
                <input class="afi-btn-primary" type="submit" name="update_integration" value="<?php esc_attr_e( 'Update Integration', 'advanced-form-integration' ); ?>" />
                <a class="afi-btn-secondary" style="color: #d63638; border-color: #d63638;" href="<?php echo admin_url('admin.php?page=advanced-form-integration')?>"> <?php esc_attr_e( 'Cancel', 'advanced-form-integration' ); ?></a>
            </div>

        </form>

    </div>
</div>

<?php do_action( 'adfoin_action_fields' ); ?>

<?php include ADVANCED_FORM_INTEGRATION_VIEWS . '/partials/integration-templates.php'; ?>

<?php do_action( 'adfoin_trigger_templates' ); ?>
