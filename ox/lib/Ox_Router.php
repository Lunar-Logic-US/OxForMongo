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

    /** @var array List of routes registered. */
    private static $_defaultRoute = array();

    /**
     * Register a route.
     *
     * This adds to the list of routes that will be evaluated when the route method is call.  (Which is called from
     * dispatch.)  The routes for an app are generally set in config/app.php.  It will also be called as part of a
     * Ox_Hook::initializeModuleConstruct call to setup routes for a module.
     * <br><br>
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


    /**
     * Register a route at top.
     *
     * This performs the same function as add but adds the route to the top of the list to be checked.
     * <br><br>
     * For example:
     * <pre><code>Ox_Router::addTop('/^\/login.*', new Ox_AssemblerAction(null, 'users', array('index'=>'login')));
     * </code></pre>
     *
     * @param string $regex
     * @param Ox_Routable $action
     */
    public static function addTop($regex, $action) {
        $newRoute = array($regex=>$action);
        self::$_routes = array_merge($newRoute,self::$_routes);
        //self::$_routes[$regex] = $action;
    }

    /**
     * Set the default route.
     *
     * Note there can only be one default -- catchall route
     * @param $regex
     * @param $action
     */
    public static function defaultRoute($regex, $action) {
        $newRoute = array($regex=>$action);
        self::$_defaultRoute = $newRoute;
    }


    /**
     * Remove a route.
     *
     * Removes the route from the the list of routes to check.
     * <br><br>
     * For example:
     * <pre><code>Ox_Router::remove('/^\/login.*');
     * </code></pre>
     *
     * @param string $regex
     */
    public static function remove($regex) {
        unset(self::$_routes[$regex]);
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
     * @param string $requestUrl
     */
    public static function route($requestUrl)
    {
        $routed = false;
        $errorMessage = null;
        $routesToTry = array_merge(self::$_routes, self::$_defaultRoute);
        foreach($routesToTry as $regex => $obj) {
            if(self::DEBUG)  Ox_Logger::logDebug("Routing:" . $requestUrl . ' : regex : ' . $regex);
            if(preg_match($regex, $requestUrl, $matches)) {
                if(self::DEBUG)  Ox_Logger::logDebug('Route Match: ' . print_r($matches,1));
                if(self::DEBUG) Ox_Logger::logDebug($requestUrl . ' matched ' . $regex);
                try {
                    // First come, first serve. One action per request.
                    $obj->go($matches);
                    $routed = true;
                } catch (Ox_RouterException $e){
                    $routed = false;
                    $errorMessage = $e->getMessage();
                }
                break;
            }
        }

        if(!$routed) {
            // Couldn't find a route. Log and return message.
            Ox_Logger::logWarning(__CLASS__ . ' - '. __FUNCTION__ .  ': Could not route:' . $requestUrl);
            if (!isset($errorMessage)) {
               $errorMessage = "Route not found for:  {$requestUrl}";
            }
            self::error404($errorMessage,$requestUrl);
        }
    }

    public static function error404($message,$requestUrl,$errorConstruct=null)
    {
        header("HTTP/1.0 404 Not Found");
        if (empty($errorConstruct)) {
            $config_parser = Ox_LibraryLoader::getResource('config_parser');
            $path = $config_parser->getAppConfigValue('errorPage');
            if (isset($path['404']) && $path['404'] != $requestUrl) {
                $errorConstruct = $path['404'];
            }
        }

        //Do we have an error page and make sure we don't create a redirect loop...
        //Are we having an error on the page we redirected to?
        if (isset($errorConstruct) && $errorConstruct != $requestUrl) {
            $_POST['errorMessage']=$message;
            self::route($errorConstruct);
        } else {
            echo "<div class=\"error404\"><h1>404 Error</h1> <p>$message</p></div>";
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
        //Only process if we don't have a FQURL
        if(preg_match('/^http/',$url)) {
            //We gave a fully qualified URL already!
            //No need to append the webdir
            //It is already a FQURL so nothing to do there
            //Just pass through.
        } else {
            //add the web directory if set.
            if(!empty($webDir)){
                $url = $webDir.$url;
            }
            //set it to the FQURL if requested.
            if ($buildFQURL){
                $url = self::getProtocol() . self::getDomain() . $url;
            }
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

}
