<?php

/**
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

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
     * @return integer
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
