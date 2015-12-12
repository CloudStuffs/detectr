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
	protected $_actions;

	/**
	 * @readwrite
	 */
	protected $_triggers;

	public function __construct($options = array()) {
		parent::__construct($options);

		$this->_actions = array(
			"1" => array(
				"title" => "Do Nothing",
				"func" => function ($inputs = '') {
					return 'return 0;';
				},
				"help" => "This will do Nothing.."
			),
			"2" => array(
				"title" => "Wait",
				"func" => function ($inputs) {
					return 'sleep('. $inputs . ');';
				},
				"help" => "For how many seconds user-agent should wait when trigger is detected"
			),
			"3" => array(
				"title" => "Redirect",
				"func" => function ($inputs) {
					return 'header("Location: '.$inputs.'");exit;';
				},
				"help" => "Enter the location where to redirect"
			),
			"4" => array(
				"title" => "POST Values",
				"func" => function ($inputs) {
					
				},
				"help" => "Enter {key} => {value} pairs separated with semicolon"
			),
			"5" => array(
				"title" => "Overlay Iframe",
				"func" => function ($inputs) {
					return 'echo "'. $inputs .'"';
				},
				"help" => "Enter the code for iframe"
			),
			"6" => array(
				"title" => "Popup",
				"func" => function ($inputs) {
					return 'echo "<script>alert(\''.$inputs.'\')</script>"';
				},
				"help" => "enter the message for popup"
			),
			"7" => array(
				"title" => "Hide Content",
				"func" => function ($inputs) {
					return 'echo "
						<script>
						document.getElementById('.$inputs.').style.display = "none";
						</script>
					";';
				},
				"help" => "Enter id of the element which is to be hidden"
			),
			"8" => array(
				"title" => "Replace Content",
				"func" => function ($inputs) {
					$data = split(";", $inputs);
					$id = preg_replace("id=", '', $data[0]);
					$content = preg_replace("subject=", '', $data[1]);
					return 'echo "
						<script>
						document.getElementById('.$id.').innerHTML = "'.$content.'";
						</script>
					";';
				},
				"help" => "Enter id of the element which is to be replaced"
			),
			"9" => array(
				"title" => "Send Email",
				"func" => function ($inputs) {
					$header = "From: ".$this->user->email."\r\n";
					$header.= "MIME-Version: 1.0\r\n";
					$header.= "Content-Type: text/html; charset=utf-8\r\n";
					$header.= "X-Priority: 1\r\n";
					
					$data = split(";", $inputs);
					$to = preg_replace("/[a-z]=/", '', $data[0]);
					$subject = preg_replace("/[a-z]=/", '', $data[1]);
					$body = preg_replace("/[a-z]=/", '', $data[2]);
					mail($to, $subject, $body, $header);
				},
				"help" => 'to="Enter the email id of recipient";subject="Add the subject of email";body="Enter the text of email"; Only change the content within the quotes'
			),
			"10" => array(
				"title" => "Run Javascript",
				"func" => function ($inputs) {
					return 'echo "<script>'.$inputs.'</script>"';
				},
				"help" => "Copy and paste the javascript code in the text box"
			),
			"11" => array(
				"title" => "Run PHP",
				"func" => function ($inputs) {
					return $inputs;
				},
				"help" => "Copy and paste the php code in the text box. Exclude <?php ?> tags"
			)
		);

		$this->_triggers = array(
			"1" => array(
				"title" => "PageView",
				"help" => "Just used for tracking website, leave the field empty"
			),
			"2" => array(
				"title" => "Location",
				"verify" => function ($inputs) {
					
				},
				"help" => 'Enter the 2-digit country code.. Refer: <a href="https://en.wikipedia.org/wiki/ISO_3166-1_alpha-2#Officially_assigned_code_elements">Country Codes</a>'
			),
			"3" => array(
				"title" => "Landing Page",
				"verify" => function ($inputs) {
					
				},
				"help" => "Enter full url of the page on which trigger is to be executed"
			),
			"4" => array(
				"title" => "Time of Visit",
				"verify" => function ($inputs) {
					
				},
				"help" => "Enter the range of time. For eg. 10.30-14.50 (Time in 24 hours)"
			),
			"5" => array(
				"title" => "Bots",
				"help" => 'This trigger will be executed for the all the Bots- User Agent. Eg: Google Bot, Baidu Spider etc. Refer: <a href="http://www.useragentstring.com/pages/Crawlerlist/">Crawlers List</a>'
			),
			"6" => array(
				"title" => "IP Range",
				"verify" => function ($inputs) {
					
				},
				"help" => "Range of IP eg: 168.240.10.10-168.241.10.10"
			),
			"7" => array(
				"title" => "User-Agent",
				"verify" => function ($inputs) {
					
				},
				"help" => 'Enter the user agent on which trigger is to be executed. Refer: <a href="http://www.useragentstring.com/pages/useragentstring.php">Differnent User Agents</a>'
			),
			"8" => array(
				"title" => "Browser",
				"verify" => function ($inputs) {
					
				},
				"help" => "Enter the name of browser on which trigger is to be executed. Eg: Chrome, Firefox, Opera etc"
			),
			"9" => array(
				"title" => "Operating System",
				"verify" => function ($inputs) {
					
				},
				"help" => "Enter the name of Operating System on which trigger is to be executed. Eg: Linux, Windows etc"
			),
			"10" => array(
				"title" => "Device Type",
				"verify" => function ($inputs) {
					
				},
				"help" => "Device Type: mobile, desktop"
			),
			"11" => array(
				"title" => "Referrer",
				"verify" => function ($inputs) {
					
				},
				"help" => "URL from which the visit was done"
			),
			"12" => array(
				"title" => "Active Login",
				"verify" => function ($inputs) {
					
				},
				"help" => "Enter the session key in which uniquely identifies the user"
			)
		);
	}

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

		$view->set('triggers', $this->triggers);
		$view->set('actions', $this->actions);

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

		$view->set('actions', $this->actions);
		$view->set('trigs', $this->triggers);
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
		if (!$opts['trigger']['saved']) {
			$trigger = new Trigger();
		} else {
			$trigger = $opts['trigger']['saved'];
		}
		$trigger->title = $opts['trigger']['title'];
		$trigger->meta = $opts['trigger']['meta'];
		$trigger->website_id = $opts['website_id'];
		$trigger->user_id = $this->user->id;
		$trigger->save();

		// what is the action corresponding to the trigger
		$code = call_user_func_array($this->actions[$opts['action']['title']]['func'], array($opts['action']['inputs']));
		
		if (!$opts['action']['saved']) {
			$action = new Action();
		} else {
			$action = $opts['action']['saved'];
		}
		
		$action->user_id = $this->user->id;
		$action->trigger_id = $trigger->id;
		$action->title = $opts['action']['title'];
		$action->inputs = $opts['action']['inputs'];
		$action->code = $code;
		$action->save();
	}

	public function read($type, $id) {
		$this->noview();
		$arr = array();
		switch ($type) {
			case 'trigger':
				$triggers = $this->_triggers;
				$arr = $triggers[$id];
				break;
			
			case 'action':
				$actions = $this->_actions;
				$arr = $actions[$id];
				break;
		}

		echo json_encode($arr);
	}
}
