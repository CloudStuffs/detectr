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
            $this->_serpRank();
            $this->log("Serp Done");
            $this->_social();
            $this->log("Social Stats Done");
            $this->_removeLogs();
            $this->log("Removed older Logs + obsolete records");
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
        $newsletters = Newsletter::all(array("scheduled = ?" => $now));
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
     */
    protected function _serpRank() {
        $keywords = Keyword::all(array("live = ?" => true, "serp = ?" => true));

        $arr = array();
        foreach ($keywords as $k) {
            $arr[] = array(
                "keyword_id" => (int) $k->id,
                "user_id" => (int) $k->user_id,
                "keyword" => $k->keyword,
                "link" => $k->link
            );
        }
        try {
            Shared\Service\Serp::record($arr, true);
        } catch (\Exception $e) {
            $this->log($e->getMessage());
        }
    }

    protected function _social() {
        try {
            $keywords = Keyword::all(array("live = ?" => true, "serp = ?" => false));
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
        $logs = Registry::get("MongoDB")->logs;

        $date = strtotime("-20 day");
        $date = new \MongoDate($date);
        $logs->remove(array(
            'created' => array('$lte' => $date)
        ));

        $ping_stats = Registry::get("MongoDB")->ping_stats;
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
