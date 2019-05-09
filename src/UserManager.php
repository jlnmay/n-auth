<?php

namespace JlnMay\NAuth; 

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

use JlnMay\NAuth\NAuth; 

/**
 * This class is used in order to manage the user session in an application that uses NAuth library, 
 */
class UserManager 
{
    /**
     * Gets nordstrom auth login url.
     * @return string Url
     */
    public static function getNordstromAuthLoginUrl() : string 
    {
        $nauth = new NAuth(getenv("NA_CLIENT_ID"), getenv("NA_CLIENT_SECRET"), 
        getenv("NA_REDIRECT_URI"), getenv("NA_ENV"));
    
        $result = $nauth->login(getenv("NA_CLAIMS"));
        $result = json_decode($result);
        $nordstromNAuthLoginUrl = "";

        if (isset($result->status) && $result->status == 200) {
            $nordstromAuthLoginUrl = $result->data; 
        }

        return $nordstromNAuthLoginUrl;
    }
}