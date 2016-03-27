<?php
namespace Jkachel\HubspotOauth\Client;

class HttpResponse {
    protected $curlopts;
    protected $request_uri;
    protected $request_params;
    protected $request_method;

    public function __construct($uri, $params, $method) {
        $this->request_uri = $uri;
        $this->request_params = $params;
        $this->request_method = $method;
    }

    public function postprocess($curl) {
        $this->curlopts = curl_getinfo($curl);
    }

    public function __set($key, $value) {
        return false;
    }

    public function __get($key) {
        if($key == 'uri') {
            return $this->request_uri;
        }

        if($key == 'params') {
            return $this->request_params;
        }

        if($key == 'method') {
            return $this->request_method;
        }

        if(array_key_exists($key, $this->curlopts)) {
            return $this->curlopts[$key];
        }

        return false;
    }
}