<?php

namespace ox\lib\exceptions;

class SessionException extends \Ox_Exception
{
    /**
     * String to prepend on the error message for the log.
     * @var string
     */
    protected static $_logHeader = 'ox\lib\exceptions\SessionException:';
}
