<?php

/**
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Gendoria\CruftFlake\Config;

use RuntimeException;

/**
 * Configuration using consul instance.
 *
 * @author Tomasz Struczyński <tomasz.struczynski@isobar.com>
 */
class ConsulConfig implements ConfigInterface
{
    /**
     * Default KV prefix on Consul.
     * 
     * @var string
     */
    const DEFAULT_KV_PREFIX = 'service/CruftFlake/machines/';
    
    /**
     * CURL requestor.
     * 
     * @var ConsulCurl
     */
    private $curl;
    
    /**
     * Consul KV prefix.
     * 
     * @var string
     */
    private $kvPrefix = self::DEFAULT_KV_PREFIX;
    
    /**
     * Consul session ID.
     * 
     * @var string
     */
    private $sessionId = "";
    
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
     * Machine ID.
     * 
     * @var integer
     */
    private $machineId;
    
    /**
     * Class constructor.
     * 
     * @param ConsulCurl $curl
     * @param integer $sessionTTL
     * @param string $kvPrefix
     */
    public function __construct(ConsulCurl $curl, $sessionTTL = 600, $kvPrefix = self::DEFAULT_KV_PREFIX)
    {
        $this->curl = $curl;
        $this->kvPrefix = $kvPrefix;
        $this->sessionTTL = (int)$sessionTTL;
        //If we cannot connect to Consul on start, we have a problem.
        $this->createSession();
        $this->lastSuccessfullCheck = time();
    }

    /**
     * On object destruction, we have to destroy session.
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
            $this->machineId = $this->acquireMachineId();
        }
        return $this->machineId;
    }

    /**
     * Configuration heartbeat. 
     * 
     * Heartbeat connects periodically to Consul to renew session and check its validity.
     * 
     * @return bool True, if configuration data had been changed during heartbeat.
     * 
     * @throws RuntimeException Thrown, when we could not create new session and it was needed.
     */
    public function heartbeat()
    {
        //If we have last successfull check recently new, we don't have to do anything
        if ($this->lastSuccessfullCheck !== null && time() - $this->lastSuccessfullCheck < $this->sessionTTL / 2 ) {
            return false;
        }
        //If session reneval succeedes, update last successfull check.
        if ($this->curl->performPutRequest("/session/renew/".$this->sessionId)) {
            $this->lastSuccessfullCheck = time();
            return false;
        }
        //Ok, we don't have a valid session. We have to create new one and signal update.
        try {
            $this->createSession();
            $this->lastSuccessfullCheck = time();
            $this->machineId = null;
            return true;
        } catch (RuntimeException $e) {
            //We could not create new session. We can work for some time in 'detached' mode,
            //but if our TTL time runs out, we have to throw an exception.
            if ($this->lastSuccessfullCheck === null || time() - $this->lastSuccessfullCheck >= $this->sessionTTL) {
                throw $e;
            }
            return false;
        }
    }
    
    /**
     * Return machine ID from consul queries.
     * 
     * @return integer
     * @throws RuntimeException
     */
    private function acquireMachineId()
    {
        //Check, if we don't have existing value for the session
        $currentValue = $this->curl->performGetRequest('/kv/'.$this->kvPrefix.$this->sessionId);
        if (!empty($currentValue['Value'])) {
            return (int)base64_decode($currentValue['Value']);
        }
        //Lock main key to block concurrent checks
        $this->lockKey();
        //Get currently locked machine IDs to check, if we can get a new one. If yes, save it.
        $currentValues = $this->curl->performGetRequest('/kv/'.$this->kvPrefix.'?recurse');
        if (!is_array($currentValues)) {
            $currentValues = array();
        }
        $machineId = $this->computePossibleMachineId($currentValues);
        if (!$this->curl->performPutRequest('/kv/'.$this->kvPrefix.$this->sessionId.'?acquire='.$this->sessionId, $machineId)) {
            throw new RuntimeException("Could not register machine ID on consul");
        }
        //Release the lock on the main key and return machine ID.
        $this->releaseKey();
        return (int)$machineId;
    }
    
    /**
     * Try to fetch machine ID.
     * 
     * @param array $currentValues
     * @return integer
     * @throws RuntimeException
     */
    private function computePossibleMachineId(array $currentValues)
    {
        $usedIds = array();
        foreach ($currentValues as $currentValue) {
            if ($currentValue['Key'] == $this->kvPrefix) {
                continue;
            } elseif ($currentValue['Key'] == $this->sessionId) {
                return (int)base64_decode($currentValue['Value']);
            }
            else {
                $usedIds[] = (int)base64_decode($currentValue['Value']);
            }
        }
        for ($k = 0; $k < 1024; $k++) {
            if (!in_array($k, $usedIds)) {
                return $k;
            }
        }
        throw new RuntimeException("Cannot acquire machine ID - all machine IDs are used up");
    }
    
    /**
     * Lock master key.
     */
    private function lockKey()
    {
        //try to acquire the lock on prefix during whole operation.
        $tryCount=0;
        do {
            $acquired = $this->curl->performPutRequest('/kv/'.$this->kvPrefix.'?acquire='.$this->sessionId."&flags=".$tryCount, $this->sessionId);
            if (!$acquired) {
                sleep(1);
            }
            $tryCount++;
        } while (!$acquired);
    }
    
    /**
     * Release master key.
     */
    private function releaseKey()
    {
        $this->curl->performPutRequest('/kv/'.$this->kvPrefix.'?release='.$this->sessionId, $this->sessionId);
    }

    /**
     * Create new session.
     * 
     * @throws RuntimeException
     */
    private function createSession()
    {
        $url ='/session/create';
        //We create new session with given TTL and with lock delay equal to half of TTL.
        $payload = array(
            'TTL' => $this->sessionTTL.'s',
            "Behavior" => "delete",
            'LockDelay' => floor($this->sessionTTL/2).'s',
        );
        $returnData = $this->curl->performPutRequest($url, json_encode($payload));
        if (empty($returnData['ID'])) {
            throw new RuntimeException("Cannot create session");
        }
        $this->sessionId = $returnData['ID'];
    }
    
    /**
     * Destroy session.
     */
    private function destroySession()
    {
        if ($this->sessionId) {
            $this->curl->performPutRequest("/session/destroy/".$this->sessionId);
        }
    }
}
