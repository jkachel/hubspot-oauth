<?php
namespace Jkachel\HubspotOauth\Client;

use Jkachel\HubspotOauth\Client\HttpResponse;

/** @brief Basic cURL-based HTTP client.
 *
 * Options for the constructor:
 * - 'verify_ssl' = verify the SSL certificate, true by default
 * - '
 */

class CurlClient {
    protected $curl;
    protected $response;

    public function __construct($uri, $method = 'GET', $params, $opts) {
        if($method == 'GET') {
            $url .= '?';

            $safeParams = [];

            foreach($params as $param => $data) {
                $safeParams[urlencode($param)] = urlencode($data);
            }

            $url .= join('&', $safeParams);

            $this->curl = curl_init($uri);
        } else {
            $this->curl = curl_init($uri);
        }

        $this->response = new HttpResponse($uri, $method, $params);

        if($method == 'POST') {
            curl_setopt($this->curl, CURLOPT_POST, true);
            curl_setopt($this->curl, CURLOPT_POSTFIELDS, $params);
        }

        if($method == 'PUT') {
            curl_setopt($this->curl, CURLOPT_PUT, true);
        }

        if(is_array($opts) && array_key_exists('verify_ssl', $opts) && $opts['verify_ssl']) {
            curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, true);
        } else {
            curl_setopt($this->curl, CURLOPT_SSL_VERIFYHOST, false);
        }

        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
    }

    public function run() {
        $ret = curl_exec($this->curl);

        $this->response->postprocess($this->curl);
        
        return $ret;
    }
    
    public function response() {
        return $this->response();
    }
}