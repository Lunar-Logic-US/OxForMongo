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

class Ox_MongoSourceTest extends PHPUnit_Framework_TestCase {

    public function testNewMongoIdCompatible()
    {
        $str = 'testing';
        $mongoId = Ox_MongoSource::newMongoIdCompatible($str);
        $this->assertTrue($mongoId instanceof MongoId);

        $str = '';
        $mongoId = Ox_MongoSource::newMongoIdCompatible($str);
        $this->assertTrue($mongoId instanceof MongoId);

        $str = null;
        $mongoId = Ox_MongoSource::newMongoIdCompatible($str);
        $this->assertTrue($mongoId instanceof MongoId);

        $mongoStr = '532373d99a8c65fbe6c15d83';
        $mongoId = Ox_MongoSource::newMongoIdCompatible($mongoStr);
        $this->assertTrue($mongoId instanceof MongoId);
        $this->assertEquals($mongoId->__toString(),$mongoStr);

        $mongoStr = '532373d99a8c65fbe6c15d83';
        $mongoIdTest = new MongoId($mongoStr);
        $mongoId = Ox_MongoSource::newMongoIdCompatible($mongoIdTest);
        $this->assertTrue($mongoId instanceof MongoId);
        $this->assertEquals($mongoId->__toString(),$mongoStr);

    }

}
 