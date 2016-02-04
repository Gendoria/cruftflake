#!/usr/bin/php
<?php
/**
 * Generate N ids, default N is 1
 *
 * Usage:
 *
 *  -n      How many to generate
 *  -p      ZeroMQ port to connect to, default 5599
 */

require __DIR__.'/../vendor/autoload.php';

$opts = getopt('n:p:');
$n    = isset($opts['n']) ? (int)$opts['n'] : 1;
$n    = $n < 0 ? 1 : $n;
$port = isset($opts['p']) ? $opts['p'] : 5599;

$context = new \ZMQContext();
$socket  = new \ZMQSocket($context, \ZMQ::SOCKET_REQ);
$cf = new Gendoria\CruftFlake\Zmq\ZmqClient($context, $socket);

for ($i=0; $i<$n; $i++) {
    $id = $cf->generateId();
    echo $id . "\n";
}