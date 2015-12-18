<?php

// initialize seo
include("seo.php");

$seo = new SEO(array(
    "title" => "Traffic Monitor",
    "keywords" => "Free Website monitor and Trigger any action for a user",
    "description" => "Welcome to Our Traffic Monitoring Trigger Network",
    "author" => "CloudStuff.Tech",
    "robots" => "INDEX,FOLLOW",
    "photo" => CDN . "images/logo.png"
));

Framework\Registry::set("seo", $seo);
