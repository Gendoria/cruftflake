<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Gendoria\CruftFlake\Config;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use PDO;
use PHPUnit_Extensions_Database_TestCase;

/**
 * Description of DoctrineConfigTest
 *
 * @author Tomasz Struczyński <tomasz.struczynski@isobar.com>
 */
class DoctrineConfigTest extends PHPUnit_Extensions_Database_TestCase
{
    /**
     * Doctrine connection.
     * 
     * @var Connection
     */
    private $connection;
    
    protected function setUp()
    {
        parent::setUp();
    }
    
    protected function getConnection()
    {
        $pdo = new PDO('sqlite::memory:');
        
        $this->connection = DriverManager::getConnection(array('pdo' => $pdo));
        DoctrineConfig::createTable($this->connection);
        
        return $this->createDefaultDBConnection($pdo, ':memory:');
    }

    protected function getDataSet()
    {
        return $this->createArrayDataSet(array());
    }
    
    public function testGetSingleMachineId()
    {
        $conn = $this->getDatabaseTester()->getConnection();
        $this->assertEquals(0, $conn->getRowCount(DoctrineConfig::DEFAULT_TABLE_NAME), "Pre-Condition");
        
        $config = new DoctrineConfig($this->connection);
        $this->assertEquals(0, $config->getMachine());
        $this->assertEquals(1, $conn->getRowCount(DoctrineConfig::DEFAULT_TABLE_NAME), "Inserting failed");
    }
    
    public function testGetSingleMachineIdException()
    {
        $this->setExpectedException('\RuntimeException', 'Cannot acquire machine ID');
        $conn = $this->getDatabaseTester()->getConnection();
        $this->assertEquals(0, $conn->getRowCount(DoctrineConfig::DEFAULT_TABLE_NAME), "Pre-Condition");
        
        $config = new DoctrineConfig($this->connection);
        $this->connection->close();
        $config->getMachine();
    }    
    
    public function testGetMultipleMachineIds()
    {
        $conn = $this->getDatabaseTester()->getConnection();
        
        $config1 = new DoctrineConfig($this->connection);
        $config2 = new DoctrineConfig($this->connection);
        $this->assertEquals(0, $config1->getMachine());
        $this->assertEquals(1, $config2->getMachine());
        $this->assertEquals(2, $conn->getRowCount(DoctrineConfig::DEFAULT_TABLE_NAME), "Inserting failed");
    }
    
    public function testDestroy()
    {
        $conn = $this->getDatabaseTester()->getConnection();
        
        $config1 = new DoctrineConfig($this->connection);
        $this->assertEquals(0, $config1->getMachine());
        $this->assertEquals(1, $conn->getRowCount(DoctrineConfig::DEFAULT_TABLE_NAME), "Inserting failed");
        $config1 = null;
        $this->assertEquals(0, $conn->getRowCount(DoctrineConfig::DEFAULT_TABLE_NAME), "Destroy failed");
        //Whenb we create new config, it should have ID equals 1.
        $config2 = new DoctrineConfig($this->connection);
        $this->assertEquals(0, $config2->getMachine());
        $this->assertEquals(1, $conn->getRowCount(DoctrineConfig::DEFAULT_TABLE_NAME), "Inserting failed");
    }    
    
    public function testGetMultipleMachineIdsWithDestroy()
    {
        $conn = $this->getDatabaseTester()->getConnection();
        
        $config1 = new DoctrineConfig($this->connection);
        $config2 = new DoctrineConfig($this->connection);
        $config3 = new DoctrineConfig($this->connection);
        $config4 = new DoctrineConfig($this->connection);
        $this->assertEquals(0, $config1->getMachine());
        $this->assertEquals(1, $config2->getMachine());
        $this->assertEquals(2, $config3->getMachine());
        $this->assertEquals(3, $conn->getRowCount(DoctrineConfig::DEFAULT_TABLE_NAME), "Inserting failed");
        $config2 = null;
        $this->assertEquals(1, $config4->getMachine());
        $this->assertEquals(3, $conn->getRowCount(DoctrineConfig::DEFAULT_TABLE_NAME), "Inserting failed");
    }
    
    public function testGetMultipleMachineIdsWithDestroyFirst()
    {
        $conn = $this->getDatabaseTester()->getConnection();
        
        $config1 = new DoctrineConfig($this->connection);
        $config2 = new DoctrineConfig($this->connection);
        $config3 = new DoctrineConfig($this->connection);
        $config4 = new DoctrineConfig($this->connection);
        $this->assertEquals(0, $config1->getMachine());
        $this->assertEquals(1, $config2->getMachine());
        $this->assertEquals(2, $config3->getMachine());
        $this->assertEquals(3, $conn->getRowCount(DoctrineConfig::DEFAULT_TABLE_NAME), "Inserting failed");
        $config1 = null;
        $this->assertEquals(0, $config4->getMachine());
        $this->assertEquals(3, $conn->getRowCount(DoctrineConfig::DEFAULT_TABLE_NAME), "Inserting failed");
    }
    
    public function testGetMultipleMachineIdsTooManyMachines()
    {
        $this->setExpectedException('\RuntimeException', 'Cannot acquire machine ID - too many machines present');
        $conn = $this->getDatabaseTester()->getConnection();
        
        $machines = array();
        for ($k=0; $k<1024; $k++) {
            $machines[$k] = new DoctrineConfig($this->connection);
            $this->assertEquals($k, $machines[$k]->getMachine());
        }
        $this->assertEquals(1024, $conn->getRowCount(DoctrineConfig::DEFAULT_TABLE_NAME), "Inserting failed");
        $connEx = new DoctrineConfig($this->connection);
        $connEx->getMachine();
    }
    
    
    public function testHeartBeatNoMachineId()
    {
        $this->getDatabaseTester()->getConnection();
        $config1 = new DoctrineConfig($this->connection);
        $this->assertFalse($config1->heartbeat());
    }
    
    public function testHeartBeatMultiple()
    {
        $this->getDatabaseTester()->getConnection();
        $config1 = new DoctrineConfig($this->connection);
        $this->assertEquals(0, $config1->getMachine());
        $this->assertFalse($config1->heartbeat());
        $this->assertFalse($config1->heartbeat());
    }
    
    public function testHeartBeatFetchNeeded()
    {
        $this->getDatabaseTester()->getConnection();
        $config1 = new DoctrineConfig($this->connection);
        $this->assertEquals(0, $config1->getMachine());
        $this->connection->createQueryBuilder()
            ->delete(DoctrineConfig::DEFAULT_TABLE_NAME)
            ->where('machine_id=0')
            ->execute();
        $this->assertTrue($config1->heartbeat());
    }
    
    public function testHeartBeatException()
    {
        $this->setExpectedException('\RuntimeException', 'Counld not connect to database');
        $this->getDatabaseTester()->getConnection();
        $config1 = new DoctrineConfig($this->connection);
        $this->assertEquals(0, $config1->getMachine());
        $this->connection->close();
        $config1->heartbeat();
    }    
    
}
