#!/usr/bin/php
<?php
/**
 * Cruft flake - simple ZMQ req/rep loop
 *
 * Usage:
 *
 *  -p      ZeroMQ port to bind to, default 5599
 *  -z      ZooKeeper hostname:port to connect to, eg: localhost:2181
 *  -m      Specify a particular machine ID
 */

require __DIR__.'/../vendor/autoload.php';

$opts    = getopt('p:z:m:');
$port    = isset($opts['p']) ? $opts['p'] : 5599;
$zks     = isset($opts['z']) ? $opts['z'] : '127.0.0.1:2181';
$machine = isset($opts['m']) ? $opts['m'] : null;

$timer = new \Gendoria\CruftFlake\Timer\Timer();
if ($machine !== NULL) {
    $config = new \Gendoria\CruftFlake\Config\FixedConfig($machine);
} else {
    $config = new \Gendoria\CruftFlake\Config\ZooKeeperConfig($zks);
}
$generator = new \Gendoria\CruftFlake\Generator($config, $timer);
$zmqRunner = new \Gendoria\CruftFlake\Zmq\ZmqServer($generator, $port);

$zmqRunner->run();
