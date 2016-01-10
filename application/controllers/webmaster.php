<?php
/**
 * Webmaster tools
 *
 * @author Hemant Mann
 */
use Framework\RequestMethods as RequestMethods;
use Framework\Registry as Registry;
use Framework\ArrayMethods as ArrayMethods;

class Webmaster extends Admin {
	/**
	 * Authenticates the code returned by Google api service when the user signs
	 * into their google account.
	 * @before _secure
	 */
	public function authenticate() {
		$code = RequestMethods::get("code");
		$session = Registry::get("session");
		$gClient = Registry::get("gClient");
		
		if ($code) {
			$gClient->authenticate($code);
			$session->set('Webmaster\Authenticate:$token', $gClient->getAccessToken());
			self::redirect("/webmaster");
		}
	}

	/**
	 * @before _secure, memberLayout
	 */
	public function index() {
		$this->seo(array("title" => "Dashboard | Webmasters","view" => $this->getLayoutView()));
		$view = $this->getActionView();

		$response = $this->_setAccessToken();
		if (isset($response["url"])) {
			$view->set("url", $response["url"]);
			return;
		}

		$gClient = $response["gClient"];
		$end = RequestMethods::get("endDate", date('Y-m-d'));
		$start = RequestMethods::get("startDate", date('Y-m-d', strtotime($end."-30 day")));
		try {
			$websites = $this->_getWebsites($gClient);
			
			$url = RequestMethods::get("website", $websites[0]->getSiteUrl());
			$opts = array("gClient" => &$gClient, "url" => $url, "startDate" => $start, "endDate" => $end);
			$response = $this->_getWebsiteStats($opts);
			
			$view->set("current", $url);
			$view->set("websites", $websites);
			$view->set("response", $response);
		} catch (\Exception $e) {
			$view->set("message", $e->getMessage());
		}
		
	}

	/**
	 * @before _secure, memberLayout
	 */
	public function crawlErrors() {
		$this->seo(array("title" => "Webmasters | Crawl Errors","view" => $this->getLayoutView()));
		$view = $this->getActionView();

		$response = $this->_setAccessToken();
		if ($response["url"]) {
			$view->set("url", $response["url"]);
			return;
		}

		$websites = $this->_getWebsites($response["gClient"]);
		$result = true;
		
		$url = RequestMethods::get("website", $websites[0]->getSiteUrl());
		$opts = array();

		$opts["category"] = RequestMethods::get("category", "notFound");
		if ($opts["category"] == "all") {
			unset($opts["category"]);
		}
		$opts["latestCountsOnly"] = RequestMethods::get("latest", true);
		if ($opts["latestCountsOnly"] == "true" || $opts["latestCountsOnly"] === true) {
			$opts["latestCountsOnly"] = true;
		} else {
			$opts["latestCountsOnly"] = false;
		}
		$opts["platform"] = RequestMethods::get("platform", "web");

		$result = $this->_getCrawlErrors($response["gClient"], $url, $opts);
		if (!is_object($result)) {
			// $result = null;
		}

		$view->set("current", $url);
		$view->set("websites", $websites);
		$view->set("response", $result);
	}

	/**
	 * @before _secure, memberLayout
	 */
	public function sitemap() {
		$this->seo(array("title" => "Webmasters | Crawl Errors","view" => $this->getLayoutView()));
		$view = $this->getActionView();

		$response = $this->_setAccessToken();
		if ($response["url"]) {
			$view->set("url", $response["url"]);
			return;
		}

		$websites = $this->_getWebsites($response["gClient"]);
		$url = RequestMethods::get("website", $websites[0]->getSiteUrl());
		$view->set("current", $url);
		$view->set("websites", $websites);
		$view->set("response", true);
	}

	/**
	 * Returns an array of Websites
	 * @return array containing objects of class \Google_Service_Webmasters_WmxSite
	 */
	protected function _getWebsites(&$gClient) {
		$webmaster = new Google_Service_Webmasters($gClient);
		$sites = $webmaster->sites;
		$websites = $sites->listSites()->getSiteEntry();
		return $websites;
	}

	/**
	 * Returns an array of objects
	 * @return array objects of class \Google_Service_Webmasters_ApiDataRow
	 */
	protected function _getWebsiteStats($opts) {
		$webmaster = new Google_Service_Webmasters($opts["gClient"]);
		$analytics = $webmaster->searchanalytics;

		$request = new Google_Service_Webmasters_SearchAnalyticsQueryRequest();
		$request->startDate = $opts["startDate"];
		$request->endDate = $opts["endDate"];
		$request->rowLimit = 5;

		$response = $analytics->query($opts["url"], $request);
		$response = $response->getRows();
		return $response;
	}

	/**
	 * Finds the access token from the session and sets the token in Google_Client
	 * library else return the Authentication URL
	 * @return array Returns an array
	 */
	protected function _setAccessToken() {
		$session = Registry::get("session");
		$gClient = Registry::get("gClient");
		$token = $session->get('Webmaster\Authenticate:$token');
		if ($token) {
			$gClient->setAccessToken($token);
		}

		if (!$gClient->getAccessToken()) {
			$url = $gClient->createAuthUrl();
			return array("url" => $url);
		} elseif ($gClient->isAccessTokenExpired()) {
			self::redirect($gClient->createAuthUrl());
		} else {
			return array("gClient" => $gClient);
		}
	}

	/**
	 * Returns an array of objects
	 * @return array|string objects of class \Google_Service_Webmasters_UrlCrawlErrorCountsPerType
	 */
	protected function _getCrawlErrors(&$gClient, $website, $opts) {
		try {
			$webmaster = new Google_Service_Webmasters($gClient);
			$crawlErrors = $webmaster->urlcrawlerrorscounts;

			$response = $crawlErrors->query($website, $opts);
			$response = $response->getCountPerTypes();
			
			$result = array();
			foreach ($response as $r) {
				$entries = $r->getEntries();
				$entries = array_slice($entries, -10);
				
				$entry = array();
				foreach ($entries as $e) {
					preg_match("/(.*)T/", $e->getTimestamp(), $match);
					$entry[] = array(
						"count" => $e->getCount(),
						"timestamp" => $match[1]
					);
				}
				$result[] = array(
					"platform" => $r->platform,
					"category" => $r->category,
					"entries" => $entry
				);
			}
			$result = ArrayMethods::toObject($result);
		} catch (\Exception $e) {
			$result = $e->getMessage();
		}
		
		return $result;
	}
}