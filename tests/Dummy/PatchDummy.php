<?php

namespace cweagans\Composer\Tests\Dummy;

use cweagans\Composer\Patch;

class PatchDummy extends Patch
{
    /**
     * Retrieve the local path where the patch is stored.
     *
     * @return string
     *   The patch location.
     */
    public function getLocalPath() {
        return $this->localPath;
    }

    /**
     * Set the local path to force behaviors during testing.
     */
    public function setLocalPath($localPath) {
        $this->localPath = $localPath;
    }
}
