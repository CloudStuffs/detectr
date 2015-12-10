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
        $this->seo(array("title" => "Dashboard","view" => $this->getLayoutView()));
        $view = $this->getActionView();

        $websites = Website::all(array("user_id = ?" => $this->user->id));

        $view->set("websites", $websites);
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

    /**
     * @before _secure, changeLayout
     */
    public function addWebsite() {
        $this->seo(array(
            "title" => "Create a Trigger for your website",
            "view" => $this->getLayoutView()
        ));
        $view = $this->getActionView();

        if (RequestMethods::post('action') == 'addWebsite') {
            $name = RequestMethods::post('name');
            $url = urlencode(RequestMethods::post('url'));

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
