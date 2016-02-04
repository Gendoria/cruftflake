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
        $generator = $this->getMock('\Gendoria\CruftFlake\Generator\Generator', array('generate'), array(), '', false);
        $generator->expects($this->once())
            ->method('generate')
            ->will($this->returnValue(10));

        $socket = $this->getMock('ZMQSocket', array('recv', 'send'), array(), '', false);
        $socket->expects($this->once())
            ->method('recv')
            ->willReturn('GEN');
        $socket->expects($this->once())
            ->method('send')
            ->with('{"code":200,"message":10}');
        
        $server = $this->getMock('\Gendoria\CruftFlake\Zmq\ZmqServer', array('getZmqSocket'), array($generator, 5599, true));
        
        $server->expects($this->once())
            ->method('getZmqSocket')
            ->with(5599)
            ->will($this->returnValue($socket));        
        
        /* @var $server ZmqServer */
        $server->run();
    }
    
    public function testStatus()
    {
        $generator = $this->getMock('\Gendoria\CruftFlake\Generator\Generator', array('status'), array(), '', false);
        $generatorStatus = new Gendoria\CruftFlake\Generator\GeneratorStatus(1, 1, 1, true);
        $generator->expects($this->once())
            ->method('status')
            ->will($this->returnValue($generatorStatus));

        $socket = $this->getMock('ZMQSocket', array('recv', 'send'), array(), '', false);
        $socket->expects($this->once())
            ->method('recv')
            ->willReturn('STATUS');
        $socket->expects($this->once())
            ->method('send')
            ->with('{"code":200,"message":{"machine":1,"lastTime":1,"sequence":1,"is32Bit":true}}');
        
        $server = $this->getMock('\Gendoria\CruftFlake\Zmq\ZmqServer', array('getZmqSocket'), array($generator, 5599, true));
        
        $server->expects($this->once())
            ->method('getZmqSocket')
            ->with(5599)
            ->will($this->returnValue($socket));        
        
        /* @var $server ZmqServer */
        $server->run();
    }
    
    public function testUnknownCommand()
    {
        $generator = $this->getMock('\Gendoria\CruftFlake\Generator\Generator', array('status'), array(), '', false);

        $socket = $this->getMock('ZMQSocket', array('recv', 'send'), array(), '', false);
        $socket->expects($this->once())
            ->method('recv')
            ->willReturn('DUMMY_UNKNOWN_COMMAND');
        $socket->expects($this->once())
            ->method('send')
            ->with('{"code":404,"message":"UNKNOWN COMMAND"}');
        
        $server = $this->getMock('\Gendoria\CruftFlake\Zmq\ZmqServer', array('getZmqSocket'), array($generator, 5599, true));
        
        $server->expects($this->once())
            ->method('getZmqSocket')
            ->with(5599)
            ->will($this->returnValue($socket));        
        
        /* @var $server ZmqServer */
        $server->run();
    }    
}
