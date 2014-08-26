<?php

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

class Ox_MongoCollectionTest extends PHPUnit_Framework_TestCase {
    public function testNormalizeIdString()
    {
        //Basic String
        $str = 'testing';
        $mongoId = Ox_MongoCollection::normalizeId($str);
        $this->assertEquals($mongoId,$str);

        //Something like a mongoId but not quite
        $str = '5323-73d99-a8c65f-be6c1-5d83';
        $mongoId = Ox_MongoCollection::normalizeId($str);
        $this->assertEquals($mongoId,$str);
    }
    public function testNormalizeIdMongoIdString()
    {
        $mongoStr = '532373d99a8c65fbe6c15d83';
        $mongoId = Ox_MongoCollection::normalizeId($mongoStr);
        $this->assertTrue($mongoId instanceof MongoId);
        $this->assertEquals($mongoId->__toString(),$mongoStr);

        //Bad mongoid
        $mongoStr = 'Z32373d99a8c65fbe6c15d83';
        $mongoId = Ox_MongoCollection::normalizeId($mongoStr);
        $this->assertFalse($mongoId instanceof MongoId);
        $this->assertEquals($mongoId,$mongoStr);
    }
    public function testNormalizeIdArray()
    {
        $mongoStr = '532373d99a8c65fbe6c15d83';
        $mongoId = new MongoId($mongoStr);
        $testArray = array(
            '_id' => $mongoId,
            'other_property' => 'blah',
        );
        $mongoId = Ox_MongoCollection::normalizeId($testArray);
        $this->assertTrue($mongoId instanceof MongoId);
        $this->assertEquals($mongoId->__toString(),$mongoStr);

        //no _id
        $testArray = array(
            'id' => $mongoId,
            'other_property' => 'blah',
        );
        $mongoId = Ox_MongoCollection::normalizeId($testArray);
        $this->assertEquals($mongoId,$testArray);

    }
}
 