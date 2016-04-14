<?php

/**
 * Description of analytics
 *
 * @author Faizan Ayubi
 */
use Framework\Registry as Registry;
use Framework\RequestMethods as RequestMethods;

class Analytics extends Auth {
    /**
     * @protected
     */
    public function _admin() {
        parent::_admin();
        $this->defaultLayout = "layouts/admin";
        $this->setLayout();
    }

    /**
     * @before _secure, _admin
     */
    public function logs($action = "", $name = "") {
        $this->seo(array("title" => "Activity Logs", "view" => $this->getLayoutView()));
        $view = $this->getActionView();

        if ($action == "unlink") {
            $file = APP_PATH ."/logs/". $name . ".txt";
            @unlink($file);
            $this->redirect("/analytics/logs");
        }

        $logs = array();
        $path = APP_PATH . "/logs";
        $iterator = new DirectoryIterator($path);

        foreach ($iterator as $item) {
            if (!$item->isDot() && substr($item->getFilename(), 0, 1) != ".") {
                $logs[] = $item->getFilename();
            }
        }
        arsort($logs);

        // find the directory size
        exec('du -h '. $path, $output, $return);
        if ($return == 0) {
            $output = array_pop($output);
            $output = explode("/", $output);
            $size = array_shift($output);
            $size = trim($size);
        } else {
            $size = 'Failed to get size';
        }
        $view->set("size", $size);
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
        if (!$website) {
            $this->redirect("/404");
        }

        $count = 0;
        $logs = Registry::get("MongoDB")->logs;
        $c = $logs->count(array('website_id' => (int) $website, 'user_id' => (int) $this->user->id));
        $count += $c;

        $view->set("count", $count)
            ->set("success", true);
    }

    /**
     * @before _secure
     */
    public function ping() {
        $this->JSONview();
        $view = $this->getActionView();

        $url = (RequestMethods::get("link"));
        if (!$url) {
            $this->redirect("/404");
        }

        $count = 0;
        $stats = Registry::get("MongoDB")->ping_stats;
        $ping = Registry::get("MongoDB")->ping;
        $record = $ping->findOne(array('url' => $url, 'user_id' => (int) $this->user->id));
        if (!$record) {
            $this->redirect("/404");
        }
        $count = $stats->count(array('ping_id' => $record['_id']));

        $cursor = $stats->find(array('ping_id' => $record['_id']));
        $cursor->sort(['created' => -1]);
        $cursor->limit(1);

        foreach ($cursor as $c) {
            $live = $c['latency'];
        }
        $count += $count;

        $view->set("count", $count)
            ->set("status", ($live === false) ? "down" : "up")
            ->set("success", true);
    }

    /**
     * @before _secure
     */
    public function detector() {
        $this->JSONview();
        $view = $this->getActionView();
        $analytics = array();

        $connection = new Mongo();
        $db = $connection->stats;
        $c = $db->logs;

        $countries = $c->distinct("user_location", array("user_id" => (int) $this->user->id));
        foreach ($countries as $key => $value) {
            $analytics[$value] = $c->count(array('user_location' => $value, "user_id" => (int) $this->user->id));
        }
        $view->set("analytics", $analytics);
    }

}
