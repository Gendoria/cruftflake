<?php

/**
 * Cruft flake config interface.
 * 
 * Implement this if you want some other way to configure machines.
 * 
 * @author @davegardnerisme
 */

namespace Gendoria\CruftFlake\Config;

interface ConfigInterface
{
    /**
     * Get machine identifier.
     * 
     * @return int Should be a 10-bit int (decimal 0 to 1023)
     * @throws RuntimeException Thrown, when something went wrong during acquiring machine ID.
     */
    public function getMachine();

    /**
     * Configuration heartbeat. 
     * 
     * @return bool True, if configuration data had been changed during heartbeat.
     * @throws RuntimeException Thrown, when something went wrong during heartbeat.
     */
    public function heartbeat();
}
