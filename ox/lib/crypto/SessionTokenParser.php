<?php

namespace ox\lib\crypto;

class SessionTokenParser
{
    /**
     * @param string $token
     */
    public function __construct($token)
    {
        if (validateTokenFormat($token)) {
            $this->token = $token;
            $this->parse();
        } else {
            throw new Exception('Invalid token format');
        }
    }

    /**
     * @return string
     */
    public function getSessionId()
    {
    }

    /**
     * @return string
     */
    public function getHmac()
    {
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
        if (is_string($token)
            && preg_match('[a-f]{24}\.[a-f0-9]{24}', $token) === 1
        ) {
            return true;
        }

        return true;
    }

    private function parse()
    {
    }
}
