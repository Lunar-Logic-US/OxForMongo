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

use \Ox_ConfigParser;
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
 * CookieManager and Ox_ConfigParser are still mocked.
 */
class MongoSessionHandlerIntegrationTest extends \PHPUnit_Framework_TestCase
{
    const TEST_DB_NAME = 'integration_test';
    const TEST_SESSION_NAME = 'TEST_SESSION';
    const TEST_SESSION_ID =
        '0000000000000000000000000000000000000000000000000000000000abcdef';
    const TEST_SESSION_ID_HASH =
        '984b0e0504d5d260155d22c86a98966a0fd4ca408cc259a78d86da82bd650b35';
    const TEST_TOKEN_HMAC =
        '0d495350c8cacd00894d02ef086a5fa489377dd134c548384289c0c713dd262e';
    const TEST_MALFORMED_TOKEN = '123.abc';
    const TEST_INVALID_HMAC =
        '0000000000000000000000000000000000000000000000000000000000000000';
    const TEST_KEY_1 = 'test_key_1';
    const TEST_KEY_2 = 'test_key_2';
    const TEST_VALUE_1 = 'test_value_1';
    const TEST_VALUE_2 = 'test_value_2';

    private static $test_token;

    // Define settings that would normally be defined by the app config but
    // which are set up here to be overridable by tests
    private $gc_interval = MongoSessionHandler::GC_INTERVAL_DEFAULT;
    private $max_session_age = MongoSessionHandler::MAX_SESSION_AGE_DEFAULT;
    private $token_hmac_key = '*whisperwhisper*';

    /** @var MongoSessionHandler The object of the class we are testing */
    private $session;

    /** @var Ox_MongoSource */
    private $mongoSource;

    /** @var Ox_MongoCollection */
    private $mongoCollection;

    /** @var CookieManager */
    private $mockCookieManager;

    /** @var Ox_ConfigParser */
    private $mockConfigParser;

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

        // TODO: Disable garbage collection maybe??

        // Tell the MongoSessionHandler to use our test MongoSource
        $this->session->setMongoSource($this->mongoSource);

        // Create a mock CookieManager, since we are not testing the
        // CookieManager here
        $this->mockCookieManager =
            $this->getMockBuilder('\ox\lib\http\CookieManager')
                 ->getMock();
        $this->session->setCookieManager($this->mockCookieManager);

        // Create a mock Ox_ConfigParser so we can control the configuration
        // (and because we don't need to test the Ox_ConfigParser class here)
        $this->mockConfigParser =
            $this->getMockBuilder('\Ox_ConfigParser')
                 ->getMock();
        $map = [
            [MongoSessionHandler::CONFIG_GC_INTERVAL_NAME, $this->gc_interval],
            [MongoSessionHandler::CONFIG_MAX_SESSION_AGE_NAME, $this->max_session_age],
            [MongoSessionHandler::CONFIG_TOKEN_HMAC_KEY_NAME, $this->token_hmac_key]
        ];
        $this->mockConfigParser
             ->method('getAppConfigValue')
             ->will($this->returnValueMap($map));
        $this->session->setConfigParser($this->mockConfigParser);
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
        $this->assertNotEquals(self::TEST_SESSION_ID_HASH, $doc['_id']);
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
        $this->assertNotEquals(self::TEST_SESSION_ID_HASH, $doc['_id']);
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
        $this->assertNotEquals(self::TEST_SESSION_ID_HASH, $doc['_id']);
    }

    /**
     * Test that when a valid session token cookie is received, for which a
     * non-expired session exists, its session ID is used.
     */
    public function testUseExistingSessionId()
    {
        // Artificially insert a session into the database
        $now = time();
        $this->artificiallyInsertSession(self::TEST_SESSION_ID_HASH, $now, $now);

        // Make mockCookieManager return a test valid token value
        $this->mockCookieManager
             ->method('getCookieValue')
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

        // Verify that the session ID is a hash of the one we gave via
        // mockCookieManager
        $this->assertEquals(self::TEST_SESSION_ID_HASH, $doc['_id']);
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
     * variables.
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
     * Test that garbage collection deletes old sessions.
     */
    public function testGarbageCollection()
    {
        // Ensure that garbage collection occurs every time a session is opened
        $this->gc_interval = 0;

        // Artificially insert an old session which will be cleaned up, given
        // the garbage collection interval we set above
        $this->artificiallyInsertSession(
            self::TEST_SESSION_ID_HASH,
            $this->max_session_age - 1,
            $this->max_session_age - 1
        );

        // Open the session
        $this->session->open(self::TEST_SESSION_NAME);

        // Verify that the old session is no longer in the database
        $query = [
            '_id' => self::TEST_SESSION_ID_HASH
        ];
        $doc = $this->mongoCollection->findOne($query);
        $this->assertNull($doc);
    }

    /**
     * Test that the garbage collection timestamp gets updated whenever garbage
     * collection runs, such that running it again (before the interval has
     * elapsed) will not run garbage collection again.
     */
    public function testSkipGarbageCollection()
    {
        // Set the garbage collection interval to a long time so that it will
        // not elapse within the time this test takes to run
        $this->gc_interval = 9999;

        // Verify that there is no garbage collection timestamp in the database
        $query = ['_id' => MongoSessionHandler::GC_ID];
        $doc = $this->mongoCollection->findOne($query);
        $this->assertNull($doc);

        // Open a new session (garbage collection should run here since there
        // is no record of it having run before)
        $this->session->open(self::TEST_SESSION_NAME);

        // Verify that there is now a garbage collection timestamp in the
        // database
        $query = ['_id' => MongoSessionHandler::GC_ID];
        $count = $this->mongoCollection->count($query);
        $this->assertEquals(1, $count);

        // Destroy the session
        $this->session->destroy();

        // Artifically insert an old session which will be deleted if garbage
        // collection is run
        $this->artificiallyInsertSession(
            self::TEST_SESSION_ID_HASH,
            $this->max_session_age - 1,
            $this->max_session_age - 1
        );

        // Open a new session
        $this->session->open(self::TEST_SESSION_NAME);

        // Verify that the old session did not get deleted
        $query = ['_id' => self::TEST_SESSION_ID_HASH];
        $count = $this->mongoCollection->count($query);
        $this->assertEquals(1, $count);
    }

    /**
     * Artificially insert a session into the database.
     *
     * @param mixed $id Hash value to be used as the document's "_id" property
     * @param int $created Unix timestamp to be used as the "created" property
     * @param int $last_request Unix timestamp to be used as the "last_request"
     *                          property
     */
    private function artificiallyInsertSession(
        $id_hash,
        $created,
        $last_request
    ) {
        $session_doc = [
            '_id' => $id_hash,
            MongoSessionHandler::SESSION_CREATED_KEY =>
                new \MongoDate($created),
            MongoSessionHandler::SESSION_LAST_REQUEST_KEY =>
                new \MongoDate($last_request)
        ];
        $options = [
            'w' => 1 // Acknowledged write
        ];
        $result = $this->mongoCollection->insert($session_doc, $options);
        if (!isset($result['ok']) || !$result['ok']) {
            $this->fail('failed to artificially insert a session');
        }
    }
}
