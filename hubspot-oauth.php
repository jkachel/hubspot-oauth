<?php
namespace Jkachel\HubspotOauth;

/** @brief HubSpot API wrapper that supports OAuth
 * @author James Kachel <james@jkachel.com>
 * @version 0.1
 *
 * This is the main driver code for the API wrapper, and is the one bit you
 * need to include to get this working.
 *
 */

use OAuth\Common\Storage\Session;
use OAuth\Common\Consumer\Credentials;
use OAuth\Common\Http\Uri\UriFactory;
use Jkachel\HubspotOauth\Config\Config;
use Jkachel\HubspotOauth\Service\HubSpot;

class HubspotOauth {
    protected $settings;
    protected $session;
    protected $creds;
    protected $service;

    public static function factory() {
        global $HubSpot;

        if(!is_object($HubSpot)) {
            $HubSpot = new HubspotOauth();
        }

        return $HubSpot;
    }

    public function __construct() {
        $this->settings = new Config();
        $this->session = new Session();

        $endpoint = (new UriFactory())->createFromAbsolute($this->settings->endpoint);

        $this->creds = new Credentials(
            
        )


    }
}