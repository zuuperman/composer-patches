<?php

/**
 * @file
 * Contains \cweagans\Composer\Resolvers\ResolverBase.
 */

namespace cweagans\Composer\Resolvers;

use Composer\Composer;
use Composer\Installer\PackageEvent;
use Composer\IO\IOInterface;
use cweagans\Composer\PatchCollection;

abstract class ResolverBase implements ResolverInterface
{

    /**
     * The main Composer object.
     *
     * @var Composer
     */
    protected $composer;

    /**
     * An array of operations that will be executed during this composer execution.
     *
     * @var IOInterface
     */
    protected $io;

    /**
     * {@inheritDoc}
     */
    public function __construct(Composer $composer, IOInterface $io)
    {
        $this->composer = $composer;
        $this->io = $io;
    }

    /**
     * {@inheritdoc}
     */
    abstract public function resolve(PatchCollection $collection, PackageEvent $event);
}
