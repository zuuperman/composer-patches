<?php

/**
 * @file
 * Contains \cweagans\Composer\Resolvers\RootComposer.
 */

namespace cweagans\Composer\Resolvers;

use Composer\Installer\PackageEvent;
use cweagans\Composer\Patch;
use cweagans\Composer\PatchCollection;

class RootComposer extends ResolverBase
{

    const PATCH_TYPE = 'root';

    /**
     * {@inheritDoc}
     */
    public function resolve(PatchCollection $collection, PackageEvent $event)
    {
        $this->io->write('  - <info>Gathering patches from root package</info>');

        $extra = $this->composer->getPackage()->getExtra();

        if (!isset($extra['patches'])) {
            return;
        }

        foreach ($extra['patches'] as $package_name => $patches) {
            foreach ($patches as $patch_entry) {
                $patch = Patch::createFromJsonObject($package_name, $patch_entry, self::PATCH_TYPE);
                $collection->addPatch($patch);
            }
        }
    }
}
