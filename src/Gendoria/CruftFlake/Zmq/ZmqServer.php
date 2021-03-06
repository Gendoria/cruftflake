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
use ZMQ;
use ZMQContext;
use ZMQSocket;

class ZmqServer implements ServerInterface, LoggerAwareInterface
{
    /**
     * Cruft flake generator.
     * 
     * @var Generator
     */
    private $generator;

    /**
     * DSN.
     * 
     * @var string
     */
    private $dsn;

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
     * @param string            $dsn       Where socket should be bound. Default 'tcp://*:5599'
     * @param bool              $debugMode Debug mode. If set to true, server will only listen for one command, before exiting.
     */
    public function __construct(Generator $generator, $dsn = 'tcp://*:5599', $debugMode = false)
    {
        $this->generator = $generator;
        $this->dsn = $dsn;
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
        $receiver = $this->getZmqSocket($this->dsn);
        while (true) {
            $msg = $receiver->recv();
            if ($msg !== false) {
                $this->logger->debug('ZMQ server received command: '.$msg);
                $response = $this->runCommand($msg);
                $receiver->send(json_encode($response));
            }
            $this->generator->heartbeat();
            if ($this->debugMode) {
                break;
            }
        }
    }
    
    private function runCommand($msg)
    {
        switch ($msg) {
            case 'GEN':
                return $this->commandGenerate();
            case 'STATUS':
                return $this->commandStatus();
            default:
                $this->logger->debug('Unknown command received: '.$msg);

                return $this->createResponse('UNKNOWN COMMAND', 404);
        }
    }

    /**
     * Create generate command response.
     * 
     * @return array
     */
    private function commandGenerate()
    {
        try {
            $response = $this->createResponse($this->generator->generate());
        } catch (Exception $e) {
            $this->logger->error('Generator error: '.$e->getMessage(), array($e, $this));
            $response = $this->createResponse('ERROR', 500);
        }

        return $response;
    }

    /**
     * Create status command response.
     * 
     * @return array
     */
    private function commandStatus()
    {
        return $this->createResponse($this->generator->status());
    }

    /**
     * Prepare response.
     * 
     * @param mixed $message Return message. Anything, which is JSON serializable.
     * @param int   $code    Response code.
     * 
     * @return array
     */
    private function createResponse($message, $code = 200)
    {
        return array(
            'code' => $code,
            'message' => $message,
        );
    }

    /**
     * Get ZMQ socket.
     * 
     * @param string $dsn DSN, on which ZMQ connection should listen.
     *
     * @return \ZMQSocket
     */
    protected function getZmqSocket($dsn)
    {
        $context = new ZMQContext();
        $receiver = new ZMQSocket($context, ZMQ::SOCKET_REP);
        //Receive timeout is set to 5s, to allow heartbeat operations to take place.
        $receiver->setSockOpt(ZMQ::SOCKOPT_RCVTIMEO, 5000);
        $this->logger->debug("Binding to {$dsn}");
        $receiver->bind($dsn);

        return $receiver;
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }
}
