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
			$website_id = RequestMethods::post("website");
			
			$keyword = RequestMethods::post("keyword");
			$link = RequestMethods::post("link");

			$regex = $this->_websiteRegex();
			if (preg_match("/^$regex$/", $link)) {
				$keyword = new Keyword(array(
					"link" => $link,
					"user_id" => $this->user->id,
					"keyword" => $keyword
				));
				if ($keyword->validate()) {
					$keyword->save();
					$message = "Serp Action registered";
				} else {
					$errors = $keyword->errors;
				}
			} else {
				$message = "Invalid URL";
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

		$keywords = \Keyword::all(array("user_id = ?" => $this->user->id));
		$view->set("serps", $keywords);
	}

	/**
	 * See stats of a keyword
	 * @before _secure, memberLayout
	 */
	public function stats($keyword_id) {
		$keyword = \Keyword::first(array("id = ?" => $keyword_id), array("user_id", "id"));
		$this->_authority($keyword);

		$end_date = RequestMethods::get("startdate", date("Y-m-d"));
		$start_date = RequestMethods::get("enddate", date("Y-m-d", strtotime($end_date."-7 day")));

		$this->seo(array("title" => "Serp | Stats","view" => $this->getLayoutView()));
		$view = $this->getActionView();

		$mongo_db = Registry::get("MongoDB");
		$rank = $mongo_db->rank;
		die('<pre>'.print_r($rank, true). '</pre>');
		$diff = date_diff(date_create($start_date), date_create($end_date));
        for ($i = 0; $i < $diff->format("%a"); $i++) {
            $date = date('Y-m-d', strtotime($start_date . " +{$i} day"));
            $record = $rank->findOne(array('created' => $date, 'keyword_id' => $keyword->id));
            if (isset($record)) {
            	$position = $record['position'];
            } else {
            	$position = 0;
            }
            $obj[] = array('y' => $date, 'a' => $position);
        }
        $view->set("data", ArrayMethods::toObject($obj));
	}

	/**
	 * @before _secure, memberLayout
	 */
	public function remove($serp_id) {
		$serp = Serp::first(array("id = ?" => $serp_id));
		if ($serp) {
			$serp->delete();
		}
		self::redirect("/serp/manage");
	}

	/**
	 * Getter
	 */
	private function _websiteRegex() {
		$regex = "((https?|ftp)\:\/\/)?"; // SCHEME
	    $regex .= "([a-z0-9+!*(),;?&=\$_.-]+(\:[a-z0-9+!*(),;?&=\$_.-]+)?@)?"; // User and Pass
	    $regex .= "([a-z0-9-.]*)\.([a-z]{2,4})"; // Host or IP
	    $regex .= "(\:[0-9]{2,5})?"; // Port
	    $regex .= "(\/([a-z0-9+\$_-]\.?)+)*\/?"; // Path
	    $regex .= "(\?[a-z+&\$_.-][a-z0-9;:@&%=+\/\$_.-]*)?"; // GET Query
	    $regex .= "(#[a-z_.-][a-z0-9+\$_.-]*)?"; // Anchor

	    return $regex;
	}
}
