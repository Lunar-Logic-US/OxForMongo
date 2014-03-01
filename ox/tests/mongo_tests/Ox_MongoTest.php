<?php
/**
 *    Copyright (c) 2012 Lunar Logic LLC
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

$appConfigFile = <<<APP_CONFIG_FILE
<?php
\$log_dir = '/tmp/';
\$mongo_config = array(
    'set_string_id' => TRUE,
    'persistent' => TRUE,
    'host'       => 'localhost',
    'database'   => 'test',
    'port'       => '27017',
    'login'         => '',
    'password'      => '',
    'replicaset'    => '',
);

?>
APP_CONFIG_FILE;

require_once(dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . '/boot.php');

    /**
     */
class Ox_MongoTest extends PHPUnit_Framework_TestCase {

    private $_db;

    public function setUp()
    {

        $this->_db = Ox_LibraryLoader::getResource('db');
        $this->_db->mongotest->drop();
    }


    public function tearDown()
    {
        //$this->_db->mongotest->drop();
    }

    public function testConnectionDefault()
    {
        $db = new Ox_MongoSource();
        $result = $db->run(array('ping'=> 1));
        $this->assertEquals($result['ok'],1);
        //print_r($result);

    }
    public function testRunAsString()
    {
        $result = $this->_db->run('{"ping": 1}');
        $this->assertEquals($result['ok'],1);
    }
    public function testRunAsStringException()
    {
        $this->setExpectedException('Ox_MongoSourceException');
        $result = $this->_db->run('{unquatedBAD: 1}');
    }

    public function setAndGet($doc)
    {
        $this->_db->mongotest->insert($doc);
        return $this->_db->mongotest->findOne();

    }

    public function testSetAndGet()
    {
        $doc = array (
            'a' => array (
                array('f' => 0),
                array('f' => 1),
            )
        );
        $this->assertEquals($doc,$this->setAndGet($doc));
    }

    public function isArrayInMongo($fieldName)
    {
        $result = $this->_db->execute('db.mongotest.find("Array.isArray(this.a)").count();');
        if ($result['retval']>0) {
            return true;
        }
        return false;
    }
    public function isObjectInMongo($fieldName)
    {
        $count = $this->_db->mongotest->find(
            array(
                $fieldName => array ('$type' => 3),
            )
        )->count();
        if ($count>0) {
            return true;
        }
        return false;
    }


    public function testMongoArrayPass()
    {

        $doc = array (
            'a' => array (
                array('f' => 0),
                array('f' => 1),
            )
        );
        $fromDB = $this->setAndGet($doc);
        $this->assertTrue($this->isArrayInMongo('a'));

        //test we are detecting it as an array
        $this->assertTrue(Ox_MongoSource::isMongoArray($fromDB['a']));
    }

    public function testMongoArrayObjectNonContigious()
    {

        $doc = array (
            'a' => array (
                0 => array('f' => 0),
                5 => array('f' => 1),
            )
        );
        $fromDB = $this->setAndGet($doc);
        $this->assertFalse($this->isArrayInMongo('a'));
        $this->assertFalse(Ox_MongoSource::isMongoArray($fromDB['a']));
    }
    public function testMongoArrayObjectOffByOne()
    {

        $doc = array (
            'a' => array (
                1 => array('f' => 0),
                2 => array('f' => 1),
            )
        );
        $fromDB = $this->setAndGet($doc);
        $this->assertFalse($this->isArrayInMongo('a'));
        $this->assertFalse(Ox_MongoSource::isMongoArray($fromDB['a']));
    }
    public function testMongoArrayObjectMixed()
    {

        $doc = array (
            'a' => array (
                0 => array('f' => 0),
                'text' => array('f' => 1),
            )
        );
        $fromDB = $this->setAndGet($doc);
        $this->assertFalse($this->isArrayInMongo('a'));
        $this->assertFalse(Ox_MongoSource::isMongoArray($fromDB['a']));
    }

}