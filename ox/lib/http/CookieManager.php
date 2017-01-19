<?php

namespace ox\lib\http;

class CookieManager
{
    /**
     * Send a cookie to the client.
     *
     * @param Cookie $cookie
     * @return bool The result of the setcookie call, i.e. true on success
     */
    public static function set(Cookie $cookie)
    {
        return setcookie(
            $cookie->getName(),
            $cookie->getValue(),
            $cookie->getExpires(),
            $cookie->getPath(),
            $cookie->getDomain(),
            $cookie->isSecure(),
            $cookie->isHttpOnly()
        );
    }

    /**
     * Clear a cookie's value.
     *
     * @param string $name The name of the cookie
     * @param string $path The path of the cookie
     * @return bool The result of the setcookie call, i.e. true on success
     */
    public static function delete($name, $path = '')
    {
        return setcookie($name, '', time() - 86400, $path);
    }

    /**
     * Return the value of a cookie received in the HTTP request
     *
     * @param string $name The name of the cookie
     * @return string The cookie's value, or null if no cookie has the
     *                specified name
     */
    public static function getCookieValue($name)
    {
        if (isset($_COOKIE[$name])) {
            return $_COOKIE[$name];
        } else {
            return null;
        }
    }
}
