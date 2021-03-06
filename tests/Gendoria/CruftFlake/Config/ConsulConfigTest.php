<?php

namespace Gendoria\CruftFlake\Config;

use Gendoria\CruftFlake\Config\ConsulConfig;
use PHPUnit_Framework_TestCase;
use RuntimeException;

/**
 * Description of ConsulConfigTest
 *
 * @author Tomasz Struczyński <tomasz.struczynski@isobar.com>
 */
class ConsulConfigTest extends PHPUnit_Framework_TestCase
{
    public function testGetMachineIdOnEmptyCurrent()
    {
        $kvPrefix = 'test/';
        $sessionId = 'test';
        $sessionCreatePayload = $payload = array(
            'TTL' => '600s',
            "Behavior" => "delete",
            'LockDelay' => '300s',
        );
        $curl = $this->getMock('\Gendoria\CruftFlake\Config\ConsulCurl', array(), array(''));
        $curl->expects($this->any())
            ->method('performPutRequest')
            ->will($this->returnValueMap(array(
                array('/session/create', json_encode($sessionCreatePayload), array('ID' => $sessionId)),
                array('/kv/'.$kvPrefix.'?acquire='.$sessionId.'&flags=0', $sessionId, true),
                array('/kv/'.$kvPrefix.$sessionId.'?acquire='.$sessionId, 0, true),
            )));
        $config = new ConsulConfig($curl, 600, $kvPrefix);
        $machine = $config->getMachine();
        $this->assertEquals(0, $machine);
    }
    
    public function testGetMachineIdException()
    {
        $this->setExpectedException('\RuntimeException', 'Could not register machine ID on consul');
        $kvPrefix = 'test/';
        $sessionId = 'test';
        $sessionCreatePayload = $payload = array(
            'TTL' => '600s',
            "Behavior" => "delete",
            'LockDelay' => '300s',
        );
        $curl = $this->getMock('\Gendoria\CruftFlake\Config\ConsulCurl', array(), array(''));
        $curl->expects($this->any())
            ->method('performPutRequest')
            ->will($this->returnValueMap(array(
                array('/session/create', json_encode($sessionCreatePayload), array('ID' => $sessionId)),
                array('/kv/'.$kvPrefix.'?acquire='.$sessionId.'&flags=0', $sessionId, true),
                array('/kv/'.$kvPrefix.$sessionId.'?acquire='.$sessionId, 0, false),
            )));
        $config = new ConsulConfig($curl, 600, $kvPrefix);
        $machine = $config->getMachine();
        $this->assertEquals(0, $machine);
    }
    
    public function testGetMachineIdConsuleTimeout()
    {
        $kvPrefix = 'test/';
        $sessionId = 'test';
        $sessionCreatePayload = $payload = array(
            'TTL' => '600s',
            "Behavior" => "delete",
            'LockDelay' => '300s',
        );
        $curl = $this->getMock('\Gendoria\CruftFlake\Config\ConsulCurl', array(), array(''));
        $curl->expects($this->any())
            ->method('performPutRequest')
            ->will($this->returnValueMap(array(
                array('/session/create', json_encode($sessionCreatePayload), array('ID' => $sessionId)),
                array('/kv/'.$kvPrefix.'?acquire='.$sessionId."&flags=0", $sessionId, false),
                array('/kv/'.$kvPrefix.'?acquire='.$sessionId."&flags=1", $sessionId, true),
                array('/kv/'.$kvPrefix.$sessionId.'?acquire='.$sessionId, 0, true),
            )));
        $config = new ConsulConfig($curl, 600, $kvPrefix);
        $machine = $config->getMachine();
        $this->assertEquals(0, $machine);
    }    
    
    public function testGetMachineIdOnExistingCurrent()
    {
        $kvPrefix = 'test/';
        $sessionId = 'test';
        $sessionCreatePayload = $payload = array(
            'TTL' => '600s',
            "Behavior" => "delete",
            'LockDelay' => '300s',
        );
        $curl = $this->getMock('\Gendoria\CruftFlake\Config\ConsulCurl', array(), array(''));
        $curl->expects($this->any())
            ->method('performPutRequest')
            ->will($this->returnValueMap(array(
                array('/session/create', json_encode($sessionCreatePayload), array('ID' => $sessionId)),
                array('/kv/'.$kvPrefix.'?acquire='.$sessionId.'&flags=0', $sessionId, true),
                array('/kv/'.$kvPrefix.$sessionId.'?acquire='.$sessionId, 2, true),
            )));
        $curl->expects($this->any())
            ->method('performGetRequest')
            ->will($this->returnValueMap(array(
                array('/kv/'.$kvPrefix.'?recurse', array(
                    array(
                        'Key' => $kvPrefix,
                        'Value' => $sessionId,
                    ),
                    array(
                        'Key' => 'testold',
                        'Value' => base64_encode(0),
                    ),
                    array(
                        'Key' => 'testold',
                        'Value' => base64_encode(1),
                    ),
                    array(
                        'Key' => 'testold',
                        'Value' => base64_encode(3),
                    ),
                )),
            )));
        $config = new ConsulConfig($curl, 600, $kvPrefix);
        $machine = $config->getMachine();
        $this->assertEquals(2, $machine);
    }
    
    public function testGetMachineIdExisting()
    {
        $kvPrefix = 'test/';
        $sessionId = 'test';
        $sessionCreatePayload = $payload = array(
            'TTL' => '600s',
            "Behavior" => "delete",
            'LockDelay' => '300s',
        );
        $curl = $this->getMock('\Gendoria\CruftFlake\Config\ConsulCurl', array(), array(''));
        $curl->expects($this->any())
            ->method('performPutRequest')
            ->will($this->returnValueMap(array(
                array('/session/create', json_encode($sessionCreatePayload), array('ID' => $sessionId)),
                array('/kv/'.$kvPrefix.'?acquire='.$sessionId.'&flags=0', $sessionId, true),
                array('/kv/'.$kvPrefix.$sessionId.'?acquire='.$sessionId, 10, true),
            )));
        $curl->expects($this->any())
            ->method('performGetRequest')
            ->will($this->returnValueMap(array(
                array('/kv/'.$kvPrefix.$sessionId, array(
                    'Key' => $sessionId,
                    'Value' => base64_encode(10),
                )),
                array('/kv/'.$kvPrefix.'?recurse', array(
                    array(
                        'Key' => $kvPrefix,
                        'Value' => $sessionId,
                    ),
                )),
            )));
        $config = new ConsulConfig($curl, 600, $kvPrefix);
        $machine = $config->getMachine();
        $this->assertEquals(10, $machine);
    }    
    
    public function testGetMachineIdInCurrent()
    {
        $kvPrefix = 'test/';
        $sessionId = 'test';
        $sessionCreatePayload = $payload = array(
            'TTL' => '600s',
            "Behavior" => "delete",
            'LockDelay' => '300s',
        );
        $curl = $this->getMock('\Gendoria\CruftFlake\Config\ConsulCurl', array(), array(''));
        $curl->expects($this->any())
            ->method('performPutRequest')
            ->will($this->returnValueMap(array(
                array('/session/create', json_encode($sessionCreatePayload), array('ID' => $sessionId)),
                array('/kv/'.$kvPrefix.'?acquire='.$sessionId.'&flags=0', $sessionId, true),
                array('/kv/'.$kvPrefix.$sessionId.'?acquire='.$sessionId, 10, true),
            )));
        $curl->expects($this->any())
            ->method('performGetRequest')
            ->will($this->returnValueMap(array(
                array('/kv/'.$kvPrefix.'?recurse', array(
                    array(
                        'Key' => $kvPrefix,
                        'Value' => $sessionId,
                    ),
                    array(
                        'Key' => 'test',
                        'Value' => base64_encode(10),
                    ),
                )),
            )));
        $config = new ConsulConfig($curl, 600, $kvPrefix);
        $machine = $config->getMachine();
        $this->assertEquals(10, $machine);
    }    
    
    public function testGetMachineIdImpossible()
    {
        $this->setExpectedException('RuntimeException', 'Cannot acquire machine ID');
        $kvPrefix = 'test/';
        $sessionId = 'test';
        $sessionCreatePayload = $payload = array(
            'TTL' => '600s',
            "Behavior" => "delete",
            'LockDelay' => '300s',
        );
        $curl = $this->getMock('\Gendoria\CruftFlake\Config\ConsulCurl', array(), array(''));
        $curl->expects($this->any())
            ->method('performPutRequest')
            ->will($this->returnValueMap(array(
                array('/session/create', json_encode($sessionCreatePayload), array('ID' => $sessionId)),
                array('/kv/'.$kvPrefix.'?acquire='.$sessionId.'&flags=0', $sessionId, true),
            )));
        
        $filledValues = array_map(function($val) {
            return array(
                'Key' => 'test'.$val,
                'Value' => base64_encode($val),
            );
        }, range(0, 1023));
        
        $curl->expects($this->any())
            ->method('performGetRequest')
            ->will($this->returnValueMap(array(
                array('/kv/'.$kvPrefix.'?recurse', $filledValues),
            )));
        $config = new ConsulConfig($curl, 600, $kvPrefix);
        $config->getMachine();
    }
    
    public function testHeartbeatNothingToDo()
    {
        $kvPrefix = 'test/';
        $sessionId = 'test';
        $sessionCreatePayload = $payload = array(
            'TTL' => '200s',
            "Behavior" => "delete",
            'LockDelay' => '100s',
        );
        $curl = $this->getMock('\Gendoria\CruftFlake\Config\ConsulCurl', array(), array(''));
        $curl->expects($this->any())
            ->method('performPutRequest')
            ->will($this->returnValueMap(array(
                array('/session/create', json_encode($sessionCreatePayload), array('ID' => $sessionId)),
                array('/kv/'.$kvPrefix.'?acquire='.$sessionId.'&flags=0', $sessionId, true),
            )));
        
        $config = new ConsulConfig($curl, 200, $kvPrefix);
        $this->assertFalse($config->heartbeat());
    }
    
    public function testHeartbeatSessionRenevalSuccessfull()
    {
        $kvPrefix = 'test/';
        $sessionId = 'test';
        $sessionCreatePayload = $payload = array(
            'TTL' => '0s',
            "Behavior" => "delete",
            'LockDelay' => '0s',
        );
        $curl = $this->getMock('\Gendoria\CruftFlake\Config\ConsulCurl', array(), array(''));
        $curl->expects($this->any())
            ->method('performPutRequest')
            ->will($this->returnValueMap(array(
                array('/session/create', json_encode($sessionCreatePayload), array('ID' => $sessionId)),
                array('/kv/'.$kvPrefix.'?acquire='.$sessionId.'&flags=0', $sessionId, true),
                array("/session/renew/".$sessionId, null, true)
            )));
        
        $config = new ConsulConfig($curl, 0, $kvPrefix);
        $this->assertFalse($config->heartbeat());
    }
    
    public function testHeartbeatSessionRenevalUnsuccessfull()
    {
        $kvPrefix = 'test/';
        $sessionId = 'test';
        $sessionCreatePayload = $payload = array(
            'TTL' => '0s',
            "Behavior" => "delete",
            'LockDelay' => '0s',
        );
        $curl = $this->getMock('\Gendoria\CruftFlake\Config\ConsulCurl', array(), array(''));
        $curl->expects($this->any())
            ->method('performPutRequest')
            ->will($this->returnValueMap(array(
                array('/session/create', json_encode($sessionCreatePayload), array('ID' => $sessionId)),
                array('/kv/'.$kvPrefix.'?acquire='.$sessionId.'&flags=0', $sessionId, true),
                array("/session/renew/".$sessionId, null, false)
            )));
        
        $config = new ConsulConfig($curl, 0, $kvPrefix);
        $this->assertTrue($config->heartbeat());
    }
    
    public function testHeartbeatSessionRenevalAndCreationUnsuccessfull()
    {
        $this->setExpectedException('RuntimeException', 'Cannot create session');
        $kvPrefix = 'test/';
        $sessionId = 'test';
        $curl = $this->getMock('\Gendoria\CruftFlake\Config\ConsulCurl', array(), array(''));
        $curl->expects($this->any())
            ->method('performPutRequest')
            ->will($this->returnCallback(function($url) use ($sessionId, $kvPrefix) {
                static $invCount = 0;
                if ($url == '/kv/'.$kvPrefix.'?acquire='.$sessionId.'&flags=0') {
                    return true;
                } elseif($url == '/session/renew') {
                    return false;
                } elseif ($url == '/session/create') {
                    if ($invCount == 0) {
                        $invCount++;
                        return array('ID' => $sessionId);
                    } else {
                        return null;
                    }
                }
            }));
        
        try {
            $config = new ConsulConfig($curl, 0, $kvPrefix);
        } catch (RuntimeException $e) {
            $this->fail('Failed too quickly on '.$e->getMessage());
        }
        $config->heartbeat();
    }    
    
    public function testHeartbeatSessionRenevalAndCreationUnsuccessfullNoUpdateNeeded()
    {
        $kvPrefix = 'test/';
        $sessionId = 'test';
        $curl = $this->getMock('\Gendoria\CruftFlake\Config\ConsulCurl', array(), array(''));
        $curl->expects($this->any())
            ->method('performPutRequest')
            ->will($this->returnCallback(function($url) use ($sessionId, $kvPrefix) {
                static $invCount = 0;
                if ($url == '/kv/'.$kvPrefix.'?acquire='.$sessionId.'&flags=0') {
                    return true;
                } elseif($url == '/session/renew') {
                    return false;
                } elseif ($url == '/session/create') {
                    if ($invCount == 0) {
                        $invCount++;
                        return array('ID' => $sessionId);
                    } else {
                        return null;
                    }
                }
            }));
        
        try {
            $config = new ConsulConfig($curl, 10, $kvPrefix);
            sleep(5);
        } catch (RuntimeException $e) {
            $this->fail('Failed too quickly on '.$e->getMessage());
        }
        $this->assertFalse($config->heartbeat());
    }    
}
