<?php

/**
 * @file
 * Contains \cweagans\Composer\Resolvers\ResolverInterface.
 */

namespace cweagans\Composer\Resolvers;

use Composer\Composer;
use cweagans\Composer\PatchCollection;

interface ResolverInterface {

    /**
     * ResolverInterface constructor.
     *
     * @param Composer $composer
     *   The current composer object from the main plugin. Used to locate/read
     *   package metadata and configuration.
     * @param array $operations
     *   A list of operations that will be executed by composer during the
     *   current execution (i.e. this instance of executing `composer install`).
     */
    public function __construct(Composer $composer, array $operations);

    /**
     * Determine if this resolver should be used or not.
     *
     * @return bool
     */
    public function isEnabled();

    /**
     * Get a message to output to the user when this resolver is used.
     *
     * @return string
     *   The message to output to the user.
     */
    public function getMessage();

    /**
     * Find and add patches to the supplied PatchCollection.
     *
     * Note that in this method, it is safe to assume that the resolver is enabled
     * because this method will never be called if ::isEnabled() returns FALSE.
     *
     * @param PatchCollection $collection
     *   A collection of patches that will eventually be applied.
     * @return mixed
     */
    public function resolve(PatchCollection $collection);
}
