#!/usr/bin/php
<?php
/**
 * Get generator status
 * 
 * Usage:
 * 
 *  -p      ZeroMQ port to connect to, default 5599
 */
require __DIR__.'/../vendor/autoload.php';

$opts = getopt('p:');
$port = isset($opts['p']) ? $opts['p'] : 5599;

$context = new \ZMQContext();
$socket  = new \ZMQSocket($context, \ZMQ::SOCKET_REQ);
$cf = new Gendoria\CruftFlake\Zmq\ZmqClient($context, $socket);
$status = $cf->status();

echo "STATUS\n\n";
print_r($status);
echo "\n";
