<?php

namespace cweagans\Composer\Tests\Dummy;

use cweagans\Composer\PatchCollection;

class PatchCollectionDummy extends PatchCollection
{
    /**
     * Get the number of patches in the collection of a given type.
     *
     * @param string $type
     * @return int
     */
    public function getPatchCount($type = 'all') {
        return count($this->getPatches($type));
    }
}
