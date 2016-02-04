<?php

use Gendoria\CruftFlake\Server\ZmqServer;

/**
 * Test the ZMQ server.
 *
 * @author Tomasz Struczyński <tomasz.struczynski@isobar.com>
 */
class ZmqServerTest extends PHPUnit_Framework_TestCase
{
    public function testGenerate()
    {
        $generator = $this->getMock('\Gendoria\CruftFlake\Generator', array('generate'), array(), '', false);
        $generator->expects($this->once())
            ->method('generate')
            ->will($this->returnValue(10));

        $socket = $this->getMock('ZMQSocket', array('recv', 'send'), array(), '', false);
        $socket->expects($this->once())
            ->method('recv')
            ->willReturn('GEN');
        $socket->expects($this->once())
            ->method('send')
            ->with(10);
        
        $server = $this->getMock('\Gendoria\CruftFlake\Server\ZmqServer', array('getZmqSocket'), array($generator, 5599, true));
        
        $server->expects($this->once())
            ->method('getZmqSocket')
            ->with(5599)
            ->will($this->returnValue($socket));        
        
        /* @var $server ZmqServer */
        $server->run();
    }
    
    public function testStatus()
    {
        $generator = $this->getMock('\Gendoria\CruftFlake\Generator', array('status'), array(), '', false);
        $generator->expects($this->once())
            ->method('status')
            ->will($this->returnValue(10));

        $socket = $this->getMock('ZMQSocket', array('recv', 'send'), array(), '', false);
        $socket->expects($this->once())
            ->method('recv')
            ->willReturn('STATUS');
        $socket->expects($this->once())
            ->method('send')
            ->with(10);
        
        $server = $this->getMock('\Gendoria\CruftFlake\Server\ZmqServer', array('getZmqSocket'), array($generator, 5599, true));
        
        $server->expects($this->once())
            ->method('getZmqSocket')
            ->with(5599)
            ->will($this->returnValue($socket));        
        
        /* @var $server ZmqServer */
        $server->run();
    }
    
    public function testUnknownCommand()
    {
        $generator = $this->getMock('\Gendoria\CruftFlake\Generator', array('status'), array(), '', false);

        $socket = $this->getMock('ZMQSocket', array('recv', 'send'), array(), '', false);
        $socket->expects($this->once())
            ->method('recv')
            ->willReturn('DUMMY_UNKNOWN_COMMAND');
        $socket->expects($this->once())
            ->method('send')
            ->with('UNKNOWN COMMAND');
        
        $server = $this->getMock('\Gendoria\CruftFlake\Server\ZmqServer', array('getZmqSocket'), array($generator, 5599, true));
        
        $server->expects($this->once())
            ->method('getZmqSocket')
            ->with(5599)
            ->will($this->returnValue($socket));        
        
        /* @var $server ZmqServer */
        $server->run();
    }    
}
