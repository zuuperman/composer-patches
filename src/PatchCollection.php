<?php

namespace cweagans\Composer;

use cweagans\Composer\Patch;

class PatchCollection
{
    /**
     * @var Patch[]
     */
    protected $patches = array();

    /**
     * Add a patch to the list.
     *
     * @param Patch $patch
     * @todo Add a way to prevent adding duplicate patches to the collection.
     */
    public function addPatch(Patch $patch)
    {
        $this->patches[] = $patch;
    }

    /**
     * Return the list of patches.
     *
     * @return Patch[]
     */
    public function getPatches($type = 'all')
    {
        $patches = $this->patches;

        if ($type != 'all') {
            $patches = array_filter($this->patches, function (Patch $patch) use ($type) {
                return ($patch->getPatchType() === $type);
            });
        }

        return $patches;
    }

    /**
     * Return a list of patches for a given package.
     *
     * @param $package_name
     *
     * @return Patch[]
     */
    public function getPatchesForPackage($package_name)
    {
        $patches = array_filter($this->patches, function (Patch $patch) use ($package_name) {
            return ($patch->getPackageName() === $package_name);
        });

        return $patches;
    }
}
