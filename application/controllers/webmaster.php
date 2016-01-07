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
		} else {
			$webmaster = new Google_Service_Webmasters($gClient);
			$sites = $webmaster->sites;

			$results = $sites->listSites();

			$view->set("results", $results->getSiteEntry());
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
