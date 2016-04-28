<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Gendoria\CruftFlake\Config;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use Exception;
use RuntimeException;

/**
 * Configuration using doctrine DBAL.
 *
 * @author Tomasz Struczyński <tomasz.struczynski@isobar.com>
 */
class DoctrineConfig implements ConfigInterface
{

    /**
     * Default table name.
     * 
     * @var string
     */
    const DEFAULT_TABLE_NAME = "gendoria_cruftflake_id";

    /**
     * Doctrine connection.
     * 
     * @var Connection
     */
    private $connection;
    
    /**
     * Session TTL.
     * 
     * @var integer
     */
    private $sessionTTL;

    /**
     * Last successfull check.
     * 
     * @var integer|null
     */
    private $lastSuccessfullCheck = null;

    /**
     * Database table name.
     * 
     * @var string
     */
    private $tableName = self::DEFAULT_TABLE_NAME;
    
    /**
     * Machine ID.
     * 
     * @var integer
     */
    private $machineId;    

    function __construct(Connection $connection, $sessionTTL = 600, $tableName = self::DEFAULT_TABLE_NAME)
    {
        $this->connection = $connection;
        $this->sessionTTL = $sessionTTL;
        $this->tableName = $tableName;
    }

    /**
     * Class destructor. 
     * 
     * Clears session in DBAL.
     */
    public function __destruct()
    {
        $this->destroySession();
    }

    /**
     * {@inheritdoc}
     */
    public function getMachine()
    {
        if ($this->machineId === null) {
            try {
                $this->machineId = $this->acquireMachineId();
            } catch (\RuntimeException $e) {
                throw $e;
            } catch (\Exception $e) {
                throw new \RuntimeException("Cannot acquire machine ID", 500, $e);
            }
        }
        return $this->machineId;
    }

    /**
     * Configuration heartbeat. 
     * 
     * Heartbeat connects periodically to database to renew session and check its validity.
     * 
     * @return bool True, if configuration data had been changed during heartbeat.
     * 
     * @throws RuntimeException Thrown, when we could not create new session and it was needed.
     */
    public function heartbeat()
    {
        //If we have last successfull check recently new, we don't have to do anything
        if ($this->lastSuccessfullCheck !== null && time() - $this->lastSuccessfullCheck < $this->sessionTTL / 2) {
            return false;
        }
        
        //If we don't yet have machine ID, nothing happens.
        if ($this->machineId === null) {
            return false;
        }
        
        try {
            $tmpSuccessfullCheck = time();
            $qb = $this->connection->createQueryBuilder();
            $qb->update($this->tableName)
                ->set('last_access', $tmpSuccessfullCheck)
                ->where('machine_id = ?')
                ->setParameter(0, $this->machineId)
                ;
            $rows = $qb->execute();
            //Perform garbage collection
            $this->gc();
            if ($rows == 0) {
                $this->machineId = null;
                $this->lastSuccessfullCheck = null;
                return true;
            }
            $this->lastSuccessfullCheck = $tmpSuccessfullCheck;
            return false;
        } catch (Exception $e) {
            throw new RuntimeException("Counld not connect to database", 500, $e);
        }
    }

    /**
     * Return machine ID from DBAL.
     * 
     * @return integer
     * @throws RuntimeException
     */
    private function acquireMachineId()
    {
        $this->gc();

        $time = time();
        $possibleMachineId = $this->acquireDbId();
        
        if ($possibleMachineId > 1023) {
            throw new \RuntimeException("Cannot acquire machine ID - too many machines present");
        } else {
            $this->connection->insert($this->tableName, array(
                'machine_id' => $possibleMachineId,
                'last_access' => $time,
            ));
            return $possibleMachineId;
        }
    }
    
    /**
     * Acquire next ID from database.
     * 
     * @return boolean|integer
     */
    private function acquireDbId()
    {
        $qbFirst = $this->connection->createQueryBuilder();
        $qbFirst->select('c1.machine_id AS machine_id')
            ->from($this->tableName, 'c1')
            ->where('c1.machine_id=0')
            ->orderBy('c1.machine_id', 'ASC')
            ->setMaxResults(1)
            ;
        //Either the table is empty, or it does not have first ID present.
        if ($qbFirst->execute()->fetchColumn() === false) {
            return 0;
        }
        
        //We have at least one ID in database, we should find next one.
        $qb = $this->connection->createQueryBuilder();
        $qb->select('c1.machine_id+1 AS machine_id')
            ->from($this->tableName, 'c1')
            ->leftJoin('c1', $this->tableName, 'c2', 'c1.machine_id+1 = c2.machine_id')
            ->leftJoin('c1', $this->tableName, 'c3', 'c1.machine_id-1 = c2.machine_id')
            ->where('c2.machine_id IS NULL')
            //->orWhere('c3.machine_id IS NULL AND c1.machine_id=1')
            ->orderBy('c1.machine_id', 'ASC')
            ->setMaxResults(1)
            ;
        
        $id = $qb->execute()->fetchColumn();
        return $id;
    }

    /**
     * Destroy session.
     */
    private function destroySession()
    {
        //Nothing to destroy
        if ($this->machineId === null) {
            return;
        }
        
        try {
            $qb = $this->connection->createQueryBuilder()
                ->delete($this->tableName)
                ->where('machine_id = ?')
                ->setParameter(0, $this->machineId);
            $qb->execute();
        } catch (\Exception $e) {
            //Nothing can be done here, we'll fail silently
        }
    }

    /**
     * Create database table.
     */
    public static function createTable(Connection $connection, $tableName = self::DEFAULT_TABLE_NAME)
    {
        $schema = new Schema();
        $myTable = $schema->createTable($tableName);
        $myTable->addColumn("machine_id", Type::INTEGER, array("unsigned" => true));
        $myTable->addColumn("last_access", Type::BIGINT, array("unsigned" => true));
        $myTable->setPrimaryKey(array("machine_id"));
        $sql = $schema->toSql($connection->getDatabasePlatform());
        foreach ($sql as $statement) {
            $connection->exec($statement);
        }
    }
    
    /**
     * Garbage collector: remove unused sessions.
     */
    private function gc()
    {
        $lastAccess = time() - $this->sessionTTL;
        $qb = $this->connection->createQueryBuilder();
        $qb->delete($this->tableName)
            ->where('last_access < ?')
            ->setParameter(0, $lastAccess);
        $qb->execute();
    }
}