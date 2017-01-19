<?php
/**
 *    Copyright (c) 2017 Lunar Logic LLC
 *
 *    This program is free software: you can redistribute it and/or  modify
 *    it under the terms of the GNU Affero General Public License, version 3,
 *    as published by the Free Software Foundation.
 *
 *    This program is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU Affero General Public License for more details.
 *
 *    You should have received a copy of the GNU Affero General Public License
 *    along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */

namespace ox\tests;

use \ox\lib\session_handlers\MongoSessionHandler;

require_once(
    dirname(dirname(__FILE__))
    . DIRECTORY_SEPARATOR . 'boot'
    . DIRECTORY_SEPARATOR . 'boot.php'
);

class SessionTest extends \PHPUnit_Framework_TestCase
{
    const TEST_KEY = 'test_key';
    const TEST_VALUE = 'test_value';
    const TEST_SESSION_NAME = 'TEST_SESSION';

    const UNOPENED_NO_EXCEPTION_MESSAGE =
        'No exception was thrown when calling %s() on an unopened session.';

    const TEST_INVALID_KEY_1 = 'test.key';
    const TEST_INVALID_KEY_2 = 'test$key';
    private static $TEST_INVALID_KEY_3 = ['test' => 'key'];
    const INVALID_KEY_NO_EXCEPTION_MESSAGE =
        'No exception was thrown when calling %s() with an invalid key.';

    /**
     * @var MongoSessionHandler
     */
    private $session;

    /**
     * @before
     */
    public function setup()
    {
        $this->session = new MongoSessionHandler();
    }

    /**
     * @test
     */
    public function testThrowOnCloseIfUnopened()
    {
        try {
            $this->session->close();
            $this->fail(sprintf(self::UNOPENED_NO_EXCEPTION_MESSAGE, 'close'));
        } catch (\ox\lib\exceptions\SessionException $exception) {
            $this->assertEquals(
                MongoSessionHandler::UNOPENED_EXCEPTION_MESSAGE,
                $exception->getMessage()
            );
        }
    }

    public function testThrowOnGetIfUnopened()
    {
        try {
            $this->session->get(self::TEST_KEY);
            $this->fail(sprintf(self::UNOPENED_NO_EXCEPTION_MESSAGE, 'get'));
        } catch (\ox\lib\exceptions\SessionException $exception) {
            $this->assertEquals(
                MongoSessionHandler::UNOPENED_EXCEPTION_MESSAGE,
                $exception->getMessage()
            );
        }
    }

    public function testThrowOnSetIfUnopened()
    {
        try {
            $this->session->set(self::TEST_KEY, self::TEST_VALUE);
            $this->fail(sprintf(self::UNOPENED_NO_EXCEPTION_MESSAGE, 'set'));
        } catch (\ox\lib\exceptions\SessionException $exception) {
            $this->assertEquals(
                MongoSessionHandler::UNOPENED_EXCEPTION_MESSAGE,
                $exception->getMessage()
            );
        }
    }

    public function testThrowOnSetWithInvalidKey()
    {
        $invalidKeys = [
            self::TEST_INVALID_KEY_1,
            self::TEST_INVALID_KEY_2,
            self::$TEST_INVALID_KEY_3
        ];

        $mockCookieManager = $this->getMockBuilder(
            '\ox\lib\http\CookieManager'
        )->getMock();

        $this->session->setCookieManager($mockCookieManager);
        $this->session->open(self::TEST_SESSION_NAME);

        foreach ($invalidKeys as $key) {
            try {
                $this->session->set($key, self::TEST_VALUE);
                $this->fail(
                    sprintf(
                        self::INVALID_KEY_NO_EXCEPTION_MESSAGE,
                        'set'
                    )
                );
            } catch (\ox\lib\exceptions\SessionException $exception) {
                $this->assertEquals(
                    MongoSessionHandler::INVALID_KEY_EXCEPTION_MESSAGE,
                    $exception->getMessage()
                );
            }
        }
    }
}
