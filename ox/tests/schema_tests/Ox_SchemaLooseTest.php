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


require_once(dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'boot.php');
require_once(dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'lib/TestSchema.php');



class LooseRunableSchema extends Ox_Schema
{
    public function __construct()
    {
        $this->_schema = array(
            'testRegEx' => array(new Ox_RegExValidator('/^\w+$/')),  //required false . no default
            'testRegExFail' => array( new Ox_RegExValidator('/^\w+$/'), false), //default of true
            'testFunction' =>  array( new Ox_RegExValidator('/^\w+$/') ),
            'object.field' =>  array( new Ox_RegExValidator('/^\w+$/') ),
            'array.$.field' => array( new Ox_RegExValidator('/^passes$/')),
            'array.$.fails' => array( new Ox_RegExValidator('/^duck$/')),
            'subarray.$.array.$.f' =>  array( new Ox_RegExValidator('/^\w$/') ),
            'subarray.$.array.$.f2' => array( new Ox_RegExValidator('/^a$/') ),
        );
    }
}

class LoosePassSchema extends Ox_Schema
{

    public function __construct()
    {
        $this->_schema = array(
            'testRegEx' => array(new Ox_RegExValidator('/^\w+$/')),  //required false . no default
            'testRegExFail' => array( new Ox_RegExValidator('/^.\w+$/'), false), //default of true
            'testFunction' =>  array( new Ox_RegExValidator('/^\w+$/') ),
            'object.field' =>  array( new Ox_RegExValidator('/^\w+$/') ),
            'array.$.field' => array( new Ox_RegExValidator('/^passes$/')),
            'array.$.fails' => array( new Ox_RegExValidator('/^duck$/')),
            'subarray.$.array.$.f' =>  array( new Ox_RegExValidator('/^\w$/') ),
            'subarray.$.array.$.f2' => array( new Ox_RegExValidator('/^\w$/') ),
        );
    }
}


/**
 */
class Ox_SchemaLooseTest extends PHPUnit_Framework_TestCase {

    private $_doc;
    private $_schema;

    public function setUp() {
        $this->_doc = array(
            'testRegEx' => 'passes',
            'testRegExFail' => '.fails',
            'testFunction' => 'passes',
            'testBadValidator' => 'exception',
            'noValidator' => 'passes',
            'object' => array(
                'field' => 'passes'
            ),
            'array' => array (
                array ('field' => 'passes', 'fails'=> 'duck'),
                array ('field' => 'passes', 'fails'=> 'duck'),
                array ('field' => 'passes', 'fails'=> 'duck'),
                array ('field' => 'passes', 'fails'=> 'goose'),
            ),
            'subarray' => array (
                array ('array' =>
                    array(
                        array('f'=>'a','f2'=>'a'),
                        array('f'=>'a','f2'=>'a'),
                        array('f'=>'a','f2'=>'a'),
                        array('f'=>'a','f2'=>'a'),
                        array('f'=>'b','f2'=>'a'),
                    ),
                ),
                array ('array' =>
                    array(
                        array('f'=>'a','f2'=>'a'),
                        array('f'=>'a','f2'=>'a'),
                        array('f'=>'a','f2'=>'a'),
                        array('f'=>'a','f2'=>'a'),
                        array('f'=>'b','f2'=>'b'),
                    ),
                ),
            ),
        );

        $schema = array(
            'testRegEx' => array(new Ox_RegExValidator('/^\w+$/')),  //required false . no default
            'testRegExFail' => array( new Ox_RegExValidator('/^\w+$/'), false), //default of true
            'testFunction' =>  array( new Ox_RegExValidator('/^\w+$/') ),
            'object.field' =>  array( new Ox_RegExValidator('/^\w+$/') ),
            'array.$.field' => array( new Ox_RegExValidator('/^passes$/')),
            'array.$.fails' => array( new Ox_RegExValidator('/^duck$/')),
            'subarray.$.array.$.f' =>  array( new Ox_RegExValidator('/^\w$/') ),
            'subarray.$.array.$.f2' => array( new Ox_RegExValidator('/^a$/') ),
        );
        $this->_schema = new TestSchema($schema);
    }
    public function tearDown()
    {
    }

    public function testBasic()
    {

        $this->assertTrue(true);
    }
/*
    public function testSimpleField()
    {
        $schema_array = array(
          'basicField' => array(new Ox_DefaultValidator()),
        );
        $doc = array (
            'basicField' => 'anything',
        );
        $schema = new TestSchema($schema_array);
        $this->assertTrue($schema->isFieldValid('basicField',$doc));
        $this->assertTrue($schema->isValid($doc));
    }

    public function testSimpleFieldFail()
    {
        $schema_array = array(
            'basicField' => array(new Ox_RegExValidator('/^\w+$/')),
        );
        $doc = array (
            'basicField' => 'any-thing',
        );
        $schema = new TestSchema($schema_array);
        $this->assertFalse($schema->isFieldValid('basicField',$doc));
        $this->assertFalse($schema->isValid($doc));
    }

    public function testObjectField()
    {
        $schema_array = array(
            'object' => array(
                 '__required' => true,
                 '__default',
                 'field' => new Ox_DefaultValidator(),
            ),
        );
        $doc = array (
            'object' => array( 'field'=>'anything'),
        );
        $schema = new TestSchema($schema_array);
        $this->assertTrue($schema->isFieldValid('object',$doc));
        $this->assertTrue($schema->isFieldValid('object.field',$doc));
        $this->assertTrue($schema->isValid($doc));
    }

    public function testObjectFieldFailed()
    {
        $schema_array = array(
            'object' => array(
                'field' => new  Ox_RegExValidator('/^\w+$/'),
            ),
        );
        $doc = array (
            'object' => array( 'field'=>'any-thing'),
        );
        $schema = new TestSchema($schema_array);
        $this->assertFalse($schema->isFieldValid('object',$doc));
        $this->assertFalse($schema->isFieldValid('object.field',$doc));
        $this->assertFalse($schema->isValid($doc));
    }

    public function testRegExFail() {
        $this->assertFalse($this->_schema->isFieldValid('testRegExFail',$this->_doc));
    }
    public function testRegEx() {
        $this->assertTrue($this->_schema->isFieldValid('testRegEx',$this->_doc));
    }
    public function testNoValidator() {
        $this->assertTrue($this->_schema->isFieldValid('noValidator',$this->_doc));
    }
    public function testSubObject() {
        $this->assertTrue($this->_schema->isFieldValid('object.field',$this->_doc));
    }
    public function testArray() {
        $this->assertTrue($this->_schema->isFieldValid('array.$.field',$this->_doc));
    }
    public function testArrayFail() {
        $this->assertFalse($this->_schema->isFieldValid('array.$.fails',$this->_doc));
    }
    public function testSubArray() {
        $this->assertTrue($this->_schema->isFieldValid('subarray.$.array.$.f',$this->_doc));
    }
    public function testSubArrayFail() {
        $this->assertFalse($this->_schema->isFieldValid('subarray.$.array.$.f2',$this->_doc));
    }
    public function testWholeSchemaFail() {
        $this->_schema = new LooseRunableSchema();
        $this->assertFalse($this->_schema->isValid($this->_doc));
        print "errors:";
        print_r($this->_schema->getErrors());
        $errors = $this->_schema->getErrors();
        $this->assertEquals($errors[0],'Bad field: testRegExFail');
    }
    public function testWholeSchemaPass() {
        $this->_schema = new LoosePassSchema();

        $this->_doc['testRegExFail']='nowpass';
        unset($this->_doc['array'][3]);
        unset($this->_doc['subarray'][0]['array'][4]);

        $result = $this->_schema->isValid($this->_doc);
        if (!$result) {
            print "errors:";
            print_r($this->_schema->getErrors());
        }
        $this->assertTrue($result);
    }
*/
}
unlink(DIR_CONFIG . 'app.php');
