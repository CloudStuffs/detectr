<?php

/**
 * Description of detectr
 *
 * @author Faizan Ayubi
 */
use Framework\Registry as Registry;
use Framework\RequestMethods as RequestMethods;
use \Curl\Curl;

class Detectr extends Admin {

	/**
	 * @readwrite
	 */
	protected $_actions = array(
		"1" => "Do Nothing",
		"2" => "Wait",
		"3" => "Redirect",
		"4" => "POST Values",
		"5" => "Overlay Iframe",
		"6" => "Popup",
		"7" => "Hide Content",
		"8" => "Replace Content",
		"9" => "Send Email",
		"10" => "Run Javascript",
		"11" => "Run PHP"
	);

	/**
	 * @readwrite
	 */
	protected $_triggers = array(
		"1" => "Everything Else",
		"2" => "Location",
		"3" => "Landing Page",
		"6" => "Time of Visit",
		"7" => "Bots",
		"8" => "Whois",
		"9" => "User-Agent",
		"10" => "Browser",
		"11" => "Operating System",
		"12" => "Device Type",
		"13" => "Referrer",
		"14" => "Incoming Search Term",
		"15" => "IP Range",
		"16" => "Active Login",
		"17" => "Javascript Enabled",
		"18" => "Repeat Visitor",
		"19" => "Custom Javascript",
		"20" => "Custom PHP"
	);

	public function index() {
		//echo php code
	}

	/**
	 * @before _secure, changeLayout
	 */
	public function create($website_id) {
		$this->seo(array(
            "title" => "Create a Trigger for your website",
            "view" => $this->getLayoutView()
        ));
		$view = $this->getActionView();

		$website = Website::first(array("id = ?" => $website_id));
		if (!$website) {
			self::redirect("/detectr/all");
		}

		if (RequestMethods::post("key") == 'createTrigger') {
			$trigger = RequestMethods::post("trigger");
			$action = RequestMethods::post("action");

			$trigger_val = RequestMethods::post("trigger_val");
			$action_val = RequestMethods::post("action_val");
			$this->_save(array(
				'trigger' => array(
					'title' => $trigger,
					'meta' => $trigger_val
				),
				'action' => array(
					'title' => $action,
					'inputs' => $action_val
				),
				'website_id' => $website->id
			));
			$view->set('message', 'Trigger created Successfully');
		}
		$view->set('website', $website->title);
		$view->set('url', $website->url);

	}

	public function remove($trigger_id) {
		$this->noview();
		// delete the trigger
		// $this->delete('Trigger', $trigger_id);
		// @todo
		// Remove all the actions corresponding to the trigger
	}

	/**
	 * @before _secure, changeLayout
	 */
	public function manage($website_id) {
		$view = $this->getActionView();

		$triggers = Trigger::all(array("website_id = ?" => $website_id, "live = ?" => true));
		$view->set("triggers", $triggers);
	}

	public function all() {
		
	}

	/**
	 * @before _secure, changeLayout
	 */
	public function addWebsite() {
		$this->seo(array(
            "title" => "Create a Trigger for your website",
            "view" => $this->getLayoutView()
        ));
		$view = $this->getActionView();

		if (RequestMethods::post('action') == 'addWebsite') {
			$name = RequestMethods::post('name');
			$url = urlencode(RequestMethods::post('url'));

			$website = new Website(array(
				"title" => $name,
				"url" => $url,
				"user_id" => $this->user->id
			));
			$website->save();
			$view->set("message", "Website Added Successfully");
		}
	}

	protected function _save($opts) {
		$trigger_title = $this->triggers[$opts['trigger']['title']];
		$trigger = new Trigger(array(
			"title" => $trigger_title,
			"meta" => $opts['trigger']['meta'],
			"website_id" => $opts['website_id'],
			"user_id" => $this->user->id
		));
		$trigger->save();

		// what is the action corresponding to the trigger
		switch ($opts['action']['title']) {
			case '1':
				$code = 'return 0;';
				break;
			
			case '2':
				if (!preg_match('/[0-9]{1,3}/', $opts['action']['inputs'])) {
					throw \Exception('Invalid time for wait');
				}
				$code = 'sleep('.$opts['action']['inputs'].');';
				break;
			
			case '3':
				$code = $this->_buildRedirect($opts['action']['inputs']);
				break;
			
			case '4':
				// post values
				break;
			
			case '5':
				// overlay iframes
				break;
			
			case '6':
				// popups
				break;
			
			case '7':
				// hide content
				break;
			
			case '8':
				// replace content
				break;
			
			case '9':
				// send email
				break;
			
			case '10':
				// run JS
				break;
			
			case '11':
				// run PHP
				break;
		}
		
		$action_title = $this->actions[$opts['action']['title']];
		$action = new Action(array(
			"user_id" => $this->user->id,
			"trigger_id" => $trigger->id,
			"title" => $action_title,
			"inputs" => $opts['action']['inputs'],
			"code" => $code
		));
		$action->save();
	}

    protected function _buildRedirect($url='') {
        return 'header("Location: '.$url.'");exit;';
    }
}
