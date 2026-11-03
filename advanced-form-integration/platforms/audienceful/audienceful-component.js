/**
 * Advanced Form Integration - "audienceful" action component.
 * Auto-extracted from assets/js/script.js. Loaded on demand by
 * adfoinComponentLoader.loadPlatform("audienceful").
 */

Vue.component('audienceful', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fields: [
                { type: 'text', value: 'email', title: 'Email', task: ['add_person'], required: true },
                { type: 'text', value: 'first_name', title: 'First Name', task: ['add_person'], required: false },
                { type: 'text', value: 'last_name', title: 'Last Name', task: ['add_person'], required: false },
                { type: 'text', value: 'tags', title: 'Tags', task: ['add_person'], required: false, description: 'Comma-separated. Added to the person\'s existing tags.' },
                { type: 'text', value: 'notes', title: 'Notes', task: ['add_person'], required: false, description: 'Replaces the person\'s existing notes.' }
            ]
        };
    },
    mounted: function () {
        if (typeof this.fielddata.credId === 'undefined') {
            this.$set(this.fielddata, 'credId', '');
        }
    },
    template: '#audienceful-action-template'
});
