<?php
/**
 * Description of Member
 *
 * @author Faizan Ayubi
 */
use Framework\RequestMethods as RequestMethods;
use Framework\Registry as Registry;

class Member extends Detector {
    
    /**
     * @before _secure, memberLayout
     */
    public function index() {
        $this->seo(array("title" => "Dashboard","view" => $this->getLayoutView()));
        $view = $this->getActionView();

        $websites = Website::all(array("user_id = ?" => $this->user->id), array("*"), "created", "desc", 10, 1);
        $referers = Referer::all(array("user_id = ?" => $this->user->id), array("*"), "created", "desc", 10, 1);

        $view->set(array(
            "actions" => $this->actions,
            "trigs" => $this->triggers,
            "websites" => $websites,
            "referers" => $referers
        ));
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
        $user = User::first(array("id = ?" => $this->user->id));

        switch (RequestMethods::post("action")) {
            case 'saveUser':
                $user->phone = RequestMethods::post('phone');
                $user->name = RequestMethods::post('name');
                $user->save();
                $view->set("success", true);
                break;

            case 'changePass':
                if (sha1(RequestMethods::post('oldpass')) == $user->password) {
                    $user->password = sha1(RequestMethods::post('newpass'));
                    $user->save();
                    $view->set("success", true);
                }
                break;
        }
        $this->setUser($user);
    }

    /**
     * @before _secure, _admin
     */
    public function all() {
        $this->seo(array("title" => "View Users Stats", "keywords" => "admin", "description" => "admin", "view" => $this->getLayoutView()));
        $view = $this->getActionView();
        
        $page = RequestMethods::get("page", 1);
        $limit = RequestMethods::get("limit", 10);
        $orderBy = RequestMethods::get("orderBy", "created");
        
        $users = \User::all(array(), array("*"), $orderBy, "desc", $limit, $page);
        $total = \User::count();
        
        $view->set(array(
            "count" => $total,
            "results" => $users,
            "limit" => $limit,
            "page" => (int) $page
        ));
    }

    /**
     * @before _secure, _admin
     */
    public function websites($user_id) {
        $this->seo(array("title" => "View Users Websites", "keywords" => "admin", "description" => "admin", "view" => $this->getLayoutView()));
        $view = $this->getActionView();
        
        $websites = Website::all(array("user_id = ?" => $user_id));
        $view->set("websites", $websites);
    }

    /**
     * @before _secure, memberLayout
     */
    public function subscriptions() {
        $this->seo(array("title" => "Subscriptions", "keywords" => "admin", "description" => "admin", "view" => $this->getLayoutView()));
        $view = $this->getActionView();
        
        $subscriptions = Subscription::all(array("user_id = ?" => $this->user->id), array("item_id", "created", "expiry", "period"));
        $view->set("subs", $subscriptions);
    }

    /**
     * @before _secure, _admin
     */
    public function subscribed() {
        $this->seo(array("title" => "Subscriptions", "keywords" => "admin", "description" => "admin", "view" => $this->getLayoutView()));
        $view = $this->getActionView();
        
        $subscriptions = Subscription::all(array("user_id = ?" => $user_id), array("item_id", "created", "expiry"));
        $view->set("subscriptions", $subscriptions);
    }
}
