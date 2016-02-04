<?php

namespace Gendoria\CruftFlake\Local;

use Gendoria\CruftFlake\ClientInterface;
use Gendoria\CruftFlake\Generator\Generator;

/**
 * Local CruftFlake client.
 * 
 * **WARNING** - it should **NOT** be used in production environment,
 * unless each instance is guaranteed to receive unique machine ID.
 *
 * @author Tomasz Struczyński <tomasz.struczynski@isobar.com>
 */
class LocalClient implements ClientInterface
{
    /**
     * Generator instance.
     * 
     * @var Generator
     */
    private $generator;
    
    /**
     * Construct local client class injected with generator instance.
     * 
     * @param Generator $generator
     */
    function __construct(Generator $generator)
    {
        $this->generator = $generator;
    }

    /**
     * {@inheritdoc}
     */
    public function generateId()
    {
        return $this->generator->generate();
    }

    /**
     * {@inheritdoc}
     */
    public function status()
    {
        return $this->generator->status();
    }
}
