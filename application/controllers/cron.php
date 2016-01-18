<?php

/**
 * Scheduler Class which executes daily and perfoms the initiated job
 * 
 * @author Faizan Ayubi
 */
use Framework\Registry as Registry;
use SEOstats\Services\Google as Google;
use Shared\SocialLinks as SocialLinks;

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
        $this->log("CRON Started");
        $this->_newsletters();
        $this->log("Newsletters Sent");
        $this->_serpRank();
        $this->_social();
        $this->log("Serp Done");
        $this->log("CRON Ended");
    }

    /**
     * Sends newsletter to User groups
     */
    protected function _newsletters() {
        $now = strftime("%Y-%m-%d", strtotime('now'));
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
                foreach ($results as $key => $value) {
                    array_push($emails, $value);
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

        $today = date('Y-m-d');
        $rank = Registry::get("MongoDB")->rank;
        foreach ($keywords as $k) {
            $record = $rank->findOne(array('keyword_id' => (int) $k->id, 'user_id' => (int) $k->user_id, 'created' => $today));
            if (!isset($record)) {
                $position = $this->_getRank($k);
                $doc = array(
                    'position' => ($position === false) ? 0 : (int) $position,
                    'keyword_id' => (int) $k->id,
                    'created' => $today,
                    'user_id' => (int) $k->user_id,
                    'live' => true
                );
                $rank->insert($doc);
            }

        }
    }

    protected function _getRank($keyword) {
        $return = false;
        $response = Google::getSerps($keyword->keyword, 200, $keyword->link);
        if ($response) {
            $response = array_shift($response);
            $return = $response["position"];
        }
        return $return;
    }

    protected function _social() {
        $keywords = Keyword::all(array("live = ?" => true, "serp = ?" => false));

        $today = date('Y-m-d');
        $socials = Registry::get("MongoDB")->socials;
        foreach ($keywords as $k) {
            $record = $socials->findOne(array('keyword_id' => (int) $k->id, 'user_id' => (int) $k->user_id, 'created' => $today));
            if (isset($record)) {
                continue;
            }
            $responses = $this->_getSocialStats($k->link);
            foreach ($responses as $r) {
                $doc = array(
                    'count_type' => $r["count_type"],
                    'count' => (string) $r["count"],
                    'social_media' => $r["social_media"],
                    'user_id' => (int) $k->user_id,
                    'live' => true,
                    'created' => $today,
                    'keyword_id' => (int) $k->id
                );
                $socials->insert($doc);
            }
        }

    }

    protected function _getSocialStats($url) {
        $social_stats = new SocialLinks($url);
        try {
            $responses = $social_stats->getResponses();
            return $responses;
        } catch (\Exception $e) {
            die($e->getMessage());
        }
    }

    /**
     * @protected
     */
    public function _secure() {
        if ($_SERVER['REMOTE_ADDR'] != $_SERVER['SERVER_ADDR']) {
            die('Path not found');
        }
    }
}