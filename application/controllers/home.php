<?php

/**
 * The Default Example Controller Class
 *
 * @author Faizan Ayubi
 */
use Shared\Controller as Controller;
use Framework\RequestMethods as RequestMethods;

class Home extends Auth {

    public function index() {
        $this->getLayoutView()->set("seo", Framework\Registry::get("seo"));
    }

    public function test() {
    	$this->_detector();
    }

    public function postReq() {
    	if (RequestMethods::post("key") == "curl_post_request") {
    		$this->log('A post request has been sent to this page');
    	} else {
    		$this->_detector();
    	}
    }

    public function complete() {
    	$this->noview();
    	echo "You have completed all the requests";
    }

}
