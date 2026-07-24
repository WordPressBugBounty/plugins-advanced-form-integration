/**
 * Advanced Form Integration - "supportcandy" action component.
 */

Vue.component('supportcandy', {
    props: ["trigger", "action", "fielddata"],
    data: function () {
        return {
            categoryLoading: false,
            priorityLoading: false,
            statusLoading: false,
            fieldsLoading: false,
            fields: []
        };
    },
    methods: {
        getFields: function () {
            adfoinHelpers.getFields(this, 'adfoin_get_supportcandy_ticket_fields', { task: 'add_ticket' });
        },
        getCategories: function () {
            adfoinHelpers.fetchToFielddata(this, 'adfoin_get_supportcandy_categories', {
                targetKey: 'categoryList',
                loadingKey: 'categoryLoading',
                requireCredId: false,
                requireSuccess: true
            });
        },
        getPriorities: function () {
            adfoinHelpers.fetchToFielddata(this, 'adfoin_get_supportcandy_priorities', {
                targetKey: 'priorityList',
                loadingKey: 'priorityLoading',
                requireCredId: false,
                requireSuccess: true
            });
        },
        getStatuses: function () {
            adfoinHelpers.fetchToFielddata(this, 'adfoin_get_supportcandy_statuses', {
                targetKey: 'statusList',
                loadingKey: 'statusLoading',
                requireCredId: false,
                requireSuccess: true
            });
        }
    },
    mounted: function () {
        if (typeof this.fielddata.categoryId == 'undefined') {
            this.fielddata.categoryId = '';
        }

        if (typeof this.fielddata.priorityId == 'undefined') {
            this.fielddata.priorityId = '';
        }

        if (typeof this.fielddata.statusId == 'undefined') {
            this.fielddata.statusId = '';
        }

        this.getCategories();
        this.getPriorities();
        this.getStatuses();
        this.getFields();
    },
    template: '#supportcandy-action-template'
});
