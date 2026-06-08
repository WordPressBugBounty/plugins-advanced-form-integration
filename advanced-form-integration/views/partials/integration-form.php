<?php
/**
 * Shared markup for the new / edit integration form.
 *
 * Used by:
 *   - views/new_integration.php  (with $mode = 'new')
 *   - views/edit_integration.php (with $mode = 'edit')
 *
 * Expected variables in scope when this file is included:
 *
 *   string $mode       'new' | 'edit'
 *   string $nonce      wp_create_nonce( 'adfoin-integration' )
 *   int    $id         Integration ID (0 for 'new')
 *   array  $field_data Field-data array used by per-platform components
 *
 * Anything else (script tag with hydration data, error notice, toast
 * container) belongs in the calling view, not here.
 *
 * @since 1.128.1
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$is_edit       = ( isset( $mode ) && 'edit' === $mode );
$form_dom_id   = $is_edit ? 'edit-integration' : 'new-integration';
$form_type     = $is_edit ? 'update_integration' : 'new_integration';
$submit_name   = $is_edit ? 'update_integration' : 'save_integration';
$submit_label  = $is_edit
    ? __( 'Update Integration', 'advanced-form-integration' )
    : __( 'Save Integration', 'advanced-form-integration' );
$cancel_url    = admin_url( 'admin.php?page=advanced-form-integration' );
$post_url      = admin_url( 'admin-post.php' );
$id            = isset( $id ) ? (int) $id : 0;
$field_data    = isset( $field_data ) ? $field_data : array();
?>
<form action="<?php echo esc_url( $post_url ); ?>" method="post" id="<?php echo esc_attr( $form_dom_id ); ?>" novalidate @submit="onSubmitForm">

    <input type="hidden" name="action" value="adfoin_save_integration">
    <input type="hidden" name="type" value="<?php echo esc_attr( $form_type ); ?>" />
    <input type="hidden" name="_wpnonce" value="<?php echo esc_attr( $nonce ); ?>" />
    <?php if ( $is_edit ) : ?>
        <input type="hidden" name="edit_id" value="<?php echo esc_attr( $id ); ?>" />
    <?php endif; ?>
    <input type="hidden" name="form_name" :value="trigger.formName" />
    <input type="hidden" name="triggerData" :value="JSON.stringify( trigger )" />
    <input type="hidden" name="actionData" :value="JSON.stringify( action )" />

    <!-- Step 1: Integration Settings -->
    <fieldset class="afi-card afi-fieldset">
        <legend class="afi-sr-only"><?php esc_html_e( 'Step 1: Integration Settings', 'advanced-form-integration' ); ?></legend>
        <div class="afi-card-header">
            <h3 class="afi-card-title">
                <span class="afi-step-badge">Step 1</span>
                <?php esc_html_e( 'Integration Settings', 'advanced-form-integration' ); ?>
            </h3>
        </div>
        <div class="afi-form-group">
            <label class="afi-label" for="adfoin-integration-title">
                <?php esc_html_e( 'Integration Title', 'advanced-form-integration' ); ?>
                <span class="afi-required-mark" aria-hidden="true"></span>
            </label>
            <input type="text"
                   id="adfoin-integration-title"
                   class="afi-input"
                   :class="{ 'has-error': showError('integrationTitle') }"
                   v-model="trigger.integrationTitle"
                   @input="onTitleInput"
                   @blur="markTouched('integrationTitle')"
                   name="integration_title"
                   placeholder="<?php esc_attr_e( 'Enter a descriptive title', 'advanced-form-integration' ); ?>"
                   aria-required="true"
                   :aria-invalid="showError('integrationTitle') ? 'true' : 'false'"
                   aria-describedby="adfoin-integration-title-error<?php echo $is_edit ? '' : ' adfoin-integration-title-help'; ?>">
            <?php if ( ! $is_edit ) : ?>
                <p class="afi-helper-text" id="adfoin-integration-title-help"><?php esc_html_e( 'Give your integration a name to identify it later.', 'advanced-form-integration' ); ?></p>
            <?php endif; ?>
            <span class="afi-field-error" id="adfoin-integration-title-error" v-if="showError('integrationTitle')">{{ errors.integrationTitle }}</span>
        </div>
    </fieldset>

    <!-- Step 2: Trigger -->
    <fieldset class="afi-card afi-fieldset">
        <legend class="afi-sr-only"><?php esc_html_e( 'Step 2: Trigger', 'advanced-form-integration' ); ?></legend>
        <div class="afi-card-header">
            <h3 class="afi-card-title">
                <span class="afi-step-badge">Step 2</span>
                <?php esc_html_e( 'Trigger', 'advanced-form-integration' ); ?>
            </h3>
        </div>

        <div class="afi-row">
            <div class="afi-col afi-col-6">
                <div class="afi-form-group">
                    <label class="afi-label" for="adfoin-form-provider">
                        <?php esc_html_e( 'Form Provider', 'advanced-form-integration' ); ?>
                        <span class="afi-required-mark" aria-hidden="true"></span>
                    </label>
                    <div class="afi-input-group">
                        <afi-searchable-select
                            input-id="adfoin-form-provider"
                            :options="adfoinFormProviders"
                            v-model="trigger.formProviderId"
                            name="form_provider_id"
                            placeholder="<?php esc_attr_e( 'Select Provider...', 'advanced-form-integration' ); ?>"
                            search-placeholder="<?php esc_attr_e( 'Search providers...', 'advanced-form-integration' ); ?>"
                            empty-text="<?php esc_attr_e( 'No providers match your search.', 'advanced-form-integration' ); ?>"
                            :required="true"
                            :has-error="showError('formProvider')"
                            described-by="adfoin-form-provider-error"
                            @change="changeFormProvider"
                            @blur="markTouched('formProvider')">
                        </afi-searchable-select>
                        <div class="afi-spinner" :class="{'is-active': formLoading}" :aria-busy="formLoading ? 'true' : 'false'" role="status"></div>
                    </div>
                    <span class="afi-field-error" id="adfoin-form-provider-error" v-if="showError('formProvider')">{{ errors.formProvider }}</span>
                </div>
            </div>

            <div class="afi-col afi-col-6">
                <div class="afi-form-group">
                    <label class="afi-label" for="adfoin-form-id">
                        <?php esc_html_e( 'Form Name', 'advanced-form-integration' ); ?>
                        <span class="afi-required-mark" aria-hidden="true"></span>
                    </label>
                    <div class="afi-input-group">
                        <afi-searchable-select
                            input-id="adfoin-form-id"
                            :options="formOptions"
                            v-model="trigger.formId"
                            name="form_id"
                            placeholder="<?php esc_attr_e( 'Select Form...', 'advanced-form-integration' ); ?>"
                            search-placeholder="<?php esc_attr_e( 'Search forms...', 'advanced-form-integration' ); ?>"
                            empty-text="<?php esc_attr_e( 'No forms match your search.', 'advanced-form-integration' ); ?>"
                            no-options-text="<?php esc_attr_e( 'Pick a form provider first.', 'advanced-form-integration' ); ?>"
                            :disabled="formValidated == 1"
                            :required="true"
                            :has-error="showError('formId')"
                            described-by="adfoin-form-id-error"
                            @change="changedForm"
                            @blur="markTouched('formId')">
                        </afi-searchable-select>
                        <button type="button"
                                class="afi-icon-btn"
                                :class="{'is-loading': refreshing || fieldLoading}"
                                :disabled="!trigger.formProviderId || refreshing || fieldLoading"
                                @click="refreshForms"
                                :aria-busy="(refreshing || fieldLoading) ? 'true' : 'false'"
                                title="<?php esc_attr_e( 'Refresh Forms', 'advanced-form-integration' ); ?>"
                                aria-label="<?php esc_attr_e( 'Refresh Forms', 'advanced-form-integration' ); ?>">
                            <svg class="afi-refresh-icon" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                                <polyline points="23 4 23 10 17 10"></polyline>
                                <polyline points="1 20 1 14 7 14"></polyline>
                                <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path>
                            </svg>
                        </button>
                    </div>
                    <span class="afi-field-error" id="adfoin-form-id-error" v-if="showError('formId')">{{ errors.formId }}</span>
                </div>
            </div>
        </div>

        <?php do_action( 'adfoin_trigger_extra_fields', $field_data ); ?>
    </fieldset>

    <!-- Step 3: Action -->
    <fieldset class="afi-card afi-fieldset">
        <legend class="afi-sr-only"><?php esc_html_e( 'Step 3: Action', 'advanced-form-integration' ); ?></legend>
        <div class="afi-card-header">
            <h3 class="afi-card-title">
                <span class="afi-step-badge">Step 3</span>
                <?php esc_html_e( 'Action', 'advanced-form-integration' ); ?>
            </h3>
        </div>

        <div class="afi-row">
            <div class="afi-col afi-col-6">
                <div class="afi-form-group">
                    <label class="afi-label" for="adfoin-action-provider">
                        <?php esc_html_e( 'Platform', 'advanced-form-integration' ); ?>
                        <span class="afi-required-mark" aria-hidden="true"></span>
                    </label>
                    <div class="afi-input-group">
                        <afi-searchable-select
                            input-id="adfoin-action-provider"
                            :options="adfoinActionProviders"
                            v-model="action.actionProviderId"
                            name="action_provider"
                            placeholder="<?php esc_attr_e( 'Select Platform...', 'advanced-form-integration' ); ?>"
                            search-placeholder="<?php esc_attr_e( 'Search platforms...', 'advanced-form-integration' ); ?>"
                            empty-text="<?php esc_attr_e( 'No platforms match your search.', 'advanced-form-integration' ); ?>"
                            :required="true"
                            :has-error="showError('actionProvider')"
                            described-by="adfoin-action-provider-error"
                            @change="changeActionProvider"
                            @blur="markTouched('actionProvider')">
                        </afi-searchable-select>
                        <div class="afi-spinner" :class="{'is-active': actionLoading}" :aria-busy="actionLoading ? 'true' : 'false'" role="status"></div>
                    </div>
                    <span class="afi-field-error" id="adfoin-action-provider-error" v-if="showError('actionProvider')">{{ errors.actionProvider }}</span>
                </div>
            </div>

            <div class="afi-col afi-col-6">
                <div class="afi-form-group">
                    <label class="afi-label" for="adfoin-task">
                        <?php esc_html_e( 'Task', 'advanced-form-integration' ); ?>
                        <span class="afi-required-mark" aria-hidden="true"></span>
                    </label>
                    <afi-searchable-select
                        input-id="adfoin-task"
                        :options="taskOptions"
                        v-model="action.task"
                        name="task"
                        placeholder="<?php esc_attr_e( 'Select Task...', 'advanced-form-integration' ); ?>"
                        search-placeholder="<?php esc_attr_e( 'Search tasks...', 'advanced-form-integration' ); ?>"
                        empty-text="<?php esc_attr_e( 'No tasks match your search.', 'advanced-form-integration' ); ?>"
                        no-options-text="<?php esc_attr_e( 'Pick a platform first.', 'advanced-form-integration' ); ?>"
                        <?php if ( ! $is_edit ) : ?>
                            :disabled="actionValidated == 1"
                            :required="true"
                        <?php else : ?>
                            :required="true"
                        <?php endif; ?>
                        :has-error="showError('task')"
                        described-by="adfoin-task-error"
                        @change="markUserInteracted"
                        @blur="markTouched('task')">
                    </afi-searchable-select>
                    <span class="afi-field-error" id="adfoin-task-error" v-if="showError('task')">{{ errors.task }}</span>
                </div>
            </div>
        </div>
    </fieldset>

    <!-- Step 4: Map Fields (single card; inner content swaps between
         the loading state and the rendered mapping table). -->
    <fieldset class="afi-card afi-fieldset" v-if="action.actionProviderId && action.task">
        <legend class="afi-sr-only"><?php esc_html_e( 'Step 4: Map Fields', 'advanced-form-integration' ); ?></legend>
        <div class="afi-card-header">
            <h3 class="afi-card-title">
                <span class="afi-step-badge">Step 4</span>
                <?php esc_html_e( 'Map Fields', 'advanced-form-integration' ); ?>
            </h3>
        </div>

        <div class="afi-empty-state" v-if="actionsComponentsLoading" role="status" aria-live="polite">
            <div class="afi-spinner is-active afi-spinner-inline" aria-busy="true"></div>
            <p><?php esc_html_e( 'Loading platform components...', 'advanced-form-integration' ); ?></p>
        </div>

        <template v-else>
            <table class="afi-mapping-table">
                <tbody>
                    <component v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fieldData" v-bind:is="action.actionProviderId" :key="action.actionProviderId + '-' + componentKey"></component>
                </tbody>
            </table>

            <?php if ( function_exists( 'adfoin_fs' ) && adfoin_fs()->is__premium_only() && adfoin_fs()->is_plan( 'professional', true ) ) : ?>
            <?php // Universal WooCommerce option: send one record per order line
                  // item (so multi-product orders register each product). Hidden
                  // for Google Sheets PRO / Google Calendar, which render their
                  // own "One row per line item" control. Backend honours the same
                  // fieldData[wcMultipleRow] flag in adfoin_woocommerce_dispatch_records(). ?>
            <div class="afi-cl-wrap afi-wc-multirow"
                 v-if="action.task && trigger.formProviderId === 'woocommerce' && action.actionProviderId !== 'googlesheetspro' && action.actionProviderId !== 'googlecalendar'">
                <label>
                    <input type="checkbox" name="fieldData[wcMultipleRow]" value="true" v-model="fieldData.wcMultipleRow">
                    <?php esc_html_e( 'Send one record per order line item', 'advanced-form-integration' ); ?>
                </label>
                <p class="description"><?php esc_html_e( 'For multi-product orders, run this integration once per product instead of once per order (e.g. register each purchased product separately). Leave off to send a single record per order.', 'advanced-form-integration' ); ?></p>
            </div>
            <?php endif; ?>

            <div class="afi-cl-wrap">
                <cl-main v-if="action.task" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fieldData"></cl-main>
            </div>
        </template>
    </fieldset>

    <!-- Save Actions -->
    <div class="afi-actions">
        <button class="afi-btn-primary"
                type="submit"
                name="<?php echo esc_attr( $submit_name ); ?>"
                :disabled="!canSave"
                :title="canSave ? '' : '<?php echo esc_js( __( 'Complete all required fields before saving.', 'advanced-form-integration' ) ); ?>'">
            <?php echo esc_html( $submit_label ); ?>
        </button>
        <a class="afi-btn-secondary" href="<?php echo esc_url( $cancel_url ); ?>"><?php esc_html_e( 'Cancel', 'advanced-form-integration' ); ?></a>
        <?php if ( $is_edit ) :
            $delete_url     = wp_nonce_url(
                add_query_arg(
                    array(
                        'page'   => 'advanced-form-integration',
                        'action' => 'delete',
                        'id'     => $id,
                    ),
                    admin_url( 'admin.php' )
                ),
                'adfoin_delete_integration_nonce'
            );
            $delete_confirm = __( 'Are you sure you want to delete this integration? This cannot be undone.', 'advanced-form-integration' );
            $delete_onclick = sprintf( 'return confirm(%s);', wp_json_encode( $delete_confirm ) );
        ?>
            <a class="afi-btn-danger"
               href="<?php echo esc_url( $delete_url ); ?>"
               onclick="<?php echo esc_attr( $delete_onclick ); ?>">
                <?php esc_html_e( 'Delete Integration', 'advanced-form-integration' ); ?>
            </a>
        <?php endif; ?>
    </div>

</form>
