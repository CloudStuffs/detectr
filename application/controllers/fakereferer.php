<?php
/**
 * Description of fakereferer
 *
 * @author Faizan Ayubi
 */
use Framework\RequestMethods as RequestMethods;
use Framework\Registry as Registry;

class FakeReferer extends Admin {
	
	protected function customfakereferer() {
	    if ($_POST["ref_spoof"] != NULL) {
            $spoof = urldecode($_POST["ref_spoof"]);
            echo "<!DOCTYPE HTML><meta charset=\"UTF-8\"><meta http-equiv=\"refresh\" content=\"1; url=http://example.com\"><script>window.location.href=\"" . $spoof . "\"</script><title>Page Redirection</title>If you are not redirected automatically, follow the <a href='" . $spoof . "'>link</a>";
            exit;
	    }
	}

	/**
	 * @before _secure, memberLayout
	 */
	public function create() {
		$this->seo(array(
            "title" => "Submit Your FakeReferer",
            "view" => $this->getLayoutView()
        ));
		$view = $this->getActionView();

		if (RequestMethods::post("action") == "submitTrigger") {
			$title = RequestMethods::post("title");
			$url = RequestMethods::post("url");
			$keyword = RequestMethods::post("keyword");
			$referer = RequestMethods::post("referer");
			$tld = RequestMethods::post("tld");

			$fakereferer = new \Referer(array(
				"user_id" => $this->user->id,
				"title" => $title,
				"url" => $url,
				"short_url" => "",
				"keyword" => $keyword,
				"referer" => $referer,
				"tld" => $tld,
				"live" => false
			));
			$fakereferer->save();

			$view->set("success", "Your request has been submiited. Will be verified within 24 hours");
		}
	}

	/**
	 * @before _secure, memberLayout
	 */
	public function manage() {
		$this->seo(array(
            "title" => "Manage FakeReferer URLs",
            "view" => $this->getLayoutView()
        ));
		$view = $this->getActionView();

		$referers = \Referer::all(array("user_id = ?" => $this->user->id));
		$view->set("referers", $referers);
	}

	/**
	 * @before _secure, changeLayout, _admin
	 */
	public function all() {
		$this->seo(array(
            "title" => "Manage FakeReferer URLs",
            "view" => $this->getLayoutView()
        ));
		$view = $this->getActionView();

		$referers = \Referer::all();
		$view->set("referers", $referers);
	}

	/**
	 * @before _secure, changeLayout, _admin
	 */
	public function approve() {
		$this->seo(array(
            "title" => "Submit Your FakeReferer",
            "view" => $this->getLayoutView()
        ));
		$view = $this->getActionView();

		$limit = RequestMethods::post("limit", 10);
		$page = RequestMethods::post("page", 1);
		$active = RequestMethods::post("active", 0);
		$count = \Referer::count();

		$results = \Referer::all(array("live = ?" => $active), array("*"), "created", "desc", $limit, $page);
		$view->set("fakereferers", $results);
		$view->set("limit", $limit);
		$view->set("page", $page);
		$view->set("count", $count);
	}

	
}