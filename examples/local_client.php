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

$timer = new \Gendoria\CruftFlake\Timer\Timer();
$config = new \Gendoria\CruftFlake\Config\FixedConfig(1);
$generator = new \Gendoria\CruftFlake\Generator\Generator($config, $timer);

$cf = new \Gendoria\CruftFlake\Local\LocalClient($generator);

for ($i=0; $i<$n; $i++) {
    $id = $cf->generateId();
    echo $id . "\n";
}