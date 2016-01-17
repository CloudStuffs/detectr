<?php

/**
 * Description of markup
 *
 * @author Faizan Ayubi
 */
namespace Shared;
use Framework\RequestMethods as RequestMethods;
use WebBot\lib\WebBot\Bot as Bot;

class SocialLinks {
	/**
	 * @readwrite
	 */
	protected $_type;

	/**
	 * Stores the different social sites 
	 * @readwrite
	 */
	protected $_sites = array(
		"facebook" => "https://api.facebook.com/method/links.getStats?format=json&urls=",
		"google" => "https://plusone.google.com/_/+1/fastbutton?url=",
		"linkedin" => "https://www.linkedin.com/countserv/count/share?format=json&url=",
		"pinterest" => "http://api.pinterest.com/v1/urls/count.json?url=",
		"reddit" => "http://buttons.reddit.com/button_info.json?url=",
		"twitter" => null
	);

	public function __construct($type) {
		$type = strtolower($type);
		if (array_key_exists($type, $this->_sites)) {
			$this->_type = $type;
		} else {
			throw new \Exception("Social Type not found");
		}
	}

	public function getResponse($url) {
		$method = "_". $this->_type . "Response";
		$response = $this->$method;

	}

	private function _formatResponse($type, $val) {
		$response = array();
		$response["count_type"] = $type;
		$response["count"] = $val;

		return $response;
	}

	protected function _facebookResponse($url) {
		$body = $this->_makeRequest($url);

		$body = array_shift(json_decode($body));
		return $this->_formatResponse("like", $body["like_count"]);
	}

	protected function _googleResponse($url) {
		$doc = $this->_makeRequest($url, "returnDoc");
		$el = $doc->query('//*[@id="aggregateCount"]');

		$item =  $el->item(0);
		return $this->_formatResponse("plusOne", $item->nodeValue);
	}

	protected function _linkedinResponse($url) {
		$body = $this->_makeRequest($url);
		$body = json_decode($body);

		return $this->_formatResponse("count", $body["count"]);
	}

	protected function _pinterestResponse($url) {
		$body = $this->_makeRequest($url);
		$body = str_replace("receiveCount", "", $body);

		if (preg_match("/\((.*)\)/", $body, $matches)) {
			$response = json_decode($matches[1]);
			return $this->_formatResponse("count", $response["count"]);
		} else {
			throw new \Exception("Invalid Response sent by server");
		}
	}

	protected function _redditResponse($url) {
		$body = $this->_makeRequest($url);
		$body = json_decode($body);

		$response = $body["data"]["children"];
		$total = 0;
		if (count($response) > 0) {
			foreach ($response as $r) {
				if ($r["kind"] == "t3" && $r["data"]["url"] == $url && $r["data"]["score"] > $total) {
					$total = $r["data"]["score"];
				}
			}
		}
		return $this->_formatResponse("Link Stats", $total);
	}

	protected function _twitterResponse($url) {
		throw new \Exception("Twitter no longer gives response");
	}

	protected function _makeRequest($url, $returnDoc = false) {
		$type = $this->_type;
		$url = $this->_sites["type"] . $url;
		$bot = new Bot(array(
			"$type" => $url
		));

		$bot->execute();
		$doc = array_shift($bot->getDocuments());

		if ($returnDoc) {
			return $doc;
		}

		$body = $doc->getHttpResponse->getBody();
		return $body;
	}
}