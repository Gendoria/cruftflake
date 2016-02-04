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
}