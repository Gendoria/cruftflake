<?php

/**
 * ZooKeeper-based configuration.
 *
 * Couple of points:
 *
 *  1. We coordinate via ZK on launch - hence ZK must be available at launch
 *     time
 *  2. We create permanent nodes (not ephmeral) so that if we get disconnected
 *     ZK still knows about us running
 *  3. There is a danger that point 2 will mean that we run out of machine IDs
 *     if host name change and we don't manually clean up.
 *  4. All of your machines have to have unique host names.
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
     * Parent path.
     *
     * @var string
     */
    private $parentPath;

    /**
     * ZK.
     *
     * @var \Zookeeper
     */
    private $zk;

    /**
     * Process ID for a multi-process-single-machine setup.
     * 
     * @var int
     */
    private $procesId = 1;

    /**
     * Logger.
     * 
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Constructor.
     *
     * @param string          $hostnames A comma separated list of hostnames (including
     *                                   port)
     * @param int             $processId If you want to run multiple server processes on a single machine, 
     *                                   you have to provide each one an unique ID,
     *                                   so the zookeeper knows, which machine ID belongs to which process.
     * @param string          $zkPath    The ZK path we look to find other machines under
     * @param LoggerInterface $logger    Logger class
     */
    public function __construct($hostnames, $processId = 1, $zkPath = '/cruftflake',
        LoggerInterface $logger = null)
    {
        if (!class_exists('\Zookeeper')) {
            $this->logger->critical('Zookeeper not present');
            throw new BadMethodCallException('ZooKeeper extension not installed. Try hitting PECL.');
        }
        $this->procesId = $processId;
        $this->zk = new \Zookeeper($hostnames);
        $this->parentPath = $zkPath;
        if ($logger) {
            $this->logger = $logger;
        } else {
            $this->logger = new NullLogger();
        }
    }

    /**
     * Get machine identifier.
     *
     * @throws RuntimeException Thrown, when obtaining machine ID has failed.
     * @return int Should be a 10-bit int (decimal 0 to 1023)
     *
     */
    public function getMachine()
    {
        $machineId = null;

        $this->createParentIfNeeded($this->parentPath);

        // get info about _this_ machine
        $machineInfo = $this->getMachineInfo();

        // get current machine list
        $children = $this->zk->getChildren($this->parentPath);

        //Find existing machine info
        foreach ($children as $child) {
            $info = $this->zk->get("{$this->parentPath}/$child");
            $info = json_decode($info, true);
            if ($this->compareMachineInfo($info, $machineInfo)) {
                $machineId = (int) $child;
                break; //We don't have to check further
            }
        }

        //Machine info not found, attempt to create one
        if ($machineId === null) {
            $machineId = $this->createMachineInfo($children, $machineInfo);
        }

        $this->logger->debug('Obtained machine ID '.$machineId.' through ZooKeeper configuration');

        return (int) $machineId;
    }

    /**
     * Periodically re-syncs with zookeeper, to obtain new machine ID, if necessary.
     * 
     * {@inheritdoc}
     */
    public function heartbeat()
    {
        return false;
    }

    /**
     * Compare found machine information with expected values.
     * 
     * @param array $found
     * @param array $expected
     *
     * @return bool
     */
    private function compareMachineInfo(array $found, array $expected)
    {
        if (!isset($found['hostname']) || !isset($found['processId'])) {
            return false;
        }

        return $found['hostname'] === $expected['hostname'] && $found['processId'] === $expected['processId'];
    }

    /**
     * Attempt to claim and create new machine ID in Zookeeper.
     * 
     * @param array $children
     * @param array $machineInfo
     *
     * @throws RuntimeException Thrown, when creation of machine ID has failed.
     * @return int Machine ID.
     *
     */
    private function createMachineInfo(array $children, array $machineInfo)
    {
        // find an unused machine number
        for ($i = 0; $i < 1024; ++$i) {
            $machineNode = $this->machineToNode($i);
            if (in_array($machineNode, $children)) {
                continue; // already used
            }

            // attempt to claim
            $created = $this->zk->create(
                "{$this->parentPath}/{$machineNode}", json_encode($machineInfo),
                array(array(// acl
                    'perms' => \Zookeeper::PERM_ALL,
                    'scheme' => 'world',
                    'id' => 'anyone',
                ))
            );
            if ($created !== null) {
                return $i;
            }
        }

        //Creating machine ID failed, throw an error
        $this->logger->critical('Cannot locate and claim a free machine ID via ZK', array($this));
        throw new RuntimeException('Cannot locate and claim a free machine ID via ZK');
    }

    /**
     * Get mac address and hostname.
     *
     * @return array "hostname","processId", "time" keys
     */
    private function getMachineInfo()
    {
        $info = array();
        $info['hostname'] = php_uname('n');

        if (empty($info['hostname'])) {
            $this->logger->critical('Unable to identify machine hostname', array($this));
            throw new RuntimeException('Unable to identify machine hostname');
        }
        $info['processId'] = $this->procesId;
        $info['time'] = (int) floor(microtime(true) * 1000);

        return $info;
    }

    /**
     * Create parent node, if needed.
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
                    'id' => 'anyone',
                ))
            );
        }
    }

    /**
     * Machine ID to ZK node.
     *
     * @param int $id
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
