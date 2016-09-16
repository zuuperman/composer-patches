<?php

/**
 * @file
 * Contains \cweagans\Composer\Resolvers\ResolverBase.
 */

namespace cweagans\Composer\Resolvers;

use Composer\Composer;
use cweagans\Composer\PatchCollection;

abstract class ResolverBase implements ResolverInterface {

    /**
     * The main Composer object.
     *
     * @var Composer
     */
    protected $composer;

    /**
     * An array of operations that will be executed during this composer execution.
     *
     * @var array
     */
    protected $operations;

    /**
     * {@inheritDoc}
     */
    public function __construct(Composer $composer, array $operations) {
        $this->composer = $composer;
        $this->operations = $operations;
    }

    /**
     * {@inheritdoc}
     */
    abstract public function isEnabled();

    /**
     * {@inheritdoc}
     */
    abstract public function getMessage();

    /**
     * {@inheritdoc}
     */
    abstract public function resolve(PatchCollection $collection);
}
