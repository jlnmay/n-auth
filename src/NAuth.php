<?php

namespace JlnMay\NAuth;

use GuzzleHttp\Client as Guzzle;
use Exception; 

class NAuth 
{
    private $client_id = "";
    private $redirect_uri = "";
    private $secret_id = "";
    protected $baseUrl = "https://9497hir2i5.execute-api.us-west-2.amazonaws.com/";
    protected $loginUri = "v1/loginurl";
    protected $tokenUri = "v1/token";
    protected $userInfoUri = "v1/userinfo";
    protected $logoutUri = "v1/logout";
    protected $introspectUri = "v1/introspect";
    protected $bearerToken;
    private $env = ""; 

    /**
     * Initializes variables
     * @param $string $client_id The ID for the registered application (found in the application's profile in N-Auth Self Service Dashboard or provided by the N-Auth team)
     * @param $string $redirect_uri URL of address the client should be redirected to upon successful login. Needs to be registered with the application. 
     *  (Can be changed in N-Auth Self Service Dashboard or updated by the N-Auth team)
     * @param $string $client_secret Client secret
     */
    public function __construct($client_id, $client_secret, $redirect_uri, $env)
    {
        if (empty($client_id)) {
            throw new Exception("Invalid client_id parameter");
        } else {
            $this->client_id = $client_id;
        }

        if (empty($client_secret)) {
            throw new Exception("Invalid client_secret parameter");
        } else {
            $this->secret_id = $client_secret;
        }

        if (empty($redirect_uri)) {
            throw new Exception("Invalid redirect_uri parameter");
        } else {
            $this->redirect_uri = $redirect_uri; 
        }

        if (empty($env)) {
            throw new Exception("Invalid env parameter");
        } else {
            $this->env = $env; 
        }
    }

    /**
     * @param string $redirect_uri  URL of address the client should be redirected to upon successful login. Needs to be registered with the application. 
     *  (Can be changed in N-Auth Self Service Dashboard or updated by the N-Auth team) Note: do not url encode
     * @param string $env The environment in which the client is hosted. ex. test
     * @param string $claims Defaults to openid if not included in query params - 
     *  Array listing values you would like returned about the user in the /userinfo call. ex. 'openid profile'.  See Current Supported Claims
     * @param bool $is_implicit Set to false when using Authentication Flow
     * @param bool $return_id (optional, bool) - defaults to false if not included - if set to true, an id_token will also be included upon successful authentication
     * @param string $state (optional, string) - if included, state will be returned upon successful authentication to the redirect_uri
     * @param bool $skip_iwa  (optional, bool) - defaults to 'false' - if set to true, once redirected to the login the IWA check (SSO) will be skipped, 
     *  forcing a user to manually enter credentials - see step 8 for more details
     */
    public function login($claims = "openid", $is_implicit = "false", 
        $return_id = false, $state = "", $skip_iwa = false) : string
    {
        $query = [];
        $query["client_id"] = $this->client_id;
        $query["redirect_uri"] = $this->redirect_uri;
        $query["env"] = $this->env; 
        $query["claims"] = $claims;
        $query["is_implicit"] = $is_implicit;

        if ($return_id) {
            $query["return_id"] = $return_id;
        }

        if (!empty($state)) {
            $query["state"] = $state;
        }

        if ($skip_iwa) {
            $query["skip_iwa"] = $skip_iwa;
        }

        $client = new Guzzle(["base_uri" => $this->baseUrl]);
        $response = $client->request("GET", $this->loginUri, [
            "query" => $query
        ]);

        return (string) $response->getBody();
    }

    /**
     * Gets the token
     * @param string $code Code received by redirect_uri as a query param
     * @param string $env The environment in which the client is hosted. ex. test
     * @return The token
     */
    public function token($code)
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->baseUrl . $this->tokenUri,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode(array("code" => $code, "redirect_uri" => $this->redirect_uri, "env" => $this->env)),
            CURLOPT_HTTPHEADER => array(
              "Authorization: Basic " . $this->getAuthorizationHeader(),
              "Cache-Control: no-cache",
              "Content-Type: application/json"
            ),
        ));
        
        $response = curl_exec($curl);
        $error = curl_error($curl);
        
        if ($error) {
            return $error;
        } 

        return $response;
    }

    /**
     * Gets authorization header 
     * @return Authorization header base64 encoded
     */
    private function getAuthorizationHeader()
    {
        return base64_encode($this->client_id.":".$this->secret_id);
    }

    /**
     * Gets the user Info
     * @param string $env The environment in which the client is hosted. ex. test
     * @param string $client_id client_id is optional for this call. It is only required if you wish to use the 
     *  "User Memberships" portion of the service.
     * @return User info
     */
    public function getUserInfo($token)
    {
        if (!empty($token)) {
            $this->bearerToken = $token; 
        }
        
        $queryParameters = array();
        $queryParameters["env"] = $this->env; 
        $queryParameters["client_id"] = $this->client_id; 
        
        $client = new Guzzle(["base_uri" => $this->baseUrl]);
        $response = $client->request("GET", $this->userInfoUri, [
            'headers' => array(
            'Authorization' => ['Bearer ' . $this->getBearerToken()]),
            'query' => $queryParameters
        ]);

        return (string) $response->getBody();
    }

    /**
     * Gets the bearer token
     */
    protected function getBearerToken()
    {
        return $this->bearerToken;
    }

    /**
     * Log outs from NAuth
     * @param string $env The environment in which the client is hosted. ex. test
     */
    public function logout()
    {
        $client = new Guzzle(["base_uri" => $this->baseUrl]);
        $response = $client->request("POST", $this->logoutUri, [
            'Authorization' => ['Basic ' . $this->getAuthorizationHeader()],
            'Body' => array(
                "token" => $this->getBearerToken, 
                "env" => $this->env
            )
        ]);

        return (string) $response->getBody();
    }

    /**
     * Checksthe validity of a token. Can be an access token or id token
     * @param string $env The environment in which the client is hosted. ex. test
     * @param string $token The access_token to or id_token to be checked
     */
    public function introspect($token)
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->baseUrl . $this->introspectUri,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode(array("token" => $token, "env" => $this->env)),
            CURLOPT_HTTPHEADER => array(
              "Authorization: Basic " . $this->getAuthorizationHeader(),
              "Cache-Control: no-cache",
              "Content-Type: application/json"
            ),
        ));
        
        $response = curl_exec($curl);
        $error = curl_error($curl);
        
        if ($error) {
            return $error;
        } 

        return $response; 
    }
}