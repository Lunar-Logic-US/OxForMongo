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
require_once(dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . '/lib/TestSchema.php');


/**
 */
class Ox_SchemaRequiredTest extends PHPUnit_Framework_TestCase {

    public function setUp() {
    }
    public function tearDown() {
    }

    public function testFieldFail() {
        $schema_array = array(
            'basicField' => array(
                '__required' => true,
            ),
        );
        $doc = array (
            //'basicField' => 'anything',
        );
        $schema = new TestSchema($schema_array);
        $this->assertFalse($schema->checkRequired($doc));
    }

    public function testField() {
        $schema_array = array(
            'basicField' => array(
                '__required' => true,
            ),
        );
        $doc = array (
            'basicField' => 'anything',
        );
        $schema = new TestSchema($schema_array);
        $this->assertTrue($schema->checkRequired($doc));
    }

    public function testObject() {
        $objectSchema = array(
            'basicField' => array(
                '__required' => true,
            ),
        );

        $schema_array = array(
            'basicObject' => array(
                '__validator' => new TestSchema($objectSchema),
            ),
        );
        $doc = array (
            'basicObject' => array(
                'basicField' => 'anything',
            ),
        );
        $schema = new TestSchema($schema_array);
//        $this->assertTrue($schema->isFieldValid('basicObject.basicField',$doc['basicObject']['basicField']));
        $this->assertTrue($schema->checkRequired($doc));
    }

    public function testObjectFail() {
        $objectSchema = array(
            'basicField' => array(
                '__required' => true,
            ),
        );

        $schema_array = array(
            'basicObject' => array(
                '__schema' => new TestSchema($objectSchema),
            ),
        );
        $doc = array (
            'basicObject' => array(
                //'basicField' => 'anything',
            ),
        );
        $schema = new TestSchema($schema_array);
//        $this->assertTrue($schema->isFieldValid('basicObject.basicField',$doc['basicObject']['basicField']));
        $this->assertFalse($schema->checkRequired($doc));
    }

    public function testObjectMissingFail() {
        $objectSchema = array(
            'basicField' => array(
                '__required' => true,
            ),
        );

        $schema_array = array(
            'basicObject' => array(
                '__schema' => new TestSchema($objectSchema),
            ),
        );
        $doc = array (
        );
        $schema = new TestSchema($schema_array);
//        $this->assertTrue($schema->isFieldValid('basicObject.basicField',$doc['basicObject']['basicField']));
        $this->assertFalse($schema->checkRequired($doc));
    }

    public function testObjectRequiredFail() {
        $objectSchema = array(
            'basicField' => array(
            ),
        );

        $schema_array = array(
            'basicObject' => array(
                '__schema' => new TestSchema($objectSchema),
                '__required' => true,
            ),
        );
        $doc = array (
        );
        $schema = new TestSchema($schema_array);
//        $this->assertTrue($schema->isFieldValid('basicObject.basicField',$doc['basicObject']['basicField']));
        $this->assertFalse($schema->checkRequired($doc));
    }

    public function testArray() {
        $objectSchema = array(
            'basicField' => array(
                '__required' => true,
            ),
        );

        $schema_array = array(
            'basicObject' => array(
                '__schema' => new TestSchema($objectSchema),
            ),
        );
        $doc = array (
            'basicObject' => array(
                'basicField' => 'anything',
            ),
        );
        $schema = new TestSchema($schema_array);
//        $this->assertTrue($schema->isFieldValid('basicObject.basicField',$doc['basicObject']['basicField']));
        $this->assertTrue($schema->checkRequired($doc));
    }

    public function testArrayFail() {
        $objectSchema = array(
            'basicField' => array(
                '__required' => true,
            ),
        );

        $schema_array = array(
            'basicObject' => array(
                '__schema' => new TestSchema($objectSchema),
            ),
        );
        $doc = array (
            'basicObject' => array(
                //'basicField' => 'anything',
            ),
        );
        $schema = new TestSchema($schema_array);
//        $this->assertTrue($schema->isFieldValid('basicObject.basicField',$doc['basicObject']['basicField']));
        $this->assertFalse($schema->checkRequired($doc));
    }

}
