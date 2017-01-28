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
//require_once (OX_FRAME_EXCEPTIONS . 'Ox_Exception.php');

require_once(
    dirname(__FILE__) . DIRECTORY_SEPARATOR .
    'boot' . DIRECTORY_SEPARATOR .
    'boot.php'
);

/**
 * Ox_LocalAssetTest
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class Ox_LocalAssetTest extends PHPUnit_Framework_TestCase {
    public function setUp() {
        //define ('DIR_FRAMEWORK',dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR );
        //define ('DIR_FRAMELIB', DIR_FRAMEWORK . 'lib' . DIRECTORY_SEPARATOR );
        //define ('OX_FRAME_DEFAULT', DIR_FRAMEWORK . 'default' . DIRECTORY_SEPARATOR);
        //define ('OX_FRAME_EXCEPTIONS', DIR_FRAMELIB . 'exceptions' . DIRECTORY_SEPARATOR);
        require_once(DIR_FRAMELIB.'Ox_Asset.php');
    }
    public function tearDown() {
    }

    public function testCreateUrl() {
        $asset_helper = new LocalAsset('/assets/');
        $asset = array(
            "_id"=>new MongoId("4efa53fa7932046105000000"),
            "original_name"=>"cat-face.jpg",
            "type"=>"image/jpeg",
            "size"=> 30516,
            "md5"=>"fd1360f059ac87571d78f5d25a545749"
        );
        $this->assertEquals('/assets/4efa53fa7932046105000000.jpg', $asset_helper->createURI($asset));
    }
}
