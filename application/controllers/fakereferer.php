<?php
/**
 * Description of fakereferer
 *
 * @author Faizan Ayubi
 */
use Framework\RequestMethods as RequestMethods;
use Framework\Registry as Registry;

class FakeReferer extends Admin {
	
	protected function customfakereferer() {
		$url = "urldecode";
	    if ($_POST["ref_spoof"] != NULL) {
	        $auth = $_POST["auth"] == "2799" ? 1 : 0;
	        if ($_POST["frverify"] == "1") {
	            echo $auth;
	            exit;
	        }
	        if ($auth == 1) {
	            $spoof = $url($_POST["ref_spoof"]);
	            echo "<!DOCTYPE HTML><meta charset=\"UTF-8\"><meta http-equiv=\"refresh\" content=\"1; url=http://example.com\"><script>window.location.href=\"" . $spoof . "\"</script><title>Page Redirection</title>If you are not redirected automatically, follow the <a href='" . $spoof . "'>link</a>";
	            exit;
	        }
	    }
	}

	
}