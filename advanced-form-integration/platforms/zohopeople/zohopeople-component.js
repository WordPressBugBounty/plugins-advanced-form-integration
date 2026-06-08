/**
 * Advanced Form Integration - "zohopeople" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("zohopeople").
 */

Vue.component('zohopeople', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            credentialsList: [],
            credentialLoading: false,
            fields: [
                { type: 'text', value: 'EmployeeID', title: 'Employee ID', task: ['create_employee'], required: false },
                { type: 'text', value: 'FirstName', title: 'First Name', task: ['create_employee'], required: true },
                { type: 'text', value: 'LastName', title: 'Last Name', task: ['create_employee'], required: true },
                { type: 'text', value: 'EmailID', title: 'Email', task: ['create_employee'], required: false },
                { type: 'text', value: 'Work_phone', title: 'Mobile', task: ['create_employee'], required: false }
            ]
        };
    },
    created: function () {
        adfoinHelpers.fetchCredentials(this, 'adfoin_get_zohopeople_credentials', { loadingKey: 'credentialLoading', clearOnFail: true });
        var that = this;

        this.fields.forEach(function (field) {
            if (typeof that.fielddata[field.value] === 'undefined') {
                that.$set(that.fielddata, field.value, '');
            }
        });

        if (typeof this.fielddata.credId === 'undefined') {
            this.$set(this.fielddata, 'credId', '');
        }

        if (typeof this.fielddata.formLinkName === 'undefined' || !this.fielddata.formLinkName) {
            this.$set(this.fielddata, 'formLinkName', 'P_EmployeeView');
        }
    },
    template: '#zohopeople-action-template'
});
