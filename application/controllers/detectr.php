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

	public function delete() {
		$this->noview();
	}

	public function manage($website_id) {
		
	}

	public function all() {
		
	}

    protected function buildRedirect($url='') {
        return 'header("Location: '.$url.'");exit;';
    }
}
