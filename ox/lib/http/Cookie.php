<?php

namespace ox\lib\http;

class Cookie
{
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
     * @return string
     */
    public function getExpires()
    {
        return $this->expires;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @return string
     */
    public function getDomain()
    {
        return $this->domain;
    }

    /**
     * @return bool
     */
    public function isSecure()
    {
        return true;
    }

    /**
     * @return bool
     */
    public function isHttpOnly()
    {
        return true;
    }
}
