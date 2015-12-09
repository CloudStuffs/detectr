<?php
/**
 * Description of Member
 *
 * @author Faizan Ayubi
 */
use Framework\RequestMethods as RequestMethods;
use Framework\Registry as Registry;

class Member extends Admin {
    
    /**
     * @before _secure, changeLayout, _admin
     */
    public function index() {
        $this->seo(array(
            "title" => "Dashboard",
            "view" => $this->getLayoutView()
        ));
        $view = $this->getActionView();
    }
    
    /**
     * @before _secure, changeLayout, _admin
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
            $user->username = RequestMethods::post('username');
            $user->save();
            $view->set("success", true);
            $view->set("user", $user);
        }
    }
}
