<?php
    /**
     * Cruft flake timer interface.
     * 
     * Implement this if you want some other way to provide time.
     * 
     * @author @davegardnerisme
     */

namespace Gendoria\CruftFlake\Timer;

interface TimerInterface
{
    /**
     * Get unix timestamp to millisecond accuracy.
     * 
     * (Number of whole milliseconds that have passed since 1970-01-01
     * 
     * @return int
     */
    public function getUnixTimestamp();
}
