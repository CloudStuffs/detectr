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
     * @before _secure, _admin, changeLayout
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
     * @before _secure, changeLayout
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
                "emails" => $emails,
                "delivery" => "mailgun"
            );
            $this->notify($options);
            $view->set("success", TRUE);
        }
    }

    /**
     * @before _secure, _admin, changeLayout
     */
    public function createPackage() {
        $this->seo(array("title" => "Create Package", "view" => $this->getLayoutView()));
        $view = $this->getActionView();

        if (RequestMethods::post("action") == "submitPackage") {
            $package = new Package();
            $this->_savePackage($package);
            $view->set("success", "Package is Saved Successfully");
        }

        $items = Item::all(array(), array("id", "name"));
        $view->set("items", $items);
    }

    /**
     * @before _secure, _admin, changeLayout
     */
    public function managePackage() {
        $this->seo(array("title" => "Manage Package", "view" => $this->getLayoutView()));
        $view = $this->getActionView();

        $packages = Package::all(array());
        $view->set("packages", $packages);
    }

    protected function _savePackage($package) {
        $package->name = RequestMethods::post("name");
        $package->item = json_encode(RequestMethods::post("items"));
        $package->price = RequestMethods::post("price");
        $package->tax = RequestMethods::post("tax");

        $package->save();
        return $package;
    }
}
