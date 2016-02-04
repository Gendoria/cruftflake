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
     */
    public function getMachine();
}
