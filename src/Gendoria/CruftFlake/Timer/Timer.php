<?php

/**
 * Cruft flake timer.
 * 
 * @author @davegardnerisme
 */

namespace Gendoria\CruftFlake\Timer;

class Timer implements TimerInterface
{
    /**
     * Get unix timestamp to millisecond accuracy.
     * 
     * (Number of whole milliseconds that have passed since 1970-01-01
     * 
     * @return int
     */
    public function getUnixTimestamp()
    {
        return (int) floor(microtime(true) * 1000);
    }
}
