<?php 

namespace Test\Functional; 

use JlnMay\NAuth\UserManager; 


class UserManagerTest extends \PHPUnit_Framework_TestCase
{ 
    public function setup()
    {
        $dotenv = Dotenv\Dotenv::create(dirname(dirname(__DIR__)));
        $dotenv->load();
    }
    
    public function testGetNordstromAuthLoginUrl()
    {
        $nordstromAuthLoginUrl = UserManager::getNordstromAuthLoginUrl();
        print_r($nordstromAuthLoginUrl);
        die;    
    }
}