<?php
	/**
	 * Created by PhpStorm.
	 * User: jkachel
	 * Date: 4/21/16
	 * Time: 4:17 PM
	 */

	namespace HubspotOauth\Exceptions;

	use Exception;

	class RefreshException extends Exception
	{
		protected $responseCode;
		protected $responseText;
		protected $refreshToken;
		protected $clientId;
		protected $accessToken;

		/** @brief
		 *
		 *
		 *
		 **/

		public function __construct($message = null, $responseCode, $responseText, $accessToken, $refreshToken, $clientId, $code = 0, Exception $previous = null) {
			$this->$responseCode = $responseCode;
			$this->$responseText = $responseText;
			$this->$refreshToken = $refreshToken;
			$this->$clientId = $clientId;
			$this->$accessToken = $accessToken;

			parent::__construct($message, $code, $previous);
		}

		public function getResponseCode() {
			return $this->responseCode();
		}

		public function getResponseText() {
			return $this->responseText();
		}

		public function getRefreshToken() {
			return $this->refreshToken();
		}

		public function getClientId() {
			return $this->clientId();
		}

		public function getAccessToken() {
			return $this->accessToken();
		}

		public function __toString() {
			return __CLASS__ . ': Error refreshing token ' . $this->accessToken . ' for client ID ' . $this->clientId . ' with refresh token ' . $this->refreshToken . ': ' .$this->responseText;
		}

	}