<?php
/**
 * Class Advanced_Form_Integration_OAuth2
 *
 * This class provides an OAuth 2.0 client for Advanced Form Integration WordPress plugin.
 */
class Advanced_Form_Integration_OAuth2 {

    /**
     * The client ID for the OAuth 2.0 client.
     *
     * @var string
     */
    protected $client_id;

    /**
     * The client secret for the OAuth 2.0 client.
     *
     * @var string
     */
    protected $client_secret;

    /**
     * The access token for the OAuth 2.0 client.
     *
     * @var string
     */
    protected $access_token;

    /**
     * The refresh token for the OAuth 2.0 client.
     *
     * @var string
     */
    protected $refresh_token;

     /**
     * The state for the OAuth 2.0 client.
     *
     * @var string
     */
    protected $state;

    /**
     * Data center for the OAuth 2.0 client.
     *
     * @var string
     */
    protected $data_center;

    /**
     * The authorization endpoint for the OAuth 2.0 server.
     *
     * @var string
     */
    protected $authorization_endpoint;

    /**
     * The token endpoint for the OAuth 2.0 server.
     *
     * @var string
     */
    protected $token_endpoint;

    /**
     * The cred_id for the OAuth 2.0 server.
     *
     * @var string
     */
    protected $cred_id;

    /**
     * The refresh token endpoint for the OAuth 2.0 server.
     *
     * @var string
     */
    protected $refresh_token_endpoint = '';

    public function get_title() {

        return '';
    }

    /**
     * Checks if the OAuth 2.0 client is active.
     *
     * @return bool
     */
    public function is_active() {

        return !empty( $this->refresh_token );
    }

    /**
     * Saves the OAuth 2.0 client data to the database.
     *
     * @return void
     */
    protected function save_data() {
    }

    /**
     * Resets the OAuth 2.0 client data.
     *
     * @return void
     */
    protected function reset_data() {
    }

    /**
     * Gets the redirect URI for the OAuth 2.0 client.
     *
     * @return string
     */
    protected function get_redirect_uri() {
        return admin_url();
    }

    /**
     * Authorizes the user to access the OAuth 2.0 server.
     *
     * @param string $scope The scope of the OAuth 2.0 authorization request.
     * @return void
     */
    protected function authorize( string $scope = '' ) {

        $data = array(
            'response_type' => 'code',
            'client_id'     => $this->client_id,
            'redirect_uri'  => urlencode( $this->get_redirect_uri() )
        );

        if( $scope ) {
            $data["scope"] = $scope;
        }

        $endpoint = add_query_arg( $data, $this->authorization_endpoint );

        if ( wp_redirect( esc_url_raw( $endpoint ) ) ) {
            exit();
        }
    }

    protected function get_http_authorization_header( $scheme = 'basic' ) {

        $scheme = strtolower( trim( $scheme ) );

        switch ( $scheme ) {
            case 'bearer':
                return sprintf( 'Bearer %s', $this->access_token );
            case 'basic':
            default:
                return sprintf( 'Basic %s',
                    base64_encode( $this->client_id . ':' . $this->client_secret )
                );
        }
    }

    /**
     * Get the bearer token authorization header.
     *
     * @return string The bearer token authorization header.
     */
    protected function get_bearer_token() {
        return $this->get_http_authorization_header( 'bearer' );
    }

    protected function request_token( $authorization_code ) {

        $params = array(
            'code'         => $authorization_code,
            'redirect_uri' => $this->get_redirect_uri(),
            'grant_type'   => 'authorization_code',
            'client_id'    => $this->client_id,
            'client_secret'=> $this->client_secret
        );

        $response = wp_remote_post( $this->token_endpoint, array(
            'body' => $params
        ) );

        $response_code = (int) wp_remote_retrieve_response_code( $response );
        $response_body = wp_remote_retrieve_body( $response );
        $response_body = json_decode( $response_body, true );

        if ( 401 == $response_code ) { // Unauthorized
            $this->access_token  = null;
            $this->refresh_token = null;
        } else {
            if ( isset( $response_body['access_token'] ) ) {
                $this->access_token = $response_body['access_token'];
            } else {
                $this->access_token = null;
            }

            if ( isset( $response_body['refresh_token'] ) ) {
                $this->refresh_token = $response_body['refresh_token'];
            } else {
                $this->refresh_token = null;
            }
        }

        $this->save_data();

        return $response;
    }

    protected function refresh_token() {

        // Store the old refresh token in case we need to preserve it
        $old_refresh_token = $this->refresh_token;

        $endpoint = add_query_arg(
            array(
                'refresh_token' => $this->refresh_token,
                'grant_type'    => 'refresh_token',
            ),
            $this->refresh_token_endpoint
        );

        $request = [
            'headers' => array(
                'Authorization' => $this->get_http_authorization_header( 'basic' ),
            ),
        ];

        $response      = wp_remote_post( esc_url_raw( $endpoint ), $request );
        $response_code = (int) wp_remote_retrieve_response_code( $response );
        $response_body = wp_remote_retrieve_body( $response );
        $response_body = json_decode( $response_body, true );

        if ( 401 == $response_code ) { // Unauthorized - refresh token is invalid
            // Log the error for debugging
            error_log( sprintf( 
                'OAuth2 refresh failed with 401 for service: %s, endpoint: %s', 
                defined('static::service_name') ? static::service_name : 'unknown',
                $this->refresh_token_endpoint
            ) );
            
            $this->access_token  = null;
            $this->refresh_token = null;
        } else if ( 200 == $response_code ) {
            // Successful refresh
            if ( isset( $response_body['access_token'] ) ) {
                $this->access_token = $response_body['access_token'];
            } else {
                $this->access_token = null;
            }

            // Some OAuth providers use rotating refresh tokens, others reuse the same one
            // If a new refresh token is provided, use it; otherwise keep the old one
            if ( isset( $response_body['refresh_token'] ) ) {
                $this->refresh_token = $response_body['refresh_token'];
            } else {
                // Preserve the old refresh token if new one not provided
                $this->refresh_token = $old_refresh_token;
            }
        } else {
            // Other error codes - log for debugging
            error_log( sprintf(
                'OAuth2 refresh failed with code %d for service: %s, response: %s',
                $response_code,
                defined('static::service_name') ? static::service_name : 'unknown',
                wp_remote_retrieve_body( $response )
            ) );
            
            // Don't clear tokens on non-401 errors, might be temporary
            // Keep existing tokens and let the calling code handle the error
        }

        $this->save_data();

        return $response;
    }

    protected function remote_request( $url, $request = array() ) {

        $request = wp_parse_args( $request, array( 'timeout' => 30 ) );

        $request['headers'] = array_merge(
            $request['headers'],
            array( 'Authorization' => $this->get_http_authorization_header( 'bearer' ), )
            
        );

        $response = wp_remote_request( esc_url_raw( $url ), $request );

        // Check if we need to refresh token (avoid using static variable for concurrent requests)
        if ( 401 === wp_remote_retrieve_response_code( $response ) ) {
            // Check if this is not already a retry by looking at request context
            if ( ! isset( $request['_retry_after_refresh'] ) ) {
                $refresh_response = $this->refresh_token();
                
                // Only retry if refresh was successful
                if ( ! is_wp_error( $refresh_response ) && 200 === wp_remote_retrieve_response_code( $refresh_response ) ) {
                    // Mark this as a retry to prevent infinite loops
                    $request['_retry_after_refresh'] = true;
                    
                    // Update authorization header with new token
                    $request['headers']['Authorization'] = $this->get_http_authorization_header( 'bearer' );
                    
                    $response = wp_remote_request( esc_url_raw( $url ), $request );
                }
            }
        }

        return $response;
    }
}