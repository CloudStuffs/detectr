<?php
/**
 * Description of serp
 *
 * @author Hemant Mann
 */
use Framework\RequestMethods as RequestMethods;
use Framework\Registry as Registry;
use Framework\ArrayMethods as ArrayMethods;

class Serp extends Admin {
	/**
	 * Create a serp for a website
	 * @before _secure, memberLayout
	 */
	public function create() {
		$this->seo(array("title" => "Serp | Create","view" => $this->getLayoutView()));
		$view = $this->getActionView();

		$websites = \Website::all(array("user_id = ?" => $this->user->id), array("id", "url"));
		$view->set("websites", $websites);
		if (RequestMethods::post("action") == "createSerp") {
			$website_id = RequestMethods::post("website");
			
			$found = false;
			foreach ($websites as $w) {
				if ($w->id == $website_id) {
					$found = true;
					break;
				}
			}
			if (!$found) {
				$view->set("success", "Invalid request");
				return;
			}
			$q = RequestMethods::post("keyword");
			
			$keyword = \Keyword::first(array("website_id = ?" => $website_id, "keyword" => $q));
			if ($keyword) {
				$view->set("success", "Serp Action already registered for this keyword");
				return;
			}
			$keyword = new \Keyword(array(
				"website_id" => $website->id,
				"user_id" => $this->user->id,
				"keyword" => $q
			));

			if (!$keyword->validate()) {
				$view->set("success", "Error Invalid keyword");
			} else {
				$keyword->save();
				$view->set("success", 'Serp Registered!! See <a href="/serp/manage">Manage</a>');
			}
		}
	}

	/**
	 * Manage all serps created by the member
	 * @before _secure, memberLayout
	 */
	public function manage() {
		$this->seo(array("title" => "Serp | Manage","view" => $this->getLayoutView()));
		$view = $this->getActionView();

		$keywords = \Keyword::all(array("user_id = ?" => $this->user->id));
		$view->set("serps", $keywords);
	}

	public function analytics($keyword_id) {
		$keyword = \Keyword::first(array("id = ?" => $keyword_id), array("user_id", "id"));
		$this->_authority($website);

		$end_date = date("Y-m-d");
		$start_date = date("Y-m-d", strtotime($end_date."-7 day"));

		$diff = date_diff(date_create($start_date), date_create($end_date));
        for ($i = 0; $i < $diff->format("%a"); $i++) {
            $date = date('Y-m-d', strtotime($start_date . " +{$i} day"));
            $count = \Rank::count(array("created LIKE ?" => "%{$date}%", "keyword_id = ?" => $keyword->id));
            $obj[] = array('y' => $date, 'a' => $count);
        }
        $view->set("data", ArrayMethods::toObject($obj));
	}
}
