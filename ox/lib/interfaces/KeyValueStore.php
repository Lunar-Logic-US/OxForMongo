<?php

namespace ox\lib\interfaces;

interface KeyValueStore
{
    /**
     * @param $key
     * @return mixed
     */
    public function get($key);

    /**
     * @param $key string
     * @param $value mixed
     * @return bool
     */
    public function set($key, $value);
}
