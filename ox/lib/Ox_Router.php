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
 *
 * @copyright Copyright (c) 2012 Lunar Logic LLC
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 * @package Ox_Boot
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
 * @package Ox_Boot
 */
class Ox_Router
{
    /** Enable/Disable Debugging for this object. */
    const DEBUG = FALSE;

    /** The domain variable name to use in the app.php file. */
    const DOMAIN_APP_CONFIG = 'domain';

    /** @var array List of routes registered. */
    private static $_routes = array();
 
    /**
     * Register a route.
     *
     * This adds to the list of routes that will be evaluated when the route method is call.  (Which is called from
     * dispatch.)  The routes for an app are generally set in config/app.php.  It will also be called as part of a
     * Ox_Hook::initializeModuleConstruct call to setup routes for a module.
     *
     * For example:
     * <pre><code>Ox_Router::add('/^\/login.*', new Ox_AssemblerAction(null, 'users', array('index'=>'login')));
     * </code></pre>
     *
     * @param string $regex
     * @param Ox_Routable $action
     */
    public static function add($regex, $action) {
        self::$_routes[$regex] = $action;
    }

    public static function addTop($regex, $action) {
        $newRoute = array($regex=>$action);
        self::$_routes = array_merge($newRoute,self::$_routes);
        //self::$_routes[$regex] = $action;
    }

    /**
     * Return all routes for inspection for unit testing
     *
     * @static
     * @return array
     */
    public static function getAll() {
        return self::$_routes;
    }


    /**
     * Route program execution.
     *
     * Figures out which action get control of the execution.  This is based on the regex that is part of the route
     * information. If a route can not be found return a 404 message.
     * Note: you can set a path for the URL in the config/app.php
     *
     * <pre><code>$errorPage = "/error/e404";
     * </code></pre>
     * @param string $request_url
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
            //@TODO why aren't we returning an actual 404 header????
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
                echo "<div class=\"error404\"><h1>404 Error</h1> <p>$errorMessage</p></div>";
            }
        }
    }

    /**
     * Redirect to the given url
     *
     * @param string $url
     * @param null|array $params
     * @param null|array $headers
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
            //The location header is a 302 redirect.. so can only be used by itself,
            header('Location: ' . $url);
        }
    }

    /**
     * Build a url with an array of parameters. This function takes into account if
     * Ox is being access from a subdirectory and corrects the path accordingly.
     *
     * @param string $url
     * @param null|array $params -- parameters to add to the URL as get vars
     * @param bool $buildFQURL -- Generate a fully qualified URL (http://domain/etc)
     * @return string
     */
    public static function buildURL($url, $params = null,$buildFQURL=false)
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
        //Allow fully qualified url's without a web directory defined.
        if(!empty($webDir)){
            $url = $webDir.$url;
        }
        if(!preg_match('/^http/',$url) && $buildFQURL){
            $url = self::getProtocol() . self::getDomain() . $webDir.$url;
        }
        return $url = $url.$param_str;
    }

    /**
     * Returns the current or set domain name for this app.
     *
     * The domain name can be be set in the app.php as using $domain = 'domain name';
     * @return string
     */
    public static function getDomain()
    {
        $config = Ox_LibraryLoader::config_parser();
        $domain = $config->getAppConfigValue(self::DOMAIN_APP_CONFIG);
        if ($domain) {
            return $domain;
        } else {
            return $_SERVER['HTTP_HOST'];
        }

    }

    /**
     * Returns the currently used protocol string for creating a Fully qualified URL
     * @return string  The protocol that use used on the HTTP request.
     */
    public static function getProtocol()
    {
            if ( (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443) {
                $protocol = "https://";
            } else {
                $protocol = "http://";
            }
            return $protocol;
    }

    /**
     * I am not sure this function is being used. TODO: See if this should be removed.
     * @param $uri
     * @param $prefix
     */
    public static function trimPrefix($uri,$prefix)
    {
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
