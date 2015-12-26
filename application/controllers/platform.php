<?php

/**
 * Description of app
 *
 * @author Faizan Ayubi
 */
use Framework\Registry as Registry;
use Framework\RequestMethods as RequestMethods;
use \Curl\Curl;

class Platform extends Member {

    /**
     * @before _secure, memberLayout
     */
    public function index() {
        $this->seo(array("title" => "Dashboard","view" => $this->getLayoutView()));
        $view = $this->getActionView();

        $websites = Website::all(array("user_id = ?" => $this->user->id));

        $view->set("websites", $websites);
    }

    /**
     * @before _secure, memberLayout
     */
    public function add() {
        $this->seo(array(
            "title" => "Create a Trigger for your website",
            "view" => $this->getLayoutView()
        ));
        $view = $this->getActionView();

        if (RequestMethods::post('action') == 'addWebsite') {
            $name = RequestMethods::post('name');
            $url = RequestMethods::post('url');
            $url = preg_replace('/^http:\/\//', '', $url);
            
            // Check if the domain already exists
            $website = Website::first(array("url = ?" => $url));
            if ($website) {
                $view->set("message", "Website Already exists");
            } else {
                $website = new Website(array(
                    "title" => $name,
                    "url" => $url,
                    "user_id" => $this->user->id
                ));
                $website->save();
                $view->set("message", "Website Added Successfully");    
            }
        }
    }

    /**
     * @before _secure, memberLayout
     */
    public function edit($id) {
        if (!$id) {
            self::redirect("/member");
        }
        $website = Website::first(array("id = ?" => $id));
        $this->_authority($website);
        $this->seo(array(
            "title" => "Edit your website",
            "view" => $this->getLayoutView()
        ));
        $view = $this->getActionView();

        if (RequestMethods::post('action') == 'editWebsite') {
            $title = RequestMethods::post('name');
            $url = RequestMethods::post('url');
            $url = preg_replace('/^http:\/\//', '', $url);

            $website->url = $url;
            $website->title = $title;
            $website->save();
            $view->set("message", "Website Changed Successfully");
        }
        $view->set('website', $website);
    }

    /**
     * @before _secure, memberLayout
     */
    public function removeWebsite($id) {
        $this->noview();

        $website = Website::first(array("id = ?" => $id));
        $this->_authority($website);
        $trigger = Trigger::all(array("website_id = ?" => $website->id, "user_id = ?" => $this->user->id));
        foreach ($trigger as $t) {
            $action = Action::first(array("trigger_id = ?" => $t->id));
            $this->delete('Action', $action->id, false);
            $this->delete('Trigger', $t->id, false);
        }
        $this->delete('Website', $website->id);
    }

    /**
     * @before _secure, changeLayout, _admin
     */
    public function all() {
        $this->seo(array("title" => "Websites added", "keywords" => "admin", "description" => "admin", "view" => $this->getLayoutView()));
        $view = $this->getActionView();
        
        $page = RequestMethods::get("page", 1);
        $limit = RequestMethods::get("limit", 10);
        
        $websites = \Website::all(array(), array("title", "url", "id", "created"), "created", "desc", $limit, $page);
        $count = count($users);
        
        $view->set('count', $count);
        $view->set("websites", $websites);
        $view->set("limit", $limit);
        $view->set("page", (int)$page);
    }
}
