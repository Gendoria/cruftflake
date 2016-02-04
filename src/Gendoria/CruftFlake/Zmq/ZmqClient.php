<?php

/**
 * Class to implement CruftFlake.
 *
 * @author @bobbyjason
 */

namespace Gendoria\CruftFlake\Zmq;

use Gendoria\CruftFlake\ClientInterface;
use Gendoria\CruftFlake\GeneratorStatus;
use RuntimeException;

class ZmqClient implements ClientInterface
{
    protected $context;
    protected $socket;

    public function __construct(\ZMQContext $context, \ZMQSocket $socket)
    {
        $this->context = $context;
        $this->socket = $socket;
    }

    /**
     * {@inheritdoc}
     * 
     * @throws RuntimeException
     */
    public function generateId()
    {
        $this->socket->connect('tcp://127.0.0.1:5599');
        $this->socket->setSockOpt(\ZMQ::SOCKOPT_LINGER, 0);
        $this->socket->send('GEN');
        $reply = $this->socket->recv();
        if (empty($reply)) {
            throw new RuntimeException('Server error - received empty reply.');
        }
        $response = json_decode($reply, true);

        if ($response['code'] != 200) {
            throw new RuntimeException('Server error: '.$response['message']);
        }

        return (int) $response['message'];
    }

    /**
     * {@inheritdoc}
     */
    public function status()
    {
        $this->socket->connect('tcp://127.0.0.1:5599');
        $this->socket->setSockOpt(\ZMQ::SOCKOPT_LINGER, 0);
        $this->socket->send('STATUS');
        $reply = $this->socket->recv();

        $response = json_decode($reply, true);

        return new GeneratorStatus($response['message']['machine'],
            $response['message']['lastTime'], $response['message']['sequence'],
            $response['message']['is32Bit']);
    }

    public function __toString()
    {
        return $this->generateId();
    }

    /**
     * Set ZMQ send timeout.
     * 
     * @param int $timeout
     */
    public function setTimeout($timeout = 5)
    {
        $this->socket->setSockOpt(\ZMQ::SOCKOPT_SNDTIMEO, $timeout);
    }
}
