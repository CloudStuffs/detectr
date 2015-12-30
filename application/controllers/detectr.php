<?php

/**
 * Description of detectr
 *
 * @author Faizan Ayubi
 */
use Framework\Registry as Registry;
use Framework\RequestMethods as RequestMethods;
use Framework\ArrayMethods as ArrayMethods;
use \Curl\Curl;
use \ClusterPoint\DB as DB;

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
					$data = explode(";", $inputs);
					
					$url = array_shift($data);
					$url = preg_replace('/url=/', '', $url);
					
					$postfields = array();
					foreach ($data as $d) {
						$d = explode("=", $d);
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
					return "echo '<script>alert($inputs)</script>';";
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
					$data = explode(";", $inputs);
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
					
					$data = explode(";", $inputs);
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
				"help" => "Just used for tracking website, leave the field empty",
				"detect" => function ($opts) {
					return true;
				}
			),
			"2" => array(
				"title" => "Location",
				"verify" => function ($inputs) {},
				"detect" => function ($opts) {
					return $opts['user']['location'] == $opts['stored'];
				},
				"help" => 'Enter the 2-digit country code.. Refer: <a href="https://en.wikipedia.org/wiki/ISO_3166-1_alpha-2#Officially_assigned_code_elements">Country Codes</a>'
			),
			"3" => array(
				"title" => "Landing Page",
				"verify" => function ($inputs) {},
				"detect" => function ($opts) {
					$stored = strtolower($opts['saved']);
					$current = strtolower($opts['server']['landingPage']);
					return $current == $stored;
				},
				"help" => "Enter full url of the page on which trigger is to be executed<br> The page should be on your domain"
			),
			"4" => array(
				"title" => "Time of Visit",
				"verify" => function ($inputs) {},
				"detect" => function ($opts) {
					$range = explode("-", $opts['saved']);
					
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
					$bots = explode(",", $opts['saved']);
					$response = false;
					foreach ($bots as $b) {
						if ($opts['user']['ua'] == trim($b)) {
							$response = true;
							break;
						}
					}

					if (strtolower($opts['saved']) == 'crawler' && $opts['user']['ua_info']->agent_type == "Crawler") {
						$response = true;
					}
					return $response;
				},
				"help" => 'This trigger will be executed for the all the Bots- User Agent. Eg: Google Bot, Baidu Spider etc. <br>Refer: <a href="http://www.useragentstring.com/pages/Crawlerlist/">Crawlers List</a><br>Enter Crawler-User agent string "," separated. Or for all bots just enter "Crawler"'
			),
			"6" => array(
				"title" => "IP Range",
				"verify" => function ($inputs) {},
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
				"verify" => function ($inputs) {},
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
					return (strtolower($opts['user']['ua_info']->agent_name) == strtolower($opts['saved']));
				},
				"help" => "Enter the name of browser on which trigger is to be executed. Eg: Chrome, Firefox, Opera etc."
			),
			"9" => array(
				"title" => "Operating System",
				"verify" => function ($inputs) {},
				"detect" => function ($opts) {
					return (strtolower($opts['user']['ua_info']->agent_name) == strtolower($opts['saved']));
				},
				"help" => "Enter the name of Operating System on which trigger is to be executed. Eg: Linux, Windows etc"
			),
			"10" => array(
				"title" => "Device Type",
				"verify" => function ($inputs) {},
				"detect" => function ($opts) {
					$saved = strtolower($opts['saved']);

					$check = strtolower($opts['user']['ua_info']->os_name);
					switch ($check) {
						case 'linux':
							$result = 'desktop';
							break;
						
						case 'windows nt':
							$result = 'desktop';
							break;

						case 'os x':
							$result = 'desktop';
							break;

						case 'unknown':
							$result = 'desktop';
							break;

						default:
							$result = 'mobile';
							break;
					}

					return ($saved == $result);
				},
				"help" => "Device Type: mobile or desktop"
			),
			"11" => array(
				"title" => "Referrer",
				"verify" => function ($inputs) {},
				"detect" => function ($opts) {
					$response = stristr($opts['server']['referer'], $opts['saved']);
					return ($response !== FALSE) ? true : false;
				},
				"help" => "URL from which the visit was done"
			),
			"12" => array(
				"title" => "Active Login",
				"verify" => function ($inputs) {},
				"detect" => function ($opts) {
					return false;
				},
				"help" => "Enter the session key in which uniquely identifies the user"
			),
			"13" => array(
				"title" => "Repeat Visitor",
				"verify" => function ($inputs) {},
				"detect" => function ($opts) {
					$key = "__trafficMonitor";
					$cookie = $opts["cookies"];
					
					return isset($cookie[$key]) ? true : false;
				},
				"help" => "Just leave the field empty. We'll check automatically :)"
			)
		);
	}

	public function index() {
		$this->noview();
		if (RequestMethods::post('plugin_detector') == 'getTrigger') {
			$data = $this->_setOpts();
			$website = Website::first(array("url = ?" => $data['server']['name']), array("id", "title", "url"));

			if (!$website) {
				echo 'return 0;';
				return;
			}
			$triggers = Trigger::all(array("website_id = ?" => $website->id, "live = ?" => true), array("id", "title", "meta", "user_id"));
			$code = ''; $last = '';
			foreach ($triggers as $t) {
				$key = $t->title;
				$title = $this->triggers["$key"]['title'];

				if ($t->meta && isset($this->triggers["$key"]["detect"])) {
					$data['saved'] = $t->meta;
					if (!call_user_func_array($this->triggers["$key"]["detect"], array($data))) {
						continue;
					}
					
					//$this->googleAnalytics($website, $t, $data['user']['location']);
					$this->clusterpoint(array(
						"trigger_id" => $t->id,
						"hit" => 1,
						"user_id" => $t->user_id
					));

					$action = Action::first(array("trigger_id = ?" => $t->id), array("code", "title"));
					$key = $action->title;
					if ($this->actions["$key"]["title"] == "Redirect") {
						$last .= $action->code;
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

	protected function _setOpts() {
		$data = array();
		$data['user']['ip'] = RequestMethods::post("REMOTE_ADDR");
		$data['user']['ua'] = RequestMethods::post("HTTP_USER_AGENT");
		
		$ip_info = Shared\Detector::IPInfo($data['user']['ip']);
		$user_agent = Shared\Detector::UA($data['user']['ua']);
		
		$data['user']['location'] = $ip_info->geoplugin_countryCode;
		$data['user']['ua_info'] = $user_agent;
		
		$data['server']['name'] = RequestMethods::post("HTTP_HOST");
		$data['server']['landingPage'] = 'http://'. $data['server']['name']. RequestMethods::post("REQUEST_URI");
		$data['server']['referer'] = RequestMethods::post("HTTP_REFERER");

		$data["posted"] = RequestMethods::post("p");
		$data["cookies"] = RequestMethods::post("c");
		$data["session"] = RequestMethods::post("s");

		return $data;
	}

	protected function googleAnalytics($website, $trigger, $country) {
		$data = array(
			"v" => 1,
			"tid" => "",
			"cid" => $trigger->user_id,
			"t" => "pageview",
			"dp" => $trigger->id,
			"uid" => $trigger->user_id,
			"ua" => "TrafficMonitor",
			"cn" => $trigger->title,
			"cs" => $trigger->user_id,
			"cm" => "TrafficMonitor",
			"ck" => $website->title,
			"ci" => $trigger->id,
			"dl" => $website->title,
			"dh" => $website->url,
			"dp" => $trigger->title,
			"dt" => $country
		);

	    $url = "https://www.google-analytics.com/collect?".http_build_query($data);
	    // Get cURL resource
	    $curl = curl_init();
	    curl_setopt_array($curl, array(
	        CURLOPT_RETURNTRANSFER => 1,
	        CURLOPT_URL => $url,
	        CURLOPT_USERAGENT => 'CloudStuff'
	    ));

	    $resp = curl_exec($curl);
	    curl_close($curl);
	}

	protected function clusterpoint($data = array()) {
		$db = new DB();
    	$record = $db->read($data);
    	if($record) {
    		$item = $record[0];
    		$db->update($item->_id, $item->hit + 1);
    	} else {
    		$db->create($data);
    	}
	}

}
