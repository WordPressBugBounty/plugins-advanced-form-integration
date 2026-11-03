/**
 * Advanced Form Integration - "keila" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("keila").
 */

Vue.component('keila', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['add_contact'], required: true },
                { type: 'text', value: 'first_name', title: 'First Name', task: ['add_contact'], required: false },
                { type: 'text', value: 'last_name', title: 'Last Name', task: ['add_contact'], required: false }
            ]
        }
    },
    mounted() {
        if (typeof this.fielddata.credId === 'undefined') {
            this.$set(this.fielddata, 'credId', '');
        }
    },
    template: '#keila-action-template'
});
