<?php
/**
 * Class to implement CruftFlake
 *
 * @author @bobbyjason
 */

namespace Davegardnerisme\CruftFlake;

class CruftFlake
{
	protected $context;
	protected $socket;

	public function __construct(\ZMQContext $context, \ZMQSocket $socket)
	{
		$this->context = $context;
		$this->socket  = $socket;
	}

	public function generateId()
	{
		$this->socket->connect("tcp://127.0.0.1:5599");
		$this->socket->setSockOpt(\ZMQ::SOCKOPT_LINGER, 0);
		$this->socket->send('GEN');
		$id = $this->socket->recv();

		return $id;
	}

	public function __toString()
	{
		return $this->generateId();
	}

	public function setTimeout($timeout = -1) {
		$this->socket->setSockOpt(\ZMQ::SOCKOPT_SNDTIMEO, 5);
	}
}