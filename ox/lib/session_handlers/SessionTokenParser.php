<?php

namespace ox\lib\session_handlers;

class SessionTokenParser
{
    const DELIMITER = '.';

    /** @var string The original token string which was passed in */
    private $token;

    /** @var string The ID which has been parsed out of the token string */
    private $id;

    /** @var string The HMAC which has been parsed out of the token string */
    private $hmac;

    /**
     * @param string $token
     */
    public function __construct($token)
    {
        if (self::validateTokenFormat($token)) {
            $this->token = $token;
            $this->parse();
        } else {
            throw new \ox\lib\exceptions\SessionException(
                'Invalid token format'
            );
        }
    }

    /**
     * @return string
     */
    public function getSessionId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getHmac()
    {
        return $this->hmac;
    }


    /*************************************************************************/
    // Private Methods
    /*************************************************************************/

    /**
     * Validate that a token is in the correct format.  This does NOT validate
     * the HMAC.
     *
     * @return bool true if the token is a valid token
     */
    private static function validateTokenFormat($token)
    {
        $id_charlength = MongoSessionHandler::SESSION_ID_BYTE_LENGTH * 2;
        $hmac_charlength = MongoSessionHandler::TOKEN_HMAC_BYTE_LENGTH * 2;

        $pattern =
            '/^[a-f0-9]{' . $id_charlength . '}' .
            preg_quote(self::DELIMITER) .
            '[a-f0-9]{' . $hmac_charlength . '}$/';

        if (is_string($token) && preg_match($pattern, $token) === 1) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Separate the session ID string from the HMAC
     *
     * @return void
     */
    private function parse()
    {
        $parts = explode('.', $this->token);

        $this->id = $parts[0];
        $this->hmac = $parts[1];
    }
}
