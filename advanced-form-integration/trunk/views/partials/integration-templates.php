<?php
/**
 * Shared Vue.js templates for integration forms
 * Used by both new_integration.php and edit_integration.php
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<!-- Editable Field Template -->
<script type="text/template" id="editable-field-template">
    <tr v-if="inArray(action.task, field.task)">
        <td>
            <label class="afi-label">
                {{field.title}}
                <span v-if="field.required" style="color: red;">*</span>
            </label>
        </td>
        <td>
            <div style="display: flex; gap: 10px; align-items: flex-start;">
                <div style="flex-grow: 1; width: 100%;">
                    <div style="display: flex; gap: 10px;">
                        <div style="flex-grow: 1;">
                            <input v-if="field.type == 'text'" type="text" ref="fieldValue" class="afi-input" v-model="fielddata[field.value]" :name="'fieldData['+field.value+']'" v-bind:required="field.required">
                            <textarea v-if="field.type == 'textarea'" class="afi-textarea" rows="3" ref="fieldValue" v-model="fielddata[field.value]" :name="'fieldData['+field.value+']'" v-bind:required="field.required"></textarea>
                        </div>
                        <div style="width: 200px; flex-shrink: 0;">
                            <select class="afi-select" @change="updateFieldValue" v-model="selected">
                                <option value=""><?php _e( 'Insert Field...', 'advanced-form-integration' ); ?></option>
                                <option v-for="(item, index) in trigger.formFields" :value="index" > {{item}}  </option>
                            </select>
                        </div>
                    </div>
                    <p v-if="field.description" class="afi-helper-text">{{field.description}}</p>
                </div>
            </div>
        </td>
    </tr>
</script>

<!-- Conditional Logic Main Template -->
<script type="text/template" id="cl-main-template">
    <div class="afi-cl-section">
        <div class="afi-form-group">
            <label class="afi-label" style="display: inline-block; margin-right: 10px;">
                <?php esc_attr_e( 'Enable Conditional Logic', 'advanced-form-integration' ); ?>
            </label>
            <input type="checkbox" v-model="action.cl.active" true-value="yes" false-value="no">
            <button class="afi-btn-secondary" style="margin-left:10px;" v-if="action.cl.active == 'yes'" @click.prevent="clAddCondition"><?php esc_attr_e( 'Add Condition', 'advanced-form-integration' ); ?></button>
        </div>

        <div v-if="action.cl.active == 'yes'" class="afi-cl-group">
            <div class="afi-form-group">
                <label class="afi-label"><?php esc_attr_e( 'Process this action if:', 'advanced-form-integration' ); ?></label>
                <select class="afi-select" style="width: auto; display: inline-block;" v-model="action.cl.match">
                    <option value="any"> <?php _e( 'Any condition matches', 'advanced-form-integration' ); ?> </option>
                    <option value="all"> <?php _e( 'All conditions match', 'advanced-form-integration' ); ?> </option>
                </select>
            </div>
            
            <conditional-logic v-for="condition in action.cl.conditions" v-bind:condition="condition" v-bind:key="condition.id" v-bind:trigger="trigger" v-bind:action="action" v-bind:fielddata="fielddata"></conditional-logic>
        </div>
    </div>
</script>

<!-- Conditional Logic Row Template -->
<script type="text/template" id="conditional-logic-template">
    <div class="afi-cl-row" v-if="action.cl.active == 'yes'">
        <select class="afi-select" style="flex: 1;" v-model="selected2" @change="updateFieldValue">
            <option value=""><?php _e( 'Select Field...', 'advanced-form-integration' ); ?></option>
            <option v-for="(item, index) in trigger.formFields" :value="index" > {{item}}  </option>
        </select>
        
        <input type="text" class="afi-input" style="flex: 1;" v-model="condition.field" placeholder="<?php esc_attr_e( 'Field Key', 'advanced-form-integration' ); ?>">
        
        <select class="afi-select" style="flex: 1;" v-model="condition.operator">
            <?php
            $operators = adfoin_get_cl_conditions();
            foreach ( $operators as $key => $value ) {
                echo "<option value='" . esc_attr( $key ) . "'> " . esc_html( $value ) . " </option>";
            }
            ?>
        </select>
        
        <input type="text" class="afi-input" style="flex: 1;" v-model="condition.value" placeholder="<?php esc_attr_e( 'Value', 'advanced-form-integration' ); ?>">
        
        <button class="afi-btn-secondary" style="color: red;" @click.prevent="clRemoveCondition(condition)">
            <span class="dashicons dashicons-trash" style="line-height: 1.5;"></span>
        </button>
    </div>
</script>
