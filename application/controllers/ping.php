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
     * @before _secure, memberLayout
     */
    public function create(){
        
        $this->seo(array("title" => "Ping | Create","view" => $this->getLayoutView()));
        $view = $this->getActionView();

        if(RequestMethods::post('title')){

            $mongo = Registry::get('MongoDB');
            $ping = $mongo->ping;
            $time = strtotime(date('d-m-Y H:i:s'));
            $mongo_date = new MongoDate($time);

            $count = $ping->count();
            $ping->insert(array(
                "user_id" => (int) $this->user->id,
                "record_id" => $count + 1,
                "title" => RequestMethods::post('title'),
                "url" => RequestMethods::post('url'),
                "interval" => RequestMethods::post('interval'),
                "live" => (int) "1",
                "created" => $mongo_date,
                ));

            $view->set('success', 'Ping Created Successfully');
        }
    }
	
    /**
     * @before _secure, memberLayout
     */
    public function edit($id){

        $this->seo(array("title" => "Ping | Edit","view" => $this->getLayoutView()));

        $mongo = Registry::get('MongoDB');
        $ping = $mongo->ping;
        $view = $this->getActionView();

        $record = $ping->findOne(array('record_id' => (int) $id));

        if(!empty($record['url'])){
                
            if(RequestMethods::post('title')){

                $time = strtotime(date('d-m-Y H:i:s'));
                $mongo_date = new MongoDate($time);

                $ping->update(array("record_id"=> (int) $id), 
                  array('$set'=>array(
                    "title" => RequestMethods::post('title'),
                    "interval" => RequestMethods::post('interval'),
                    "modified" => $mongo_date,
                    )));

                self::redirect('/ping/manage');
            }else{

                $view->set('title', $record['title'])->set('url', $record['url'])->set('interval', $record['interval']);
            }
        }else{
            self::redirect('/member/index');
        }
    }

    /**
     * @before _secure, memberLayout
     */

    public function manage(){

        $this->seo(array("title" => "Ping | Manage","view" => $this->getLayoutView()));

        $mongo = Registry::get('MongoDB');
        $ping = $mongo->ping;
        $view = $this->getActionView();

        $records = $ping->find(array('live' => 1));
        $result = array();
        foreach ($records as $r) {
            $result[] = $r;
        }
        $count = $ping->count(array('live' => 1));
        $view->set('records', $result)->set('count', $count);

    }
/*
    public function hits(){
        $url, $user_id
        $view->set('c')
    }
*/
    /**
     * @before _secure, memberLayout
     */
    public function remove($id){

        $mongo = Registry::get('MongoDB');

        $ping = $mongo->ping;
        $ping->update(array("record_id" => (int) $id), 
                  array('$set'=>array("live" => 0)));

        self::redirect('/ping/manage');

    }
    
    /**
     * @before _secure, memberLayout
     */

    public function execute($id='', $port = 80, $errno = 10){

        $mongo = Registry::get('MongoDB');
        $website = $mongo->website;
        $web_details = $website->findOne(array('website_id' => $id));


        $tB = microtime(true); 
        $fP = fSockOpen($web_details['url'], $port, $errno); 
        if (!$fP) { 
            return "down"; 
        } 
            $tA = microtime(true); 
            $rs =round((($tA - $tB) * 1000), 0)." ms";

        $ping = $mongo->ping;

        $ping->insert(array(
            'website_id' => $id,
            'respnse_time' => $rs));


    }

}