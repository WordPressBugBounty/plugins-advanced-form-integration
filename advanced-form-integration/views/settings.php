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
    <div>
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
                        <button @click="editRow(index)"><span class="dashicons dashicons-edit"></span></button>
                        <button @click="confirmDelete(index)"><span class="dashicons dashicons-trash"></span></button>
                    </td>
                </tr>
            </tbody>
        </table>
        <br>
        <form @submit.prevent="addOrUpdateRow">
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"> <?php _e( 'Title', 'advanced-form-integration' ); ?></th>
                    <td>
                        <input type="text" class="regular-text"v-model="rowData.title" placeholder="Add any title here" required />
                    </td>
                </tr>
                <tr v-for="field in fields" :key="field" valign="top">
                    <th scope="row">{{field.label}}</th>
                    <td>
                        <input type="text" class="regular-text"v-model="rowData[field.key]" :placeholder="field.label" required />
                    </td>
                </tr>
            </table>
            <button class="button button-primary" type="submit">{{ isEditing ? 'Update' : 'Add' }}</button>
        </form>
    </div>
</script>