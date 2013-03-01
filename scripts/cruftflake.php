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

$opts    = getopt('p:z:m:');
$port    = isset($opts['p']) ? $opts['p'] : 5599;
$zks     = isset($opts['z']) ? $opts['z'] : '127.0.0.1:2181';
$machine = isset($opts['m']) ? $opts['m'] : rand(0,1023);

// Autoload the class
spl_autoload_register(function ($class) {
    $filename = strtolower(__DIR__ . '/../src/' .  str_replace('\\', '/', $class) . '.php');
	if (file_exists($filename)) {
		require $filename;
	}
});

$timer = new \Davegardnerisme\CruftFlake\Timer;
if ($machine !== NULL) {
    $config = new \Davegardnerisme\CruftFlake\FixedConfig($machine);
} else {
    $config = new \Davegardnerisme\CruftFlake\ZkConfig($zks);
}
$generator = new \Davegardnerisme\CruftFlake\Generator($config, $timer);
$zmqRunner = new \Davegardnerisme\CruftFlake\ZeroMq($generator, $port);

$zmqRunner->run();
