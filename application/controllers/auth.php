<?php
/**
 * Description of auth
 *
 * @author Faizan Ayubi
 */
use Shared\Controller as Controller;
use Framework\RequestMethods as RequestMethods;
use Framework\Registry as Registry;
use Shared\CloudMail as Mail;

class Auth extends Plan {
    
    /**
     * @before _session
     */
    public function login() {
        $this->seo(array("title" => "Login", "view" => $this->getLayoutView()));
        $view = $this->getActionView();

        if (RequestMethods::get("action") == "reset") {
            $exist = User::first(array("email = ?" => RequestMethods::get("email")), array("id", "email", "name"));
            if ($exist) {
                $this->notify(array(
                    "template" => "forgotPassword",
                    "subject" => "New Password Requested",
                    "user" => $exist
                ));

                $view->set("message", "Password Reset Email Sent Check Your Email. Check in Spam too.");
            }
        }
        
        if (RequestMethods::post("action") == "login") {
            $email = RequestMethods::post("email");
            $exist = User::first(array("email = ?" => $email), array("id"));
            if($exist) {
                $user = User::first(array(
                    "email = ?" => $email,
                    "password = ?" => sha1(RequestMethods::post("password"))
                ));
                if($user) {
                    if ($user->live) {
                        $this->session($user);
                        self::redirect('/member/index.html');
                    } else {
                        $view->set("message", "Invalid login or user blocked");
                    }
                } else{
                    $view->set("message", 'Invalid login credentials, Try again or <a href="/auth/login?action=reset&email='.$email.'">Reset Password</a>');
                }
            } else {
                $view->set("message", 'User doesnot exist. Please signup <a href="/auth/register">here</a>');
            }
        }
    }
    
    /**
     * @before _session
     */
    public function register($package_id = NULL) {
        if (!$package_id) {
            self::redirect('/packages');
        }
        $this->seo(array("title" => "Register", "view" => $this->getLayoutView()));
        $view = $this->getActionView();
        
        if (RequestMethods::post("action") == "register") {
            $exist = User::first(array("email = ?" => RequestMethods::post("email")));
            if (!$exist) {
                $user = new User(array(
                    "name" => RequestMethods::post("name"),
                    "email" => RequestMethods::post("email"),
                    "password" => sha1(RequestMethods::post("password")),
                    "phone" => RequestMethods::post("phone"),
                    "paypal" => RequestMethods::post("paypal"),
                    "admin" => false,
                    "live" => false
                ));
                $user->save();
                $this->notify(array(
                    "template" => "register",
                    "subject" => "Welcome to TrafficMonitor.ca",
                    "user" => $user
                ));
                $this->pay($package_id, $user);
                $view->set("message", "Your account has been created and will be activate within 3 hours after verification.");
            } else {
                $view->set("message", 'Email exists, login from <a href="/auth/login">here</a>');
            }
        }
    }

    public function forgotpassword() {
        $this->seo(array("title" => "Register", "view" => $this->getLayoutView()));
        $view = $this->getActionView();

        if (RequestMethods::post("action") == "change") {
            $token = RequestMethods::post("token");
            $id = base64_decode($token);
            $user = User::first(array("id = ?" => $id));
            if(RequestMethods::post("password") == RequestMethods::post("cpassword")) {
                $user->password = sha1(RequestMethods::post("password"));
                $user->save();
                $this->session($user);
                self::redirect("/member");
            } else{
                $view->set("message", 'Password Does not match');
            }
        }
        if (RequestMethods::get("action") == "reset") {
            $token = RequestMethods::get("token");
            $id = base64_decode($token);
            $exist = User::first(array("id = ?" => $id), array("id"));
            if($exist) {
                $view->set("token", $token);
            } else{
                $view->set("message", 'Something Went Wrong please contact admin');
            }
        }
    }
    
    protected function getBody($options) {
        $template = $options["template"];
        $view = new Framework\View(array(
            "file" => APP_PATH . "/application/views/layouts/email/{$template}.html"
        ));
        foreach ($options as $key => $value) {
            $view->set($key, $value);
            $$key = $value;
        }

        return $view->render();
    }
    
    protected function notify($options) {
        $body = $this->getBody($options);
        $emails = isset($options["emails"]) ? $options["emails"] : array($options["user"]->email);

        $params = array();
        $params["to"] = $emails;
        $params["subject"] = $options["subject"];
        $params["body"] = $body;
        
        $mail = new Mail($params);
        
    }

    public function memberLayout() {
        $this->defaultLayout = "layouts/member";
        $this->setLayout();
    }

    public function render() {
        $session = Registry::get("session");
        $subscriptions = $session->get("subscriptions");
        if ($this->actionView) {
            $this->actionView->set("subscriptions", $subscriptions);
        }

        if ($this->layoutView) {
            $this->layoutView->set("subscriptions", $subscriptions);
        }
        parent::render();
    }

    protected function _authority($model) {
        if (!$model) {
            $redirect = true;
        }
        if ($model->user_id != $this->user->id) {
            if ($this->user->admin) {
                $redirect = false;
            } else {
                $redirect = true;
            }
        }
        if ($redirect) {
            self::redirect("/member");
        }
    }

    /**
     * @protected
     */
    public function _check() {
        $session = Registry::get("session");
        $subscriptions = $session->get("subscriptions");
        if (!in_array(get_class($this), $subscriptions)) {
            die('Not Subscrbed');
        }
    }
}
