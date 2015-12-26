<?php

/**
 * Description of analytics
 *
 * @author Faizan Ayubi
 */
use Framework\Registry as Registry;
use Framework\RequestMethods as RequestMethods;
use \Curl\Curl;
use ClusterPoint\DB as DB;

class Analytics extends Admin {

    /**
     * @before _secure, changeLayout, _admin
     */
    public function logs() {
        $this->seo(array("title" => "Activity Logs", "view" => $this->getLayoutView()));
        $view = $this->getActionView();

        $logs = array();
        $path = APP_PATH . "/logs";
        $iterator = new DirectoryIterator($path);

        foreach ($iterator as $item) {
            if (!$item->isDot()) {
                array_push($logs, $item->getFilename());
            }
        }

        $view->set("logs", $logs);
    }

    /**
     * @before _secure
     */
    public function referer() {
        $this->JSONview();
        $view = $this->getActionView();
        $shortURL = RequestMethods::get("shortURL");
        
        $googl = Registry::get("googl");
        $object = $googl->analyticsClick($shortURL);
        $view->set("googl", $object);
    }

    public function trigger() {
        $this->JSONview();
        $view = $this->getActionView();


    }

    public function clusterpoint() {
        $count = 0;
        $clusterpoint = new DB();
        $query = "SELECT * FROM stats WHERE item_id == '{$this->item_id}' && user_id == '{$this->user_id}' LIMIT 0, 100";
        $results = $clusterpoint->index($query);
        if ($results) {
            foreach ($results as $result) {
                $count += $result->click;
            }
        }
        return $count;
    }

}
