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

        $ps = array();
        $packages = Package::all(array("live = ?" => 1));
        foreach ($packages as $p) {
        	$is = array();
        	$items = json_decode($p->item);
        	foreach ($items as $key => $i) {
        		$it = Item::first(array("id = ?" => $i), array("name"));
        		array_push($is, $it->name);
        	}
        	array_push($ps, array(
        		"name" => $p->name,
        		"price" => ($p->price + $p->tax),
        		"item" => implode(",", $is)
        	));
        }
        $view->set("packages", $ps);
	}

}
