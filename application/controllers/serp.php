<?php
/**
 * Description of serp
 *
 * @author Hemant Mann
 */
use Framework\RequestMethods as RequestMethods;
use Framework\Registry as Registry;
use Framework\ArrayMethods as ArrayMethods;

class Serp extends Admin {
	public function create() {
		if (RequestMethods::post("action") == "createSerp") {
			$domain = RequestMethods::post("domain");
			$keyword = RequestMethods::post("keyword");

			//$results = \SEOstats\Services\Google::getSerps($keyword, 100);
			// @do something with the results
		}
	}
}