<?php

/**
 * Ping Cron
 * 
 * @author Hemant Mann
 */
use Framework\Registry as Registry;

class PingCron extends Auth {
	public function __construct($options = array()) {
		parent::__construct($options);

		$this->willRenderLayoutView = false;
		$this->willRenderActionView = false;
	}

	/**
	 * @before _secure
	 */
	public function index($type) {
		if (!$type) {
			die('Type must be defined for cron job');
		}
		$this->_execute($type);
	}

	protected function _execute($type) {
		$mongo = Registry::get("MongoDB");
		$ping = $mongo->ping;
		$ping_stats = $mongo->ping_stats;

		$records = $ping->find(array(
			'live' => 1,
			'interval' => $type
		));

		foreach ($records as $r) {
			/*$ping = new JJ\Ping($r['url']);
			$latency = $ping->ping();
			// todo
			'ping_id' => $r['_id'],
			'record_id' => $r['record_id'],
			'created' => date,
			'latency' => ''

			$ping_stats->insert()*/
		}
	}

	/**
	 * @protected
	 */
	public function _secure() {
		if (php_sapi_name() !== 'cli') {
			self::redirect("/404");
		}
	}
}