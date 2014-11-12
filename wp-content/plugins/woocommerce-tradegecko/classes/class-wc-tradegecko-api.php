<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class WC_TradeGecko_API
 * This class handles the main API functions of sending and recieving API calls.
 *
 * @since 1.0
 */
class WC_TradeGecko_API {

	public $access_token;

	public $refresh_token;

	public $token_url = 'https://api.tradegecko.com/oauth/token/';

	public $api_url = 'https://api.tradegecko.com/';

	public function __construct() {

		$this->client_id	= WC_TradeGecko_Init::get_setting( 'client_id' );
		$this->client_secret	= WC_TradeGecko_Init::get_setting( 'client_secret' );
		$this->redirect_uri	= WC_TradeGecko_Init::get_setting( 'redirect_uri' );
		$this->auth_code	= WC_TradeGecko_Init::get_setting( 'auth_code' );

	}

	/**
	 * Check if we have a valid token <br />
	 * Get a new token, if we don't have a valid one.
	 *
	 * @access public
	 * @since 1.0
	 * @return boolean
	 * @throws Exception
	 */
	public function check_valid_access_token() {

		// We cannot have an access token, if any of the the main authentication parameters are missing from the settings.
		// These settings need to be present at all times
		if ( empty( $this->client_id ) ||
			empty( $this->client_secret ) ||
			empty( $this->redirect_uri ) ||
			empty( $this->auth_code ) )
		{
			throw new Exception( __( 'Cannot obtain TradeGecko API access token.'
				. ' Some or all of the essential API settings are missing.'
				. ' Please check "API Application Id", "API Secret", "Redirect URI" and "Authorization Code".'
				. ' If any of them are missing, please fill them in and re-authenticate the application.',
				WC_TradeGecko_Init::$text_domain ) );
		}

		// Check if we have a valid access token saved in the database
		$this->access_token = get_transient( 'wc_tradegecko_api_access_token' );
		$this->refresh_token = get_option( 'wc_tradegecko_api_refresh_token' );

		if ( false === $this->access_token ) {

			// Add log
			WC_TradeGecko_Init::add_log( 'Access token expired. Obtaining a new access token' );

			if ( empty( $this->refresh_token ) ) {
				$token_data = $this->build_token_request( 'token' );
			} else {
				$token_data = $this->build_token_request( 'refresh_token' );
			}

			$token_data = json_decode( $token_data );

			if ( isset( $token_data->error ) ) {

				// We have an auth error so log it in a option field
				update_option( 'wc_tg_auth_error', 'error' );

				throw new Exception( sprintf( __( 'Access token could not be generated.'
					. ' Error Code: %s.'
					. ' Error Message: %s.', WC_TradeGecko_Init::$text_domain ),
					$token_data->error,
					$token_data->error_description ) );
			}

			// Save the new refresh token
			update_option( 'wc_tradegecko_api_refresh_token', $token_data->refresh_token );
			$this->refresh_token = $token_data->refresh_token;

			// Save the access token to a transient.
			// Set it to expire 60 second earlier, to make sure no request is interupted because of expired token.
			set_transient( 'wc_tradegecko_api_access_token', $token_data->access_token, (int) $token_data->expires_in - 60 );
			$this->access_token = $token_data->access_token;

			return true;
		}

		return true;

	}

	/**
	 * Build an access token request
	 *
	 * @access public
	 * @since 1.0
	 * @param type $request
	 * @return string The json encoded string of the response.
	 */
	private function build_token_request( $request = 'token' ) {

		if ( 'token' == $request ) {
			$body = json_encode( array(
				'client_id'		=> $this->client_id,
				'client_secret'	=> $this->client_secret,
				'redirect_uri'	=> $this->redirect_uri,
				'code'		=> $this->auth_code,
				'grant_type'	=> 'authorization_code'
			));

		} else {
			$body = json_encode( array(
				'client_id'		=> $this->client_id,
				'client_secret'	=> $this->client_secret,
				'redirect_uri'	=> $this->redirect_uri,
				'refresh_token'	=> $this->refresh_token,
				'grant_type'	=> 'refresh_token'
			));
		}

		$url = $this->token_url;
		$params = array(
			'method'	=> 'POST',
			'headers'	=> array( 'Content-Type' => 'application/json' ),
			'body'		=> $body,
			'sslverify'	=> false,
			'timeout' 	=> apply_filters( 'tradegecko_token_request_timeout', 60 ),
			'user-agent'	=> 'WooCommerceTradeGecko/'. WC_TradeGecko_Init::VERSION,
		);

		$response = $this->send( $url, $params );

		// Add log
		WC_TradeGecko_Init::add_log( 'Token Response: '. print_r( $response, true ) );

		// Data we need is in the body of the response
		$data = $response['body'];

		return $data;

	}

	/**
	 * Build an API request
	 *
	 * @access public
	 * @since 1.0
	 * @param string $method Method of request GET, POST, PUT, DELETE.
	 * @param string $request_type The type of request performed exp: orders, products, order_line_items
	 * @param mixed $request_body The request body. Can be associative array or a json string.
	 * @param int|optional $specific_id The ID of a specific item we want to request.
	 * @param array|optional $filters Parameters to filter the request by. Exp: ids, company_id, order_id, purchase_order_id, since etc.
	 * @return string The json encoded string of the response.
	 */
	public function process_api_request( $method, $request_type, $request_body = null, $specific_id = null, $filters = array() ) {

		$url = '';
		if ( $this->check_valid_access_token() ) {

			// Build the request url
			$url .= $this->api_url . $request_type .'/';

			// Add the id is we want to call a specific item
			if ( ! empty( $specific_id ) ) {
				$url .= $specific_id .'/';
			} elseif ( ! empty( $filters ) ) {
				// Add the filter query to the url
				$query = '';
				foreach ( $filters as $key => $value ) {
					// IDs should be a query with an array key
					if ( 'ids' == $key ) {
						$k = 'ids[]=';
						if ( is_array( $value ) ) {
							foreach ( $value as $v ) {
								$query .= $k . urlencode( $v ) .'&';
							}
						} else {
							$query .= $k . urlencode( $value ) .'&';
						}
						continue;
					}

					$query .= $key .'='. urlencode( $value ) .'&';
				}
				$query = trim( $query, '&' );

				$url .= '?'. $query;

			}

			$params = array(
				'method'	=> $method,
				'headers'	=> array( 'Authorization' => 'Bearer '. $this->access_token, 'Content-Type' => 'application/json' ),
				'sslverify'	=> false,
				'timeout'	=> apply_filters( 'tradegecko_api_request_timeout', 60 ),
				'redirection'	=> 0,
				'user-agent'	=> 'WooCommerceTradeGecko/'. WC_TradeGecko_Init::VERSION,
			);

			// Add the body of the request if needed
			if ( ! empty( $request_body ) ) {
				$body = is_array( $request_body ) ? json_encode( $request_body ) : $request_body;
				$params['body'] = $body;
			}

			return $this->send( $url, $params );
		}

	}

	/**
	 * Send and Receive API calls
	 *
	 * @access public
	 * @since 1.0
	 * @param type $url URL to send the request to
	 * @param type $params The parameters of the request.
	 * @return string The json string of the response
	 */
	private function send( $url, $params ) {

		$return = array();

		// Send the request and get the response
		$response = wp_remote_post($url, $params);

		// If Error return the code and message
		if ( is_wp_error($response) ) {
			$return['body']['error'] = $response->get_error_code();
			$return['body']['error_description'] = $response->get_error_message();

			// Json encode the error as the main response is encoded, too.
			$return['body'] = json_encode( $return['body'] );
			return $return;
		}

		// Return code should be 200 < code < 300. If it's not in this range return error.
		if ( 200 > $response['response']['code'] || 300 <= $response['response']['code'] ) {

			$return['body']['error'] = $response['response']['code'];

			$desc = '';
			$body = json_decode( $response['body'] );

			// If we had a json response, we should have its object
			if ( is_object( $body ) ) {
				if ( isset( $body->errors ) ) {

					foreach( $body->errors as $code => $message ) {
						$desc .= $code .': '. $message[0] .', ';
					}

					$desc = substr( $desc, 0, -2 );

					$return['body']['error_description'] = $desc;
				} else {
					$return['body']['error_description'] = isset( $body->message ) ? $body->message : '';
				}

			} else {
				$return['body']['error_description'] = $response['response']['message'];
			}

			$return['body'] = json_encode( $return['body'] );
			return $return;
		}

		// Return the response body
		return $response;

	}

}