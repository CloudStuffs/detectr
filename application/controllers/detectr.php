<?php

/**
 * Description of analytics
 *
 * @author Faizan Ayubi
 */
use Framework\Registry as Registry;
use Framework\RequestMethods as RequestMethods;
use \Curl\Curl;

class Detectr extends Admin {

    protected function buildRedirect($url='') {
        return 'header("Location: '.$url.'")exit;';
    }
}
