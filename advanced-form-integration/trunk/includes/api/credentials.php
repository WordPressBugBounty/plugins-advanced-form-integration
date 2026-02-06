<?php

function adfoin_save_credentials( $platform, $data ) {

    if ( $platform && is_array( $data ) ) {

        $platform = sanitize_text_field( $platform );

        $all_credentials = (array) maybe_unserialize( get_option( 'adfoin_credentials', array() ) );
        $all_credentials[ $platform ] = $data;
    
        update_option( 'adfoin_credentials', maybe_serialize( $all_credentials ) );
    } 
}

function adfoin_read_credentials( $platform ) {
    $all_credentials = ( array ) maybe_unserialize( get_option( 'adfoin_credentials' ), array() );
    $credentials     = array();

    if( isset( $all_credentials[$platform] ) && count( $all_credentials[$platform] ) > 0 ) {
        $credentials = $all_credentials[$platform];
    }

    $credentials = apply_filters( 'adfoin_get_credentials', $credentials, $platform );

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