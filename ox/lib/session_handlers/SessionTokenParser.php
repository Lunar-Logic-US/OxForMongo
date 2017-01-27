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
        if (self::tokenIsWellFormed($token)) {
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
     * Determine whether a token string is well-formed.  This does NOT validate
     * the HMAC.  In order to be well-formed, the token must be a string
     * comprised of two lowercase hex strings separated by a delimiter
     * character.  The required length of the hex strings and the delimter
     * character are defined by constants.
     *
     * @param mixed $token
     * @return bool True if the token is a well-formed token
     */
    private static function tokenIsWellFormed($token)
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
