<?php

/**
 * Description of detectr
 *
 * @author Faizan Ayubi
 */
use Framework\Registry as Registry;
use Framework\RequestMethods as RequestMethods;
use Framework\ArrayMethods as ArrayMethods;

class Monitor extends Detectr {
	/**
     * @before _secure, _admin, changeLayout
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
        	"count" => $count,
        	"limit" => page,
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
			self::redirect("/member");
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

		$m_trigs->remove(array('trigger_id' => $trigger->id), array('justOne' => true));
		$this->delete('Trigger', $trigger_id, false);
		
		$action = Action::first(array("id = ?" => $action_id));
		$m_actions = $mongo->selectCollection("actions");
		$this->_authority($action);
		
		$m_actions->remove(array('action_id' => $action->id), array('justOne' => true));
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
		$triggers = Trigger::all(array("website_id = ?" => $website_id), array("title", "meta", "website_id", "user_id", "id", "live"));

		$view->set(array(
			'actions' => $this->actions,
			'trigs' => $this->triggers,
			'triggers' => $triggers,
			'website' => $website
		));
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

		$this->_mongoSave($trigger, $action);
	}

	/**
	 * Save the trigger and action in MongoDB
	 */
	protected function _mongoSave($trigger, $action) {
		$mongo = Registry::get("MongoDB");
		$m_trigs = $mongo->selectCollection("triggers");
		$m_actions = $mongo->selectCollection("actions");

		$record = $m_trigs->findOne(array('trigger_id' => $trigger->id));
		$doc = array(
				'title' => $trigger->title,
				'meta' => $trigger->meta,
				'user_id' => $trigger->user_id,
				'website_id' => $trigger->website_id,
				'trigger_id' => $trigger->id,
				'live' => (bool) $trigger->live,
				'created' => $trigger->created
		);
		if (isset($record)) {
			$m_trigs->update(array('trigger_id' => $trigger->id), array('$set' => $doc));
		} else {
			$m_trigs->insert($doc);
		}

		$record = $m_actions->findOne(array('action_id' => $action->id));
		$doc = array(
			'title' => $action->title,
			'inputs' => $action->inputs,
			'code' => $action->code,
			'user_id' => $action->user_id,
			'trigger_id' => $action->trigger_id,
			'action_id' => $action->id,
			'live' => (bool) $action->live,
			'created' => $action->created
		);
		if (isset($record)) {
			$m_actions->update(array('trigger_id' => $trigger->id, 'action_id' => $action->id), array('$set' => $doc));
		} else {
			$m_actions->insert($doc);
		}
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