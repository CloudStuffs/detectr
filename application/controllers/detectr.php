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
		"4" => "Time of Visit",
		"5" => "Bots",
		"6" => "Whois",
		"7" => "User-Agent",
		"8" => "Browser",
		"9" => "Operating System",
		"10" => "Device Type",
		"11" => "Referrer",
		"12" => "Incoming Search Term",
		"13" => "IP Range",
		"14" => "Active Login",
		"15" => "Javascript Enabled",
		"16" => "Repeat Visitor",
		"17" => "Custom Javascript",
		"18" => "Custom PHP"
	);

	public function index() {
		$this->noview();
		if (RequestMethods::post('plugin_detector') == 'getTrigger') {
			$domain = RequestMethods::post("HTTP_HOST");
			$ip = RequestMethods::post("REMOTE_ADDR");
			$ua = RequestMethods::post("HTTP_USER_AGENT");
			
			$ip_info = Shared\Detector::IPInfo($ip);
			// $user_agent = Shared\Detector::UA($ua);
			$website = Website::first(array("url = ?" => $domain));

			if (!$website) {
				echo 'return 0;';
				return;
			}
			$triggers = Trigger::all(array("website_id = ?" => $website->id));
			$code = '';
			foreach ($triggers as $t) {
				switch ($t->title) {
					case 'Location':
						// check if condition is fullfilling if yes then execute the action

						if ($t->meta == $ip_info->geoplugin_countryCode) {
							$action = Action::first(array("trigger_id = ?" => $t->id));
							$code .= $action->code;
						}
						break;
					
					default:
						$code .= 'return;';
						break;
				}
			}

			echo $code;
		} else {
			self::redirect('/404');
		}
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
		if (!$website || $website->user_id != $this->user->id) {
			self::redirect("/member");
		}

		if (RequestMethods::post("key") == 'createTrigger') {
			$this->_process(array('trigger' => false, 'action' => false, 'website_id' => $website->id));
			$view->set('message', 'Trigger created Successfully');
		}

		$view->set('triggers', $this->triggers);
		$view->set('actions', $this->actions);
		$view->set('website', $website);

	}

	/**
	 * @before _secure, changeLayout
	 */
	public function edit($trigger_id) {
		if (!$trigger_id) {
			self::redirect("/member");
		}
		$trigger = Trigger::first(array("id = ?" => $trigger_id));
		if (!$trigger) {
			self::redirect("/member");
		}
		$website = Website::first(array("id = ?" => $trigger->website_id));
		$action = Action::first(array("trigger_id = ?" => $trigger->id));

		$this->seo(array(
            "title" => "Edit trigger",
            "view" => $this->getLayoutView()
        ));
		$view = $this->getActionView();

		if (RequestMethods::post('key') == 'editTrigger') {
			$this->_process(array('trigger' => $trigger, 'action' => $action, 'website_id' => $trigger->website_id));
			$view->set('message', 'Trigger edited Successfully');
		}

		$trigger_key = null;
		foreach ($this->triggers as $key => $value) {
			if ($value == $trigger->title) {
				$trigger_key = $key;
				break;
			}
		}
		$action_key = null;
		foreach ($this->actions as $key => $value) {
			if ($value == $action->title) {
				$action_key = $key;
				break;
			}
		}
		$view->set('triggers', $this->triggers);
		$view->set('actions', $this->actions);

		$view->set('trigger_key', $trigger_key);
		$view->set('action_key', $action_key);
		$view->set('trigger', $trigger);
		$view->set('action', $action);
		$view->set('website', $website);
	}

	/**
	 * @before _secure
	 */
	public function remove($trigger_id, $action_id) {
		$this->noview();
		
		$this->delete('Trigger', $trigger_id, false);
		$this->delete('Action', $action_id);
	}

	/**
	 * @before _secure, changeLayout
	 */
	public function manage($website_id) {
		$this->seo(array(
            "title" => "All Triggers for your website",
            "view" => $this->getLayoutView()
        ));
		$view = $this->getActionView();

		$website = Website::first(array("id = ?" => $website_id));
		if (!$website || $website->user_id != $this->user->id) {
			self::redirect("/member");
		}
		$triggers = Trigger::all(array("website_id = ?" => $website_id, "live = ?" => true));
		$view->set("triggers", $triggers);
		$view->set("website", $website);
	}

	public function test() {
		$this->noview();
		$this->_detector();
	}

	protected function _process($opts) {
		$trigger_title = RequestMethods::post("trigger");
		$action_title = RequestMethods::post("action");

		$trigger_val = RequestMethods::post("trigger_val");
		$action_val = RequestMethods::post("action_val");
		$this->_save(array(
			'trigger' => array(
				'title' => $trigger_title,
				'meta' => $trigger_val,
				'saved' => $opts['trigger']
			),
			'action' => array(
				'title' => $action_title,
				'inputs' => $action_val,
				'saved' => $opts['action']
			),
			'website_id' => $opts['website_id']
		));
	}

	protected function _save($opts) {
		$trigger_title = $this->triggers[$opts['trigger']['title']];
		if (!$opts['trigger']['saved']) {
			$trigger = new Trigger();
		} else {
			$trigger = $opts['trigger']['saved'];
		}
		$trigger->title = $trigger_title;
		$trigger->meta = $opts['trigger']['meta'];
		$trigger->website_id = $opts['website_id'];
		$trigger->user_id = $this->user->id;
		$trigger->save();

		// what is the action corresponding to the trigger
		switch ($opts['action']['title']) {
			case '1':
				$code = 'return 0;';
				break;
			
			case '2':
				if (!preg_match('/[0-9]{1,3}/', $opts['action']['inputs'])) {
					throw new \Exception('Invalid time for wait');
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
		if (!$opts['action']['saved']) {
			$action = new Action();
		} else {
			$action = $opts['action']['saved'];
		}
		
		$action->user_id = $this->user->id;
		$action->trigger_id = $trigger->id;
		$action->title = $action_title;
		$action->inputs = $opts['action']['inputs'];
		$action->code = $code;
		$action->save();
	}

    protected function _buildRedirect($url='') {
        return 'header("Location: '.$url.'");exit;';
    }
}
