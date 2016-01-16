<?php
$apiContext = new \PayPal\Rest\ApiContext(
    new \PayPal\Auth\OAuthTokenCredential(
        '<ClientID>',     // ClientID
        '<ClientSecret>'      // ClientSecret
    )
);

Framework\Registry::set("PayPalApiContext", $apiContext);
