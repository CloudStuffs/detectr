<?php

/**
 * Description of analytics
 *
 * @author Faizan Ayubi
 */
use Framework\Registry as Registry;
use Framework\RequestMethods as RequestMethods;

class Analytics extends Admin {

    /**
     * @before _secure, _admin
     */
    public function logs($action = "", $name = "") {
        $this->seo(array("title" => "Activity Logs", "view" => $this->getLayoutView()));
        $view = $this->getActionView();

        if ($action == "unlink") {
            $file = APP_PATH ."/logs/". $name . ".txt";
            @unlink($file);
            self::redirect("/analytics/logs");
        }

        $logs = array();
        $path = APP_PATH . "/logs";
        try {
            $iterator = new DirectoryIterator($path);

            foreach ($iterator as $item) {
                if (!$item->isDot()) {
                    array_push($logs, $item->getFilename());
                }
            }
        } catch (\Exception $e) {
            $logs = array();
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

    /**
     * @before _secure
     */
    public function trigger() {
        $this->JSONview();
        $view = $this->getActionView();

        $trigger_id = RequestMethods::get("trigger");
        $action_id = RequestMethods::get("action");
        
        $count = 0;
        $hits = Registry::get("MongoDB")->hits;
        $record = $hits->findOne(array('trigger_id' => (int) $trigger_id, 'action_id' => (int) $action_id));
        if (isset($record)) {
            $count += $record['count'];
        }

        $view->set("count", $count)
            ->set("success", true);
    }

}
