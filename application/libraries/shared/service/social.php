<?php

namespace Shared\Service;

use Framework\Registry as Registry;
use Shared\SocialLinks as SocialLinks;

/**
 * Static class to save social link stats in mongodb
 */
class Social {
	public static function record($k) {
		$socials = Registry::get("MongoDB")->socials;

		$today = new \MongoDate(strtotime(date('Y-m-d')));
		$record = $socials->findOne(array(
			'keyword_id' => (int) $k->id, 
			'user_id' => (int) $k->user_id, 
			'created' => $today
		));

		if (isset($record)) {
		    return false;
		}

		$responses = self::getStats($k->link);
		foreach ($responses as $r) {
		    $doc = array(
		        'count_type' => $r["count_type"],
		        'count' => (string) $r["count"],
		        'social_media' => $r["social_media"],
		        'user_id' => (int) $k->user_id,
		        'live' => true,
		        'created' => $today,
		        'keyword_id' => (int) $k->id
		    );
		    $socials->insert($doc);
		}
	}

	private static function getStats($url) {
		$social_stats = new SocialLinks($url);
        $responses = $social_stats->getResponses();
        return $responses;
	}
}
