<?php

/**
 * Description of marketing
 *
 * @author Faizan Ayubi, Hemant Mann
 */
use Framework\Registry as Registry;
use Framework\RequestMethods as RequestMethods;
use Framework\ArrayMethods as ArrayMethods;

class Marketing extends Admin {
    
    /**
     * @before _secure, _admin
     */
    public function createNewsletter() {
        $this->seo(array("title" => "Create Newsletter", "keywords" => "admin", "description" => "admin", "view" => $this->getLayoutView()));
        $view = $this->getActionView();

        $groups = \Group::all(array(), array("name", "id"));
        if (count($groups) == 0) {
            $this->_createGroup(array("name" => "All", "users" => json_encode(array("*"))));
            $groups = \Group::all(array(), array("name", "id"));
        }

        if (RequestMethods::post("action") == "createNewsletter") {
            $message = new Template(array(
                "subject" => RequestMethods::post("subject"),
                "body" => RequestMethods::post("body")
            ));
            $message->save();

            $newsletter = new Newsletter(array(
                "template_id" => $message->id,
                "group_id" => RequestMethods::post("user_group"),
                "scheduled" => RequestMethods::post("scheduled")
            ));
            $newsletter->save();

            $view->set("success", TRUE);
        }
        $view->set("groups", $groups);
    }

    /**
     * @before _secure, _admin
     */
    public function manageNewsletter() {
        $this->seo(array("title" => "Manage Newsletter", "keywords" => "admin", "description" => "admin", "view" => $this->getLayoutView()));
        $view = $this->getActionView();

        $page = Shared\Markup::page(array("model" => "Newsletter", "where" => array()));
        $newsletters = Newsletter::all(array(), array("*"), "created", "desc", $page["limit"], $page["page"]);

        $view->set($page);
        $view->set("newsletters", $newsletters);
    }

    /**
     * @before _secure, _admin
     */
    public function Template() {
        $this->seo(array("title" => "Message", "view" => $this->getLayoutView()));
        $view = $this->getActionView();

        if (RequestMethods::post("message")) {
            $emails = array();
            array_push($emails, RequestMethods::post("email"));

            $options = array(
                "template" => "blank",
                "subject" => RequestMethods::post("subject"),
                "message" => RequestMethods::post("message"),
                "emails" => $emails
            );
            $this->notify($options);
            $view->set("success", TRUE);
        }
    }

    /**
     * @before _secure, _admin
     */
    public function createPackage() {
        $this->seo(array("title" => "Create Package", "view" => $this->getLayoutView()));
        $view = $this->getActionView();

        $errors = array();
        if (RequestMethods::post("action") == "submitPackage") {
            $response = $this->_savePackage();
            if ($response["success"]) {
                $view->set("success", "Package is Saved Successfully");
            } else {
                $errors = $response["errors"];
            }
        }

        $items = Item::all(array(), array("id", "name"));
        $view->set("items", $items)
            ->set("errors", $errors);
    }

    /**
     * @before _secure, _admin
     */
    public function editPackage($package_id) {
        $package = Package::first(array("id = ?" => $package_id));
        if (!$package) {
            $this->redirect("/admin");
        }
        $this->seo(array("title" => "Create Package", "view" => $this->getLayoutView()));
        $view = $this->getActionView();

        $errors = array();
        if (RequestMethods::post("action") == "submitPackage") {
            $response = $this->_savePackage($package);
            if ($response["success"]) {
                $view->set("success", "Package is Updated");
            } else {
                $errors = $response["errors"];
            }
        }

        $items = Item::all(array(), array("id", "name"));
        $view->set("items", $items)
            ->set("package", $package)
            ->set("errors", $errors);
    }

    /**
     * @before _secure, _admin
     */
    public function managePackage() {
        $this->seo(array("title" => "Manage Package", "view" => $this->getLayoutView()));
        $view = $this->getActionView();

        $items = Item::all(array(), array("id", "name"));
        $setItems = array();
        foreach ($items as $i) {
            $setItems["$i->id"] = $i;
        }

        $page = Shared\Markup::page(array("model" => "Package", "where" => array()));
        $packages = Package::all(array(), array("*"), "created", "desc", $page["limit"], $page["page"]);
        
        $view->set("packages", $packages)
            ->set("items", $setItems)
            ->set($page);
    }

    /**
     * @before _secure, _admin
     */
    public function createGroup() {
        if (RequestMethods::post("action") == "createGroup") {
            $opts = array();
            $opts["name"] = RequestMethods::post("name");
            $opts["users"] = "";
            $group = $this->_createGroup($opts);
            if ($group) {
                $this->redirect("/marketing/groupMembers/{$group->id}");
            }
        }
    }

    /**
     * @before _secure, _admin
     */
    public function groupMembers($group_id) {
        $group = Group::first(array("id = ?" => $group_id));
        if (!$group || $group->name == "All") {
            $this->redirect("/admin");
        }

        $this->seo(array("title" => "Manage Group Members", "view" => $this->getLayoutView()));
        $view = $this->getActionView();

        $count = RequestMethods::get("count", 10);
        $group->users = json_decode($group->users);
        if ($count < count($group->users)) {
            $count = count($group->users) + 1;
        }
        $total = array();
        for ($i = 0; $i < $count; ++$i) {
            $total[] = $i;
        }

        if (RequestMethods::post("action") == "addMembers") {
            unset($_POST["action"]);
            $members = ArrayMethods::reArray($_POST);

            $members = json_encode($members);
            $group->users = $members;
            $group->save();
            $view->set("success", "Members were added for the group: $group->name");
        }
        $view->set("group", $group);
        $view->set("count", $total);
    }

    /**
     * @before _secure, _admin
     */
    public function manageGroups() {
        $this->seo(array("title" => "Manage Group Members", "view" => $this->getLayoutView()));
        $view = $this->getActionView();

        $page = Shared\Markup::page(array("model" => "Group", "where" => array()));
        $groups = Group::all();
        $view->set("groups", $groups)
            ->set($page);
    }

    protected function _savePackage($package = null) {
        if (!$package) {
            $package = new Package(array());
        }
        $package->name = RequestMethods::post("name");
        $package->item = json_encode(RequestMethods::post("items"));
        $package->price = RequestMethods::post("price");
        $package->tax = RequestMethods::post("tax");
        $package->user_id = $this->user->id;
        $package->period = RequestMethods::post("period", 30);

        if ($package->validate()) {
            $package->save();
            return array("success" => true, "package" => $package);       
        }
        return array("success" => false, "errors" => $package->errors);
    }

    protected function _createGroup($opts = array()) {
        $group = new Group(array(
            "name" => $opts["name"],
            "users" => $opts["users"]
        ));
        if ($group->validate()) {
            $group->save();
            return $group;
        }
        return false;
    }
}
