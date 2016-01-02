<?php

/**
 * Description of analytics
 *
 * @author Faizan Ayubi
 */
use Framework\Registry as Registry;
use Framework\RequestMethods as RequestMethods;
use \Curl\Curl;
use ClusterPoint\DB as DB;

class Payment extends Admin {

	public function index() {
		$this->seo(array("title" => "Payment", "view" => $this->getLayoutView()));
        $view = $this->getActionView();
	}
	
	/**
     * @before _secure, _admin, changeLayout
     */
	public function createPackage() {
		$this->seo(array("title" => "Pricing", "view" => $this->getLayoutView()));
        $view = $this->getActionView();
	}


}