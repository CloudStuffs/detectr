<?php

/**
 * The Default Example Controller Class
 *
 * @author Faizan Ayubi
 */
use Framework\Controller as Controller;

class Home extends Controller {

    public function index() {
        $this->getLayoutView()->set("seo", Framework\Registry::get("seo"));
    }

    public function pricing() {
		$this->seo(array("title" => "Pricing", "view" => $this->getLayoutView()));
        $view = $this->getActionView();
	}

}
