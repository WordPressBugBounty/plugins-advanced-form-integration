<?php

function adfoin_save_credentials( $platform, $data ) {

    if ( $platform && is_array( $data ) ) {

        $platform = sanitize_text_field( $platform );

        $all_credentials = (array) maybe_unserialize( get_option( 'adfoin_credentials', array() ) );
        $previous = isset( $all_credentials[ $platform ] ) && is_array( $all_credentials[ $platform ] )
            ? $all_credentials[ $platform ]
            : array();

        // Index previous records by id so we can diff per-record.
        $previous_by_id = array();
        foreach ( $previous as $prev ) {
            if ( is_array( $prev ) && isset( $prev['id'] ) ) {
                $previous_by_id[ (string) $prev['id'] ] = $prev;
            }
        }

        $now = time();
        foreach ( $data as &$cred ) {
            if ( ! is_array( $cred ) || ! isset( $cred['id'] ) ) {
                continue;
            }
            $cred_id  = (string) $cred['id'];
            $existing = isset( $previous_by_id[ $cred_id ] ) ? $previous_by_id[ $cred_id ] : null;

            if ( $existing === null ) {
                // Brand new record.
                if ( empty( $cred['created_at'] ) ) {
                    $cred['created_at'] = $now;
                }
                if ( empty( $cred['updated_at'] ) ) {
                    $cred['updated_at'] = $now;
                }
            } else {
                // Stamp updated_at only when the record actually changed
                // (compare ignoring the timestamp itself so a no-op save
                // through this function doesn't perpetually bump it).
                $without_ts = $cred;
                unset( $without_ts['updated_at'] );
                $existing_without_ts = $existing;
                unset( $existing_without_ts['updated_at'] );

                if ( $without_ts !== $existing_without_ts ) {
                    $cred['updated_at'] = $now;
                } elseif ( isset( $existing['updated_at'] ) ) {
                    // Carry forward when no content change.
                    $cred['updated_at'] = $existing['updated_at'];
                } elseif ( empty( $cred['updated_at'] ) ) {
                    // Backfill for records that pre-date this audit field.
                    $cred['updated_at'] = $now;
                }
                // Carry created_at forward — never overwrite once set.
                if ( isset( $existing['created_at'] ) ) {
                    $cred['created_at'] = $existing['created_at'];
                } elseif ( empty( $cred['created_at'] ) ) {
                    // Backfill for records that pre-date this audit field.
                    $cred['created_at'] = $now;
                }
            }
        }
        unset( $cred );

        $all_credentials[ $platform ] = $data;
        // Pass the raw array — update_option() serializes once on its own. The
        // previous explicit maybe_serialize() produced a double-serialized row.
        // The 4th arg flips autoload off so this (potentially tens-of-KB) blob
        // is not loaded into memory on every pageload.
        update_option( 'adfoin_credentials', $all_credentials, false );
    }
}

/**
 * Mark a credential as recently used.
 *
 * Updates `last_used_at` on the credential record matching `$cred_id` under
 * `$platform`. Throttled internally — at most one DB write per minute per
 * credential — so it's safe to call on the hot API-request path without
 * generating excessive option updates on busy sites.
 *
 * @param string $platform Platform slug.
 * @param string $cred_id  Credential id.
 * @return void
 */
function adfoin_mark_credential_used( $platform, $cred_id ) {
    if ( ! $platform || '' === (string) $cred_id ) {
        return;
    }

    $platform        = sanitize_text_field( $platform );
    $all_credentials = (array) maybe_unserialize( get_option( 'adfoin_credentials', array() ) );

    if ( ! isset( $all_credentials[ $platform ] ) || ! is_array( $all_credentials[ $platform ] ) ) {
        return;
    }

    $now      = time();
    $throttle = 60; // seconds — coarsest granularity we care about for "recently used"
    $changed  = false;

    foreach ( $all_credentials[ $platform ] as &$cred ) {
        if ( ! is_array( $cred ) || ! isset( $cred['id'] ) ) {
            continue;
        }
        if ( (string) $cred['id'] !== (string) $cred_id ) {
            continue;
        }
        $last = isset( $cred['last_used_at'] ) ? (int) $cred['last_used_at'] : 0;
        if ( $now - $last >= $throttle ) {
            $cred['last_used_at'] = $now;
            $changed = true;
        }
        break;
    }
    unset( $cred );

    if ( $changed ) {
        // Raw array + autoload=no — see adfoin_save_credentials() for rationale.
        update_option( 'adfoin_credentials', $all_credentials, false );
    }
}

function adfoin_read_credentials( $platform ) {
    $all_credentials = ( array ) maybe_unserialize( get_option( 'adfoin_credentials', array() ) );
    $credentials     = array();

    if( isset( $all_credentials[$platform] ) && count( $all_credentials[$platform] ) > 0 ) {
        $credentials = $all_credentials[$platform];
    }

    $credentials = apply_filters( 'adfoin_get_credentials', $credentials, $platform );

    // Expose every credential under BOTH camelCase and snake_case spellings.
    //
    // The one-shot `adfoin_run_credential_casing_migration()` rewrites stored
    // records to snake_case and DELETES the camelCase keys, but ~40 platform
    // files still read the camelCase spelling (e.g. attio.php reads
    // $credentials['accessToken']). Keeping this alias bidirectional means
    // both old (camelCase) and new (snake_case) platform code resolve to the
    // same value regardless of how the stored record is cased.
    //
    // DEPRECATED — once every platform reads snake_case, the camel->snake half
    // can be dropped; the snake->camel half stays until no callers read
    // camelCase. Until then this must run on every read, migrated or not.
    if ( is_array( $credentials ) ) {
        foreach ( $credentials as &$cred ) {
            if ( ! is_array( $cred ) ) {
                continue;
            }
            $map = array(
                'clientId'     => 'client_id',
                'clientSecret' => 'client_secret',
                'accessToken'  => 'access_token',
                'refreshToken' => 'refresh_token',
                'tokenExpires' => 'token_expires',
                'expiresAt'    => 'expires_at',
                'dataCenter'   => 'data_center',
            );
            foreach ( $map as $camel => $snake ) {
                $has_camel = isset( $cred[ $camel ] ) && '' !== $cred[ $camel ];
                $has_snake = isset( $cred[ $snake ] ) && '' !== $cred[ $snake ];

                if ( $has_camel && ! $has_snake ) {
                    $cred[ $snake ] = $cred[ $camel ];
                } elseif ( $has_snake && ! $has_camel ) {
                    $cred[ $camel ] = $cred[ $snake ];
                }
            }
        }
        unset( $cred );
    }

    return $credentials;
}

function adfoin_get_credentials_by_id( $platform, $cred_id ) {
    $credentials     = array();
    $all_credentials = adfoin_read_credentials( $platform );

    if ( is_array( $all_credentials ) && ! empty( $all_credentials ) ) {
        // Default to first credential if available
        $credentials = isset( $all_credentials[0] ) ? $all_credentials[0] : array();

        foreach ( $all_credentials as $single ) {
            if ( $cred_id && isset( $single['id'] ) && $cred_id == $single['id'] ) {
                $credentials = $single;
                break;
            }
        }
    }

    return $credentials;
}

/**
 * One-shot migration: rewrite stored credential records from legacy camelCase
 * keys (clientId, accessToken, …) to canonical snake_case (client_id, access_token, …).
 *
 * Idempotent and tracked by the `adfoin_credentials_casing_migration` option.
 * Once this has run on enough installs, the read-time shim in
 * `adfoin_read_credentials()` becomes redundant and can be removed.
 *
 * Snake_case wins on conflict: if a record has both `clientId` and `client_id`,
 * we keep `client_id`'s value and drop `clientId`. The shim has been adding
 * snake_case versions on every read since 1.130.0, so the conflict path is
 * the common case here, not the edge case.
 *
 * @return void
 */
function adfoin_run_credential_casing_migration() {
    $version_option   = 'adfoin_credentials_casing_migration';
    $current_version  = (int) get_option( $version_option, 0 );
    $target_version   = 1;

    if ( $current_version >= $target_version ) {
        return;
    }

    $all_credentials = (array) maybe_unserialize( get_option( 'adfoin_credentials', array() ) );

    $map = array(
        'clientId'     => 'client_id',
        'clientSecret' => 'client_secret',
        'accessToken'  => 'access_token',
        'refreshToken' => 'refresh_token',
        'tokenExpires' => 'token_expires',
        'expiresAt'    => 'expires_at',
        'dataCenter'   => 'data_center',
    );

    $changed = false;
    foreach ( $all_credentials as $platform => &$creds ) {
        if ( ! is_array( $creds ) ) {
            continue;
        }
        foreach ( $creds as &$cred ) {
            if ( ! is_array( $cred ) ) {
                continue;
            }
            foreach ( $map as $camel => $snake ) {
                if ( ! array_key_exists( $camel, $cred ) ) {
                    continue;
                }
                // Snake_case wins on conflict; only copy when snake is missing or empty.
                $snake_set    = array_key_exists( $snake, $cred );
                $snake_filled = $snake_set && $cred[ $snake ] !== '' && $cred[ $snake ] !== null;
                if ( ! $snake_filled ) {
                    $cred[ $snake ] = $cred[ $camel ];
                }
                unset( $cred[ $camel ] );
                $changed = true;
            }
        }
        unset( $cred );
    }
    unset( $creds );

    if ( $changed ) {
        // Raw array + autoload=no — see adfoin_save_credentials() for rationale.
        update_option( 'adfoin_credentials', $all_credentials, false );
    }

    update_option( $version_option, $target_version );
}
add_action( 'plugins_loaded', 'adfoin_run_credential_casing_migration', 20 );

/**
 * One-shot migration: flip the `adfoin_credentials` option to autoload='no'.
 *
 * The option stores every platform's saved API credentials and can grow to
 * tens of KB. It was originally created without an explicit autoload flag, so
 * WordPress loaded the whole blob into memory on every pageload — front-end
 * included — even though credentials are only needed when an integration
 * actually fires. This rewrites just the autoload column; the value is left
 * untouched and decodes identically (maybe_unserialize is idempotent, so the
 * legacy double-serialized rows still read cleanly until their next save).
 *
 * Runs in admin context only, to keep the write off front-end requests.
 *
 * @return void
 */
function adfoin_run_credentials_autoload_migration() {
    if ( (int) get_option( 'adfoin_credentials_autoload_v1', 0 ) >= 1 ) {
        return;
    }

    global $wpdb;

    $wpdb->update(
        $wpdb->options,
        array( 'autoload' => 'no' ),
        array( 'option_name' => 'adfoin_credentials' )
    );

    // Drop the cached alloptions blob so the flag change takes effect at once.
    wp_cache_delete( 'alloptions', 'options' );

    update_option( 'adfoin_credentials_autoload_v1', 1, 'no' );
}
add_action( 'admin_init', 'adfoin_run_credentials_autoload_migration' );

/**
 * One-time migration: fold the legacy per-platform `adfoin_<slug>_credentials`
 * options (written by the old ADFOIN_OAuth_Manager parallel store) into the
 * canonical `adfoin_credentials` option.
 *
 * Additive only — records are merged into adfoin_credentials by `id` and the
 * source options are left in place, so the migration cannot lose data and is
 * safe to re-run. Idempotent via the `adfoin_credentials_consolidation_v1`
 * version stamp.
 *
 * @return void
 */
function adfoin_run_oauth_credentials_consolidation() {
    if ( (int) get_option( 'adfoin_credentials_consolidation_v1', 0 ) >= 1 ) {
        return;
    }

    global $wpdb;

    // Match per-platform credential options: adfoin_<slug>_credentials.
    // esc_like() neutralises the `_` single-character LIKE wildcards.
    $like = $wpdb->esc_like( 'adfoin_' ) . '%' . $wpdb->esc_like( '_credentials' );
    $rows = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE %s",
            $like
        ),
        ARRAY_A
    );

    if ( $rows ) {
        $canonical = (array) maybe_unserialize( get_option( 'adfoin_credentials', array() ) );

        foreach ( $rows as $row ) {
            $name = $row['option_name'];

            // Never treat the canonical option itself as a source.
            if ( 'adfoin_credentials' === $name ) {
                continue;
            }
            if ( ! preg_match( '/^adfoin_(.+)_credentials$/', $name, $m ) ) {
                continue;
            }
            $slug = $m[1];

            $records = maybe_unserialize( $row['option_value'] );
            if ( ! is_array( $records ) || empty( $records ) ) {
                continue;
            }

            $existing = ( isset( $canonical[ $slug ] ) && is_array( $canonical[ $slug ] ) )
                ? $canonical[ $slug ]
                : array();

            // Index existing record ids so the merge never duplicates an account.
            $existing_ids = array();
            foreach ( $existing as $rec ) {
                if ( is_array( $rec ) && isset( $rec['id'] ) ) {
                    $existing_ids[ (string) $rec['id'] ] = true;
                }
            }

            foreach ( $records as $rec ) {
                if ( ! is_array( $rec ) || ! isset( $rec['id'] ) ) {
                    continue;
                }
                $rid = (string) $rec['id'];
                if ( '' === $rid || isset( $existing_ids[ $rid ] ) ) {
                    continue; // already in the canonical store — keep that copy
                }
                $existing[]           = $rec;
                $existing_ids[ $rid ] = true;
            }

            $canonical[ $slug ] = $existing;
        }

        update_option( 'adfoin_credentials', $canonical, false );
    }

    update_option( 'adfoin_credentials_consolidation_v1', 1, 'no' );
}
add_action( 'admin_init', 'adfoin_run_oauth_credentials_consolidation' );