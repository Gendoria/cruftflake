<?php

namespace Davegardnerisme\CruftFlake;

class CruftFlake
{
	protected $_context;
	protected $_socket;

	public function __construct(\ZMQContext $context, \ZMQSocket $socket)
	{
		$this->_context = $context;
		$this->_socket  = $socket;
	}

	public function generateId()
	{
		$this->_socket->connect("tcp://127.0.0.1:5599");
        $this->_socket->setSockOpt(\ZMQ::SOCKOPT_LINGER, 0);   
		$this->_socket->send('GEN');
		$id = $this->_socket->recv();

		return $id;
	}

	public function __toString()
	{
		return $this->generateId();
	}
}