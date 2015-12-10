<?php

/**
 * Description of markup
 *
 * @author Faizan Ayubi
 */

namespace Shared {

    class Markup {

        public function __construct() {
            // do nothing
        }

        public function __clone() {
            // do nothing
        }

        public static function errors($array, $key, $separator = "<br />", $before = "<br />", $after = "") {
            if (isset($array[$key])) {
                return $before . join($separator, $array[$key]) . $after;
            }
            return "";
        }

        public static function pagination($page) {
            if (strpos(URL, "?")) {
                $request = explode("?", URL);
                if (strpos($request[1], "&")) {
                    parse_str($request[1], $params);
                }

                $params["page"] = $page;
                return $request[0]."?".http_build_query($params);
            } else {
                $params["page"] = $page;
                return URL."?".http_build_query($params);
            }
            return "";
        }

        public static function unique($length = 22) {
            //Not 100% unique, not 100% random, but good enought for a salt
            //MD5 returns 32 characters
            $unique_random_string = md5(uniqid(mt_rand(), true));

            //valid characters for a salt are [a-z A-Z 0-9 ./]
            $base64_string = base64_encode($unique_random_string);

            //but not '+' which is in base64 encoding
            $modified_base64_string = str_replace('+', '.', $base64_string);

            $string = substr($modified_base64_string, 0, $length);
            return $string;
        }

    }

}