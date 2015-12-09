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

	public function index() {
		//echo php code
	}

	public function create() {
		
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

    protected function buildRedirect($url='') {
        return 'header("Location: '.$url.'");exit;';
    }
}
