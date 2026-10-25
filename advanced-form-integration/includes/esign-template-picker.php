<?php
/**
 * Template / role picker shared by the e-signature actions (DocuSign, signNow).
 *
 * Renders the dropdown rows for an action template and loads the matching Vue
 * mixin (assets/js/esign-template-picker.js). The dropdowns are the
 * templateId / roleName fields themselves (the option value is what's saved).
 * Their last option, "Use a form field...", swaps the dropdown for a mapping
 * field so a form answer can choose the template or role per submission.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'adfoin_esign_template_picker_rows' ) ) :
/**
 * @param string $task Action task the rows belong to.
 */
function adfoin_esign_template_picker_rows( $task ) {
    $on_task = "action.task == '" . esc_attr( $task ) . "'";

    // Mapping fields shown in "Use a form field..." mode.
    $template_field = array(
        'type'        => 'text',
        'value'       => 'templateId',
        'title'       => __( 'Template ID', 'advanced-form-integration' ),
        'task'        => array( $task ),
        'required'    => true,
        'description' => __( 'Map the form field that holds the template ID, or paste an ID.', 'advanced-form-integration' ),
    );
    $role_field = array(
        'type'        => 'text',
        'value'       => 'roleName',
        'title'       => __( 'Recipient Role', 'advanced-form-integration' ),
        'task'        => array( $task ),
        'required'    => false,
        'description' => __( 'Must match a role name on the template.', 'advanced-form-integration' ),
    );
    ?>
    <tr valign="top" class="alternate" v-if="<?php echo $on_task; ?> && fielddata.credId">
        <td scope="row-title"><label><?php esc_html_e( 'Template', 'advanced-form-integration' ); ?> <span style="color: red;">*</span></label></td>
        <td>
            <select v-model="templateChoice" :name="templateMapped ? null : 'fieldData[templateId]'" required :disabled="templatesLoading">
                <option value=""><?php esc_html_e( 'Select a template...', 'advanced-form-integration' ); ?></option>
                <option v-if="fielddata.templateId && !templateMapped && !templateInList" :value="fielddata.templateId"><?php esc_html_e( 'Saved template (not in the list)', 'advanced-form-integration' ); ?></option>
                <option v-for="template in templates" :value="template.id">{{ template.name }}</option>
                <option value="__mapped__"><?php esc_html_e( 'Use a form field...', 'advanced-form-integration' ); ?></option>
            </select>
            <a href="#" @click.prevent="loadTemplates" title="<?php esc_attr_e( 'Refresh templates', 'advanced-form-integration' ); ?>" aria-label="<?php esc_attr_e( 'Refresh templates', 'advanced-form-integration' ); ?>" style="margin-left: 8px; text-decoration: none; vertical-align: middle;"><span class="dashicons dashicons-update" style="margin-top: 3px;" aria-hidden="true"></span></a>
            <div class="afi-spinner" v-bind:class="{'is-active': templatesLoading}"></div>
            <p class="description" v-if="templatesError" style="color:#b32d2e;">{{ templatesError }} <?php esc_html_e( 'Choose "Use a form field..." to enter a template ID instead.', 'advanced-form-integration' ); ?></p>
            <p class="description" v-else-if="!templatesLoading && templatesLoaded && !templates.length"><?php esc_html_e( 'No templates found in this account. Create one, then click Refresh.', 'advanced-form-integration' ); ?></p>
        </td>
    </tr>
    <editable-field v-if="fielddata.credId && templateMapped" :field="<?php echo esc_attr( wp_json_encode( $template_field ) ); ?>" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
    <tr valign="top" class="alternate" v-if="<?php echo $on_task; ?> && showRoleSelect">
        <td scope="row-title"><label><?php esc_html_e( 'Recipient Role', 'advanced-form-integration' ); ?></label></td>
        <td>
            <select v-model="roleChoice" :name="roleMapped ? null : 'fieldData[roleName]'">
                <option value=""><?php esc_html_e( 'Select a role...', 'advanced-form-integration' ); ?></option>
                <option v-for="role in templateRoles" :value="role.roleName">{{ role.roleName }}</option>
                <option value="__mapped__"><?php esc_html_e( 'Use a form field...', 'advanced-form-integration' ); ?></option>
            </select>
            <div class="afi-spinner" v-bind:class="{'is-active': rolesLoading}"></div>
            <p class="description"><?php esc_html_e( 'The form submitter is sent the document in this role.', 'advanced-form-integration' ); ?></p>
        </td>
    </tr>
    <editable-field v-if="showRoleField" :field="<?php echo esc_attr( wp_json_encode( $role_field ) ); ?>" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></editable-field>
    <?php
}
endif;

if ( ! function_exists( 'adfoin_esign_template_picker_script' ) ) :
/**
 * Print the picker mixin once per page. Called from action_fields, so it is on
 * the page before the lazy-loaded action components register.
 */
function adfoin_esign_template_picker_script() {
    static $printed = false;

    if ( $printed ) {
        return;
    }

    $printed = true;

    printf(
        '<script src="%s"></script>',
        esc_url( ADVANCED_FORM_INTEGRATION_URL . '/assets/js/esign-template-picker.js?ver=' . ADVANCED_FORM_INTEGRATION_VERSION )
    );
}
endif;
