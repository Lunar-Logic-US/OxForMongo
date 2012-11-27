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

APP_CONFIG_FILE;


require_once(dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . '/boot.php');
require_once(dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . '/lib/TestSchema.php');


/**
 */
class Ox_MongoSchemaTest extends PHPUnit_Framework_TestCase {

    private $_db;

    public function setUp()
    {

        $this->_db = Ox_LibraryLoader::getResource('db');
        $this->_db->mongotest->drop();
    }


    public function tearDown()
    {
    }

    public function setAndGet($doc)
    {
        $this->_db->mongotest->insert($doc);
        return $this->_db->mongotest->findOne();

    }

    public function testInsertError()
    {
        $data = <<<PHP
<?php
class MongoTestSchema extends Ox_Schema
{
    protected \$_onInsert = true;
    protected \$_replaceOnInvalid=true;

    public function __construct()
    {
        \$this->_schema = array(
            'basicField' => array(
                '__validator' => new Ox_RegExValidator('/w+/'),
                '__default' => 'valueInserted',
            ),
        );
    }
}

PHP;

        $fileName = DIR_APP . 'schemas/mongotestschema.php';
        print("XXXXXXXXXXXXX $fileName\n");
        file_put_contents($fileName,$data);
        $doc = array (
            'basicField' => 'any-thing',
        );
        $result = $this->setAndGet($doc);

        //print_r($doc);
        $this->assertEquals($result['basicField'],'valueInserted');
        unlink($fileName);
    }

}
