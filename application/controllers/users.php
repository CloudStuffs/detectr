<?php
/**
 * The Users controller
 *
 * @author Hemant Mann
 */
use Framework\RequestMethods as RequestMethods;
use Framework\Registry as Registry;

class Users extends Admin {
    /**
     * @before _secure, changeLayout, _admin
     */
    public function stats() {
        $this->seo(array("title" => "View Users Stats", "keywords" => "admin", "description" => "admin", "view" => $this->getLayoutView()));
        $view = $this->getActionView();
        
        $page = RequestMethods::get("page", 1);
        $limit = RequestMethods::get("limit", 10);
        $orderBy = RequestMethods::get("orderBy", "created");
        
        $users = \User::all(array(), array("*"), $orderBy, "desc", $limit, $page);
        $total = count($users);
        
        $view->set('count', $total);
        $view->set("results", $users);
        $view->set("limit", $limit);
        $view->set("page", (int)$page);
    }
    
    /**
     * @before _secure, changeLayout, _admin
     */
    public function websites($user_id) {
        $this->seo(array("title" => "View Users Websites", "keywords" => "admin", "description" => "admin", "view" => $this->getLayoutView()));
        $view = $this->getActionView();
        
        $websites = Website::all(array("user_id = ?" => $user_id));
        $view->set("websites", $websites);
    }
}
