<?php
/**
 * ZeroMQ interface for cruftflake
 * 
 * @author @davegardnerisme
 */

namespace Gendoria\CruftFlake\Server;

use Exception;
use Gendoria\CruftFlake\Generator;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class ZmqServer implements LoggerAwareInterface
{    
    /**
     * Cruft flake generator
     * 
     * @var Generator
     */
    private $generator;
    
    /**
     * Port
     * 
     * @var integer
     */
    private $port;
    
    /**
     * Logger
     * 
     * @var LoggerInterface
     */
    private $logger;
    
    private $debugMode = false;
    
    /**
     * Constructor
     * 
     * @param @inject Generator $generator
     * @param string $port Which TCP port to list on, default 5599
     * @param boolean $debugMode Debug mode. If set to true, server will only listen for one command, before exiting.
     */
    public function __construct(Generator $generator, $port = 5599, $debugMode = false)
    {
        $this->generator = $generator;
        $this->port = $port;
        $this->logger = new NullLogger();
        $this->debugMode = $debugMode;
    }
    
    /**
     * Run ZMQ interface for generator
     * 
     * Req-rep pattern; msgs are commands:
     * 
     * GEN    = Generate ID
     * STATUS = Get status string
     */
    public function run()
    {
        $receiver = $this->getZmqSocket($this->port);
        while (TRUE) {
            $msg = $receiver->recv();
            $this->logger->debug("ZMQ server received command: ".$msg);
            switch ($msg) {
                case 'GEN':
                    try {
                        $response = $this->generator->generate();
                    } catch (Exception $e) {
                        $this->logger->error('Generator error: '.$e->getMessage(), array($e, $this));
                        $response = "ERROR";
                    }
                    break;
                case 'STATUS':
                    $response = json_encode($this->generator->status());
                    break;
                default:
                    $this->logger->debug('Unknown command received: '.$msg);
                    $response = 'UNKNOWN COMMAND';
                    break;
            }
            $receiver->send($response);
            if ($this->debugMode) {
                break;
            }
        }
    }
    
    /**
     * Get ZMQ socket.
     * 
     * @param integer $port Port, on which ZMQ connection should listen.
     * @return \ZMQSocket
     */
    public function getZmqSocket($port)
    {
        $context = new \ZMQContext();
        $receiver = new \ZMQSocket($context, \ZMQ::SOCKET_REP);
        $bindTo = 'tcp://*:' . $port;
        $this->logger->debug("Binding to {$bindTo}");
        $receiver->bind($bindTo);
        return $receiver;
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }
}