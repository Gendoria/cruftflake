<?php
/**
 * ZeroMQ interface for cruftflake
 * 
 * @author @davegardnerisme
 */

namespace Gendoria\CruftFlake;

use Exception;

class ZeroMqServer
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
     * Constructor
     * 
     * @param @inject Generator $generator
     * @param string $port Which TCP port to list on, default 5599
     */
    public function __construct(Generator $generator, $port = 5599)
    {
        $this->generator = $generator;
        $this->port = $port;
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
        $context = new \ZMQContext();
        $receiver = new \ZMQSocket($context, \ZMQ::SOCKET_REP);
        $bindTo = 'tcp://*:' . $this->port;
        echo "Binding to {$bindTo}\n";
        $receiver->bind($bindTo);
        while (TRUE) {
            $msg = $receiver->recv();
            switch ($msg) {
                case 'GEN':
                    try {
                        $response = $this->generator->generate();
                    } catch (Exception $e) {
                        $response = "ERROR";
                    }
                    break;
                case 'STATUS':
                    $response = json_encode($this->generator->status());
                    break;
                default:
                    $response = 'UNKNOWN COMMAND';
                    break;
            }
            $receiver->send($response);
        }
    }
}