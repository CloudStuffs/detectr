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
    public function website() {
        $this->JSONview();
        $view = $this->getActionView();

        $website = RequestMethods::get("website");
        
        $count = 0;
        $logs = Registry::get("MongoDB")->logs;
        $c = $logs->count(array('website_id' => (int) $website));
        $count += $c;

        $view->set("count", $count)
            ->set("success", true);
    }

}
