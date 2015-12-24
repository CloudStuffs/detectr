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

		$page = RequestMethods::get("page", 1);
		$limit = RequestMethods::get("limit", 10);
		$count = \Referer::count(array("user_id = ?" => $this->user->id));

		$referers = \Referer::all(array("user_id = ?" => $this->user->id));
		$view->set("referers", $referers);
		$view->set("page", $page);
		$view->set("limit", $limit);
		$view->set("count", $count);
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

		$page = RequestMethods::get("page", 1);
		$limit = RequestMethods::get("limit", 10);
		$count = \Referer::count(array());

		$referers = \Referer::all(array());
		$view->set("referers", $referers);
		$view->set("page", $page);
		$view->set("limit", $limit);
		$view->set("count", $count);
	}

	/**
	 * @before _secure, changeLayout
	 */
	public function edit($ref_id) {
		$this->seo(array(
            "title" => "Submit Your FakeReferer",
            "view" => $this->getLayoutView()
        ));
		$view = $this->getActionView();

		$referer = \Referer::first(array("id = ?" => $ref_id));
		if ((!$referer || $referer->user_id != $this->user->id) && !$this->user->admin) {
			self::redirect("/member");
		}

		if ($referer->short_url) {
			self::redirect("/fakereferer/manage");
		}

		if (RequestMethods::post("action") == "editSubmission") {
			$referer->title = RequestMethods::post("title");
			$referer->url = RequestMethods::post("url");
			$referer->keyword = RequestMethods::post("keyword");

			$referer->save();
			$view->set("success", "Edited successfully!");
		}

		$view->set("referer", $referer);
	}

	/**
	 * @before _secure
	 */
	public function remove($ref_id) {
		$this->noview();
		$referer = \Referer::first(array("id = ?" => $ref_id));
		if (!$referer) {
			self::redirect("/member");
		}

		$this->delete("Referer", $ref_id);
	}

	/**
	 * @before _secure, changeLayout, _admin
	 */
	public function shortUrl($ref_id) {
		$this->seo(array(
            "title" => "Shorten the FakeReferer URL",
            "view" => $this->getLayoutView()
        ));
		$view = $this->getActionView();

		$referer = \Referer::first(array("id = ?" => $ref_id));
		if (!$referer) {
			self::redirect("/admin");
		}

		if (RequestMethods::post("action") == "approve") {
			$long_url = RequestMethods::post("longUrl");

			$parsed = parse_url($long_url);
			$host = $parsed["host"];
			$path = $parsed["path"];
			$parsed_url = $parsed["query"];
			
			$params = array();
			parse_str($parsed_url, $params);

			if (isset($params["q"])) {
				$params["q"] = urlencode($referer->keyword);
			}
			$query = http_build_query($params);
			$final_url = "http://". $host . $path . "?". $query;

			$googl = Registry::get("googl");
            $object = $googl->shortenURL($final_url);

			$referer->short_url = $object->id;
			$referer->live = true;
			$referer->save();
			
			$view->set("success", "Referer Approved");
		}
		$view->set("referer", $referer);
	}
	
}
