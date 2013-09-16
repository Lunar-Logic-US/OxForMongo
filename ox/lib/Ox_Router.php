<?php
/**
 *    Copyright (c) 2012 Lunar Logic LLC
 *
 *    This program is free software: you can redistribute it and/or  modify
 *    it under the terms of the GNU Affero General Public License, version 3,
 *    as published by the Free Software Foundation.
 *
 *    This program is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU Affero General Public License for more details.
 *
 *    You should have received a copy of the GNU Affero General Public License
 *    along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Routing to actions.
 *
 * Request come into the framework and are routed to the appropriate action.
 * Routed and argument capture are determined by regex.
 *
 * Routing is performed on a first come first serve basis. Any route added with
 * a regex that matches (exactly) an existing regex will overwrite said regex.
 *
 * The router also handles redirects.
 */
class Ox_Router
{
    /**
     * Enable/Disable Debugging for this object.
     */
    const DEBUG = FALSE;

    /**
     * List of routes registered.
     * @var array
     */
    private static $_routes = array();
 
    /**
     * Register a route.
     *
     * @param $regex
     * @param $action
     */
    public static function add($regex, $action) {
        self::$_routes[$regex] = $action;
    }

    /**
     * Return all route for inspection for unit testing
     *
     * @static
     * @return array
     */
    public static function getAll() {
        return self::$_routes;
    }


    /**
     * Route based on the url and registered routes.
     * @param $request_url
     */
    public static function route($request_url)
    {
        $routed = false;
        $errorMessage = null;
        foreach(self::$_routes as $regex => $obj) {
            if(self::DEBUG)  Ox_Logger::logDebug("Routing:" . $request_url . ' : regex : ' . $regex);
            if(preg_match($regex, $request_url, $matches)) {
                if(self::DEBUG)  Ox_Logger::logDebug('Route Match: ' . print_r($matches,1));
                if(self::DEBUG) Ox_Logger::logDebug($request_url . ' matched ' . $regex);
                try {
                    $obj->go($matches);
                } catch (Ox_RouterException $e){
                    $routed = false;
                    $errorMessage = $e->getMessage();
                    break;
                }
                $routed = true;
                // First come, first serve. One action per request.
                break;
            }
        }

        if(!$routed) {
            // Couldn't find a route. Log and return message.
            Ox_Logger::logWarning('Could not route ' . $request_url);
            //header("HTTP/1.0 404 Not Found");
            if (!isset($errorMessage)) {
               $errorMessage = "Route not found for:  {$request_url}";
            }
            $config_parser = Ox_LibraryLoader::getResource('config_parser');
            $path = $config_parser->getAppConfigValue('errorPage');
            if (isset($path['404']) && $path['404'] != $request_url) {
                $_POST['errorMessage']=$errorMessage;
                self::route($path['404']);
            } else {
                //die($path['404'].$request_url);
                echo "<div class=\"error404\"><h1>404 Error</h1> <p>$errorMessage</p></div>";
            }
        }
    }

    /**
     * Redirect to the give url
     *
     * @param $url
     * @param null $params
     * @param null $headers
     */
    public static function redirect($url, $params = null, $headers = null)
    {
        $url = self::buildURL($url, $params);
        if(is_array($headers)) {
            //The location header is a 302 redirect.. so can only be used by itself,
            //is we use any other header we, need to send those headers and then do a
            //JS redirect.
            foreach($headers as $header) {
                Ox_Logger::logDebug('Sending Headers  ' . $header);
                header($header);
            }
            ?>
            <html>
            <head>
                <script>window.location='<?= $url ?>'</script>
            </head>
            <body>Redirecting to login page...</body>
            </html>
            <?php
        } else {
            header('Location: ' . self::buildURL($url, $params));
        }
    }

    /**
     * Build a url with an array of parameters.
     *
     * @param $url
     * @param null $params
     * @return string
     */
    public static function buildURL($url, $params = null)
    {
        $param_str = "";
        if($params) {
            foreach($params as $name => $value) {
                if(!strlen($param_str)) {
                    $param_str = $param_str."?{$name}=" . urlencode($value);
                } else {
                    $param_str = $param_str."&{$name}=" . urlencode($value);
                }
            }
        }
        $config = Ox_LibraryLoader::config_parser();
        $webDir = $config->getAppConfigValue(Ox_Dispatch::CONFIG_WEB_BASE_NAME);
        if(!preg_match('/^http/',$url) && !empty($webDir)){
            $url = $webDir.$url;
        }
        return $url = $url.$param_str;
    }

    public static function trimPrefix($uri,$prefix) {
        //decode the URL
        $url_info = parse_url($uri);
        if (self::DEBUG) {
            Ox_Logger::logDebug("Ox_Dispatch: Before Trim: " . $url_info['path'] . " Trim string: " . self::$_appWebBase);
        }

        // Strip off part of the uri as needed
        $url = $url_info['path'];
        if (substr($url, 0, strlen($prefix)) == $prefix) {
            $url = substr($url, strlen($prefix), strlen($url) );
        }
    }
}
