<?php 

require __DIR__ . '/vendor/autoload.php';

$nAuth = new TekConnect\NAuth\NAuth("0oaigq29kx0T2ks5s0h7", "vgtMcGrcWtjkH0Ob0_6UvxRgBrl1O0gL6f8aa3ti");
$result = $nAuth->login("https://uat-mdad.nordstrom.net", "test");

/*
$nAuthTokenUrl = "https://9497hir2i5.execute-api.us-west-2.amazonaws.com/v1/token";
$nAuth->tokenUri = $nAuthTokenUrl;
$result = $nAuth->token();
*/

echo $result;