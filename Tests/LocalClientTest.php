<?php

use Gendoria\CruftFlake\Config\FixedConfig;
use Gendoria\CruftFlake\Generator\Generator;
use Gendoria\CruftFlake\Local\LocalClient;
use Gendoria\CruftFlake\Timer\Timer;

/**
 * Description of LocalClientTest
 *
 * @author Tomasz Struczyński <tomasz.struczynski@isobar.com>
 */
class LocalClientTest extends PHPUnit_Framework_TestCase
{

    public function testGenerate()
    {
        $timer = new Timer();
        $config = new FixedConfig(1);
        $generator = new Generator($config, $timer);

        $cf = new LocalClient($generator);
        
        $id = $cf->generateId();
        
        $this->assertInternalType('string', $id);
    }
    
    public function testStatus()
    {
        $timer = new Timer();
        $config = new FixedConfig(1);
        $generator = new Generator($config, $timer);

        $cf = new LocalClient($generator);
        
        $cf->generateId();
        $cf->generateId();
        
        $status = $cf->status();
        
        $this->assertInstanceOf('Gendoria\CruftFlake\Generator\GeneratorStatus', $status);
        $this->assertEquals(1,  $status->sequence);
    }
}