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
class Ox_SchemaDefaultTest extends PHPUnit_Framework_TestCase {

    public function setUp() {
    }
    public function tearDown() {
    }

    public function testFieldReplaceIfMissing() {
        $schema_array = array(
            'basicField' => array(
                '__required' => true,
                '__default' => 'valueInserted'
            ),
        );
        $doc = array (
            //'basicField' => 'anything',
        );
        $schema = new TestSchema($schema_array);
        $schema->setInjectOnMissing(true);
        $schema->setReplaceOnInvalid(false);
        $valid = $schema->isValid($doc);
        //print_r($doc);
        $this->assertEquals($doc['basicField'],'valueInserted');

    }

    public function testFieldReplaceIfInvalid() {
        $schema_array = array(
            'basicField' => array(
                '__validator' => new Ox_RegExValidator('/w+/'),
                '__default' => 'valueInserted'
            ),
        );
        $doc = array (
            'basicField' => 'any-thing',
        );
        $schema = new TestSchema($schema_array);
        $schema->setInjectOnMissing(false);
        $schema->setReplaceOnInvalid(true);
        $valid = $schema->isValid($doc);
        //print_r($doc);
        $this->assertEquals($doc['basicField'],'valueInserted');

    }


}