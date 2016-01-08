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
	 * @before _secure, memberLayout
	 */
	public function index() {
		$this->seo(array("title" => "Dashboard | Webmasters","view" => $this->getLayoutView()));
		$view = $this->getActionView();
		
		$session = Registry::get("session");
		$gClient = Registry::get("gClient");

		$token = $session->get('Webmaster\Authenticate:$token');
		if ($token) {
			$gClient->setAccessToken($token);
		}
		
		if (!$gClient->getAccessToken()) {
			$url = $gClient->createAuthUrl();
			$view->set("url", $url);
		} elseif ($gClient->isAccessTokenExpired()) {
			self::redirect($gClient->createAuthUrl());
		} else {
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
	 * Authenticates the code returned by Google api service when the user signs
	 * into their google account
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
		} else {
			self::redirect("/404");
		}
	}

}
