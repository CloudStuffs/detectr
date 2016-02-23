<?php

/**
 * Manage Social Stats
 *
 * @author Hemant Mann
 */
use Framework\RequestMethods as RequestMethods;
use Framework\Registry as Registry;
use Framework\ArrayMethods as ArrayMethods;
use Framework\StringMethods as StringMethods;

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
		$this->seo(array("title" => "Social | Manage", "view" => $this->getLayoutView()));
        $view = $this->getActionView();

        $socials = \Keyword::all(array('user_id = ?' => $this->user->id, "serp = ?" => false));
        $view->set("socials", $socials);

        $now = strtotime(date('Y-m-d'));
        $user_registered = strtotime(StringMethods::only_date($this->user->created));
        $datediff = $now - $user_registered;
		$datediff = floor($datediff/(60*60*24));
		if ($datediff < 7) {
			$view->set("message", true);
		}
	}

	/**
	 * @before _secure, memberLayout
	 */
	public function stats($keyword_id) {
		$keyword = \Keyword::first(array("id = ?" => $keyword_id, "serp = ?" => false), array("link", "user_id", "id"));
		$this->_authority($keyword);
		Shared\Service\Social::record($keyword);

		$end_date = RequestMethods::get("enddate", date("Y-m-d"));
		$start_date = RequestMethods::get("startdate", date("Y-m-d", strtotime($end_date."-7 day")));
		$social_media = RequestMethods::get("media", "facebook");

		$this->seo(array("title" => "Serp | Stats","view" => $this->getLayoutView()));
		$view = $this->getActionView();

		$socials = Registry::get("MongoDB")->socials;
		$start_time = strtotime($start_date); $end_time = strtotime($end_date);
		$i = 1; $obj = array();

		$records = $socials->find(array(
			'created' => array('$gte' => new MongoDate($start_time), '$lte' => new MongoDate($end_time)),
			'social_media' => (string) $social_media,
			'keyword_id' => (int) $keyword->id
		));

		foreach ($records as $r) {
			$position = $r['count'];
			$media = array();
			$media['count_type'] = $r['count_type'];
			$media['social_media'] = $r['social_media'];

			$obj[] = array('y' => date('Y-m-d', $r['created']->sec), 'a' => $position);
		}

        $view->set("keyword", $keyword)
			->set("social", array("type" => $media['count_type'], "media" => $media['social_media']))
        	->set("data", ArrayMethods::toObject($obj));
	}

	/**
	 * @before _secure, memberLayout
	 */
	public function changeState($keyword_id, $live) {
		$keyword = Keyword::first(array("id = ?" => $keyword_id));
		$this->_authority($keyword);

		if (!$keyword->serp) {
			$keyword->live = $live;
			$keyword->save();
		}
		self::redirect(RequestMethods::server('HTTP_REFERER', '/member'));
	}

	/**
	 * @return string
	 */
	private function _saveSocial() {
		$regex = Shared\Markup::websiteRegex();
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
