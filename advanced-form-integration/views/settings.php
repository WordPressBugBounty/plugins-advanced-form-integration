<div class="wrap">
    <?php adfoin_display_admin_header(); ?>

    <?php
    $current_tab = isset( $_REQUEST['tab'] ) ? sanitize_key( $_REQUEST['tab'] ) : 'general';
    ?>
    <h2 class="nav-tab-wrapper">
        <?php foreach ( $tabs as $tab_key => $tab_label ) { ?>
            <a class="nav-tab <?php echo ( $current_tab === $tab_key ) ? 'nav-tab-active' : ''; ?>"
               href="<?php echo esc_url( admin_url( 'admin.php?page=advanced-form-integration-settings&tab=' . $tab_key ) ); ?>">
                <?php echo esc_html( $tab_label ); ?>
            </a>
        <?php } ?>
    </h2>

    <?php do_action( 'adfoin_settings_view', $current_tab ); ?>
</div>

<script type="text/x-template" id="api-key-management-template">
    <div class="afi-accounts-card">
        <div class="afi-accounts-header">
            <h2 class="afi-accounts-title">
                <span class="dashicons dashicons-admin-users"></span>
                {{ title }}
            </h2>
            <button type="button" class="afi-btn-primary" @click="openAddModal">
                <?php esc_html_e( 'Add Account', 'advanced-form-integration' ); ?>
            </button>
        </div>
        <div class="afi-accounts-body">
            <table v-if="tableData.length > 0" class="wp-list-table widefat striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Title', 'advanced-form-integration' ); ?></th>
                        <th v-for="field in fields">{{ field.label }}</th>
                        <th><?php esc_html_e( 'Actions', 'advanced-form-integration' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(item, index) in tableData" :key="item.id">
                        <td>{{ item.title }}</td>
                        <td v-for="field in fields">{{ formatApiKey(item, field) }}</td>
                        <td>
                            <button type="button" class="afi-icon-btn" @click="editRow(index)" title="<?php esc_attr_e( 'Edit', 'advanced-form-integration' ); ?>">
                                <svg class="afi-svg-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                            </button>
                            <button type="button" class="afi-icon-btn afi-icon-btn-delete" @click="confirmDelete(index)" title="<?php esc_attr_e( 'Delete', 'advanced-form-integration' ); ?>">
                                <svg class="afi-svg-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/></svg>
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
            <div v-else class="afi-accounts-empty">
                <p><?php esc_html_e( 'No accounts found.', 'advanced-form-integration' ); ?></p>
            </div>
        </div>

        <!-- Modal -->
        <div class="afi-modal" v-if="showModal">
            <div class="afi-modal-content">
                <span class="afi-close" @click="closeModal">&times;</span>
                <h3>{{ isEditing ? '<?php esc_html_e( 'Edit Account', 'advanced-form-integration' ); ?>' : '<?php esc_html_e( 'Add Account', 'advanced-form-integration' ); ?>' }}</h3>
                <form @submit.prevent="addOrUpdateRow">
                    <table class="form-table">
                        <tr valign="top">
                            <th scope="row"><?php esc_html_e( 'Title', 'advanced-form-integration' ); ?></th>
                            <td>
                                <input type="text" class="regular-text" v-model="rowData.title" placeholder="<?php esc_attr_e( 'Add any title here', 'advanced-form-integration' ); ?>" required />
                            </td>
                        </tr>
                        <tr v-for="field in fields" :key="field.key" valign="top">
                            <th scope="row">{{field.label}}</th>
                            <td>
                                <input type="text" class="regular-text" v-model="rowData[field.key]" :placeholder="field.label" required />
                            </td>
                        </tr>
                    </table>
                    <button class="afi-btn-primary" type="submit">{{ isEditing ? '<?php esc_html_e( 'Update', 'advanced-form-integration' ); ?>' : '<?php esc_html_e( 'Save', 'advanced-form-integration' ); ?>' }}</button>
                </form>
            </div>
        </div>
    </div>
</script>
