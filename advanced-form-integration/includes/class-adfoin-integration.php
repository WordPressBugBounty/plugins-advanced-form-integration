<?php

class Advanced_Form_Integration_Integration extends Advanced_Form_Integration_DB {

    /**
     * Cache of all integration rows. Populated lazily on first get()/get_title()
     * call rather than in __construct() — most triggers only need
     * get_by_trigger() and would otherwise pay for a full SELECT * + a few MB
     * of `data` longtext per form submission they never read.
     *
     * Initialized to null (not array()) so callers can distinguish
     * "never loaded" from "loaded, table empty".
     *
     * @var array<int,array<string,mixed>>|null
     */
    public $integrations = null;

    /*
    * The constructor function
    */
    public function __construct() {
        global $wpdb;

        $this->db    = $wpdb;
        $this->table = $this->db->prefix . 'adfoin_integration';
    }

    /**
     * Fetch every row in the integrations table. Used to back $this->integrations
     * for the by-id lookups (get / get_title); also callable directly for
     * callers that genuinely want every row (e.g. import/export).
     *
     * @return array<int,array<string,mixed>>
     */
    public function all() {
        $all = $this->db->get_results( "SELECT * FROM {$this->table}", 'ARRAY_A' );
        return $all;
    }

    public function get( $id ) {
        if ( null === $this->integrations ) {
            $this->integrations = $this->all();
        }
        foreach( $this->integrations as $single_integration ) {
            if( $id == $single_integration['id'] ) {
                return $single_integration;
            }
        }
    }

    public function get_title( $id ) {
        $integration = $this->get( $id );

        if( $integration &&  isset( $integration['title'] ) ) {
            return $integration['title'];
        }
    }

    /**
     * Duplicate an integration row.
     *
     * Inserts a copy of the source row with a "Copy of " title prefix and
     * status = 0 (inactive), so the new integration does not start firing
     * until the user reviews and activates it. Shared by the list-table bulk
     * "Duplicate" action and the single-row duplicate handler — callers remain
     * responsible for their own nonce / capability checks.
     *
     * @param int $id Source integration id.
     * @return int|false New integration id on success, or false when the
     *                   source row is missing or the insert fails.
     */
    public function duplicate( $id ) {
        $row = $this->db->get_row(
            $this->db->prepare( "SELECT * FROM {$this->table} WHERE id = %d", (int) $id ),
            ARRAY_A
        );

        if ( ! $row ) {
            return false;
        }

        $inserted = $this->db->insert(
            $this->table,
            array(
                'title'           => __( 'Copy of ', 'advanced-form-integration' ) . $row['title'],
                'form_provider'   => $row['form_provider'],
                'form_id'         => $row['form_id'],
                'form_name'       => $row['form_name'],
                'action_provider' => $row['action_provider'],
                'task'            => $row['task'],
                'data'            => $row['data'],
                'status'          => 0,
            )
        );

        return $inserted ? (int) $this->db->insert_id : false;
    }

    /**
     * Return active integration records matching a trigger (form provider) and
     * optional form id. Canonical entry point for triggers — replaces the
     * historical pattern of raw $wpdb->get_results() with hardcoded provider
     * names that was scattered across 15+ trigger files.
     *
     * @param string             $trigger_platform Form provider slug (e.g. 'cf7').
     * @param string|int|false   $form             Form id to filter on, or false for all forms.
     * @return array<int,array<string,mixed>>      Records as ARRAY_A.
     */
    public function get_by_trigger( $trigger_platform, $form = false ) {
        if ( false !== $form && '' !== $form ) {
            $sql = $this->db->prepare(
                "SELECT * FROM {$this->table} WHERE status = 1 AND form_provider = %s AND form_id = %s",
                $trigger_platform,
                (string) $form
            );
        } else {
            $sql = $this->db->prepare(
                "SELECT * FROM {$this->table} WHERE status = 1 AND form_provider = %s",
                $trigger_platform
            );
        }

        return $this->db->get_results( $sql, ARRAY_A );
    }

    /**
     * Same as get_by_trigger() but matches form_id with a "<anything>_<form>"
     * suffix pattern. Used by triggers whose form_id is namespaced
     * (e.g. "section_3" matches a stored form_id of "post_id_section_3").
     *
     * @param string             $trigger_platform Form provider slug.
     * @param string|int|false   $form             Form-id suffix to match, or false for all.
     * @return array<int,array<string,mixed>>
     */
    public function get_by_trigger_partial( $trigger_platform, $form = false ) {
        if ( false !== $form && '' !== $form ) {
            $like_pattern = '%_' . $this->db->esc_like( (string) $form );
            $sql = $this->db->prepare(
                "SELECT * FROM {$this->table} WHERE status = 1 AND form_provider = %s AND form_id LIKE %s",
                $trigger_platform,
                $like_pattern
            );
        } else {
            $sql = $this->db->prepare(
                "SELECT * FROM {$this->table} WHERE status = 1 AND form_provider = %s",
                $trigger_platform
            );
        }

        return $this->db->get_results( $sql, ARRAY_A );
    }

    /**
     * @deprecated Use adfoin_dispatch_integrations() directly. Kept as a back-compat
     * alias for any third-party code that instantiates this class and calls send().
     * All in-tree callers were migrated to the function in the Tier B refactor.
     *
     * @param array $saved_records
     * @param array $posted_data
     * @return void
     */
    public function send( $saved_records, $posted_data ) {
        adfoin_dispatch_integrations( $saved_records, $posted_data );
    }
}