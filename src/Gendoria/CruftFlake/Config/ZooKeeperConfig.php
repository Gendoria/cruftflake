<?php

/**
 * ZooKeeper-based configuration
 *
 * Couple of points:
 *
 *  1. We coordinate via ZK on launch - hence ZK must be available at launch
 *     time
 *  2. We create permanent nodes (not ephmeral) so that if we get disconnected
 *     ZK still knows about us running
 *  3. There is a danger that point 2 will mean that we run out of machine IDs
 *     if Mac Addresses change and we don't manually clean up
 *  4. This is assuming we don't run > 1 server on the same box - which we
 *     won't be able to do anyway since we bind to a ZeroMQ TCP port (which
 *     we can only do once)
 *
 * @author @davegardnerisme
 */

namespace Gendoria\CruftFlake\Config;

use BadMethodCallException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RuntimeException;

class ZooKeeperConfig implements ConfigInterface, LoggerAwareInterface
{

    /**
     * Parent path
     *
     * @var string
     */
    private $parentPath;

    /**
     * ZK
     *
     * @var \Zookeeper
     */
    private $zk;

    /**
     * Logger
     * 
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Constructor
     *
     * @param string $hostnames A comma separated list of hostnames (including
     *      port)
     * @param string $zkPath The ZK path we look to find other machines under
     * @param LoggerInterface $logger Logger class
     */
    public function __construct($hostnames, $zkPath = '/cruftflake', LoggerInterface $logger = null)
    {
        if (!class_exists('\Zookeeper')) {
            $this->logger->critical('Zookeeper not present');
            throw new BadMethodCallException(
            'ZooKeeper extension not installed. Try hitting PECL.'
            );
        }
        $this->zk = new \Zookeeper($hostnames);
        $this->parentPath = $zkPath;
        if ($logger) {
            $this->logger = $logger;
        } else {
            $this->logger = new NullLogger();
        }
    }

    /**
     * Get machine identifier
     *
     * @return integer Should be a 10-bit int (decimal 0 to 1023)
     */
    public function getMachine()
    {
        $machineId = NULL;

        $this->createParentIfNeeded($this->parentPath);

        // get info about _this_ machine
        $machineInfo = $this->getMachineInfo();

        // get current machine list
        $children = $this->zk->getChildren($this->parentPath);
        foreach ($children as $child) {
            $info = $this->zk->get("{$this->parentPath}/$child");
            $info = json_decode($info, TRUE);
            if (isset($info['macAddress']) && $info['macAddress'] === $machineInfo['macAddress']) {
                $machineId = (int) $child;
            }
        }

        // find an unused machine number
        for ($i = 0; $i < 1024, $machineId === NULL; $i++) {
            $machineNode = $this->machineToNode($i);
            if (in_array($machineNode, $children)) {
                continue;   // already used
            }

            // attempt to claim
            $created = $this->zk->create(
                "{$this->parentPath}/{$machineNode}", json_encode($machineInfo),
                array(array(// acl
                    'perms' => \Zookeeper::PERM_ALL,
                    'scheme' => 'world',
                    'id' => 'anyone'
                ))
            );
            if ($created !== NULL) {
                $machineId = $i;
                break;
            }
        }

        if ($machineId === NULL) {
            $this->logger->critical("Cannot locate and claim a free machine ID via ZK",
                array($this));
            throw new RuntimeException(
            "Cannot locate and claim a free machine ID via ZK"
            );
        }
        $this->logger->debug("Obtained machine ID ".$machineId.' through ZooKeeper configuration');
        return (int) $machineId;
    }

    /**
     * Get mac address and hostname
     *
     * @return array "macAddress", "hostname" keys
     */
    private function getMachineInfo()
    {
        $info = array();
        $output = array();
        // HWaddr 12:31:3c:01:65:b8
        // ether 00:1c:42:00:00:08
        exec('ifconfig', $output);
        foreach ($output as $o) {
            $matched = array();
            if (preg_match('/(HWaddr|ether) ([a-f0-9]{2}:[a-f0-9]{2}:[a-f0-9]{2}:[a-f0-9]{2}:[a-f0-9]{2}:[a-f0-9]{2})/i',
                    $o, $matched)) {
                $info['macAddress'] = $matched[2];
                break;
            }
        }
        $info['hostname'] = exec('hostname');

        if (empty($info['hostname']) || empty($info['macAddress'])) {
            $this->logger->critical('Unable to identify machine mac address and hostname',
                array($this));
            throw new RuntimeException(
            'Unable to identify machine mac address and hostname'
            );
        }
        return $info;
    }

    /**
     * Create parent node, if needed
     *
     * @param string $nodePath
     */
    private function createParentIfNeeded($nodePath)
    {
        if (!$this->zk->exists($nodePath)) {
            $this->zk->create(
                $nodePath, 'Cruftflake machines',
                array(array(// acl
                    'perms' => \Zookeeper::PERM_ALL,
                    'scheme' => 'world',
                    'id' => 'anyone'
                ))
            );
        }
    }

    /**
     * Machine ID to ZK node
     *
     * @param integer $id
     *
     * @return string The node path to use in ZK
     */
    private function machineToNode($id)
    {
        return str_pad($id, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Set logger.
     * 
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

}
