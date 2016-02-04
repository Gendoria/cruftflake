<?php

namespace Gendoria\CruftFlake\Generator;

/**
 * Description of GeneratorStatus.
 *
 * @author Tomasz Struczyński <tomasz.struczynski@isobar.com>
 */
class GeneratorStatus
{
    /**
     * Machine ID.
     * 
     * @var int
     */
    public $machine;

    /**
     * Generator timestamp of last ID generation (converted with epoch).
     * 
     * @var int
     */
    public $lastTime;

    /**
     * Current sequence.
     * 
     * @var int
     */
    public $sequence;

    /**
     * True, if server works in 32bit mode.
     * 
     * @var bool
     */
    public $is32Bit;

    /**
     * Class constructor.
     * 
     * @param int  $machine
     * @param int  $lastTime
     * @param int  $sequence
     * @param bool $is32Bit
     */
    public function __construct($machine, $lastTime, $sequence, $is32Bit)
    {
        $this->machine = $machine;
        $this->lastTime = $lastTime;
        $this->sequence = $sequence;
        $this->is32Bit = $is32Bit;
    }
}
