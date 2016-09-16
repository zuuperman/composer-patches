<?php

namespace cweagans\Composer;

use cweagans\Composer\Patch;

class PatchCollection
{
    /**
     * @var Patch[]
     */
    protected $patches;

    /**
     * Add a patch to the list.
     *
     * @param Patch $patch
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
}
