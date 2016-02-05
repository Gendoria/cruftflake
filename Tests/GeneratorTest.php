<?php

use Gendoria\CruftFlake\Generator\Generator;

class GeneratorTest extends \PHPUnit_Framework_TestCase
{
    private $machineId = 1;
    
    private $timer;
    private $config;
    
    public function setUp()
    {
        $this->timer = $this->getMockBuilder('\Gendoria\CruftFlake\Timer\TimerInterface')
                            ->disableOriginalConstructor()
                            ->getMock();
        $this->config = $this->getMockBuilder('\Gendoria\CruftFlake\Config\ConfigInterface')
                            ->disableOriginalConstructor()
                            ->getMock();
    }
    
    /**
     * Get generator for normal tests.
     * 
     * @return Generator
     */
    private function buildSystemUnderTest()
    {
        $this->config->expects($this->once())
                     ->method('getMachine')
                     ->will($this->returnValue($this->machineId));
        return new Generator($this->config, $this->timer);
    }
    
    /**
     * Get generator for 32 bit tests.
     * 
     * @return Generator
     */
    private function buildSystemUnderTest32Bit()
    {
        $this->config->expects($this->once())
                     ->method('getMachine')
                     ->will($this->returnValue($this->machineId));
        $generator = $this->getMock('Gendoria\CruftFlake\Generator\Generator', array('is32Bit'), array($this->config, $this->timer));
        $generator->expects($this->any())
            ->method('is32Bit')
            ->will($this->returnValue(true));
        return $generator;
    }
    
    /**
     * Get generator for normal tests.
     * 
     * @return Generator
     */
    private function buildSystemUnderTestHeartbeat($newMachineId)
    {
        $this->config->expects($this->exactly(2))
                     ->method('getMachine')
                     ->will($this->onConsecutiveCalls($this->machineId, $newMachineId));
        $this->config->expects($this->once())
                     ->method('heartbeat')
                     ->will($this->returnValue(true));
        return new Generator($this->config, $this->timer);
    }
        
    private function assertId($id)
    {
        $this->assertTrue(is_string($id));
        $this->assertTrue(ctype_digit($id));
    }
    
    private function assertReallyNotEquals($v1, $v2)
    {
        $this->assertTrue($v1 !== $v2);
    }
    
    // ---
    
    public function testConstructs()
    {
        $cf = $this->buildSystemUnderTest();
        $this->assertInstanceOf('\Gendoria\CruftFlake\Generator\Generator', $cf);
    }
    
    public function testFailsWithBadMachineIdString()
    {
        $this->setExpectedException('\InvalidArgumentException');
        $this->machineId = '1';
        $cf = $this->buildSystemUnderTest();
    }
    
    public function testFailsWithBadMachineIdNegative()
    {
        $this->setExpectedException('\InvalidArgumentException');
        $this->machineId = -1;
        $cf = $this->buildSystemUnderTest();
    }

    public function testFailsWithBadMachineIdTooBig()
    {
        $this->setExpectedException('\InvalidArgumentException');
        $this->machineId = 1024;
        $cf = $this->buildSystemUnderTest();
    }

    public function testFailsWithBadMachineIdFloat()
    {
        $this->setExpectedException('\InvalidArgumentException');
        $this->machineId = 1.1;
        $cf = $this->buildSystemUnderTest();
    }

    public function testLargestPossibleMachineId()
    {
        $this->machineId = 1023;
        $cf = $this->buildSystemUnderTest();
        $this->assertInstanceOf('\Gendoria\CruftFlake\Generator\Generator', $cf);
    }
    
    public function testGenerate()
    {
        $this->timer->expects($this->once())
                    ->method('getUnixTimestamp')
                    ->will($this->returnValue(1341246960000));
        $cf = $this->buildSystemUnderTest();
        $id = $cf->generate();
        $this->assertId($id);
    }
    
    public function testGenerate32bit()
    {
        $this->timer->expects($this->once())
                    ->method('getUnixTimestamp')
                    ->will($this->returnValue(1341246960000));
        $cf = $this->buildSystemUnderTest32Bit();
        $id = $cf->generate();
        $this->assertId($id);
    }
    
    
    public function testGenerateForPerMillisecondCollisions()
    {
        $this->timer->expects($this->any())
                    ->method('getUnixTimestamp')
                    ->will($this->returnValue(1341246960000));
        $cf = $this->buildSystemUnderTest();
        
        $id1 = $cf->generate();
        $id2 = $cf->generate();
        $id3 = $cf->generate();
        
        $this->assertId($id1);
        $this->assertId($id2);
        $this->assertId($id3);
        
        $this->assertReallyNotEquals($id1, $id2);
        $this->assertReallyNotEquals($id2, $id3);
    }

    public function testGenerateAsksForTimeEachTime()
    {
        $this->timer->expects($this->at(0))
                    ->method('getUnixTimestamp')
                    ->will($this->returnValue(1341246960000));
        $this->timer->expects($this->at(1))
                    ->method('getUnixTimestamp')
                    ->will($this->returnValue(1341246960001));
        $cf = $this->buildSystemUnderTest();
        
        $id1 = $cf->generate();
        $id2 = $cf->generate();
        
        $this->assertId($id1);
        $this->assertId($id2);
        
        $this->assertReallyNotEquals($id1, $id2);
    }
    
    public function testFailsWithTimestampBeforeEpoch()
    {
        $this->timer->expects($this->at(0))
                    ->method('getUnixTimestamp')
                    ->will($this->returnValue(1325375999999));
        $cf = $this->buildSystemUnderTest();
        
        $e = NULL;
        try {
            $id1 = $cf->generate();
        } catch (UnexpectedValueException $e) {
            
        }
        $this->assertInstanceOf('\UnexpectedValueException', $e);
        $this->assertEquals('Time is currently set before our epoch - unable to generate IDs for 1 milliseconds', $e->getMessage());
    }
    
    public function testFailsIfTimeRunsBackwards()
    {
        //$this->setExpectedException('\UnexpectedValueException');
        
        $this->timer->expects($this->at(0))
                    ->method('getUnixTimestamp')
                    ->will($this->returnValue(1341246960001));
        $this->timer->expects($this->at(1))
                    ->method('getUnixTimestamp')
                    ->will($this->returnValue(1341246960000));
        $cf = $this->buildSystemUnderTest();
        
        $id1 = $cf->generate();
        
        $e = NULL;
        try {
            $id2 = $cf->generate();
        } catch (UnexpectedValueException $e) {
            
        }
        $this->assertInstanceOf('\UnexpectedValueException', $e);
        $this->assertEquals('Time moved backwards. We cannot generate IDs for 1 milliseconds', $e->getMessage());
    }
    
    public function testFullSequenceRange()
    {
        $this->timer->expects($this->any())
                    ->method('getUnixTimestamp')
                    ->will($this->returnValue(1341246960000));
        $cf = $this->buildSystemUnderTest();
        
        $ids = array();
        for ($i=0; $i<4095; $i++) {
            $id = $cf->generate();
            $ids[$id] = 1;
        }
        
        $this->assertEquals(4095, count($ids));
    }
    
    public function testFailsIfSequenceOverflow()
    {
        $this->timer->expects($this->any())
                    ->method('getUnixTimestamp')
                    ->will($this->returnValue(1341246960000));
        $cf = $this->buildSystemUnderTest();
        
        $ids = array();
        for ($i=0; $i<4096; $i++) {
            $id = $cf->generate();
            $ids[$id] = 1;
        }
        
        $e = NULL;
        try {
            $id2 = $cf->generate();
        } catch (OverflowException $e) {
            
        }
        $this->assertInstanceOf('\OverflowException', $e);
        $this->assertEquals('Sequence overflow (too many IDs generated) - unable to generate IDs for 1 milliseconds', $e->getMessage());
    }
    
    public function testSmallestTimestampId()
    {
        $this->machineId = 0;
        $this->timer->expects($this->any())
                    ->method('getUnixTimestamp')
                    ->will($this->returnValue(1325376000000));
        $cf = $this->buildSystemUnderTest();
        
        $id1 = $cf->generate();
        $id2 = $cf->generate();
        
        $this->assertId($id1);
        $this->assertId($id2);
        $this->assertReallyNotEquals($id1, $id2);
        
        $this->assertEquals(0, $id1);
    }
    
    public function testSmallestTimestampWithMachine()
    {
        $this->timer->expects($this->any())
                    ->method('getUnixTimestamp')
                    ->will($this->returnValue(1325376000000));
        $cf = $this->buildSystemUnderTest();
        
        $id1 = $cf->generate();
        $id2 = $cf->generate();
        
        $this->assertId($id1);
        $this->assertId($id2);
        $this->assertReallyNotEquals($id1, $id2);
        
        $this->assertEquals(1 << 12, $id1);
        $this->assertEquals(1 << 12 | 1, $id2);
    }

    public function testSmallTimestampWithMachine()
    {
        $this->timer->expects($this->any())
                    ->method('getUnixTimestamp')
                    ->will($this->returnValue(1325376000001));
        $cf = $this->buildSystemUnderTest();
        
        $id1 = $cf->generate();
        $id2 = $cf->generate();
        
        $this->assertId($id1);
        $this->assertId($id2);
        $this->assertReallyNotEquals($id1, $id2);
        
        $this->assertEquals(1 << 22 | 1 << 12, $id1);
        $this->assertEquals(1 << 22 | 1 << 12 | 1, $id2);
    }
    
    public function testFailsOnTimestampOverflow()
    {
        $this->timer->expects($this->any())
                    ->method('getUnixTimestamp')
                    ->will($this->returnValue(3524399255552));
        $cf = $this->buildSystemUnderTest();
        $e = NULL;
        try {
            $id2 = $cf->generate();
        } catch (OverflowException $e) {
            
        }
        $this->assertInstanceOf('\OverflowException', $e);
        $this->assertEquals('Timestamp overflow (past end of lifespan) - unable to generate any more IDs', $e->getMessage());
    }
    
    public function testLargestTimestampWithLargestEverythingElse()
    {
        $this->machineId = 1023;
        $this->timer->expects($this->any())
                    ->method('getUnixTimestamp')
                    ->will($this->returnValue(3524399255551));
        $cf = $this->buildSystemUnderTest();
        
        $id1 = $cf->generate();
        $id2 = $cf->generate();
        
        $this->assertId($id1);
        $this->assertId($id2);
        $this->assertReallyNotEquals($id1, $id2);
        $this->assertEquals('9223372036854771712', $id1);
    }
    
    public function testHeartbeat()
    {
        $this->machineId = 1;
        $this->timer->expects($this->any())
                    ->method('getUnixTimestamp')
                    ->will($this->returnValue(1325376000000));
        $cf = $this->buildSystemUnderTestHeartbeat(2);
        
        //Test. First ID, heartbeat changing machine ID to 2, second ID
        $id1 = $cf->generate();
        $cf->heartbeat();
        $id2 = $cf->generate();
        
        $expectedId1 = $value = (0 << 22) | (1 << 12) | 0; //timestamp 0, machine 1, sequence 0
        $expectedId2 = $value = (0 << 22) | (2 << 12) | 1; //timestamp 0, machine 2, sequence 1
        $this->assertEquals($expectedId1, $id1);
        $this->assertEquals($expectedId2, $id2);
    }
}
