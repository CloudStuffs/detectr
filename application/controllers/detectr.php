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
					$data = split(";", $inputs);
					
					$url = array_shift($data);
					$url = preg_replace('/url=/', '', $url);
					
					$postfields = array();
					foreach ($data as $d) {
						$d = split("=", $d);
						$postfields["$d[0]"] = $d[1];
					}

					return '
					$ch = curl_init();
					curl_setopt($ch, CURLOPT_URL, "'.$url.'");
					curl_setopt($ch, CURLOPT_POST, ' .count($postfields).');
					curl_setopt($ch, CURLOPT_POSTFIELDS, "'.http_build_query($postfields).'");
					curl_setopt($ch, CURLOPT_HEADER, TRUE);
					curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
					curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
					curl_setopt($ch, CURLOPT_TIMEOUT, 5);
					curl_exec($ch);
					';
				},
				"help" => "Enter {key} => {value} pairs separated with semicolon and url of the page must be set using url='something' URL should be the the first key. <br/>Eg: url=http://somepage.com/something;name=Darrin;country=Canada"
			),
			"5" => array(
				"title" => "Overlay Iframe",
				"func" => function ($inputs) {
					return "echo '$inputs';";
				},
				"help" => "Enter the code for iframe"
			),
			"6" => array(
				"title" => "Popup",
				"func" => function ($inputs) {
					return "echo '<script>alert($inputs)</script>'";
				},
				"help" => 'enter the message for popup in "double quotes"'
			),
			"7" => array(
				"title" => "Hide Content",
				"func" => function ($inputs) {
					return "echo '
						<script>
						document.getElementById($inputs).style.display = 'none';
						</script>
					';";
				},
				"help" => 'Enter id of the element which is to be hidden. eg: "My_Custom_ID". (Id must be in double quotes)'
			),
			"8" => array(
				"title" => "Replace Content",
				"func" => function ($inputs) {
					$data = split(";", $inputs);
					$id = preg_replace("/id=/", '', $data[0]);
					$content = preg_replace("/content=/", '', $data[1]);
					return "echo '
						<script>
						document.getElementById($id).innerHTML = $content;
						</script>
					';";
				},
				"help" => 'Enter id of the element which is to be replaced. Eg: id="myThisContent";content="Your Content" (id & content must be in double-inverted-quotes)'
			),
			"9" => array(
				"title" => "Send Email",
				"func" => function ($inputs, $email) {
					$header = "From: $email \r\n";
					
					$data = split(";", $inputs);
					$to = preg_replace("/to=/", '', $data[0]);
					$subject = preg_replace("/subject=/", '', $data[1]);
					$body = preg_replace("/body=/", '', $data[2]);
					
					return "mail($to, $subject, $body, '$header');";
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
				"detect" => function ($opts) {
					return $opts['user']['location'] == $opts['stored'];
				},
				"help" => 'Enter the 2-digit country code.. Refer: <a href="https://en.wikipedia.org/wiki/ISO_3166-1_alpha-2#Officially_assigned_code_elements">Country Codes</a>'
			),
			"3" => array(
				"title" => "Landing Page",
				"verify" => function ($inputs) {
					// see if the $inputs is correct
				},
				"detect" => function ($opts) {
					$stored = strtolower($opts['saved']);
					$current = strtolower($opts['server']['landingPage']);
					return $current == $stored;
				},
				"help" => "Enter full url of the page on which trigger is to be executed<br> The page should be on your domain"
			),
			"4" => array(
				"title" => "Time of Visit",
				"verify" => function ($inputs) {
					
				},
				"detect" => function ($opts) {
					$range = split("-", $opts['saved']);
					
					$start = $range[0];
					$current = date('G:i');
					$end = $range[1];

					$start_time = strtotime($start);
					$current_time = strtotime($current);
					$end_time = strtotime($end);

					return ($current_time > $start_time && $current_time < $end_time);
				},
				"help" => "Enter the range of time. For eg. 10:30-14:50 (Time in 24 hours)"
			),
			"5" => array(
				"title" => "Bots",
				"detect" => function ($opts) {
					$bots = split(",", $opts['saved']);
					$response = false;
					foreach ($bots as $b) {
						if ($opts['user']['ua'] == $b) {
							$response = true;
							break;
						}
					}

					if ($opts['saved'] == 'Crawler') {
						$response = true;
					}
					return $response;
				},
				"help" => 'This trigger will be executed for the all the Bots- User Agent. Eg: Google Bot, Baidu Spider etc. <br>Refer: <a href="http://www.useragentstring.com/pages/Crawlerlist/">Crawlers List</a><br>Enter Crawler-User agent string "," separated. Or for all bots just enter "Crawler"'
			),
			"6" => array(
				"title" => "IP Range",
				"verify" => function ($inputs) {
					
				},
				"detect" => function ($opts) {
					if (strpos($opts['saved'], '/') == false) {
					    $range.= '/32';
					}
					// $range is in IP/CIDR format eg 127.0.0.1/24
					list($opts['saved'], $netmask) = explode('/', $opts['saved'], 2);
					$range_decimal = ip2long($opts['saved']);
					$ip_decimal = ip2long($opts['user']['ip']);
					$wildcard_decimal = pow(2, (32 - $netmask)) - 1;
					$netmask_decimal = ~ $wildcard_decimal;
					return (($ip_decimal & $netmask_decimal) == ($range_decimal & $netmask_decimal));
				},
				"help" => "Range of IP eg: 168.240.10.10/168.241.10.10"
			),
			"7" => array(
				"title" => "User-Agent",
				"verify" => function ($inputs) {
					
				},
				"detect" => function ($opts) {
					return ($opts['user']['ua'] == $opts['saved']);
				},
				"help" => 'Enter the user agent on which trigger is to be executed. Refer: <a href="http://www.useragentstring.com/pages/useragentstring.php">Differnent User Agents</a>'
			),
			"8" => array(
				"title" => "Browser",
				"verify" => function ($inputs) {
					
				},
				"detect" => function ($opts) {
					return ($opts['user']['ua_info']->agent_name == $opts['saved']);
				},
				"help" => "Enter the name of browser on which trigger is to be executed. Eg: Chrome, Firefox, Opera etc."
			),
			"9" => array(
				"title" => "Operating System",
				"verify" => function ($inputs) {
					
				},
				"detect" => function ($opts) {
					return ($opts['user']['ua_info']->agent_name == $opts['saved']);
				},
				"help" => "Enter the name of Operating System on which trigger is to be executed. Eg: Linux, Windows etc"
			),
			"10" => array(
				"title" => "Device Type",
				"verify" => function ($inputs) {
					
				},
				"detect" => function ($opts) {
					return true;
				},
				"help" => "Device Type: mobile, desktop"
			),
			"11" => array(
				"title" => "Referrer",
				"verify" => function ($inputs) {
					
				},
				"detect" => function ($opts) {
					$response = stristr($opts['server']['referer'], $opts['saved']);
					return ($response !== FALSE) ? true : false;
				},
				"help" => "URL from which the visit was done"
			),
			"12" => array(
				"title" => "Active Login",
				"verify" => function ($inputs) {
					
				},
				"detect" => function ($opts) {
					return false;
				},
				"help" => "Enter the session key in which uniquely identifies the user"
			)
		);
	}

	public function index() {
		$this->noview();
		if (RequestMethods::post('plugin_detector') == 'getTrigger') {
			$ip_info = Shared\Detector::IPInfo($ip);
			$user_agent = Shared\Detector::UA($ua);
			
			$data = array();
			$data['user']['ip'] = RequestMethods::post("REMOTE_ADDR");
			$data['user']['location'] = $ip_info->geoplugin_countryCode;
			$data['user']['ua'] = RequestMethods::post("HTTP_USER_AGENT");
			$data['user']['ua_info'] = $user_agent;
			//$data['user']['time'] = 
			
			$data['server']['name'] = RequestMethods::post("HTTP_HOST");
			$data['server']['landingPage'] = 'http://'. $data['server']['name']. RequestMethods::post("REQUEST_URI");
			$data['server']['referer'] = RequestMethods::post("HTTP_REFERER");
			
			$website = Website::first(array("url = ?" => $data['server']['name']));

			if (!$website) {
				echo 'return 0;';
				return;
			}
			$triggers = Trigger::all(array("website_id = ?" => $website->id));
			$code = ''; $last = '';
			foreach ($triggers as $t) {
				$key = $t->title;
				$title = $this->triggers["$key"]['title'];

				if ($t->meta && isset($this->triggers["$key"]["detect"])) {
					$data['saved'] = $t->meta;
					if (!call_user_func_array($this->triggers["$key"]["detect"], array($data))) {
						continue;
					}
					$action = Action::first(array("trigger_id = ?" => $t->id), array("code", "title"));
					if ($this->actions[$action->title]["title"] == "Redirect") {
						if (!empty($last)) {
							$last = $action->code;
						}
					} else {
						$code .= $action->code;
					}
				}
			}
			$code .= $last;

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
		if ($this->actions[$opts['action']['title']]['title'] == 'Send Email') {
			$args = array($opts['action']['inputs'], $this->user->email);
		} else {
			$args = array($opts['action']['inputs']);
		}
		$code = call_user_func_array($this->actions[$opts['action']['title']]['func'], $args);
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
