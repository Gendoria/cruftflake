<?php
/**
     * ZeroMQ interface for cruftflake.
     * 
     * @author @davegardnerisme
     */

namespace Gendoria\CruftFlake\Zmq;

use Exception;
use Gendoria\CruftFlake\Generator\Generator;
use Gendoria\CruftFlake\ServerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class ZmqServer implements ServerInterface, LoggerAwareInterface
{
    /**
     * Cruft flake generator.
     * 
     * @var Generator
     */
    private $generator;

    /**
     * Port.
     * 
     * @var int
     */
    private $port;

    /**
     * Logger.
     * 
     * @var LoggerInterface
     */
    private $logger;

    private $debugMode = false;

    /**
     * Constructor.
     * 
     * @param @inject Generator $generator
     * @param string            $port      Which TCP port to list on, default 5599
     * @param bool              $debugMode Debug mode. If set to true, server will only listen for one command, before exiting.
     */
    public function __construct(Generator $generator, $port = 5599, $debugMode = false)
    {
        $this->generator = $generator;
        $this->port = $port;
        $this->logger = new NullLogger();
        $this->debugMode = $debugMode;
    }

    /**
     * Run ZMQ interface for generator.
     * 
     * Req-rep pattern; msgs are commands:
     * 
     * GEN    = Generate ID
     * STATUS = Get status string
     */
    public function run()
    {
        $receiver = $this->getZmqSocket($this->port);
        $response = array(
            'code' => 200,
            'message' => '',
        );
        while (true) {
            $msg = $receiver->recv();
            $this->logger->debug('ZMQ server received command: '.$msg);
            switch ($msg) {
                case 'GEN':
                    try {
                        $response['message'] = $this->generator->generate();
                    } catch (Exception $e) {
                        $this->logger->error('Generator error: '.$e->getMessage(), array($e, $this));
                        $response = array(
                            'code' => 500,
                            'message' => 'ERROR',
                        );
                    }
                    break;
                case 'STATUS':
                    $response['message'] = $this->generator->status();
                    break;
                default:
                    $this->logger->debug('Unknown command received: '.$msg);
                    $response = array(
                        'code' => 404,
                        'message' => 'UNKNOWN COMMAND',
                    );
                    break;
            }
            $receiver->send(json_encode($response));
            if ($this->debugMode) {
                break;
            }
        }
    }

    /**
     * Get ZMQ socket.
     * 
     * @param int $port Port, on which ZMQ connection should listen.
     *
     * @return \ZMQSocket
     */
    public function getZmqSocket($port)
    {
        $context = new \ZMQContext();
        $receiver = new \ZMQSocket($context, \ZMQ::SOCKET_REP);
        $bindTo = 'tcp://*:'.$port;
        $this->logger->debug("Binding to {$bindTo}");
        $receiver->bind($bindTo);

        return $receiver;
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }
}
