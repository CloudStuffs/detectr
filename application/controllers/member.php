<?php
/**
 * Description of auth
 *
 * @author Faizan Ayubi
 */
use Framework\RequestMethods as RequestMethods;
use Framework\Registry as Registry;

class Member extends Admin {
    
    /**
     * @before _secure, memberLayout
     */
    public function index() {
        $this->seo(array(
            "title" => "Dashboard",
            "view" => $this->getLayoutView()
        ));
        $view = $this->getActionView();
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
        $account = Account::first(array("user_id = ?" => $this->user->id));
        if(!$account) {
            $account = new Account();
        }
        
        if (RequestMethods::post('action') == 'saveUser') {
            $user = User::first(array("id = ?" => $this->user->id));
            $user->phone = RequestMethods::post('phone');
            $user->name = RequestMethods::post('name');
            $user->username = RequestMethods::post('username');
            $user->save();
            $view->set("success", true);
            $view->set("user", $user);
        }
        
        if (RequestMethods::get("action") == "saveAccount") {
            $account->user_id = $this->user->id;
            $account->name = RequestMethods::post("name");
            $account->bank = RequestMethods::post("bank");
            $account->number = RequestMethods::post("number");
            $account->ifsc = RequestMethods::post("ifsc");
            
            $account->save();
            $view->set("success", true);
        }
        
        $view->set("account", $account);
    }
        
    public function memberLayout() {
        $this->defaultLayout = "layouts/member";
        $this->setLayout();
    }
}
