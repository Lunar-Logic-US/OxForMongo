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

class WalkSchema extends Ox_Schema
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


/**
 */
class Ox_SchemaWalkTest extends PHPUnit_Framework_TestCase {

    private $_doc;
    private $_schema;
    private $_db;

    public function setUp() {
        $this->_db = Ox_LibraryLoader::getResource('db');
        $this->_doc = array(
            'testRegEx' => 'passes',
            'testRegExFail' => 'fails',
            'testFunction' => 'passes',
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
            'fake array' => array (
                0 => array ('field' => 'passes', 'fails'=> 'duck'),
                '1' => array ('field' => 'passes', 'fails'=> 'duck'),
                //'bob' => array ('field' => 'passes', 'fails'=> 'duck'),
                //'cat' => array ('field' => 'passes', 'fails'=> 'goose'),
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
        $this->_schema = new WalkSchema();
        $this->_db->schematest->drop();
        $this->_db->schematest->insert($this->_doc);
        $fromDB = $this->_db->schematest->findOne();
        //var_dump($fromDB);
        //die();
    }


    public function tearDown() {
    }

    public function testRegEx() {
        //$this->assertTrue($this->_schema->isValid($this->_doc));
    }
    //TODO write tests to make sure the PHP driver works as we expect.
    //see https://jira.mongodb.org/browse/SERVER-1475 on how to test the mongo side as well as the php side.
    //to make sure we are testing correectly.


}
