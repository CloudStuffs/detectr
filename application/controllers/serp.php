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
	 * @before _secure, memberLayout
	 */
	public function create($website_id) {
		$website = \Website::first(array("id = ?" => $website_id), array("user_id", "id", "url"));
		$this->_authority($website);

		$this->seo(array("title" => "Serp | Create","view" => $this->getLayoutView()));
		$view = $this->getActionView();

		$view->set("website", $website);
		if (RequestMethods::post("action") == "createSerp") {
			$q = RequestMethods::post("keyword");
			
			$keyword = \Keyword::first(array("website_id = ?" => $website->id, "keyword" => $q));
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
	 * @before _secure, memberLayout
	 */
	public function manage($website_id) {
		$website = \Website::first(array("id = ?" => $website_id), array("user_id", "id", "url"));
		$this->_authority($website);

		$this->seo(array("title" => "Serp | Manage","view" => $this->getLayoutView()));
		$view = $this->getActionView();

		$keywords = \Keyword::all(array("website_id" => $website_id));
		$view->set("keywords", $keywords);
	}

	public function analytics($keyword_id) {
		$keyword = \Keyword::first(array("id = ?" => $keyword_id), array("user_id", "id"));
		$this->_authority($website);

		$end_date = date("Y-m-d");
		$date_allowed = date("Y-m-d", strtotime($end_date."-7 day"));

		$diff = date_diff(date_create($start_date), date_create($end_date));
        for ($i = 0; $i < $diff->format("%a"); $i++) {
            $date = date('Y-m-d', strtotime($start_date . " +{$i} day"));
            $count = \Rank::count(array("created LIKE ?" => "%{$date}%", "keyword_id = ?" => $keyword->id));
            $obj[] = array('y' => $date, 'a' => $count);
        }
        $view->set("data", ArrayMethods::toObject($obj));
	}
}
