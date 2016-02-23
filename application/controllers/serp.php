<?php
/**
 * Description of serp
 *
 * @author Hemant Mann
 */
use Framework\RequestMethods as RequestMethods;
use Framework\Registry as Registry;
use Framework\ArrayMethods as ArrayMethods;
use Framework\StringMethods as StringMethods;

class Serp extends Admin {
	/**
	 * Create a serp for a website
	 * @before _secure, memberLayout
	 */
	public function create() {
		$this->seo(array("title" => "Serp | Create","view" => $this->getLayoutView()));
		$view = $this->getActionView();

		$message = null; $errors = array();
		if (RequestMethods::post("action") == "createSerp") {
			$message = $this->_saveSerp();
			if (is_array($message)) {
				$errors = $message;
				$message = null;
			}
		}
		$view->set("message", $message)
			->set("errors", $errors);
	}

	/**
	 * Manage all serps created by the member
	 * @before _secure, memberLayout
	 */
	public function manage() {
		$this->seo(array("title" => "Serp | Manage","view" => $this->getLayoutView()));
		$view = $this->getActionView();

		$keywords = \Keyword::all(array("user_id = ?" => $this->user->id, "serp" => true));
		$view->set("serps", $keywords);

		$now = strtotime(date('Y-m-d'));
        $user_registered = strtotime(StringMethods::only_date($this->user->created));
        $datediff = $now - $user_registered;
		$datediff = floor($datediff/(60*60*24));
		if ($datediff < 7) {
			$view->set("message", true);
		}
	}

	/**
	 * See stats of a keyword
	 * @before _secure, memberLayout
	 */
	public function stats($keyword_id) {
		$keyword = \Keyword::first(array("id = ?" => $keyword_id, "serp = ?" => true));
		$this->_authority($keyword);
		if ($keyword->live) {
			Shared\Service\Serp::record(array($keyword));	
		}
		$end_date = RequestMethods::get("enddate", date("Y-m-d"));
		$start_date = RequestMethods::get("startdate", date("Y-m-d", strtotime($end_date."-7 day")));

		$this->seo(array("title" => "Serp | Stats","view" => $this->getLayoutView()));
		$view = $this->getActionView();

		$rank = Registry::get("MongoDB")->rank;
		
		$start_time = strtotime($start_date); $end_time = strtotime($end_date); $i = 0;

		$obj = array();
        while ($start_time < $end_time) {
        	$start_time = strtotime($start_date . " +{$i} day");
            $date = date('Y-m-d', $start_time);
            $record = $rank->findOne(array('created' => $date, 'keyword_id' => (int) $keyword->id));
            if (isset($record)) {
            	$position = $record['position'];
            } else {
            	$position = 0;
            }
            $obj[] = array('y' => $date, 'a' => $position);

            ++$i;
        }
        $view->set("keyword", $keyword)
        	->set("label", "Rank")
        	->set("data", ArrayMethods::toObject($obj));
	}

	/**
	 * @before _secure, memberLayout
	 */
	public function changeState($keyword_id, $live) {
		$keyword = Keyword::first(array("id = ?" => $keyword_id));
		$this->_authority($keyword);

		if ($keyword->serp) {
			$keyword->live = $live;
			$keyword->save();
		}
		self::redirect($_SERVER['HTTP_REFERER']);
	}

	/**
	 * @return string|array Array on DB validation errors, else string messages
	 */
	private function _saveSerp($keyword, $link) {
		$keyword = RequestMethods::post("keyword");
		$link = RequestMethods::post("link");
		$regex = Shared\Markup::websiteRegex();
		if (!preg_match("/^$regex$/", $link)) {
			return "Invalid URL";
		}

		$serp = Keyword::first(array("link = ?" => $link, "user_id = ?" => $this->user->id, "keyword = ?" => $keyword, "serp = ?" => true));
		if ($serp) {
			return "SERP Already Registered";
		}

		$serp = new Keyword(array(
			"link" => $link,
			"user_id" => $this->user->id,
			"keyword" => $keyword,
			"serp" => true
		));
		if ($serp->validate()) {
			$serp->save();
			return "Serp Action saved succesfully!!";
		} else {
			$errors = $keyword->errors;
			return $errors;
		}
	}
}
