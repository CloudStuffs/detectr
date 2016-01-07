<?php
$client_id = '<Client Id>';
$client_secret = '<Client Secret>';
$redirect_uri = 'http://trafficmonitor.ca/webmaster/authenticate';

$client = new Google_Client();
$client->setClientId($client_id);
$client->setClientSecret($client_secret);
$client->setRedirectUri($redirect_uri);
$client->setScopes('https://www.googleapis.com/auth/webmasters');

Framework\Registry::set("gClient", $client);
