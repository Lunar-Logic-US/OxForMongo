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
require_once(dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . '/lib/TestAsset.php');


/**
 */
class Ox_AssetTest extends PHPUnit_Framework_TestCase {

    public function setUp() {
    }
    public function tearDown() {
    }

    public function testSave()
    {
        $db = Ox_LibraryLoader::Db();
        $db->assets->drop();
        $file_info = array();
        $file_info["file"]["tmp_name"] = __FILE__;
        $file_info['file']['name']='Name';
        $file_info['file']['type']='Type';
        $file_info['file']['size']='Size';
        $md5_file = md5_file(__FILE__);
        $asset = new TestAsset();
        $doc = $asset->save($file_info);
        $this->assertEquals($file_info['file']['name'],$doc['original_name']);
        $this->assertEquals($file_info['file']['type'],$doc['type']);
        $this->assertEquals($file_info['file']['size'],$doc['size']);
        $this->assertEquals($md5_file,$doc['md5']);

        //check what was saved in the database
        $result = $db->assets->findOne();
        //print_r($result);
        $this->assertEquals($file_info['file']['name'],$result['original_name']);
        $this->assertEquals($file_info['file']['type'],$result['type']);
        $this->assertEquals($file_info['file']['size'],$result['size']);
        $this->assertEquals($md5_file,$result['md5']);


    }

    public function testSaveNoFile()
    {
        $db = Ox_LibraryLoader::Db();
        $db->assets->drop();
        $file_info = array();
        $file_info["file"]["tmp_name"] = __FILE__ . 'XXX';
        $file_info['file']['name']='Name';
        $file_info['file']['type']='Type';
        $file_info['file']['size']='Size';
        $asset = new TestAsset();
        $result = $asset->save($file_info);
        $this->assertEquals($result,null);

        //check what was saved in the database
        $result = $db->assets->findOne();
        print_r($result);
        $this->assertEquals($result,null);


    }

}