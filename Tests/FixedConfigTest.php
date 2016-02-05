<?php

/**
 * Description of LocalClientTest
 *
 * @author Tomasz Struczyński <tomasz.struczynski@isobar.com>
 */
class FixedConfigTest extends PHPUnit_Framework_TestCase
{
    public function testCreate()
    {
        $config = new \Gendoria\CruftFlake\Config\FixedConfig(1);
        $config->setLogger(new \Psr\Log\NullLogger());
        $this->assertEquals(1, $config->getMachine());
    }
    
    public function testCreateWithLogger()
    {
        $config = new \Gendoria\CruftFlake\Config\FixedConfig(1, new \Psr\Log\NullLogger());
        $this->assertEquals(1, $config->getMachine());
    }
    
    public function testHeartbeat()
    {
        $config = new \Gendoria\CruftFlake\Config\FixedConfig(10);
        $this->assertEquals(10, $config->getMachine());
        $this->assertFalse($config->heartbeat());
        $this->assertEquals(10, $config->getMachine());
    }
}