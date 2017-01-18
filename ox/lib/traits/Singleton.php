<?php

namespace ox\lib\traits;

trait Singleton
{
    private static $_instance = null;

    /**
     * Get the singleton instance
     *
     * @return object
     */
    public static function getInstance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }
}
