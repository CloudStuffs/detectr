<?php

/**
 * Description of app
 *
 * @author Faizan Ayubi
 */
use Framework\Registry as Registry;
use Framework\RequestMethods as RequestMethods;

class Platform extends Member {

    /**
     * @before _secure, memberLayout
     */
    public function index() {
        $this->seo(array("title" => "Dashboard","view" => $this->getLayoutView()));
        $view = $this->getActionView();

        $websites = Website::all(array("user_id = ?" => $this->user->id));

        $view->set(array(
            "actions" => $this->actions,
            "trigs" => $this->triggers,
            "websites" => $websites
        ));
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
            $url = preg_replace('/^https?:\/\//', '', $url);
            $url = rtrim($url, "/");
            
            // Check if the domain already exists
            $website = Website::first(array("url = ?" => $url));
            if ($website) {
                $view->set("message", "Website Already exists");
            } else {
                $doc = array(
                    "title" => $name,
                    "url" => $url,
                    "user_id" => (int) $this->user->id,
                    "live" => true
                );
                $website = new Website($doc);
                $website->save();

                $collection = Registry::get("MongoDB")->selectCollection("website");
                $collection->insert(array_merge($doc, array('website_id' => (int) $website->id)));
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
            $url = preg_replace('/^https?:\/\//', '', $url);
            $url = rtrim($url, "/");

            $website->url = $url;
            $website->title = $title;
            $website->save();

            $collection = Registry::get("MongoDB")->selectCollection("website");
            $record = $collection->findOne(array('website_id' => (int) $website->id));
            if (isset($record)) {
                $collection->update(array('website_id' => (int) $website->id), array('$set' => array("title" => $website->title, "url" => $website->url)));
            }
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

        $mongo_db = Registry::get("MongoDB");
        $mongo_trigger = $mongo_db->selectCollection("triggers");
        $mongo_action = $mongo_db->selectCollection("actions");
        $hits = $mongo_db->selectCollection("hits");
        
        foreach ($trigger as $t) {
            $action = Action::first(array("trigger_id = ?" => $t->id));
            $mongo_trigger->remove(array('trigger_id' => (int) $t->id), array('justOne' => true));
            $mongo_action->remove(array('action_id' => (int) $action->id), array('justOne' => true));
            $hits->remove(array('trigger_id' => (int) $t->id, 'action_id' => (int) $action->id), array('justOne' => true));

            $this->delete('Action', $action->id, false);
            $this->delete('Trigger', $t->id, false);
        }

        $mongo_db->selectCollection("website")->remove(array('website_id' => (int) $website->id), array('justOne' => true));
        $this->delete('Website', $website->id);
    }

    /**
     * @before _secure, _admin
     */
    public function all() {
        $this->seo(array("title" => "Websites added", "keywords" => "admin", "description" => "admin", "view" => $this->getLayoutView()));
        $view = $this->getActionView();
        
        $page = Shared\Markup::page(array("model" => "Website", "where" => array()));
        
        $websites = \Website::all(array(), array("title", "url", "id", "created"), "created", "desc", $page["limit"], $page["page"]);
        
        $view->set("websites", $websites)
            ->set($page);
    }
}
