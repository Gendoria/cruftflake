<?php

namespace Gendoria\CruftFlake;

use Gendoria\CruftFlake\Generator\Generator;
use Gendoria\CruftFlake\Generator\GeneratorStatus;

/**
 * CruftFlake client interface.
 *
 * @author Tomasz Struczyński <tomasz.struczynski@isobar.com>
 */
interface ClientInterface
{
    /**
     * Generate new ID.
     * 
     * @return string
     */
    public function generateId();

    /**
     * Get generator status.
     * 
     * @return GeneratorStatus Generator status.
     *
     * @see Generator::status()
     */
    public function status();
}
