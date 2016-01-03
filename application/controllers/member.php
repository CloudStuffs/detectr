<?php
/**
 * Description of Member
 *
 * @author Faizan Ayubi
 */
use Framework\RequestMethods as RequestMethods;
use Framework\Registry as Registry;

class Member extends Detectr {
    
    /**
     * @before _secure, memberLayout
     */
    public function index() {
        $this->seo(array("title" => "Dashboard","view" => $this->getLayoutView()));
        $view = $this->getActionView();

        $websites = Website::all(array("user_id = ?" => $this->user->id), array("*"), "created", "desc", 10, 1);
        $referers = Referer::all(array("user_id = ?" => $this->user->id), array("*"), "created", "desc", 10, 1);

        $view->set('actions', $this->actions);
        $view->set('trigs', $this->triggers);
        $view->set("websites", $websites);
        $view->set("referers", $referers);
    }
    
    /**
     * @before _secure, memberLayout
     */
    public function profile() {
        $this->seo(array(
            "title" => "Profile",
            "view" => $this->getLayoutView()
        ));
        $view = $this->getActionView();
        
        if (RequestMethods::post('action') == 'saveUser') {
            $user = User::first(array("id = ?" => $this->user->id));
            $user->phone = RequestMethods::post('phone');
            $user->name = RequestMethods::post('name');
            $user->save();
            $view->set("success", true);
            $this->setUser($user);
        }
    }

    /**
     * @before _secure, changeLayout, _admin
     */
    public function all() {
        $this->seo(array("title" => "View Users Stats", "keywords" => "admin", "description" => "admin", "view" => $this->getLayoutView()));
        $view = $this->getActionView();
        
        $page = RequestMethods::get("page", 1);
        $limit = RequestMethods::get("limit", 10);
        $orderBy = RequestMethods::get("orderBy", "created");
        
        $users = \User::all(array(), array("*"), $orderBy, "desc", $limit, $page);
        $total = \User::count();
        
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

    /**
     * @before _secure, changeLayout, _admin
     */
    public function subscriptions() {
        $this->seo(array("title" => "Subscriptions", "keywords" => "admin", "description" => "admin", "view" => $this->getLayoutView()));
        $view = $this->getActionView();
        
        $subscriptions = Subscription::all(array("user_id = ?" => $user_id));
        $view->set("subscriptions", $subscriptions);
    }
}
