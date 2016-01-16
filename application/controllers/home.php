<?php

/**
 * The Default Home Controller
 *
 * @author Faizan Ayubi
 */
use Shared\Controller as Controller;

class Home extends Controller {

    public function index() {
        $this->getLayoutView()->set("seo", Framework\Registry::get("seo"));
    }

    public function packages() {
		$this->seo(array("title" => "Packages", "view" => $this->getLayoutView()));
        $view = $this->getActionView();

        $packages = Package::all(array("live = ?" => 1));
        $view->set("packages", $packages);
	}

}
