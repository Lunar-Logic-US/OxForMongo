<?php

namespace ox\lib;

class Cookie
{
    /*
     * bool setcookie ( string $name [, string $value = "" [, int $expire = 0 [, string $path = "" [, string $domain = "" [, bool $secure = false [, bool $httponly = false ]]]]]] )
     */

    private $name;
    private $value;
    private $expires;
    private $path;
    private $domain;

    /**
     * @param string $name
     */
    public function __construct(
        $name,
        $value,
        $expires = 0,
        $path = "",
        $domain = ""
    ) {
        $this->name = (string) $name;
        $this->value = $value;
        $this->expires = $expires;
        $this->path = $path;
        $this->domain = $domain;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return bool
     */
    public function isSecure()
    {
        return true;
    }

    public static function sendRequestCookie($name)
    {
        setcookie(
            $this->name,
            $this->value,
            $this->expires,
            $this->path,
            $this->domain,
            true, // secure
            true  // httponly
        );
    }

    public static function readRequestCookie($name)
    {
        return $_COOKIE[$name];
    }
}
