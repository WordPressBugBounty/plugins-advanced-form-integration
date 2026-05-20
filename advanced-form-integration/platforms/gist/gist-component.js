/**
 * Advanced Form Integration — "gist" action component.
 * Loaded on demand by adfoinComponentLoader.loadPlatform("gist").
 *
 * Free edition exposes only the basic Gist contact fields recognized
 * by POST /contacts. Tags, custom_properties and unsubscribe state
 * live in the Pro Vue component (#gistpro-action-template).
 */

Vue.component('gist', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            fields: [
                { type: 'text', value: 'email',   title: 'Email',                  task: ['create_contact'], required: true,  description: 'Required if user_id is not set.' },
                { type: 'text', value: 'name',    title: 'Name',                   task: ['create_contact'], required: false },
                { type: 'text', value: 'phone',   title: 'Phone',                  task: ['create_contact'], required: false },
                { type: 'text', value: 'user_id', title: 'User ID (your unique)',  task: ['create_contact'], required: false, description: 'Required if email is not set. Gist deduplicates by id > user_id > email.' }
            ]
        };
    },
    mounted: function () {
        if (typeof this.fielddata.credId == 'undefined') {
            this.fielddata.credId = '';
        }
    },
    template: '#gist-action-template'
});
