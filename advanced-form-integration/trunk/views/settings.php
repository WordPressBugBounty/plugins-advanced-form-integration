<?php adfoin_display_admin_header(); ?>
<div class="wrap">

    <?php
    $current_tab = isset( $_REQUEST['tab'] ) ? $_REQUEST['tab'] : 'general';
    ?>
    <h2 class="nav-tab-wrapper">
        <?php foreach ($tabs as $tab_key => $tab_label) { ?>
            <a class="nav-tab <?php echo ( $current_tab == $tab_key ) ? 'nav-tab-active' : ''; ?>" href="<?php echo admin_url( 'admin.php?page=advanced-form-integration-settings&tab=' ) . $tab_key; ?>"><?php echo $tab_label ?></a>
        <?php } ?>
    </h2>

    <?php
    if( $current_tab == 'general' ) {

    }

    do_action( 'adfoin_settings_view', $current_tab );
    ?>
</div>

<script type="text/x-template" id="api-key-management-template">
    <div class="afi-accounts-card">
        <div class="afi-accounts-header">
            <h2 class="afi-accounts-title">
                <span class="dashicons dashicons-admin-users"></span>
                {{ title }}
            </h2>
            <button type="button" class="button button-primary" @click="openAddModal">
                <?php esc_html_e( 'Add Account', 'advanced-form-integration' ); ?>
            </button>
        </div>
        <div class="afi-accounts-body">
            <table v-if="tableData.length > 0" class="wp-list-table widefat striped">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th v-for="field in fields">{{ field.label }}</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(item, index) in tableData" :key="item.id">
                        <td>{{ item.title }}</td>
                        <td v-for="field in fields">{{ formatApiKey(item, field) }}</td>
                        <td>
                            <button type="button" class="button-link" @click="editRow(index)"><span class="dashicons dashicons-edit"></span></button>
                            <button type="button" class="button-link" @click="confirmDelete(index)"><span class="dashicons dashicons-trash"></span></button>
                        </td>
                    </tr>
                </tbody>
            </table>
            <p v-else style="padding: 20px; text-align: center; color: #666;"><?php esc_html_e( 'No accounts found.', 'advanced-form-integration' ); ?></p>
        </div>

        <!-- Modal -->
        <div class="afi-modal" v-if="showModal">
            <div class="afi-modal-content">
                <span class="afi-close" @click="closeModal">&times;</span>
                <h3>{{ isEditing ? '<?php esc_html_e( 'Edit Account', 'advanced-form-integration' ); ?>' : '<?php esc_html_e( 'Add Account', 'advanced-form-integration' ); ?>' }}</h3>
                <form @submit.prevent="addOrUpdateRow">
                    <table class="form-table">
                        <tr valign="top">
                            <th scope="row"> <?php _e( 'Title', 'advanced-form-integration' ); ?></th>
                            <td>
                                <input type="text" class="regular-text" v-model="rowData.title" placeholder="Add any title here" required />
                            </td>
                        </tr>
                        <tr v-for="field in fields" :key="field.key" valign="top">
                            <th scope="row">{{field.label}}</th>
                            <td>
                                <input type="text" class="regular-text" v-model="rowData[field.key]" :placeholder="field.label" required />
                            </td>
                        </tr>
                    </table>
                    <button class="button button-primary" type="submit">{{ isEditing ? 'Update' : 'Save' }}</button>
                </form>
            </div>
        </div>
    </div>
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Function to update checkbox container background based on checkbox state
    function updateCheckboxBackground(checkbox) {
        const container = checkbox.closest('.afi-checkbox');
        if (container) {
            if (checkbox.checked) {
                container.classList.add('active');
            } else {
                container.classList.remove('active');
            }
        }
    }

    // Initialize all checkboxes on page load
    const checkboxes = document.querySelectorAll('.afi-checkbox input[type="checkbox"]');
    checkboxes.forEach(function(checkbox) {
        // Set initial state
        updateCheckboxBackground(checkbox);
        
        // Add event listener for changes
        checkbox.addEventListener('change', function() {
            updateCheckboxBackground(this);
        });
    });
});
</script>