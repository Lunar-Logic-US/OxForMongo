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
    const TEST_SESSION_ID = '000000000000000000000abc';
    const TEST_KEY = 'test_key';
    const TEST_VALUE = 'test_value';

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
     * Test that when an existing session cookie is received, its session ID is
     * used.
     */
    public function testUseExistingSessionId()
    {
        // Make mockCookieManager return a pre-determined value for the
        // existing session ID
        $this->mockCookieManager
             ->method('getCookieValue')
             ->with($this->equalTo(self::TEST_SESSION_NAME))
             ->willReturn(self::TEST_SESSION_ID);

        // Open the session
        $this->session->open(self::TEST_SESSION_NAME);
        \Ox_Logger::logDebug('opened session in test');

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
        \Ox_Logger::logDebug('opened session in test');

        // Verfiy that there is one session document
        $query = [
            '_id' => ['$ne' => MongoSessionHandler::GC_ID]
        ];
        $count = $this->mongoCollection->count($query);
        $this->assertEquals(1, $count);

        // Get the contents of the session document
        $doc = $this->mongoCollection->findOne($query);

        // Verfiy that the timestamp is present
        $this->assertTrue(
            array_key_exists(
                MongoSessionHandler::SESSION_TIMESTAMP_KEY,
                $doc
            )
        );
    }

    /**
     * @todo
     */
    public function testSetAndGet()
    {
        // Open the session
        $this->session->open(self::TEST_SESSION_NAME);
        \Ox_Logger::logDebug('opened session in test');

        // Set a key to a value and verify that it was set successfully
        $result = $this->session->set(self::TEST_KEY, self::TEST_VALUE);
        $this->assertTrue($result);

        // Read the value and verify that it is the same
        $value = $this->session->get(self::TEST_KEY);
        $this->assertEquals(self::TEST_VALUE, $value);
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
