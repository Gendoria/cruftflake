<?php
/**
 * Fixed configuration.
 * 
 * This is designed to be used where each machine **knows** what its machine
 * ID is - eg: via some kind of automatically deployed configuration
 * (puppet etc.)
 * 
 * @author @davegardnerisme
 */

namespace Gendoria\CruftFlake\Config;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class FixedConfig implements ConfigInterface, LoggerAwareInterface
{
    /**
     * Machine ID.
     * 
     * @var int
     */
    private $machineId;

    /**
     * Logger.
     * 
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Constructor.
     * 
     * @param int             $machineId Fixed machine ID.
     * @param LoggerInterface $logger    Logger class
     */
    public function __construct($machineId, LoggerInterface $logger = null)
    {
        $this->machineId = (int) $machineId;
        if ($logger) {
            $this->logger = $logger;
        } else {
            $this->logger = new NullLogger();
        }
    }

    /**
     * Get machine identifier.
     * 
     * @return int Should be a 10-bit int (decimal 0 to 1023)
     */
    public function getMachine()
    {
        $this->logger->debug('Obtained machine ID '.$this->machineId.' through fixed configuration.');

        return $this->machineId;
    }

    /**
     * {@inheritdoc}
     * 
     * This function will always return false, as fixed config does not resync machine ID.
     */
    public function heartbeat()
    {
        return false;
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
