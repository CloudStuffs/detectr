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
	}

	/**
	 * See stats of a keyword
	 * @before _secure, memberLayout
	 */
	public function stats($keyword_id) {
		$keyword = \Keyword::first(array("id = ?" => $keyword_id, "serp = ?" => true), array("user_id", "id"));
		$this->_authority($keyword);

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
            $record = $rank->findOne(array('created' => $date, 'keyword_id' => $keyword->id));
            if (isset($record)) {
            	$position = $record['position'];
            } else {
            	$position = 0;
            }
            $obj[] = array('y' => $date, 'a' => $position);

            ++$i;
        }
        $view->set("k_id", $keyword->id);
        $view->set("data", ArrayMethods::toObject($obj));
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
	 * Getter
	 */
	protected function _websiteRegex() {
		$regex = "((https?|ftp)\:\/\/)?"; // SCHEME
	    $regex .= "([a-z0-9+!*(),;?&=\$_.-]+(\:[a-z0-9+!*(),;?&=\$_.-]+)?@)?"; // User and Pass
	    $regex .= "([a-z0-9-.]*)\.([a-z]{2,4})"; // Host or IP
	    $regex .= "(\:[0-9]{2,5})?"; // Port
	    $regex .= "(\/([a-z0-9+\$_-]\.?)+)*\/?"; // Path
	    $regex .= "(\?[a-z+&\$_.-][a-z0-9;:@&%=+\/\$_.-]*)?"; // GET Query
	    $regex .= "(#[a-z_.-][a-z0-9+\$_.-]*)?"; // Anchor

	    return $regex;
	}

	/**
	 * @return string|array Array on DB validation errors, else string messages
	 */
	private function _saveSerp($keyword, $link) {
		$keyword = RequestMethods::post("keyword");
		$link = RequestMethods::post("link");

		$regex = $this->_websiteRegex();
		if (!preg_match("/^$regex$/", $link)) {
			return "Invalid URL";
		}

		$keyword = Keyword::first(array("link = ?" => $link, "user_id = ?" => $this->user->id, "keyword = ?" => $keyword, "serp = ?" => true));
		if ($keyword) {
			return "SERP Already Registered";
		}

		$keyword = new Keyword(array(
			"link" => $link,
			"user_id" => $this->user->id,
			"keyword" => $keyword,
			"serp" => true
		));
		if ($keyword->validate()) {
			$keyword->save();
			return "Serp Action saved succesfully!!";
		} else {
			$errors = $keyword->errors;
		}
	}
}
