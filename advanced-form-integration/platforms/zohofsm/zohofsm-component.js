/**
 * Advanced Form Integration - "zohofsm" action component.
 */

Vue.component('zohofsm', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        var customerTasks = ['upsert_customer', 'create_service_appointment', 'create_work_order'];
        var appointmentTasks = ['create_service_appointment'];
        var workOrderTasks = ['create_work_order'];
        return {
            credentialsList: [],
            credentialLoading: false,
            fields: [
                { type: 'text', value: 'name',         title: 'Customer Name (Display)', task: customerTasks, required: false, description: 'Falls back to "First Last" if blank.' },
                { type: 'text', value: 'first_name',   title: 'First Name',   task: customerTasks, required: false },
                { type: 'text', value: 'last_name',    title: 'Last Name',    task: customerTasks, required: false },
                { type: 'text', value: 'email',        title: 'Email',        task: customerTasks, required: true },
                { type: 'text', value: 'phone',        title: 'Phone',        task: customerTasks, required: false },
                { type: 'text', value: 'mobile',       title: 'Mobile',       task: customerTasks, required: false },
                { type: 'text', value: 'company_name', title: 'Company Name', task: customerTasks, required: false },

                { type: 'text', value: 'billing_address', title: 'Billing — Street',  task: customerTasks, required: false },
                { type: 'text', value: 'billing_city',    title: 'Billing — City',    task: customerTasks, required: false },
                { type: 'text', value: 'billing_state',   title: 'Billing — State',   task: customerTasks, required: false },
                { type: 'text', value: 'billing_zip',     title: 'Billing — Zip',     task: customerTasks, required: false },
                { type: 'text', value: 'billing_country', title: 'Billing — Country', task: customerTasks, required: false },

                { type: 'text', value: 'shipping_address', title: 'Shipping — Street',  task: customerTasks, required: false },
                { type: 'text', value: 'shipping_city',    title: 'Shipping — City',    task: customerTasks, required: false },
                { type: 'text', value: 'shipping_state',   title: 'Shipping — State',   task: customerTasks, required: false },
                { type: 'text', value: 'shipping_zip',     title: 'Shipping — Zip',     task: customerTasks, required: false },
                { type: 'text', value: 'shipping_country', title: 'Shipping — Country', task: customerTasks, required: false },

                { type: 'text',     value: 'summary',     title: 'Summary',          task: appointmentTasks.concat(workOrderTasks), required: false },
                { type: 'textarea', value: 'description', title: 'Description',      task: appointmentTasks.concat(workOrderTasks), required: false },
                { type: 'text',     value: 'priority',    title: 'Priority',         task: appointmentTasks.concat(workOrderTasks), required: false },
                { type: 'text',     value: 'start_time',  title: 'Start (YYYY-MM-DDTHH:MM:SS+00:00)', task: appointmentTasks, required: false },
                { type: 'text',     value: 'end_time',    title: 'End (YYYY-MM-DDTHH:MM:SS+00:00)',   task: appointmentTasks, required: false },
                { type: 'text',     value: 'status',      title: 'Status',           task: appointmentTasks, required: false },
                { type: 'text',     value: 'due_date',    title: 'Due Date (YYYY-MM-DD)', task: workOrderTasks, required: false },
                { type: 'text',     value: 'reference_number', title: 'Reference Number', task: workOrderTasks, required: false }
            ]
        };
    },
    created: function () {
        adfoinHelpers.fetchCredentials(this, 'adfoin_get_zohofsm_credentials', { loadingKey: 'credentialLoading', clearOnFail: true });
        var that = this;
        this.fields.forEach(function (field) {
            if (typeof that.fielddata[field.value] === 'undefined') {
                that.$set(that.fielddata, field.value, '');
            }
        });
        if (typeof this.fielddata.credId === 'undefined') { this.$set(this.fielddata, 'credId', ''); }
    },
    template: '#zohofsm-action-template'
});
