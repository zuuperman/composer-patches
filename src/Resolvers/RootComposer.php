<?php

/**
 * @file
 * Contains \cweagans\Composer\Resolvers\RootComposer.
 */

namespace cweagans\Composer\Resolvers;

use cweagans\Composer\Patch;
use cweagans\Composer\PatchCollection;

class RootComposer extends ResolverBase {

    const PATCH_TYPE = 'root';

    /**
     * {@inheritDoc}
     */
    public function isEnabled()
    {
        return array_key_exists('patches', $this->getExtra());
    }

    /**
     * {@inheritdoc}
     */
    public function getMessage()
    {
        return 'Gathering patches from root package.';
    }

    /**
     * {@inheritDoc}
     */
    public function resolve(PatchCollection $collection)
    {
        $extra = $this->getExtra();

        foreach ($extra['patches'] as $package_name => $patches) {
            foreach ($patches as $patch_entry) {
                $patch_entry->package = $package_name;
                $patch = Patch::createFromJsonObject($package_name, $patch_entry, self::PATCH_TYPE);
                $collection->addPatch($patch);
            }
        }
    }

    /**
     * Retrieve the 'extra' section from the root composer.json.
     */
    protected function getExtra()
    {
        return $this->composer->getPackage()->getExtra();
    }
}
