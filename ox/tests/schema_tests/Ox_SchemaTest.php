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
class Ox_SchemaTest extends PHPUnit_Framework_TestCase
{
    public function setUp() {
    }
    public function tearDown() {
    }

    public function testField() {
        $schema_array = array(
            'basicField' => array(
                '__validator' => new Ox_DefaultValidator(),
            ),
        );
        $doc = array (
            'basicField' => 'anything',
        );
        $schema = new TestSchema($schema_array);
        $this->assertTrue($schema->isFieldValid('basicField',$doc['basicField'],$doc));
        $this->assertTrue($schema->isValid($doc));
    }

    public function testFieldFail() {
        $schema_array = array(
            'basicField' => array(
                '__validator' => new Ox_RegExValidator('/^willFail$/'),
            ),
        );
        $doc = array (
            'basicField' => 'anything',
        );
        $schema = new TestSchema($schema_array);
        $this->assertFalse($schema->isFieldValid('basicField',$doc['basicField'],$doc));
        $this->assertFalse($schema->isValid($doc));
    }


    public function testObject() {
        $objectSchema = array(
            'basicField' => array(
                '__validator' => new Ox_DefaultValidator(),
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
        $this->assertTrue($schema->isValid($doc));
    }

    public function testObjectFail() {
        $objectSchema = array(
            'basicField' => array(
                '__validator' => new Ox_RegExValidator('/^willFail$/'),
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
        $this->assertFalse($schema->isValid($doc));
    }

    public function testEmbeddedObject() {
        $objectSchema = array(
            'basicField' => array(
                '__validator' => new Ox_RegExValidator('/^\w+/'),
            ),
        );

        $embedArray = array(
            'basicObject' => array(
                '__schema' => new TestSchema($objectSchema),
            ),
        );

        $schemaArray = array(
            'basicObject' => array(
                '__schema' => new TestSchema($embedArray),
            ),
        );
        $doc = array (
            'basicObject' => array(
                'embedObject' => array(
                    'basicField' => 'anything',
                ),
            ),
        );
        $schema = new TestSchema($schemaArray);
//        $this->assertTrue($schema->isFieldValid('basicObject.basicField',$doc['basicObject']['basicField']));
        $this->assertTrue($schema->isValid($doc));
    }

    public function testEmbeddedObjectFail() {
        $objectSchema = array(
            'basicField' => array(
                '__validator' => new Ox_RegExValidator('/^willFail$/'),
            ),
        );

        $embedArray = array(
            'embedObject' => array(
                '__schema' => new TestSchema($objectSchema),
            ),
        );

        $schemaArray = array(
            'basicObject' => array(
                '__schema' => new TestSchema($embedArray),
            ),
        );
        $doc = array (
            'basicObject' => array(
                'embedObject' => array(
                    'basicField' => 'anything',
                ),
            ),
        );
        $schema = new TestSchema($schemaArray);
//        $this->assertTrue($schema->isFieldValid('basicObject.basicField',$doc['basicObject']['basicField']));
        $this->assertFalse($schema->isValid($doc));
    }

    public function testArray() {
        $objectSchema = array(
            'basicField' => array(
                '__validator' => new Ox_RegExValidator('/^\w+$/'),
            ),
        );

        $schemaObject = new TestSchema($objectSchema);
        $schemaObject->setType(Ox_Schema::TYPE_ARRAY);

        $schemaArray = array(
            'basicArray' => array(
                '__schema' => $schemaObject,
            ),
        );
        $doc = array (
            'basicArray' => array(
                array('basicField' => 'anything'),
                array('basicField' => 'anything'),
                array('basicField' => 'anything'),
            ),
        );
        $schema = new TestSchema($schemaArray);
        $valid = $schema->isValid($doc);
        if (!$valid) {
            print_r($schema->getErrors());
        }
        $this->assertTrue($valid);
    }

    public function testArrayFail() {
        $objectSchema = array(
            'basicField' => array(
                '__validator' => new Ox_RegExValidator('/^\w+$/'),
            ),
        );

        $schemaObject = new TestSchema($objectSchema);
        $schemaObject->setType(Ox_Schema::TYPE_ARRAY);

        $schemaArray = array(
            'basicArray' => array(
                '__schema' => new TestSchema($objectSchema),
            ),
        );
        $doc = array (
            'basicArray' => array(
                array('basicField' => 'anything'),
                array('basicField' => 'anything'),
                array('basicField' => 'any-thing'), //this one fails
            ),
        );
        $schema = new TestSchema($schemaArray);
//        $this->assertTrue($schema->isFieldValid('basicObject.basicField',$doc['basicObject']['basicField']));
        $this->assertFalse($schema->isValid($doc));
    }

    public function testEmbedArray() {
        $objectSchema = array(
            'basicField' => array(
                '__validator' => new Ox_RegExValidator('/^\w+$/'),
            ),
        );

        $schemaObject = new TestSchema($objectSchema);
        $schemaObject->setType(Ox_Schema::TYPE_ARRAY);

        $embedArray = array(
            'embedArray' => array(
                '__schema' => $schemaObject,
            ),
        );

        $schemaObject = new TestSchema($embedArray);
        $schemaObject->setType(Ox_Schema::TYPE_ARRAY);

        $schemaArray = array(
            'basicArray' => array(
                '__schema' => $schemaObject,
            ),
        );
        $doc = array (
            'basicArray' => array(
                array('embedArray' => array(
                    array('basicField' => 'anything'),
                    array('basicField' => 'anything'),
                    array('basicField' => 'anything'),
                )),
                array('embedArray' => array(
                    array('basicField' => 'anything'),
                    array('basicField' => 'anything'),
                    array('basicField' => 'anything'),
                )),
                array('embedArray' => array(
                    array('basicField' => 'anything'),
                    array('basicField' => 'anything'),
                    array('basicField' => 'anything'),
                )),
            ),
        );
        $schema = new TestSchema($schemaArray);
//        $this->assertTrue($schema->isFieldValid('basicObject.basicField',$doc['basicObject']['basicField']));
        $valid = $schema->isValid($doc);
        if (!$valid) {
            print_r($schema->getErrors());
        }
        $this->assertTrue($valid);
    }


    public function testEmbedArrayFail() {
        $objectSchema = array(
            'basicField' => array(
                '__validator' => new Ox_RegExValidator('/^\w+$/'),
            ),
        );

        $schemaObject = new TestSchema($objectSchema);
        $schemaObject->setType(Ox_Schema::TYPE_ARRAY);

        $embedArray = array(
            'embedArray' => array(
                '__schema' => $schemaObject,
            ),
        );

        $schemaObject = new TestSchema($embedArray);
        $schemaObject->setType(Ox_Schema::TYPE_ARRAY);

        $schemaArray = array(
            'basicArray' => array(
                '__schema' => $schemaObject,
            ),
        );
        $doc = array (
            'basicArray' => array(
                array('embedArray' => array(
                    array('basicField' => 'anything'),
                    array('basicField' => 'anything'),
                    array('basicField' => 'anything'),
                )),
                array('embedArray' => array(
                    array('basicField' => 'anything'),
                    array('basicField' => 'anything'),
                    array('basicField' => 'anything'),
                )),
                array('embedArray' => array(
                    array('basicField' => 'anything'),
                    array('basicField' => 'anything'),
                    array('basicField' => 'any-thing'), //will fail
                )),
            ),
        );
        $schema = new TestSchema($schemaArray);
//        $this->assertTrue($schema->isFieldValid('basicObject.basicField',$doc['basicObject']['basicField']));
        $this->assertFalse($schema->isValid($doc));
    }
/*
    public function testArrayButHaveObjectFail() {
        $objectSchema = array(
            'basicField' => array(
                '__validator' => new Ox_RegExValidator('/^\w+$/'),
            ),
        );

        $schemaObject = new TestSchema($objectSchema);
        $schemaObject->setType(Ox_Schema::TYPE_ARRAY);

        $schemaArray = array(
            'basicArray' => array(
                '__schema' => new TestSchema($objectSchema),
            ),
        );
        $doc = array (
            'basicArray' => array(
                'basicField' => 'anything',
            ),
        );
        $schema = new TestSchema($schemaArray);
//        $this->assertTrue($schema->isFieldValid('basicObject.basicField',$doc['basicObject']['basicField']));
        $this->assertFalse($schema->isValid($doc));
    }
*/
}
