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
				$webmaster = new Google_Service_Webmasters($gClient);
				$analytics = $webmaster->searchanalytics;

				$request = new Google_Service_Webmasters_SearchAnalyticsQueryRequest();
				$request->startDate = $start;
				$request->endDate = $end;
				$request->rowLimit = 5;

				$response = $analytics->query("http://swiftintern.com/", $request);
				$response = $response->getRows();
				$view->set("response", $response);
			} catch (\Exception $e) {
				// something went wrong
			}
		}

	}

	/**
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
