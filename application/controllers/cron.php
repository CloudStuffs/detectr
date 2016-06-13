<?php

/**
 * Scheduler Class which executes daily and perfoms the initiated job
 * 
 * @author Faizan Ayubi, Hemant Mann
 */
use Framework\Registry as Registry;
use \SEOstats\Services\Google as Google;

class CRON extends Auth {

    public function __construct($options = array()) {
        parent::__construct($options);
        $this->willRenderLayoutView = false;
        $this->willRenderActionView = false;
    }

    /**
     * @before _secure
     */
    public function index() {
        try {
            $this->log("CRON Started");
            $this->_newsletters();
            $this->log("Newsletters Sent");
            $this->_removeLogs();
            $this->log("Removed older Logs + obsolete records");

            $this->_serpRank();
            $this->log("Serp Done");

            $this->_social();
            $this->log("Social Stats Done");

            $this->log("CRON Ended");
        } catch (\Exception $e) {
            $this->log(print_r($e));
        }
    }

    /**
     * Sends newsletter to User groups
     */
    protected function _newsletters() {
        $now = date('Y-m-d');
        $emails = array();
        $newsletters = Newsletter::all(array("scheduled = ?" => $now), array("template_id", "group_id"));
        foreach ($newsletters as $n) {
            $template = Template::first(array("id = ?" => $n->template_id));
            $group = Group::first(array("id = ?" => $n->group_id), array("users"));
            $results = json_decode($group->users);

            if (count($results) == 1 && $results[0] == "*") {
                $users = User::all(array(), array("email"));
                foreach ($users as $user) {
                    array_push($emails, $user->email);
                }
            } else {
                foreach ($results as $r) {
                    array_push($emails, $r->email);
                }
            }

            $batches = array_chunk($emails, 100);
            foreach ($batches as $batch) {
                $e = implode(",", $batch);
                $this->notify(array(
                    "template" => "newsletter",
                    "subject" => $template->subject,
                    "message" => $template->body,
                    "track" => true,
                    "emails" => $e
                ));
            }
        }
    }

    /**
     * Check SERP stats
     * Error: Showing MySql server has gone away,
     * Possible solutions: Add Limit + page (2nd store in mongoDB)
     */
    protected function _serpRank() {
        try {
            $keywords = Keyword::all(array("live = ?" => true, "serp = ?" => true), array("id", "user_id", "keyword", "link"));

            $arr = array();
            foreach ($keywords as $k) {
                Shared\Service\Serp::record([$k]);
                sleep(30); // sleep 30 seconds for every crawl
            }
        } catch (\Exception $e) {
            $this->log($e->getMessage());
        }
    }

    protected function _social() {
        try {
            $keywords = Keyword::all(array("live = ?" => true, "serp = ?" => false), array("id", "user_id", "link"));
            foreach ($keywords as $k) {
                Shared\Service\Social::record($k);
                sleep(2); // to prevent bandwidth load
            }
        } catch (\Exception $e) {
            $this->log(print_r($e));
            $this->log("Error in getting Social Link Stats");
        }
    }

    protected function _removeLogs() {
        $mongoDB = Registry::get("MongoDB");
        $logs = $mongoDB->logs;

        $date = strtotime("-20 day");
        $date = new \MongoDate($date);
        $logs->remove(array(
            'created' => array('$lte' => $date)
        ));

        $ping_stats = $mongoDB->ping_stats;
        $pings = $mongoDB->ping;

        // minutely - should be removed more than 2 days old
        $find = $pings->find(array("interval" => "Minutely"));
        $date = new \MongoDate(strtotime("-1 day"));
        foreach ($find as $f) {
            $ping_stats->remove(array(
                'ping_id' => $f['_id'],
                'created' => array('$lte' => $date)
            ));
        }

        // else remove more than 4 days old
        $date = new \MongoDate(strtotime("-4 day"));
        $ping_stats->remove(array(
            'created' => array('$lte' => $date)
        ));
    }

    /**
     * @protected
     */
    public function _secure() {
        if (php_sapi_name() !== 'cli') {
            $this->redirect("/404");
        }
    }
}
