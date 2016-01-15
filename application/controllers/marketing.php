<?php

/**
 * Description of marketing
 *
 * @author Faizan Ayubi, Hemant Mann
 */
use Framework\Registry as Registry;
use Framework\RequestMethods as RequestMethods;

class Marketing extends Admin {
    
    /**
     * @before _secure, _admin
     */
    public function createNewsletter() {
        $this->seo(array("title" => "Create Newsletter", "keywords" => "admin", "description" => "admin", "view" => $this->getLayoutView()));
        $view = $this->getActionView();

        $groups = \Group::all(array(), array("name", "id"));

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
     * @before _secure, changeLayout
     */
    public function manageNewsletter() {
        $this->seo(array("title" => "Manage Newsletter", "keywords" => "admin", "description" => "admin", "view" => $this->getLayoutView()));
        $view = $this->getActionView();

        $page = RequestMethods::get("page", 1);
        $limit = RequestMethods::get("limit", 10);
        $newsletters = Newsletter::all(array(), array("*"), "created", "desc", $limit, $page);

        $view->set("limit", $limit);
        $view->set("page", $page);
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
            self::redirect("/admin");
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

        $limit = RequestMethods::get("limit", 20);
        $page = RequestMethods::get("page", 1);
        $count = Package::count();

        $packages = Package::all(array(), array("*"), "created", "desc", $limit, $page);
        
        $view->set("packages", $packages)
            ->set("items", $setItems)
            ->set("count", $count)
            ->set("limit", $limit)
            ->set("page", $page);
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

        if ($package->validate()) {
            $package->save();
            return array("success" => true, "package" => $package);       
        }
        return array("success" => false, "errors" => $package->errors);
    }
}
