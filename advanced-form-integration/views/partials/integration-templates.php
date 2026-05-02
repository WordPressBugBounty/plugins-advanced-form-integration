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
            <label class="afi-label" for="afi-cl-active" style="display: inline-block; margin-right: 10px;">
                <?php esc_attr_e( 'Enable Conditional Logic', 'advanced-form-integration' ); ?>
            </label>
            <input type="checkbox" id="afi-cl-active" v-model="action.cl.active" true-value="yes" false-value="no">
            <button class="afi-btn-secondary" style="margin-left:10px;" v-if="action.cl.active == 'yes'" @click.prevent="clAddCondition"><?php esc_attr_e( 'Add Condition', 'advanced-form-integration' ); ?></button>
        </div>

        <div v-if="action.cl.active == 'yes'" class="afi-cl-group">
            <div class="afi-form-group">
                <label class="afi-label" for="afi-cl-match"><?php esc_attr_e( 'Process this action if:', 'advanced-form-integration' ); ?></label>
                <select class="afi-select" id="afi-cl-match" style="width: auto; display: inline-block;" v-model="action.cl.match">
                    <option value="any"> <?php _e( 'Any condition matches', 'advanced-form-integration' ); ?> </option>
                    <option value="all"> <?php _e( 'All conditions match', 'advanced-form-integration' ); ?> </option>
                </select>
            </div>

            <div class="afi-cl-empty"
                 v-if="!action.cl.conditions || action.cl.conditions.length === 0">
                <p><?php esc_html_e( 'No conditions yet. Add one to start filtering submissions.', 'advanced-form-integration' ); ?></p>
                <button class="afi-btn-secondary" @click.prevent="clAddCondition"><?php esc_attr_e( 'Add your first condition', 'advanced-form-integration' ); ?></button>
            </div>

            <div class="afi-cl-conditions"
                 role="list"
                 aria-live="polite"
                 aria-relevant="additions removals"
                 aria-label="<?php esc_attr_e( 'Conditions', 'advanced-form-integration' ); ?>">
                <conditional-logic v-for="(condition, index) in action.cl.conditions"
                                   v-bind:condition="condition"
                                   v-bind:position="index + 1"
                                   v-bind:key="condition.id"
                                   v-bind:trigger="trigger"
                                   v-bind:action="action"
                                   v-bind:fielddata="fielddata"></conditional-logic>
            </div>
        </div>
    </div>
</script>

<!-- Conditional Logic Row Template -->
<script type="text/template" id="conditional-logic-template">
    <div class="afi-cl-row" role="listitem"
         :aria-label="rowLabel"
         v-if="action.cl.active == 'yes'">
        <label class="afi-sr-only" :for="'afi-cl-field-' + position">{{ rowLabel }}: <?php esc_html_e( 'form field', 'advanced-form-integration' ); ?></label>
        <select class="afi-select" :id="'afi-cl-field-' + position" style="flex: 1;" v-model="selected2" @change="updateFieldValue">
            <option value=""><?php _e( 'Select Field...', 'advanced-form-integration' ); ?></option>
            <option v-for="(item, index) in trigger.formFields" :value="index" > {{item}}  </option>
        </select>

        <label class="afi-sr-only" :for="'afi-cl-key-' + position">{{ rowLabel }}: <?php esc_html_e( 'field key', 'advanced-form-integration' ); ?></label>
        <input type="text" class="afi-input" :id="'afi-cl-key-' + position" style="flex: 1;" v-model="condition.field" placeholder="<?php esc_attr_e( 'Field Key', 'advanced-form-integration' ); ?>">

        <label class="afi-sr-only" :for="'afi-cl-operator-' + position">{{ rowLabel }}: <?php esc_html_e( 'operator', 'advanced-form-integration' ); ?></label>
        <select class="afi-select" :id="'afi-cl-operator-' + position" style="flex: 1;" v-model="condition.operator">
            <?php
            $operators = adfoin_get_cl_conditions();
            $groups    = adfoin_get_cl_conditions_grouped();
            foreach ( $groups as $group_label => $group_keys ) {
                echo '<optgroup label="' . esc_attr( $group_label ) . '">';
                foreach ( $group_keys as $key ) {
                    if ( isset( $operators[ $key ] ) ) {
                        echo '<option value="' . esc_attr( $key ) . '">' . esc_html( $operators[ $key ] ) . '</option>';
                    }
                }
                echo '</optgroup>';
            }
            ?>
        </select>

        <label class="afi-sr-only" :for="'afi-cl-value-' + position"
               v-if="condition.operator !== 'is_empty' && condition.operator !== 'is_not_empty'">{{ rowLabel }}: <?php esc_html_e( 'value', 'advanced-form-integration' ); ?></label>
        <input type="text" class="afi-input" :id="'afi-cl-value-' + position" style="flex: 1;"
               v-model="condition.value"
               v-if="condition.operator !== 'is_empty' && condition.operator !== 'is_not_empty'"
               :placeholder="valuePlaceholder">
        <span class="afi-helper-text" style="flex: 1;"
              v-else><?php esc_html_e( 'No value needed for this operator.', 'advanced-form-integration' ); ?></span>

        <button type="button"
                class="afi-icon-btn afi-cl-remove"
                @click.prevent="clRemoveCondition(condition)"
                :aria-label="removeLabel"
                :title="removeLabel">
            <span class="dashicons dashicons-trash" aria-hidden="true"></span>
        </button>

        <p class="afi-helper-text afi-cl-hint"
           v-if="condition.operator && condition.operator.indexOf('date_') === 0">
            <?php esc_html_e( 'Date format: YYYY-MM-DD or relative phrases like "today", "now", "now -7 days", "first day of last month".', 'advanced-form-integration' ); ?>
        </p>

        <p class="afi-helper-text afi-cl-hint"
           v-if="condition.operator === 'in_list' || condition.operator === 'not_in_list'">
            <?php esc_html_e( 'Comma-separated list of values. Comparison is case-insensitive.', 'advanced-form-integration' ); ?>
        </p>

        <p class="afi-helper-text afi-cl-hint"
           v-if="condition.operator === 'between' || condition.operator === 'not_between'">
            <?php esc_html_e( 'Two numbers separated by a comma. Range is inclusive on both ends.', 'advanced-form-integration' ); ?>
        </p>

        <p class="afi-helper-text afi-cl-hint"
           v-if="condition.operator === 'matches_regex' || condition.operator === 'does_not_match_regex'">
            <?php esc_html_e( 'PHP-style regex. Bare patterns are auto-wrapped in ~…~; include your own delimiters (e.g. /…/i) to use flags.', 'advanced-form-integration' ); ?>
        </p>
    </div>
</script>
