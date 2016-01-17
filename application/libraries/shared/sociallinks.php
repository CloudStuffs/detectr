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
	protected $_url;

	/**
	 * @readwrite
	 */
	protected $_links;

	/**
	 * Stores the different social sites 
	 * @readwrite
	 */
	protected $_sites = array(
		"facebook" => "https://api.facebook.com/method/links.getStats?format=json&urls=",
		"google" => "https://plusone.google.com/_/+1/fastbutton?url=",
		"linkedin" => "https://www.linkedin.com/countserv/count/share?format=json&url=",
		"pinterest" => "http://api.pinterest.com/v1/urls/count.json?url=",
		"reddit" => "https://www.reddit.com/api/info.json?url="
	);

	public function __construct($url) {
		$this->_url = $url;

		$links = array();
		foreach ($this->_sites as $key => $value) {
			$links[$key] = $value . $this->_url;
		}
		$this->_links = $links;
	}

	public function getResponses() {
		$responses = array();
		$documents = $this->_makeRequest();

		foreach ($documents as $doc) {
			$method = "_". $doc->id . "Response";
			$response = call_user_func_array(array($this, $method), array($doc));

			$responses[] = array(
				"social_media" => $doc->id,
				"count_type" => $response["count_type"],
				"count" => $response["count"]
			);
		}
		return $responses;
	}

	private function _formatResponse($type, $val) {
		$response = array();
		$response["count_type"] = $type;
		$response["count"] = $val;

		return $response;
	}

	protected function _facebookResponse($doc) {
		$body = array_shift(json_decode($doc->getBody()));
		return $this->_formatResponse("like", $body->like_count);
	}

	protected function _googleResponse($doc) {
		$el = $doc->query('//*[@id="aggregateCount"]');

		$item =  $el->item(0);
		return $this->_formatResponse("plusOne", $item->nodeValue);
	}

	protected function _linkedinResponse($doc) {
		$body = json_decode($doc->getBody());

		return $this->_formatResponse("count", $body->count);
	}

	protected function _pinterestResponse($doc) {
		$body = str_replace("receiveCount", "", $doc->getBody());

		if (preg_match("/\((.*)\)/", $body, $matches)) {
			$response = json_decode($matches[1]);
			return $this->_formatResponse("count", $response->count);
		} else {
			throw new \Exception("Invalid Response sent by server");
		}
	}

	protected function _redditResponse($doc) {
		$body = json_decode($doc->getBody());

		$response = $body->data->children;
		$total = 0;
		if (count($response) > 0) {
			foreach ($response as $r) {
				if ($r->kind == "t3" && $r->data->score > $total) {
					$total = $r->data->score;
				}
			}
		}
		return $this->_formatResponse("Link Stats", $total);
	}

	protected function _makeRequest() {
		$bot = new Bot($this->_links);

		$bot->execute();
		$docs = $bot->getDocuments();
		return $docs;
	}
}
