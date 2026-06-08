/**
 * Advanced Form Integration - "zohomeeting" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("zohomeeting").
 */

Vue.component('zohomeeting', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            credentialsList: [],
            credentialLoading: false,
            fields: [],
            fieldsLoading: false
        }
    },
    methods: {
        ensureDefaults: function () {
            adfoinHelpers.ensureFielddataDefaults(this, { credId: '', webinarKey: '' });
        },
        loadFields: function () {
            adfoinHelpers.loadFields(this, 'adfoin_get_zohomeeting_fields', {
                task: 'register_contact',
                taskGate: 'register_contact'
            });
        }
    },
    watch: {
        'action.task': function () {
            this.loadFields();
        }
    },
    mounted: function () {
        adfoinHelpers.fetchCredentials(this, 'adfoin_get_zohomeeting_credentials', { loadingKey: 'credentialLoading', clearOnFail: true });
        this.ensureDefaults();
        this.loadFields();
    },
    template: '#zohomeeting-action-template'
});
