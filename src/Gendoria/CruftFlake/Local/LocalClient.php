<?php

namespace Gendoria\CruftFlake\Local;

/**
 * Local CruftFlake client.
 * 
 * **WARNING** - it should **NOT** be used in production environment,
 * unless each instance is guaranteed to receive unique machine ID.
 *
 * @author Tomasz Struczyński <tomasz.struczynski@isobar.com>
 */
class LocalClient implements \Gendoria\CruftFlake\ClientInterface
{
    /**
     * Generator instance.
     * 
     * @var \Gendoria\CruftFlake\Generator
     */
    private $generator;

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
