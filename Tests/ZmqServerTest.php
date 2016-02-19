<?php

use Gendoria\CruftFlake\Generator\GeneratorStatus;
use Gendoria\CruftFlake\Zmq\ZmqServer;
use Psr\Log\NullLogger;

/**
 * Test the ZMQ server.
 *
 * @author Tomasz Struczyński <tomasz.struczynski@isobar.com>
 */
class ZmqServerTest extends PHPUnit_Framework_TestCase
{
    public function testGenerate()
    {
        $generator = $this->getMock('\Gendoria\CruftFlake\Generator\Generator', array('generate', 'heartbeat'), array(), '', false);
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
    
    /**
     * Test ticket generation after some sleep - to allow heartbeat operation to be called.
     */
    public function testGenerateAfterTimeout()
    {
        $generator = $this->getMock('\Gendoria\CruftFlake\Generator\Generator', array('generate', 'heartbeat'), array(), '', false);
        $generator->expects($this->once())
            ->method('generate')
            ->will($this->returnValue(10));
        $generator->expects($this->once())
            ->method('heartbeat');

        $socket = $this->getMock('ZMQSocket', array('recv', 'send'), array(), '', false);
        $socket->expects($this->once())
            ->method('recv')
            ->willReturnCallback(function() {
                sleep(6);
                return 'GEN';
        });
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
    
    public function testGenerateException()
    {
        $generator = $this->getMock('\Gendoria\CruftFlake\Generator\Generator', array('generate', 'heartbeat'), array(), '', false);
        $generator->expects($this->once())
            ->method('generate')
            ->will($this->throwException(new Exception()));

        $socket = $this->getMock('ZMQSocket', array('recv', 'send'), array(), '', false);
        $socket->expects($this->once())
            ->method('recv')
            ->willReturn('GEN');
        $socket->expects($this->once())
            ->method('send')
            ->with('{"code":500,"message":"ERROR"}');
        
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
        $generator = $this->getMock('\Gendoria\CruftFlake\Generator\Generator', array('status', 'heartbeat'), array(), '', false);
        $generatorStatus = new GeneratorStatus(1, 1, 1, true);
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
        $generator = $this->getMock('\Gendoria\CruftFlake\Generator\Generator', array('status', 'heartbeat'), array(), '', false);

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
    
    public function testHeartBeat()
    {
        $generator = $this->getMock('\Gendoria\CruftFlake\Generator\Generator', array('status', 'heartbeat'), array(), '', false);
        $generator->expects($this->once())
            ->method('heartbeat');

        $socket = $this->getMock('ZMQSocket', array('recv', 'send'), array(), '', false);
        $socket->expects($this->once())
            ->method('recv')
            ->willReturn(false);
        $socket->expects($this->never())
            ->method('send');
        
        $server = $this->getMock('\Gendoria\CruftFlake\Zmq\ZmqServer', array('getZmqSocket'), array($generator, 5599, true));
        
        $server->expects($this->once())
            ->method('getZmqSocket')
            ->with(5599)
            ->will($this->returnValue($socket));        
        
        /* @var $server ZmqServer */
        $server->run();
    }
    
    public function testSetLogger()
    {
        $generator = $this->getMock('\Gendoria\CruftFlake\Generator\Generator', array('status', 'heartbeat'), array(), '', false);
        $server = new ZmqServer($generator, 5599, true);
        $logger = new NullLogger();
        $server->setLogger($logger);
        $refl = new ReflectionObject($server);
        $loggerProp = $refl->getProperty('logger');
        $loggerProp->setAccessible(true);
        $this->assertSame($logger, $loggerProp->getValue($server));
    }
    
    public function testGetSmqSocket()
    {
        $generator = $this->getMock('\Gendoria\CruftFlake\Generator\Generator', array('status', 'heartbeat'), array(), '', false);
        $server = new ZmqServer($generator, 5599, true);
        $refl = new ReflectionObject($server);
        $socketMethod = $refl->getMethod('getZmqSocket');
        $socketMethod->setAccessible(true);
        $socket = $socketMethod->invoke($server, 'tcp://*:5599');
        $this->assertInstanceOf('\ZmqSocket', $socket);
        $this->assertEquals(array('connect' => array(), 'bind' => array('tcp://*:5599')), $socket->getEndpoints());
        $socket->unbind('tcp://*:5599');
    }
}
