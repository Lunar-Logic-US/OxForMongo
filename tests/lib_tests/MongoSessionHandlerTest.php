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

namespace ox\tests;

use \ox\lib\exceptions\SessionException;
use \ox\lib\session_handlers\MongoSessionHandler;

require_once(
    dirname(dirname(__FILE__))
    . DIRECTORY_SEPARATOR . 'boot'
    . DIRECTORY_SEPARATOR . 'boot.php'
);

class MongoSessionHandlerTest extends \PHPUnit_Framework_TestCase
{
    const TEST_SESSION_NAME = 'TEST_SESSION';
    const TEST_KEY = 'test_key';
    const TEST_VALUE = 'test_value';
    const TEST_NONEXISTENT_KEY = 'test_nonexistent_key';

    const UNOPENED_NO_EXCEPTION_MESSAGE =
        'No exception was thrown when calling %s() on an unopened session.';

    const TEST_INVALID_KEY_1 = 'test.key';
    const TEST_INVALID_KEY_2 = 'test$key';
    private static $TEST_INVALID_KEY_3 = ['test' => 'key'];
    const INVALID_KEY_NO_EXCEPTION_MESSAGE =
        'No exception was thrown when calling %s() with an invalid key.';

    /** @var MongoSessionHandler The object of the class we are testing */
    private $session;

    /** @var CookieManager */
    private $mockCookieManager;

    /** @var Ox_MongoSource */
    private $mockOxMongoSource;

    /** @var Ox_MongoCollection */
    private $mockOxMongoCollection;

    /**
     * @before
     */
    public function setUp()
    {
        // Create the session
        $this->session = new MongoSessionHandler();

        // Create mock objects
        $this->mockCookieManager =
            $this->getMockBuilder('\ox\lib\http\CookieManager')
                 ->getMock();
        $this->mockOxMongoSource =
            $this->getMockBuilder('\Ox_MongoSource')
                 ->getMock();
        $this->mockOxMongoCollection =
            $this->getMockBuilder('\Ox_MongoCollection')
                 ->disableOriginalConstructor()
                 ->getMock();

        // Define mock behaviors
        $this->mockOxMongoSource
             ->method('getCollection')
             ->willReturn($this->mockOxMongoCollection);

        // Give mock objects to the session
        $this->session->setCookieManager($this->mockCookieManager);
        $this->session->setMongoSource($this->mockOxMongoSource);
    }

    public function testThrowOnDestroyIfUnopened()
    {
        try {
            // Attempt to destroy an unopened session
            $this->session->destroy();

            // If we got this far, fail the test
            $this->fail(sprintf(self::UNOPENED_NO_EXCEPTION_MESSAGE, 'destroy'));
        } catch (SessionException $exception) {
            $this->assertEquals(
                MongoSessionHandler::UNOPENED_EXCEPTION_MESSAGE,
                $exception->getMessage()
            );
        }
    }

    public function testThrowOnGetIfUnopened()
    {
        try {
            // Attempt to get a session variable on an unopened session
            $this->session->get(self::TEST_KEY);

            // If we got this far, fail the test
            $this->fail(sprintf(self::UNOPENED_NO_EXCEPTION_MESSAGE, 'get'));
        } catch (SessionException $exception) {
            $this->assertEquals(
                MongoSessionHandler::UNOPENED_EXCEPTION_MESSAGE,
                $exception->getMessage()
            );
        }
    }

    public function testThrowOnSetIfUnopened()
    {
        try {
            // Attempt to set a session variable on an unopened session
            $this->session->set(self::TEST_KEY, self::TEST_VALUE);

            // If we got this far, fail the test
            $this->fail(sprintf(self::UNOPENED_NO_EXCEPTION_MESSAGE, 'set'));
        } catch (SessionException $exception) {
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

        // Open the session
        $this->session->open(self::TEST_SESSION_NAME);

        // Make sure each invalid key throws an exception
        foreach ($invalidKeys as $key) {
            try {
                $this->session->set($key, self::TEST_VALUE);
                $this->fail(
                    sprintf(
                        self::INVALID_KEY_NO_EXCEPTION_MESSAGE,
                        'set'
                    )
                );
            } catch (SessionException $exception) {
                $this->assertEquals(
                    MongoSessionHandler::INVALID_KEY_EXCEPTION_MESSAGE,
                    $exception->getMessage()
                );
            }
        }
    }

    public function testGetNonexistentKey()
    {
        // Open the session
        $this->session->open(self::TEST_SESSION_NAME);

        // Attempt to get a nonexistent key
        $value = $this->session->get(self::TEST_NONEXISTENT_KEY);

        // Verify that the value returned is null
        $this->assertEquals(null, $value);
    }
}
