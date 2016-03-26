<?php

/**
 * Manages Triggers and Action
 *
 * @author Faizan Ayubi, Hemant Mann
 */
use Framework\Registry as Registry;
use Framework\RequestMethods as RequestMethods;
use Framework\ArrayMethods as ArrayMethods;

class Detector extends Admin {
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
				"help" => "Can be used to track the traffic on the website"
			),
			"2" => array(
				"title" => "Wait",
				"func" => function ($inputs) {
					return 'sleep('. $inputs . ');';
				},
				"help" => "For how many seconds user should wait when above trigger is detected"
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
				"help" => "Enter {key} => {value} pairs separated with semicolon and url of the page must be set using url='something' URL should be the the first key. <br/>Eg: url=http://somepage.com/something;name=Tom;country=Canada"
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
					$to = preg_replace("/to=/", '', trim($data[0]));
					$subject = preg_replace("/subject=/", '', trim($data[1]));
					$body = preg_replace("/body=/", '', trim($data[2]));
					
					return "mail($to, $subject, $body, '$header');";
				},
				"help" => 'to="Enter the email id of recipient";subject="Add the subject of email";body="Enter the text of email"; (Only change the content within the quotes)'
			),
			"10" => array(
				"title" => "Run Javascript",
				"func" => function ($inputs) {
					return 'echo "<script>'.$inputs.'</script>"';
				},
				"help" => "Copy and paste the javascript code in the text box. JS syntax should be valid!"
			),
			"11" => array(
				"title" => "Run PHP",
				"func" => function ($inputs) {
					return $inputs;
				},
				"help" => "Copy and paste the php code in the text box. Exclude <?php ?> tags. Be careful about the syntax!!"
			)
		);

		$this->_triggers = array(
			"1" => array(
				"title" => "PageView",
				"help" => "Just used for tracking website, leave the field empty"
			),
			"2" => array(
				"title" => "Location",
				"verify" => function ($inputs) {},
				"help" => 'Enter the 2-digit country code.. Refer: <a href="https://en.wikipedia.org/wiki/ISO_3166-1_alpha-2#Officially_assigned_code_elements">Country Codes</a>'
			),
			"3" => array(
				"title" => "Landing Page",
				"verify" => function ($inputs) {},
				"help" => "Enter full url of the page on which trigger is to be executed<br> The page should be on your domain for trigger to work"
			),
			"4" => array(
				"title" => "Time of Visit",
				"verify" => function ($inputs) {},
				"help" => "Enter the range of time. For eg. 10:30-14:50 (Time in 24 hours)"
			),
			"5" => array(
				"title" => "Bots",
				"help" => 'This trigger will be executed for the all the Bots- User Agent. Eg: Google Bot, Baidu Spider etc. <br>Refer: <a href="http://www.useragentstring.com/pages/Crawlerlist/">Crawlers List</a><br>Enter Crawler-User agent string "," separated. Or for all bots just enter "Crawler"'
			),
			"6" => array(
				"title" => "IP Range",
				"verify" => function ($inputs) {},
				"help" => "Range of IP eg: 168.240.10.10/168.241.10.10"
			),
			"7" => array(
				"title" => "User-Agent",
				"verify" => function ($inputs) {},
				"help" => 'Enter the user agent on which trigger is to be executed.<br> Refer: <a href="http://www.useragentstring.com/pages/useragentstring.php">Differnent User Agents</a>'
			),
			"8" => array(
				"title" => "Browser",
				"help" => "Enter the name of browser on which trigger is to be executed.<br> Eg: Chrome, IE, Firefox, Opera etc."
			),
			"9" => array(
				"title" => "Operating System",
				"verify" => function ($inputs) {},
				"help" => "Enter the name of Operating System on which trigger is to be executed.<br> Eg: Linux, Windows etc"
			),
			"10" => array(
				"title" => "Device Type",
				"verify" => function ($inputs) {},
				"help" => "Device Type: mobile or desktop"
			),
			"11" => array(
				"title" => "Referrer",
				"verify" => function ($inputs) {},
				"help" => "URL from which the visit was done"
			),
			"12" => array(
				"title" => "Active Login",
				"verify" => function ($inputs) {},
				"help" => "Enter the session key in which uniquely identifies the user"
			),
			"13" => array(
				"title" => "Repeat Visitor",
				"verify" => function ($inputs) {},
				"help" => "Just leave the field empty. We'll check automatically :)"
			)
		);
	}

	/**
     * @before _secure, _admin
     */
    public function approve() {
        $this->seo(array("title" => "Approve or Disapprove websites triggers", "keywords" => "admin", "description" => "admin", "view" => $this->getLayoutView()));
        $view = $this->getActionView();

        $limit = RequestMethods::get("limit", 10);
        $page = RequestMethods::get("page", 1);
        $live = RequestMethods::get("live", 0);
        
        $triggers = \Trigger::all(array("live = ?" => $live), array("*"), "created", "desc", $limit, $page);
        $count = Trigger::count(array("live = ?" => $live));

        $view->set(array(
        	"live" => $live,
        	"count" => (int) $count,
        	"limit" => $limit,
        	"page" => $page,
        	"triggers" => $triggers,
        	"ts" => $this->triggers
        ));
    }

	/**
	 * @before _secure, memberLayout
	 */
	public function create($website_id = null) {
		$this->seo(array(
            "title" => "Create a Trigger for your website",
            "view" => $this->getLayoutView()
        ));
		$view = $this->getActionView();

		$website = Website::first(array("id = ?" => $website_id), array("id", "title", "url", "user_id"));
		$this->_authority($website);

		if (RequestMethods::post("key") == 'createTrigger') {
			$this->_process(array('trigger' => false, 'action' => false, 'website_id' => $website->id));
			$view->set('message', 'Trigger created Successfully');
		}

		$view->set(array(
			'triggers' => $this->triggers,
			'actions' => $this->actions,
			'website' => $website
		));
	}

	/**
	 * @before _secure, memberLayout
	 */
	public function edit($trigger_id) {
		if (!$trigger_id) {
			$this->redirect("/member");
		}
		$trigger = Trigger::first(array("id = ?" => $trigger_id));
		$this->_authority($trigger);
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

		$view->set(array(
			'triggers' => $this->triggers,
			'actions' => $this->actions,
			'trigger' => $trigger,
			'action' => $action,
			'website' => $website
		));
	}

	/**
	 * @before _secure
	 */
	public function remove($trigger_id, $action_id) {
		$this->noview();
		
		$mongo = Registry::get("MongoDB");
		$m_trigs = $mongo->selectCollection("triggers");
		$trigger = Trigger::first(array("id = ?" => $trigger_id));
		$this->_authority($trigger);

		$m_trigs->remove(array('trigger_id' => (int) $trigger->id), array('justOne' => true));
		$this->delete('Trigger', $trigger_id, false);
		
		$action = Action::first(array("id = ?" => $action_id));
		$m_actions = $mongo->selectCollection("actions");
		$this->_authority($action);

		$hits = $mongo->selectCollection("hits");
		$hits->remove(array('trigger_id' => (int) $trigger->id, 'action_id' => (int) $action->id), array('justOne' => true));
		
		$m_actions->remove(array('action_id' => (int) $action->id), array('justOne' => true));
		$this->delete('Action', $action_id);
	}

	/**
	 * @before _secure, memberLayout
	 */
	public function manage($website_id) {
		$this->seo(array(
            "title" => "All Triggers for your website",
            "view" => $this->getLayoutView()
        ));
		$view = $this->getActionView();

		$website = Website::first(array("id = ?" => $website_id));
		$this->_authority($website);
		$triggers = Trigger::all(array("website_id = ?" => $website_id), array("*"), "priority", "asc");
		$count = count($triggers);

		$priorities = array();
		for ($i = 0; $i <= $count; ++$i) {
			$priorities[] = $i;
		}

		$view->set(array(
			'actions' => $this->actions,
			'trigs' => $this->triggers,
			'triggers' => $triggers,
			'website' => $website,
			'priorities' => $priorities
		));
	}

	/**
	 * @before _secure
	 */
	public function updatePriority() {
		if (RequestMethods::post("action") == "changePriority") {
			$view = $this->getActionView();
			$t = Trigger::first(array("id = ?" => RequestMethods::post("trigger")));
			if (!$t || $t->user_id != $this->user->id) {
				return;
			}
			$t->priority = RequestMethods::post("priority");
			$t->save();

			$trig = Registry::get("MongoDB")->triggers;
			$trig->update(array('trigger_id' => (int) $t->id), array('$set' => array('priority' => (int) $t->priority)));
			$view->set("success", true);
		} else {
			$this->redirect("/404");
		}
	}

	/**
	 * @before _secure, _admin
	 */
	public function status($id, $value) {
		$m = strtolower($model);

		$trigger = Registry::get("MongoDB")->triggers;
		$live = (bool) ((int) $value);
		$trigger->update(array('trigger_id' => (int) $id), array('$set' => array('live' => $live)));
		parent::edit('trigger', $id, 'live', $value);
	}

	/**
	 * @before _secure, memberLayout
	 */
	public function logs($website_id) {
		$this->seo(array(
            "title" => "Logs for your website",
            "view" => $this->getLayoutView()
        ));
		$view = $this->getActionView();
		$this->getLayoutView()->set("logs", true);

		$mongo = Registry::get("MongoDB");
		$website = $mongo->website;
		$record = $website->findOne(array("website_id" => (int) $website_id));
		if (!$record || $record['user_id'] != $this->user->id) {
			$this->redirect("/404");
		}
		$this->_clearLogs($website_id);

		$logs = $mongo->logs;
		$where = array('website_id' => (int) $website_id);
		$page = RequestMethods::get("page", 1);
        $limit = RequestMethods::get("limit", 30);
        $count = $logs->count($where);

		$cursor = $logs->find($where, array('_id' => false));
		$cursor->skip($limit * ($page - 1));
		$cursor->limit($limit);
		$cursor->sort(array('created' => -1));

		$results = array();
		foreach ($cursor as $c) {
			$c = ArrayMethods::toObject($c);
			$results[] = $c;
		}
		$view->set("logs", $results)
			->set("page", $page)
			->set("limit", $limit)
			->set("actions", $this->actions)
			->set("ts", $this->triggers)
			->set("count", $count);
	}

	protected function _clearLogs($website_id) {
		$logs = Registry::get("MongoDB")->logs;
		if (RequestMethods::post("action") == "clearLogs") {
			$from_default = (date('Y-m-d', strtotime("-15 day")));
			$to_default = (date('Y-m-d', strtotime("-7 day")));

			$from = RequestMethods::post("from", $from_default);
			$to = RequestMethods::post("to", $to_default);
			
			$from = new MongoDate(strtotime($from));
			$to = new MongoDate(strtotime($to));

			$logs->remove(array(
				'user_id' => (int) $this->user->id, 
				'website_id' => (int) $website_id, 
				'created' => array('$gte' => $from, '$lte' => $to)
			));
		}
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
			$trigger->priority = 0;
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

		$this->_mongoSave($trigger, $action);
	}

	/**
	 * Save the trigger and action in MongoDB
	 */
	protected function _mongoSave($trigger, $action) {
		$mongo = Registry::get("MongoDB");
		$m_trigs = $mongo->selectCollection("triggers");
		$m_actions = $mongo->selectCollection("actions");

		$where = array('trigger_id' => (int) $trigger->id, 'user_id' => (int) $trigger->user_id, 'website_id' => (int) $trigger->website_id);
		$record = $m_trigs->findOne($where);
		$doc = array(
				'title' => (int) $trigger->title,
				'meta' => $trigger->meta,
				'live' => (bool) $trigger->live,
				'created' => $trigger->created,
				'priority' => (int) $trigger->priority
		);
		if (isset($record)) {
			$m_trigs->update($where, array('$set' => $doc));
		} else {
			$m_trigs->insert(array_merge($doc, $where));
		}

		$where = array('action_id' => (int) $action->id, 'user_id' => (int) $action->user_id, 'trigger_id' => (int) $action->trigger_id);
		$record = $m_actions->findOne($where);
		$doc = array(
			'title' => (int) $action->title,
			'inputs' => $action->inputs,
			'code' => $action->code,
			'live' => (bool) $action->live,
			'created' => $action->created
		);
		if (isset($record)) {
			$m_actions->update($where, array('$set' => $doc));
		} else {
			$m_actions->insert(array_merge($doc, $where));
		}
	}

	public function read($type, $id) {
		$this->noview(); $arr = array();
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
