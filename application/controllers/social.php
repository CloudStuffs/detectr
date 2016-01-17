<?php

/**
 * Manage Social Stats
 *
 * @author Hemant Mann
 */
use Framework\RequestMethods as RequestMethods;
use Framework\Registry as Registry;
use Framework\ArrayMethods as ArrayMethods;

class Social extends Serp {
	/**
	 * @before _secure, memberLayout
	 */
	public function create() {
		$this->seo(array("title" => "Social | Create", "view" => $this->getLayoutView()));
        $view = $this->getActionView();

        if (RequestMethods::post("action") == "socialTracker") {
        	$message = $this->_saveSocial();
        }

        $view->set("message", $message);
	}

	/**
	 * @before _secure, memberLayout
	 */
	public function manage() {
		$this->seo(array("title" => "Social | Create", "view" => $this->getLayoutView()));
        $view = $this->getActionView();

        $socials = \Keyword::all(array('user_id = ?' => $this->user->id, "serp = ?" => false));
        $view->set("socials", $socials);
	}

	/**
	 * @before _secure, memberLayout
	 */
	public function stats($keyword_id) {
		$keyword = \Keyword::first(array("id = ?" => $keyword_id, "serp = ?" => false), array("user_id", "id"));
		$this->_authority($keyword);

		$end_date = RequestMethods::get("enddate", date("Y-m-d"));
		$start_date = RequestMethods::get("startdate", date("Y-m-d", strtotime($end_date."-7 day")));

		$this->seo(array("title" => "Serp | Stats","view" => $this->getLayoutView()));
		$view = $this->getActionView();

		$socials = Registry::get("MongoDB")->socials;

		$start_time = strtotime($start_date); $end_time = strtotime($end_date); $i = 1;

        while ($start_time < $end_time) {
        	$start_time = strtotime($start_date . " +{$i} day");
            $date = date('Y-m-d', $start_time);

            $record = $socials->findOne(array('created' => $date, 'keyword_id' => $keyword->id));
            if (isset($record)) {
            	$position = $record['position'];
            } else {
            	$position = 0;
            }
            $obj[] = array('y' => $date, 'a' => $position);

            ++$i;
        }

        $view->set("k_id", $keyword->id);
        $view->set("keyword", $keyword);
        $view->set("data", ArrayMethods::toObject($obj));
	}

	/**
	 * @before _secure, memberLayout
	 */
	public function disable($keyword_id) {
		$keyword = Keyword::first(array("id = ?" => $keyword_id));
		$this->_authority($keyword);

		if (!$keyword->serp) {
			$keyword->live = false;
			$keyword->save();
		}
		self::redirect($_SERVER['HTTP_REFERER']);
	}

	/**
	 * @return string
	 */
	private function _saveSocial() {
		$regex = $this->_websiteRegex();
		$link = RequestMethods::post("link");

		if (!preg_match("/^$regex$/", $link)) {
			return "Invalid URL";
		}
		
		$tracker = Keyword::first(array("link = ?" => $link, "user_id = ?" => $this->user->id, "serp = ?" => false));
		if ($tracker) {
			return "Already added";
		}

		$tracker = new Keyword(array(
			"keyword" => "social",
			"link" => $link,
			"user_id" => $this->user->id,
			"serp" => false
		));
		$tracker->save();
		return "Social Tracker Added";
	}

}