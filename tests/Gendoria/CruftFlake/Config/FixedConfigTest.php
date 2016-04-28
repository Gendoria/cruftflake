<?php

namespace Gendoria\CruftFlake\Config;

use PHPUnit_Framework_TestCase;
use Psr\Log\NullLogger;

/**
 * Description of LocalClientTest
 *
 * @author Tomasz Struczyński <tomasz.struczynski@isobar.com>
 */
class FixedConfigTest extends PHPUnit_Framework_TestCase
{
    public function testCreate()
    {
        $config = new FixedConfig(1);
        $config->setLogger(new NullLogger());
        $this->assertEquals(1, $config->getMachine());
    }
    
    public function testCreateWithLogger()
    {
        $config = new FixedConfig(1, new NullLogger());
        $this->assertEquals(1, $config->getMachine());
    }
    
    public function testHeartbeat()
    {
        $config = new FixedConfig(10);
        $this->assertEquals(10, $config->getMachine());
        $this->assertFalse($config->heartbeat());
        $this->assertEquals(10, $config->getMachine());
    }
}