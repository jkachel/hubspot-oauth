<?php
	/**
	 * Created by PhpStorm.
	 * User: jkachel
	 * Date: 4/21/16
	 * Time: 12:40 PM
	 */

	namespace HubspotOauth\Authenticate;

	use GuzzleHttp\Client;
	use GuzzleHttp\Exception\RequestException;
	use GuzzleHttp\Exception\ConnectException;
	use GuzzleHttp\Exception\ClientException;
	use GuzzleHttp\Exception\ServerException;
	use GuzzleHttp\Exception\TooManyRedirectsException;
	use Carbon\Carbon;
	use HubspotOauth\Exceptions\RefreshException;

	class Authenticate
	{
		protected $access_token = false;
		protected $expiry_time = 0;
		protected $expiry_timestamp = false;
		protected $refresh_token = false;
		protected $refresh_count = false;

		protected $clientid = false;
		protected $hubid = false;
		protected $endpoint = false;
		protected $scopes = false;

		protected $client = false;

		/** @brief Sets the object up.
		 *
		 * Parameters:
		 * $hubid				The HubSpot hub ID (all numbers) that you are requesting access for
		 * $clientid			The application client ID (looks like a UUID) for your application
		 * $access_token		A stored access token. (Optional)
		 * $refresh_token		A stored refresh token. (Optional)
		 * $expiry_timestamp 	A stored expiry timestamp. (Optional)
		 *
		 * Note that you must provide at least an access token and a timestamp
		 * if you wish to use them. The expiry timestamp should be a DateTime
		 * at least (it will be converted to Carbon internally). Ideally, you
		 * should also provide a refresh token.
		 **/

		public function __construct($hubid, $clientid, $access_token = false, $refresh_token = false, $expiry_timestamp = false) {
			$this->hubid = $hubid;
			$this->clientid = $clientid;

			$this->client = new Client([ 'base_uri' => 'https://api.hubapi.com/' ]);

			if($access_token != false && is_object($expiry_timestamp)) {
				$this->access_token = $access_token;
				$this->refresh_token = $refresh_token;

				if(get_class($expiry_timestamp) == 'DateTime') {
					$expiry_timestamp = Carbon::instance($expiry_timestamp);
				}

				$this->expiry_timestamp = $expiry_timestamp;
				$this->expiry_time = Carbon::now()->diffInSeconds($expiry_timestamp);
			}
		}

		/** @brief Creates the URL to redirect the user to for login.
		 *
		 * Parameters:
		 * $endpoint	The endpoint URL that the client should be redirected to once they attempt to log in
		 * $scopes		The scopes you are requesting access for. If passed as an array, this will be converted using buildScopes.
		 *
		 * Returns string
		 **/

		public function initiate($endpoint, $scopes) {
			if(is_array($scopes)) {
				$scopes = Authenticate::buildScopes($scopes);
			}

			$this->endpoint = $endpoint;
			$this->scopes = $scopes;

			$url = 'https://app.hubspot.com/auth/authenticate?client_id=' . $this->clientid . '&portalId=' . $this->hubid . '&redirect_uri=' . $endpoint;

			if(is_string($this->scopes) && strlen($this->scopes) > 0) {
				$url .= '&scope=' . $this->scopes;
			}

			return $url;
		}

		/** @brief Processes the redirect from HubSpot
		 *
		 * Parameters:
		 * $request		Either the Laravel Request for the request or the GET variables from the request as an array.
		 *
		 * Returns true on success, or throws an Exception on failure
		 **/

		public function process($request) {
			if(is_object($request) && get_class($request) == 'Illuminate\Http\Request') {
				$data = $request->input('access_token', 'expires_in', 'refresh_token', 'error', 'scope');
			} else {
				$data = $request;
			}

			if(array_key_exists('error', $data)) {
				throw new \Exception('Could not process the redirect: ' . $data['error']);
			}

			$this->access_token = $data['access_token'];
			$this->refresh_token = $data['refresh_token'];

			if(array_key_exists('scope', $data) && strlen($data['scope']) > 0) {
				$this->scopes = $request->input('scope');
			}

			$this->expiry_time = $data['expires_in'];
			$this->expiry_timestamp = Carbon::now()->addSeconds($this->expiry_time);

			return true;
		}

		/** @brief Refreshes the OAuth access token.
		 *
		 * This will not work if the offline scope wasn't requested or if the token has expired. You will
		 * get a regular exception in these cases. If there is a problem after the refresh call has been
		 * made, this will throw a RefreshException.
		 *
		 * Returns true on success or throws an exception on failure.
		 **/

		public function reauth() {
			if(!stristr('offline', $this->scopes) || strlen($this->refresh_token) == 0) {
				throw new \Exception('Cannot refresh; offline scope was not requested or no valid refresh token.');
			}

			if(Carbon::now() > $this->expiry_timestamp) {
				throw new \Exception('Token has expired; cannot refresh.');
			}

			try {
				$resp = $this->client->request('POST', '/auth/v1/refresh', ['form_params' => ['refresh_token' => $this->refresh_token, 'client_id' => $this->clientid, 'grant_type' => 'refresh_token']]);

				$data = json_decode($resp->getBody()->getContents(), true);

				$this->access_token = $data['access_token'];
				$this->refresh_token = $data['refresh_token'];
				$this->hubid = $data['portal_id'];
				$this->expiry_time = $data['expires_in'];
				$this->expiry_timestamp = Carbon::now()->addSeconds($this->expiry_time);
				$this->refresh_count++;

				return true;
			} catch(RequestException $e) {
				if($e->getResponse()->getStatusCode() == 401) {
					throw new RefreshException('Unauthorized request', 'Unauthorized request', $e->getResponse()->getStatusCode(), $this->access_token, $this->refresh_token, $this->clientid, $e->getCode(), $e);
				} elseif($e->getResponse()->getStatusCode() == 410) {
					throw new RefreshException('Inactive portal requested', 'Inactive portal requested', $e->getResponse()->getStatusCode(), $this->access_token, $this->refresh_token, $this->clientid, $e->getCode(), $e);
				} elseif($e->getResponse()->getStatusCode() == 500) {
					throw new RefreshException('HubSpot internal API error', 'HubSpot internal API error', $e->getResponse()->getStatusCode(), $this->access_token, $this->refresh_token, $this->clientid, $e->getCode(), $e);
				} else {
					throw new RefreshException('Unknown Error', $e->getResponse()->getReasonPhrase(), $e->getResponse()->getStatusCode(), $this->access_token, $this->refresh_token, $this->clientid, $e->getCode(), $e);
				}
			}

		}

		/** @brief Performs a request against the HubSpot API.
		 *
		 * Parameters:
		 * $method		What HTTP method to use (get, post) - this will be uppercased
		 * $endpoint	The API endpoint to use
		 * $data		Any data that needs to be sent
		 *
		 * The data element should have three keys, each containing the data to pass to the API endpoint. The
		 * keys should be:
		 * - query		Items that belong in the query string (attached to the URI)
		 * - form		Items that should be submitted like a form
		 * - json		A JSON payload. If this is not a string, it will be json_encode'd.
		 * - body		A payload to insert into the document body.
		 *
		 * Note that parameters that are part of the URI itself will not be resolved by this function.
		 * For instance, the Get Contact by Email endpoint requires the email address in question to be
		 * in the URI (/contacts/v1/contact/email/test@kindergartencop.edu/profile); as such you should make sure
		 * that the email address is in the endpoint rather than in the data variable.
		 *
		 * Do not supply an API key or OAuth token, this code will take care of that on its own.
		 *
		 * You cannot mix form, json and body; only one of each is allowed.
		 *
		 * Returns an array of the data retrieved or throws an exception on error.
		 * Data is returned in a hash with these keys:
		 * - result		Either success or error
		 * - response	JSON-decoded response from HubSpot (otherwise unmodified)
		 * - type		Error only: type of error. 'api' for a problem with the request, 'tcp' for a connection issue, or 'remote' for a server-side issue
		 * - message	Error only: the raw response from the server
		 * 'result' is the only guaranteed returnable value.
		 **/

		public function call($method, $endpoint, $data = []) {
			if($this->expiry_timestamp->diffInSeconds(Carbon::now(), true) < 30) {
				// about 30 seconds until timeout, try to refresh now
				// not going to try to handle errors here: if the API timed out, the underlying app needs to deal with that
				$this->reauth();
			} elseif($this->expiry_timestamp->diffInSeconds(Carbon::now(), true) < 1) {
				throw new \Exception('API access timed out.');
			}

			if(!in_array(strtolower($method), [ 'get', 'post', 'put', 'patch', 'delete' ])) {
				throw new \Exception('Invalid method "'.$method.'".');
			}

			$myQuery = [
				'access_token' => $this->access_token
			];

			$myForm = [];
			$myJson = '';

			$myOpts = [
				'query' => $myQuery
			];

			if(array_key_exists('query', $data)) {
				foreach($data['query'] as $key => $value) {
					if($key != 'access_token' && $key != 'hapikey') {
						$myQuery[$key] = $value;
					}
				}

				$myOpts['query'] = $myQuery;
			}

			if(array_key_exists('form', $data)) {
				foreach($data['form'] as $key => $value) {
					if($key != 'access_token' && $key != 'hapikey') {
						$myForm[$key] = $value;
					}
				}

				$myOpts['form_params'] = $myForm;
			}

			if(array_key_exists('json', $data)) {
				$myOpts['json'] = is_string($data['json']) ? $data['json'] : json_encode($data['json']);
			}

			if(array_key_exists('body', $data)) {
				$myOpts['body'] = $data['body'];
			}

			if(array_key_exists('form_params', $myOpts) + array_key_exists('json', $myOpts) + array_key_exists('body', $myOpts) > 1) {
				throw new \Exception('Invalid option set for the request.');
			}

			try {
				$resp = $this->client->request(strtoupper($method), $endpoint, $myOpts);

				$code = $resp->getStatusCode();

				if($code < 300) {
					$retVal = ['result' => 'success', 'code' => $code, 'response' => json_decode($resp->getBody()->getContents(), true)];
				} else {
					if($code >= 500) {
						$retVal = [ 'result' => 'error', 'type' => 'remote', 'message' => $resp->getBody()->getContents() ];
					} elseif($code >= 400 && $code < 500) {
						$retVal = [ 'result' => 'error', 'type' => 'api', 'message' => $resp->getBody()->getContents() ];
					} else {
						// at this point we've gotten back something in the 300 range, which are all redirects
						// theoretically should never get here but stranger things have happened
						$retVal = [ 'result' => 'error', 'type' => 'redirect', 'message' => $resp->getBody()->getContents() ];
					}
				}
			} catch(ConnectException $e) {
				// TCP error occurred
				$retVal = [ 'result' => 'error', 'type' => 'tcp', 'message' => 'Connection error.' . ($e->hasResponse() ? ' '.$e->getResponse()->getBody()->getContents() : '' )];
			} catch(ClientException $e) {
				// 400 errors; technically should be handled in the try block but just in case someone screws with the Guzzle client
				$retVal = [ 'result' => 'error', 'type' => 'api', 'message' => $e->getResponse()->getBody()->getContents() ];
			} catch(ServerException $e) {
				$retVal = [ 'result' => 'error', 'type' => 'remote', 'message' => $e->getResponse()->getBody()->getContents() ];
			} catch(TooManyRedirectsException $e) {
				// Too many redirects occurred. Treating this like a TCP issue
				$retVal = [ 'result' => 'error', 'type' => 'tcp', 'message' => 'Too many redirects.' . ($e->hasResponse() ? ' '.$e->getResponse()->getBody()->getContents() : '' )];
			} catch(RequestException $e) {
				// TCP error occurred
				$retVal = [ 'result' => 'error', 'type' => 'tcp', 'message' => 'Connection error.' . ($e->hasResponse() ? ' '.$e->getResponse()->getBody()->getContents() : '' )];
			}

			return $retVal;
		}

		/** @brief Generates a properly formatted scope string.
		 *
		 * This will additionally check to make sure the scopes you're requesting
		 * are valid HubSpot scopes. If the scope is not a valid scope, it will
		 * be skipped.
		 *
		 * Parameters:
		 * $scopes 		The scopes you wish to work with. Specify this as many times as necessary.
		 *
		 * Returns string
		 **/

		public static function buildScopes(...$scopes) {
			$retVal = '';

			foreach($scopes as $scope) {
				if(in_array($scope, [ 'offline', 'contacts-rw', 'contacts-ro', 'blog-rw', 'blog-ro', 'events-rw', 'keywords-rw' ])) {
					$retVal .= $scope . '+';
				}
			}

			return trim($retVal, '+');
		}
	}