<?php

/**
 * Ping controller
 *
 * @author Shreyansh Goel
 */
use Framework\RequestMethods as RequestMethods;
use Framework\Registry as Registry;

class Ping extends Admin {

    /**
     * @before _secure, _admin
     */
    public function create($id = ''){
        
        $this->seo(array("title" => "Ping | Create","view" => $this->getLayoutView()));

        if(RequestMethods::post('hours')){

            $mongo = Registry::get('MongoDB');
            $website = $mongo->website;
            $website->update(array("website_id"=> (int) "$id"), 
                      array('$set'=>array("ping_live"=> (int) "1")));

            $hours = RequestMethods::post('hours');
            $min = RequestMethods::post('minutes');

            $total_min = ($hours * 60) + $min;

            $website->update(array("website_id"=> (int) "$id"), 
                  array('$set'=>array("ping_interval"=> (int) "$total_min")));

            self::redirect('/member/index');
        }
    }
	
    /**
     * @before _secure, _admin
     */
    public function edit($id){

        $this->seo(array("title" => "Ping | Edit","view" => $this->getLayoutView()));

        $mongo = Registry::get('MongoDB');
        $website = $mongo->website;

        $row = $website->findOne(array('website_id' => (int) $id);

        $interval = $row->ping_interval;
        if(!empty($interval)){
                
            if(RequestMethods::post('hours')){

                $website->update(array("website_id"=> (int) "$id"), 
                          array('$set'=>array("ping_live"=> (int) "1")));

                $hours = RequestMethods::post('hours');
                $min = RequestMethods::post('minutes');

                $total_min = ($hours * 60) + $min;

                $website->update(array("website_id"=> (int) "$id"), 
                      array('$set'=>array("ping_interval"=> (int) "$total_min")));

                self::redirect('/member/index');
            }
        }else{
            self::redirect('/member/index');
        }
    }

    /**
     * @before _secure, _admin
     */
    public function remove($id){

        $mongo = Registry::get('MongoDB');

        $website = $mongo->website;
        $website->update(array("website_id"=> (int) "$id"), 
                  array('$set'=>array("ping_live"=> (int) "0")));
    }

    public function execute($id='', $port = 80, $errno = 10){

        $mongo = Registry::get('MongoDB');
        $website = $mongo->website;
        $web_details = $website->findOne(array('website_id' => $id));


        $tB = microtime(true); 
        $fP = fSockOpen($web_details->url, $port, $errno); 
        if (!$fP) { 
            return "down"; 
        } 
            $tA = microtime(true); 
            return round((($tA - $tB) * 1000), 0)." ms";

        $ping = $mongo->ping;

        $ping->insert(array(
            'website_id' => $id));


    }

}