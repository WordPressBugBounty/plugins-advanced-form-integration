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
        update_option( 'adfoin_credentials', maybe_serialize( $all_credentials ) );
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
        update_option( 'adfoin_credentials', maybe_serialize( $all_credentials ) );
    }
}

function adfoin_read_credentials( $platform ) {
    $all_credentials = ( array ) maybe_unserialize( get_option( 'adfoin_credentials', array() ) );
    $credentials     = array();

    if( isset( $all_credentials[$platform] ) && count( $all_credentials[$platform] ) > 0 ) {
        $credentials = $all_credentials[$platform];
    }

    $credentials = apply_filters( 'adfoin_get_credentials', $credentials, $platform );

    // Normalize legacy camelCase credential keys to canonical snake_case.
    //
    // DEPRECATED — kept as a safety net for installs that haven't yet run
    // `adfoin_run_credential_casing_migration` (which rewrites stored records
    // to snake_case once on plugin upgrade). Safe to remove once a release
    // or two have shipped past 1.131.0 and migration has run on most sites.
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
                if ( isset( $cred[ $camel ] ) && ! isset( $cred[ $snake ] ) ) {
                    $cred[ $snake ] = $cred[ $camel ];
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
        update_option( 'adfoin_credentials', maybe_serialize( $all_credentials ) );
    }

    update_option( $version_option, $target_version );
}
add_action( 'plugins_loaded', 'adfoin_run_credential_casing_migration', 20 );