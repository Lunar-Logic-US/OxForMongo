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

use \Ox_MongoSource;
use \ox\lib\session_handlers\MongoSessionHandler;
use \ox\lib\session_handlers\SessionTokenParser;

require_once(
    dirname(dirname(__FILE__))
    . DIRECTORY_SEPARATOR . 'boot'
    . DIRECTORY_SEPARATOR . 'boot.php'
);

/**
 * Integration Tests for MongoSessionHandler which use an actual Mongo
 * database.  Note that this is not a 100% full integration test because
 * CookieManager is still mocked.
 */
class MongoSessionHandlerIntegrationTest extends \PHPUnit_Framework_TestCase
{
    const TEST_DB_NAME = 'integration_test';
    const TEST_SESSION_NAME = 'TEST_SESSION';
    const TEST_SESSION_ID = '00000000000000000000000000abcdef';
    const TEST_TOKEN_HMAC =
        '0bfc3b331478b651764ba3bf68797c2a0c0cf75717dd10446e0cb6cb8e7499b3';
    const TEST_MALFORMED_TOKEN = '123.abc';
    const TEST_INVALID_HMAC =
        '0000000000000000000000000000000000000000000000000000000000000000';
    const TEST_KEY_1 = 'test_key_1';
    const TEST_KEY_2 = 'test_key_2';
    const TEST_VALUE_1 = 'test_value_1';
    const TEST_VALUE_2 = 'test_value_2';

    private static $test_token;

    /** @var MongoSessionHandler The object of the class we are testing */
    private $session;

    /** @var Ox_MongoSource */
    private $mongoSource;

    /** @var Ox_MongoCollection */
    private $mongoCollection;

    /** @var CookieManager */
    private $mockCookieManager;

    /**
     * @before
     */
    public function setUp()
    {
        $this->test_token = sprintf(
            '%s%s%s',
            self::TEST_SESSION_ID,
            SessionTokenParser::DELIMITER,
            self::TEST_TOKEN_HMAC
        );

        // Obtain a connection to the MongoDB server
        $this->mongoSource = new Ox_MongoSource();

        // Select the test database and get a reference to the session
        // collection
        $this->mongoSource->selectDB(self::TEST_DB_NAME);
        $this->mongoCollection = $this->mongoSource->getCollection(
            MongoSessionHandler::DB_COLLECTION_NAME
        );

        // Make sure the session collection is empty/nonexistent
        $this->mongoCollection->drop();

        // Create a new MongoSessionHandler
        $this->session = new MongoSessionHandler(self::TEST_SESSION_NAME);

        // TODO: Disable garbage collection
        //$this->session->setGarbageCollectionPeriod(-1);

        // Tell the MongoSessionHandler to use our test MongoSource
        $this->session->setMongoSource($this->mongoSource);

        // Create a mock CookieManager, since we are not testing the
        // CookieManager here
        $this->mockCookieManager =
            $this->getMockBuilder('\ox\lib\http\CookieManager')
                 ->getMock();
        $this->session->setCookieManager($this->mockCookieManager);
    }

    /**
     * @after
     */
    public function tearDown()
    {
        // Drop the test database
        $this->mongoSource->dropDB(self::TEST_DB_NAME);
    }

    /**
     * Test that when a valid session token cookie is received, but that
     * session no longer exists, a new session is created instead.
     */
    public function testReceiveValidNonexistentToken()
    {
        // Make mockCookieManager return a test valid token value
        $this->mockCookieManager
             ->method('getCookieValue')
             ->with($this->equalTo(self::TEST_SESSION_NAME))
             ->willReturn($this->test_token);

        // Open the session
        $this->session->open(self::TEST_SESSION_NAME);

        // Verfiy that there is one session document
        $query = [
            '_id' => ['$ne' => MongoSessionHandler::GC_ID]
        ];
        $count = $this->mongoCollection->count($query);
        $this->assertEquals(1, $count);

        // Get the contents of the session document
        $doc = $this->mongoCollection->findOne($query);

        // Verify that the session ID is not the one we gave via
        // mockCookieManager
        $this->assertNotEquals(self::TEST_SESSION_ID, $doc['_id']);
    }

    /**
     * Test that when a malformed token is received, the token is not used and
     * a new session ID is created.
     */
    public function testMalformedToken()
    {
        // Make mockCookieManager return the test malformed token
        $this->mockCookieManager
             ->method('getCookieValue')
             ->with($this->equalTo(self::TEST_SESSION_NAME))
             ->willReturn(self::TEST_MALFORMED_TOKEN);

        // Open the session
        $this->session->open(self::TEST_SESSION_NAME);

        // Verfiy that there is one session document
        $query = [
            '_id' => ['$ne' => MongoSessionHandler::GC_ID]
        ];
        $count = $this->mongoCollection->count($query);
        $this->assertEquals(1, $count);

        // Get the contents of the session document
        $doc = $this->mongoCollection->findOne($query);

        // Verify that the session ID is not the one we gave via
        // mockCookieManager
        $this->assertNotEquals(self::TEST_SESSION_ID, $doc['_id']);
    }

    /**
     * Test that when a well-formed token is received whose HMAC is invalid,
     * the token is not used and a new session ID is created.
     */
    public function testTokenWithInvalidHmac()
    {
        // Create a well-formed token with an invalid HMAC
        $token = sprintf(
            '%s%s%s',
            self::TEST_SESSION_ID,
            SessionTokenParser::DELIMITER,
            self::TEST_INVALID_HMAC
        );

        // Make mockCookieManager return the test token
        $this->mockCookieManager
             ->method('getCookieValue')
             ->with($this->equalTo(self::TEST_SESSION_NAME))
             ->willReturn($token);

        // Open the session
        $this->session->open(self::TEST_SESSION_NAME);

        // Verfiy that there is one session document
        $query = [
            '_id' => ['$ne' => MongoSessionHandler::GC_ID]
        ];
        $count = $this->mongoCollection->count($query);
        $this->assertEquals(1, $count);

        // Get the contents of the session document
        $doc = $this->mongoCollection->findOne($query);

        // Verify that the session ID is not the one we gave via
        // mockCookieManager
        $this->assertNotEquals(self::TEST_SESSION_ID, $doc['_id']);
    }

    /**
     * Test that when a valid session token cookie is received, for which a
     * non-expired session exists, its session ID is used.
     */
    public function testUseExistingSessionId()
    {
        // Artificially insert a session into the database
        $now = time();
        $new_doc = [
            '_id' => self::TEST_SESSION_ID,
            MongoSessionHandler::SESSION_CREATED_KEY => new \MongoDate($now),
            MongoSessionHandler::SESSION_LAST_REQUEST_KEY =>
                new \MongoDate($now)
        ];
        $options = [
            'w' => 1 // Acknowledged write
        ];
        $result = $this->mongoCollection->insert($new_doc, $options);
        if (!isset($result['ok']) || !$result['ok']) {
            $this->fail('failed to insert fake existing session');
        }

        // Make mockCookieManager return a test valid token value
        $this->mockCookieManager
             ->method('getCookieValue')
             ->with($this->equalTo(self::TEST_SESSION_NAME))
             ->willReturn($this->test_token);

        // Open the session
        $this->session->open(self::TEST_SESSION_NAME);

        // Verfiy that there is one session document
        $query = [
            '_id' => ['$ne' => MongoSessionHandler::GC_ID]
        ];
        $count = $this->mongoCollection->count($query);
        $this->assertEquals(1, $count);

        // Get the contents of the session document
        $doc = $this->mongoCollection->findOne($query);

        // Verify that the session ID is the one we gave via mockCookieManager
        $this->assertEquals(self::TEST_SESSION_ID, $doc['_id']);
    }

    public function testOpenWithNoCookie()
    {
        // Open the session
        $this->session->open(self::TEST_SESSION_NAME);

        // Verfiy that there is one session document
        $query = [
            '_id' => ['$ne' => MongoSessionHandler::GC_ID]
        ];
        $count = $this->mongoCollection->count($query);
        $this->assertEquals(1, $count);

        // Get the contents of the session document
        $doc = $this->mongoCollection->findOne($query);

        // Verfiy that the "created" timestamp is present
        $this->assertArrayHasKey(
            MongoSessionHandler::SESSION_CREATED_KEY,
            $doc
        );

        // Verfiy that the "last_request" timestamp is present
        $this->assertArrayHasKey(
            MongoSessionHandler::SESSION_LAST_REQUEST_KEY,
            $doc
        );
    }

    /**
     * Test that get() and set() work as expected to store and retrieve session
     * variables
     */
    public function testSetAndGetSessionVariables()
    {
        // Open the session
        $this->session->open(self::TEST_SESSION_NAME);

        // Set a key to a value and verify that it was set successfully
        $result = $this->session->set(self::TEST_KEY_1, self::TEST_VALUE_1);
        $this->assertTrue($result);

        // Set another key to a value and verify that it was set successfully
        $result = $this->session->set(self::TEST_KEY_2, self::TEST_VALUE_2);
        $this->assertTrue($result);

        // Read the first value and verify that it is correct
        $value = $this->session->get(self::TEST_KEY_1);
        $this->assertEquals(self::TEST_VALUE_1, $value);

        // Read the second value and verify that it is correct
        $value = $this->session->get(self::TEST_KEY_2);
        $this->assertEquals(self::TEST_VALUE_2, $value);
    }

    public function testDestroy()
    {
        // Open the session
        $this->session->open(self::TEST_SESSION_NAME);

        // Destroy the session
        $this->session->destroy();

        // Verify that the session is not present in the database (i.e. verify
        // that there are no sessions at all, since that was the only session)
        $query = [
            '_id' => ['$ne' => MongoSessionHandler::GC_ID]
        ];
        $count = $this->mongoCollection->count($query);
        $this->assertEquals(0, $count);
    }

    /**
     * @todo
     */
    public function testGarbageCollection()
    {
        // TODO: Enable garbage collection, since we disabled it in setUp()
        //$this->session->setGarbageCollectionPeriod(1);
    }
}
