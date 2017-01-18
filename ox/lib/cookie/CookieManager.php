<?php

namespace ox\lib\cookie;

class CookieManager
{
    public static function set(Cookie $cookie)
    {
        setcookie(
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
     * Return the value of a cookie received in the HTTP request
     *
     * @param string $name The name of the cookie
     * @return string The cookie's value, or null if no cookie has the
     *                specified name
     */
    public static function getCookieValue($name)
    {
        if (isset($_COOKIE, $name)) {
            return $_COOKIE[$name];
        } else {
            return null;
        }
    }
}
