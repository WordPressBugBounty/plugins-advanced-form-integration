<?php

global $wpdb;

$integration_table = $wpdb->prefix . 'adfoin_integration';
$last_id           = $wpdb->get_var( "SELECT MAX(id) FROM {$integration_table}" );
$last_id           = empty( $last_id ) ? 0 : $last_id;
$integration_title = "Integration #" . ( $last_id + 1 );
$nonce             = wp_create_nonce( 'adfoin-integration' );
$field_data        = array();
$form_providers_html = adfoin_get_form_providers_html();
?>
<script type="text/javascript">
    var integrationTitle = <?php echo json_encode( $integration_title, true ); ?>;
    window.adfoinIntegrationId = 0;
</script>

<?php
    do_action( "adfoin_add_js_fields", $field_data );
    adfoin_display_admin_header();
?>

<div class="wrap">
    <div id="adfoin-new-integration" v-cloak class="afi-container">

        <form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" id="new-integration">

            <input type="hidden" name="action" value="adfoin_save_integration">
            <input type="hidden" name="type" value="new_integration" />
            <input type="hidden" name="_wpnonce" value="<?php echo esc_attr( $nonce ); ?>" />
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
                    <p class="afi-helper-text"><?php esc_html_e( 'Give your integration a name to identify it later.', 'advanced-form-integration' ); ?></p>
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
                                <span @click="refreshForms" v-if="!refreshing" class="afi-refresh-button dashicons dashicons-update" title="<?php esc_attr_e( 'Refresh Forms', 'advanced-form-integration' ); ?>"></span>
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
                            <select class="afi-select" name="task" v-model="action.task" :disabled="actionValidated == 1" required="required">
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
                <input class="afi-btn-primary" type="submit" name="save_integration" value="<?php esc_attr_e( 'Save Integration', 'advanced-form-integration' ); ?>" />
                <a class="afi-btn-secondary" style="color: #d63638; border-color: #d63638;" href="<?php echo admin_url('admin.php?page=advanced-form-integration')?>"> <?php esc_attr_e( 'Cancel', 'advanced-form-integration' ); ?></a>
            </div>

        </form>

    </div>
</div>

<?php do_action( 'adfoin_action_fields' ); ?>

<?php include ADVANCED_FORM_INTEGRATION_VIEWS . '/partials/integration-templates.php'; ?>

<?php do_action( 'adfoin_trigger_templates' ); ?>
