<?php

namespace Shared\Service;
use Framework\Registry as Registry;

/**
 * Static class to save serp results in mongodb
 */
class Serp {
	public static function record($keywords = array(), $cron = false) {
		$rank = Registry::get("MongoDB")->rank;

		$today = date('Y-m-d');
		$count = count($keywords);
		if ($count == 1 && !$cron) {
			$k = array_shift($keywords);
			$k = self::makeObject($k);

			$record = $rank->findOne(array(
				'keyword_id' => (int) $k->keyword_id,
				'user_id' => (int) $k->user_id,
				'created' => $today
			));

			if (isset($record)) {
				return;
			}

			$file = dirname(__FILE__) . '/' . uniqid() . '.json';
			self::execute($file, array($k), $file);
		} elseif ($count >= 1 && $cron) {
			self::execute(APP_PATH . '/logs/serpRank.json', $keywords);
		}
	}

	private static function execute($file, $content, $cmd = '') {
		if (file_put_contents($file, json_encode($content)) === false) {
			throw new \Exception("Unable to write content file for -----SERP stats----", 1);
		}
		
		exec('node '. APP_PATH.'/application/libraries/NodeSEO/index.js ' . $cmd);
		unlink($file);
	}

	private static function makeObject($k) {
		$obj = array(
			"keyword_id" => (int) $k->id,
			"user_id" => (int) $k->user_id,
			"keyword" => $k->keyword,
			"link" => $k->link
		);
		return $obj;
	}
}
