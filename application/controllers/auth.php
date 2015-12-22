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

class Auth extends Controller {
    
    /**
     * @before _session
     */
    public function login() {
        $this->defaultLayout = "layouts/blank";
        $this->setLayout();
        $this->seo(array(
            "title" => "Login",
            "view" => $this->getLayoutView()
        ));
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
            $exist = User::first(array("email = ?" => RequestMethods::post("email")), array("id"));
            if($exist) {
                $user = User::first(array(
                    "email = ?" => RequestMethods::post("email"),
                    "password = ?" => sha1(RequestMethods::post("password"))
                ));
                if($user) {
                    if ($user->live) {
                        $this->setUser($user);
                        self::redirect('/member/index.html');
                    } else {
                        $view->set("message", "User account not verified");
                    }
                } else{
                    $view->set("message", 'Wrong Password, Try again or <a href="/auth/forgotpassword">Reset Password</a>');
                }
            } else {
                $view->set("message", 'User doesnot exist. Please signup <a href="/auth/register">here</a>');
            }
        }
    }
    
    /**
     * @before _session
     */
    public function register() {
        $this->defaultLayout = "layouts/blank";
        $this->setLayout();
        $this->seo(array(
            "title" => "Register",
            "view" => $this->getLayoutView()
        ));
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
                $view->set("message", "Your account has been created and will be activate within 3 hours after verification.");
            } else {
                $view->set("message", 'Email exists, login from <a href="/auth/login">here</a>');
            }
        }
    }

    public function forgotpassword() {
        $this->defaultLayout = "layouts/blank";
        $this->setLayout();
        $this->seo(array(
            "title" => "Register",
            "view" => $this->getLayoutView()
        ));
        $view = $this->getActionView();

        if (RequestMethods::get("reset")) {
            $token = RequestMethods::get("id");
            $id = base64_decode($token);
            $exist = User::first(array("id = ?" => $id), array("id"));
            if($exist) {
                $view->set("token", $token);
            } else{
                $view->set("message", 'Something Went Wrong please contact admin');
            }
        }

        if (RequestMethods::post("action") == "change") {
            $token = RequestMethods::post("token");
            $id = base64_decode($token);
            $user = User::first(array("id = ?" => $id), array("id"));
            if(RequestMethods::post("password") == RequestMethods::post("cpassword")) {
                $user->password = sha1(RequestMethods::post("password"));
                $user->save();
                $this->setUser($user);
                $this->session();
            } else{
                $view->set("message", 'Password Does not match');
            }
        }
    }

    protected function session() {
        $session = Registry::get("session");
        $where = array(
            "property = ?" => "domain",
            "live = ?" => true
        );
        $domains = Meta::all($where);
        $session->set("domains", $domains);
        self::redirect("/member");
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
    
    protected function log($message = "") {
        $logfile = APP_PATH . "/logs/" . date("Y-m-d") . ".txt";
        $new = file_exists($logfile) ? false : true;
        if ($handle = fopen($logfile, 'a')) {
            $timestamp = strftime("%Y-%m-%d %H:%M:%S", time());
            $content = "[{$timestamp}]{$message}\n";
            fwrite($handle, $content);
            fclose($handle);
            if ($new) {
                chmod($logfile, 0755);
            }
        } else {
            echo "Could not open log file for writing";
        }
    }

    protected function changeDate($date, $day) {
        return date_format(date_add(date_create($date),date_interval_create_from_date_string("{$day} day")), 'Y-m-d');;
    }
    
    public function logout() {
        $this->setUser(false);
        self::redirect("/home");
    }
    
    public function noview() {
        $this->willRenderLayoutView = false;
        $this->willRenderActionView = false;
    }

    public function JSONview() {
        $this->willRenderLayoutView = false;
        $this->defaultExtension = "json";
    }

    public function memberLayout() {
        $this->defaultLayout = "layouts/member";
        $this->setLayout();
    }
    
    /**
     * The method checks whether a file has been uploaded. If it has, the method attempts to move the file to a permanent location.
     * @param string $name
     * @param string $type files or images
     */
    protected function _upload($name, $type = "files") {
        if (isset($_FILES[$name])) {
            $file = $_FILES[$name];
            $path = APP_PATH . "/public/assets/uploads/{$type}/";
            $extension = pathinfo($file["name"], PATHINFO_EXTENSION);
            $filename = uniqid() . ".{$extension}";
            if (move_uploaded_file($file["tmp_name"], $path . $filename)) {
                return $filename;
            } else {
                return FALSE;
            }
        }
    }
}
